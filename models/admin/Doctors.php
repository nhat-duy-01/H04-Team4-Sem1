<?php
require_once('../../config/connectDB.php');

$conn = (new ConnectDB())->connection();


function createDoctor($conn, $username, $email, $password, $fullname, $phone, $contactNumber, $specializations) {

    $sqlUser = "INSERT INTO Users (UserName, Email, Password, FullName, Phone, Created_at, UserType) 
                VALUES (?, ?, ?, ?, ?, NOW(), 'Doctor')";
    $stmtUser = $conn->prepare($sqlUser);
    $stmtUser->bind_param("sssss", $username, $email, $password, $fullname, $phone);
    $stmtUser->execute();

    $user_id = $conn->insert_id;

    $sqlDoctor = "INSERT INTO Doctors (ContactNumber, Created_at, User_ID) 
                  VALUES (?, NOW(), ?)";
    $stmtDoctor = $conn->prepare($sqlDoctor);
    $stmtDoctor->bind_param("si", $contactNumber, $user_id);
    $stmtDoctor->execute();

    $doctor_id = $conn->insert_id;

    if (!empty($specializations)) {
        $sqlSpec = "INSERT INTO Doctor_Specializations (Doctor_ID, Specialization_ID) VALUES (?, ?)";
        $stmtSpec = $conn->prepare($sqlSpec);
        foreach ($specializations as $spec_id) {
            $stmtSpec->bind_param("ii", $doctor_id, $spec_id);
            $stmtSpec->execute();
        }
    }

    return true;
}

function updateDoctor($conn, $doctor_id, $fullname, $email, $phone, $contactNumber, $specializations) {

    $sqlGetUser = "SELECT User_ID FROM Doctors WHERE Doctor_ID = ?";
    $stmtGetUser = $conn->prepare($sqlGetUser);
    $stmtGetUser->bind_param("i", $doctor_id);
    $stmtGetUser->execute();
    $result = $stmtGetUser->get_result()->fetch_assoc();
    if (!$result) return false;
    $user_id = $result['User_ID'];


    $sqlUser = "UPDATE Users SET FullName = ?, Email = ?, Phone = ? WHERE User_ID = ?";
    $stmtUser = $conn->prepare($sqlUser);
    $stmtUser->bind_param("sssi", $fullname, $email, $phone, $user_id);
    $stmtUser->execute();

    $sqlDoctor = "UPDATE Doctors SET ContactNumber = ? WHERE Doctor_ID = ?";
    $stmtDoctor = $conn->prepare($sqlDoctor);
    $stmtDoctor->bind_param("si", $contactNumber, $doctor_id);
    $stmtDoctor->execute();

    $stmtDel = $conn->prepare("DELETE FROM Doctor_Specializations WHERE Doctor_ID = ?");
    $stmtDel->bind_param("i", $doctor_id);
    $stmtDel->execute();


    if (!empty($specializations)) {
        $stmtSpec = $conn->prepare("INSERT INTO Doctor_Specializations (Doctor_ID, Specialization_ID) VALUES (?, ?)");
        foreach ($specializations as $spec_id) {
            $stmtSpec->bind_param("ii", $doctor_id, $spec_id);
            $stmtSpec->execute();
        }
    }

    return true;
}

function getAllDoctors($conn) {
    $sql = "SELECT d.Doctor_ID, d.ContactNumber, u.User_ID, u.UserName, u.FullName, u.Email, u.Phone
            FROM Doctors d
            JOIN Users u ON d.User_ID = u.User_ID
            WHERE u.User_ID > 1
            ORDER BY d.Doctor_ID DESC";
    return $conn->query($sql);
}

function searchDoctors($conn, $keyword) {
    $keyword = "%$keyword%";
    $sql = "SELECT d.Doctor_ID, d.ContactNumber, u.User_ID, u.UserName, u.FullName, u.Email, u.Phone
            FROM Doctors d
            JOIN Users u ON d.User_ID = u.User_ID
            WHERE (u.FullName LIKE ? OR u.UserName LIKE ? OR u.Email LIKE ?) AND u.User_ID > 1
            ORDER BY d.Doctor_ID DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $keyword, $keyword, $keyword);
    $stmt->execute();
    return $stmt->get_result();
}


function getDoctorById($conn, $doctor_id) {
    $sql = "SELECT d.Doctor_ID, d.ContactNumber, u.User_ID, u.UserName, u.FullName, u.Email, u.Phone,
                   GROUP_CONCAT(ds.Specialization_ID) AS Specialization_IDs
            FROM Doctors d
            JOIN Users u ON d.User_ID = u.User_ID
            LEFT JOIN Doctor_Specializations ds ON d.Doctor_ID = ds.Doctor_ID
            WHERE d.Doctor_ID = ? AND u.User_ID > 1
            GROUP BY d.Doctor_ID";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getAllSpecializations($conn) {
    $sql = "SELECT * FROM Specialization ORDER BY Name ASC";
    $result = $conn->query($sql);
    $specs = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $specs[] = $row;
        }
    }
    return $specs;
}

function deleteDoctor($conn, $doctor_id) {
    // 1. Lấy User_ID trước khi xóa
    $sqlGetUser = "SELECT User_ID FROM Doctors WHERE Doctor_ID = ?";
    $stmtGetUser = $conn->prepare($sqlGetUser);
    $stmtGetUser->bind_param("i", $doctor_id);
    $stmtGetUser->execute();
    $result = $stmtGetUser->get_result()->fetch_assoc();
    
    if (!$result) return false;
    $user_id = $result['User_ID'];

    // Bắt đầu Transaction để đảm bảo tính toàn vẹn dữ liệu
    $conn->begin_transaction();

    try {
        // 2. XÓA Ở BẢNG CON TRƯỚC (Đây là bước bạn đang thiếu)
        $sqlSpec = "DELETE FROM doctor_specializations WHERE Doctor_ID = ?";
        $stmtSpec = $conn->prepare($sqlSpec);
        $stmtSpec->bind_param("i", $doctor_id);
        $stmtSpec->execute();

        // 3. Xóa ở bảng Doctors
        $sqlDoctor = "DELETE FROM Doctors WHERE Doctor_ID = ?";
        $stmtDoctor = $conn->prepare($sqlDoctor);
        $stmtDoctor->bind_param("i", $doctor_id);
        $stmtDoctor->execute();

        // 4. Xóa ở bảng Users
        $sqlUser = "DELETE FROM Users WHERE User_ID = ?";
        $stmtUser = $conn->prepare($sqlUser);
        $stmtUser->bind_param("i", $user_id);
        $stmtUser->execute();

        // Nếu mọi thứ ổn, lưu thay đổi vào DB
        $conn->commit();
        return true;

    } catch (mysqli_sql_exception $exception) {
        // Nếu có lỗi, hoàn tác lại toàn bộ (không xóa gì cả)
        $conn->rollback();
        throw $exception; 
        return false;
    }
}
?>