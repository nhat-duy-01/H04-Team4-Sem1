<?php
require_once '../../config/connectDB.php';

class Patient extends ConnectDB
{
    private $conn;

    // Properties
    private $User_ID;
    private $UserName;
    private $Password;
    private $FullName;
    private $Email;
    private $Address;
    private $Date_Of_Birth;
    private $Phone;
    private $UserType = 'Patient';

    public function __construct()
    {
        $this->conn = $this->connection();
    }

    /**
     * Fetch all patients
     */
    public function getAllPatients() {
        $sql = "SELECT * FROM Users WHERE UserType = 'Patient' ORDER BY User_ID DESC";
        return $this->conn->query($sql);
    }

    /**
     * Find a single patient by ID and map data to object properties (Object Mapping)
     */
    public function findPatientById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM Users WHERE User_ID = ? AND UserType = 'Patient'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        
        if ($row) {
            $this->setUser_ID($row['User_ID']);
            $this->setFullName($row['FullName']);
            $this->setEmail($row['Email']);
            $this->setAddress($row['Address']);
            $this->setDate_Of_Birth($row['Date_Of_Birth']);
            $this->setPhone($row['Phone']);
        }
        return $row;
    }

    /**
     * Update record using current object property values
     */
    public function updatePatient() {
        $stmt = $this->conn->prepare("UPDATE Users SET FullName = ?, Email = ?, Date_Of_Birth = ?, Address = ?, Phone = ? WHERE User_ID = ?");
        $stmt->bind_param("sssssi", $this->FullName, $this->Email, $this->Date_Of_Birth, $this->Address, $this->Phone, $this->User_ID);
        return $stmt->execute();
    }

    /**
     * Delete a patient by ID
     */
public function deletePatient($id) {
    // 1. Tìm Patient_ID từ User_ID truyền vào
    $sqlGet = "SELECT Patient_ID FROM Patients WHERE User_ID = ?";
    $stmtGet = $this->conn->prepare($sqlGet);
    $stmtGet->bind_param("i", $id);
    $stmtGet->execute();
    $res = $stmtGet->get_result()->fetch_assoc();
    
    // Nếu không tìm thấy bệnh nhân trong bảng Patients, có thể chỉ cần xóa trong bảng Users
    $patient_id = $res ? $res['Patient_ID'] : null;

    // Bắt đầu Transaction để đảm bảo nếu một lệnh lỗi thì không bảng nào bị xóa
    $this->conn->begin_transaction();

    try {
        if ($patient_id) {
            // Bước 1: Xóa Feedback (liên quan đến các cuộc hẹn của bệnh nhân này)
            $sql1 = "DELETE FROM Feedback WHERE Patient_ID = ?";
            $stmt1 = $this->conn->prepare($sql1);
            $stmt1->bind_param("i", $patient_id);
            $stmt1->execute();

            // Bước 2: Xóa Appointments (các cuộc hẹn của bệnh nhân)
            $sql2 = "DELETE FROM Appointments WHERE Patient_ID = ?";
            $stmt2 = $this->conn->prepare($sql2);
            $stmt2->bind_param("i", $patient_id);
            $stmt2->execute();

            // Bước 3: Xóa trong bảng Patients
            $sql3 = "DELETE FROM Patients WHERE Patient_ID = ?";
            $stmt3 = $this->conn->prepare($sql3);
            $stmt3->bind_param("i", $patient_id);
            $stmt3->execute();
        }

        // Bước 4: Xóa thông báo của User (bảng User_Notifications)
        $sql4 = "DELETE FROM User_Notifications WHERE User_ID = ?";
        $stmt4 = $this->conn->prepare($sql4);
        $stmt4->bind_param("i", $id);
        $stmt4->execute();

        // Bước 5: Cuối cùng mới xóa trong bảng Users
        $sql5 = "DELETE FROM Users WHERE User_ID = ? AND UserType = 'Patient'";
        $stmt5 = $this->conn->prepare($sql5);
        $stmt5->bind_param("i", $id);
        $stmt5->execute();

        // Nếu tất cả thành công, xác nhận thay đổi
        $this->conn->commit();
        return true;

    } catch (mysqli_sql_exception $e) {
        // Nếu có bất kỳ lỗi nào, hoàn tác lại toàn bộ (không xóa gì cả)
        $this->conn->rollback();
        error_log("Lỗi xóa bệnh nhân: " . $e->getMessage());
        return false;
    }
}
    /**
     * Search patients by keyword (Name, Email, or Phone)
     */
    public function searchPatients($keyword) {
        $search = "%$keyword%";
        $stmt = $this->conn->prepare("SELECT * FROM Users WHERE UserType = 'Patient' AND (FullName LIKE ? OR Email LIKE ? OR Phone LIKE ?)");
        $stmt->bind_param("sss", $search, $search, $search);
        $stmt->execute();
        return $stmt->get_result();
    }

 /**
 * Insert a new patient using object properties
 */
public function insertPatient() {
    // Sửa câu SQL: Sử dụng NOW() trực tiếp và bỏ dấu phẩy/biến thừa
    $sql = "INSERT INTO Users (UserName, Password, FullName, Email, Date_Of_Birth, Address, Phone, UserType, Created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Patient', NOW())";
            
    $stmt = $this->conn->prepare($sql);
    
    // Chỉ bind 7 tham số (tương ứng với 7 dấu hỏi chấm)
    $stmt->bind_param("sssssss", 
        $this->UserName, 
        $this->Password, 
        $this->FullName, 
        $this->Email, 
        $this->Date_Of_Birth, 
        $this->Address, 
        $this->Phone
    );
    
    return $stmt->execute();
}
    /**
     * Check for duplicate UserName or Email
     */
    public function isDuplicate($username, $email, $id = null) {
        $sql = "SELECT User_ID FROM Users WHERE (UserName = ? OR Email = ?) AND UserType = 'Patient'";
        if ($id) $sql .= " AND User_ID != ?"; 
        
        $stmt = $this->conn->prepare($sql);
        if ($id) {
            $stmt->bind_param("ssi", $username, $email, $id);
        } else {
            $stmt->bind_param("ss", $username, $email);
        }
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    // Getters and Setters
    public function getUser_ID() { return $this->User_ID; }
    public function setUser_ID($id) { $this->User_ID = $id; }

    public function getUserName() { return $this->UserName; }
    public function setUserName($username) { $this->UserName = $username; }

    public function getPassword() { return $this->Password; }
    public function setPassword($password) { $this->Password = $password; }

    public function getFullName() { return $this->FullName; }
    public function setFullName($fullname) { 
        // Automatically capitalize first letters
        $this->FullName = ucwords(mb_strtolower($fullname)); 
    }

    public function getEmail() { return $this->Email; }
    public function setEmail($email) { $this->Email = $email; }

    public function getAddress() { return $this->Address; }
    public function setAddress($address) { $this->Address = $address; }

    public function getDate_Of_Birth() { return $this->Date_Of_Birth; }
    public function setDate_Of_Birth($dob) { $this->Date_Of_Birth = $dob; }

    public function getPhone() { return $this->Phone; }
    public function setPhone($phone) { $this->Phone = $phone; }
}
?>