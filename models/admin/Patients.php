<?php
require_once '../../config/connectDB.php';

class Patient extends ConnectDB
{
    private $conn;

    // Các thuộc tính (Properties)
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


    // Lấy tất cả bệnh nhân
    public function getAllPatients() {
        $sql = "SELECT * FROM Users WHERE UserType = 'Patient' ORDER BY User_ID DESC";
        return $this->conn->query($sql);
    }

    // Lấy 1 bệnh nhân và đổ vào các thuộc tính (Object Mapping)
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

    // Cập nhật bằng cách sử dụng dữ liệu từ Object (không cần truyền tham số rời rạc)
    public function updatePatient() {
        $stmt = $this->conn->prepare("UPDATE Users SET FullName = ?, Email = ?, Date_Of_Birth = ?, Address = ?, Phone = ? WHERE User_ID = ?");
        $stmt->bind_param("sssssi", $this->FullName, $this->Email, $this->Date_Of_Birth, $this->Address, $this->Phone, $this->User_ID);
        return $stmt->execute();
    }

    // Xóa bệnh nhân
    public function deletePatient($id) {
        $stmt = $this->conn->prepare("DELETE FROM Users WHERE User_ID = ? AND UserType = 'Patient'");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // Tìm kiếm
    public function searchPatients($keyword) {
        $search = "%$keyword%";
        $stmt = $this->conn->prepare("SELECT * FROM Users WHERE UserType = 'Patient' AND (FullName LIKE ? OR Email LIKE ? OR Phone LIKE ?)");
        $stmt->bind_param("sss", $search, $search, $search);
        $stmt->execute();
        return $stmt->get_result();
    }

    /**
 * Thêm mới bệnh nhân từ Object
 */
public function insertPatient() {
    $stmt = $this->conn->prepare("INSERT INTO Users (UserName, Password, FullName, Email, Date_Of_Birth, Address, Phone, UserType) VALUES (?, ?, ?, ?, ?, ?, ?, 'Patient')");
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

// Kiểm tra trùng lặp UserName hoặc Email
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

        public function getUser_ID() { return $this->User_ID; }
    public function setUser_ID($id) { $this->User_ID = $id; }

    public function getUserName() { return $this->UserName; }
    public function setUserName($username) { $this->UserName = $username; }

    public function getPassword() { return $this->Password; }
    public function setPassword($password) { $this->Password = $password; }

    public function getFullName() { return $this->FullName; }
    public function setFullName($fullname) { $this->FullName = ucwords(mb_strtolower($fullname)); } // Tự động viết hoa chữ cái đầu

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