<?php
require_once __DIR__ . '/../../config/connectDB.php';

class Users extends ConnectDB
{
    private $conn;

    // Private properties
    private $User_ID;
    private $UserName;
    private $Email;
    private $Phone;
    private $Password;
    private $FullName;
    private $Address;
    private $Date_Of_Birth;
    private $ProfilePicture;
    private $UserType;
    private $Created_at;
    private $City_ID;
    private $Specialization_ID;

    public function __construct()
    {
        $this->conn = $this->connection();
    }

    /**
     * Authenticate user login
     */
    public function checkLogin($user, $pass) {
        $stmt = $this->conn->prepare("SELECT * FROM Users WHERE UserName = ? AND Password = ?");
        $stmt->bind_param("ss", $user, $pass);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc(); 
        }
        return null;
    }

    /**
     * Add a new user (Registration)
     * Handles insertion into both 'Users' table and specific role tables ('Patients' or 'Doctors')
     */
   public function insertUsers($userName, $password, $fullName, $email, $phone, $address, $userType, $dob) {
 
    $cityName = trim(explode(',', $address)[0]);
    $cityId = null;


    $stmtCity = $this->conn->prepare("SELECT City_ID FROM Cities WHERE Name = ?");
    $stmtCity->bind_param("s", $cityName);
    $stmtCity->execute();
    $resCity = $stmtCity->get_result();

    if ($resCity->num_rows > 0) {
     
        $cityRow = $resCity->fetch_assoc();
        $cityId = $cityRow['City_ID'];
    } else {
      
        $stmtInsertCity = $this->conn->prepare("INSERT INTO Cities (Name) VALUES (?)");
        $stmtInsertCity->bind_param("s", $cityName);
        $stmtInsertCity->execute();
        $cityId = $this->conn->insert_id;
    }

    $sql = "INSERT INTO Users 
            (UserName, Password, FullName, Email, Phone, Address, UserType, Date_Of_Birth, Created_at, City_ID) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";

    $stmt = $this->conn->prepare($sql);
    if ($stmt === false) {
        die("SQL Error (Users): " . $this->conn->error);
    }
    $stmt->bind_param("ssssssssi", $userName, $password, $fullName, $email, $phone, $address, $userType, $dob, $cityId);

    if ($stmt->execute()) {
        $newUserId = $this->conn->insert_id;
        $stmtSub = null;


        if ($userType === 'Patient') {
            $sqlSub = "INSERT INTO Patients (User_ID, Address, Created_at) VALUES (?, ?, NOW())";
            $stmtSub = $this->conn->prepare($sqlSub);
            $stmtSub->bind_param("is", $newUserId, $address);
        } else if ($userType === 'Doctor') {
            $sqlSub = "INSERT INTO Doctors (User_ID, ContactNumber, Created_at) VALUES (?, ?, NOW())";
            $stmtSub = $this->conn->prepare($sqlSub);
            $stmtSub->bind_param("is", $newUserId, $phone); 
        }

        if ($stmtSub) {
            return $stmtSub->execute();
        }

        return true;
    }

    return false;
}
    /**
     * Check if UserName exists (Used for Sign In/Sign Up validation)
     */
    public function checkSignIn($userName){
        $stmt = $this->conn->prepare("SELECT User_ID, UserName, Password, FullName FROM Users WHERE UserName = ?");
        $stmt->bind_param("s", $userName);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Update user profile picture
     */
    public function updateAvatar($userId, $fileName) {
        $stmt = $this->conn->prepare("UPDATE Users SET ProfilePicture = ? WHERE User_ID = ?");
        $stmt->bind_param("si", $fileName, $userId);
        return $stmt->execute();
    }

    /**
     * Verify credentials for password reset
     */
    public function checkResetPassword($userName, $email) {
        $stmt = $this->conn->prepare("SELECT * FROM Users WHERE UserName = ? AND Email = ?");
        $stmt->bind_param("ss", $userName, $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Update user password
     */
    public function updatePassword($userName, $newPassword) {
        $stmt = $this->conn->prepare("UPDATE Users SET Password = ? WHERE UserName = ?");
        $stmt->bind_param("ss", $newPassword, $userName);
        return $stmt->execute();
    }

    /**
     * Update basic user profile information
     */
    public function updateUserProfile($userId, $fullName, $phone, $email, $address) {
        $stmt = $this->conn->prepare("UPDATE Users SET FullName = ?, Email = ?, Address = ? WHERE User_ID = ?");
        $stmt->bind_param("sssi", $fullName, $email, $address, $userId);
        return $stmt->execute();
    }

    /**
     * Get detailed information for a single user by ID
     */
    public function getUserById($userId) {
        $stmt = $this->conn->prepare("SELECT * FROM Users WHERE User_ID = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Search users by keyword (Username, Full Name, or Email)
     */
    public function searchUsers($keyword) {
        $search = "%$keyword%";
        $stmt = $this->conn->prepare("SELECT * FROM Users WHERE UserName LIKE ? OR FullName LIKE ? OR Email LIKE ?");
        $stmt->bind_param("sss", $search, $search, $search);
        $stmt->execute();
        return $stmt->get_result();
    }

    /**
     * Fetch all users from the database
     */
    public function showAllUsers() {
        $sql = "SELECT * FROM Users";
        return $this->conn->query($sql);
    }

    /**
     * Delete user by ID
     */
    public function deleteUserById($userId) {
        $stmt = $this->conn->prepare("DELETE FROM Users WHERE User_ID = ?");
        $stmt->bind_param("i", $userId);
        return $stmt->execute();
    }

    // Getters and Setters with Fluent Interface (return $this)
    public function getUser_ID() { return $this->User_ID; }
    public function setUser_ID($User_ID) { $this->User_ID = $User_ID; return $this; }

    public function getUserName() { return $this->UserName; }
    public function setUserName($UserName) { $this->UserName = $UserName; return $this; }

    public function getEmail() { return $this->Email; }
    public function setEmail($Email) { $this->Email = $Email; return $this; }

    public function getPassword() { return $this->Password; }
    public function setPassword($Password) { $this->Password = $Password; return $this; }

    public function getFullName() { return $this->FullName; }
    public function setFullName($FullName) { $this->FullName = $FullName; return $this; }

    public function getAddress() { return $this->Address; }
    public function setAddress($Address) { $this->Address = $Address; return $this; }

    public function getDate_Of_Birth() { return $this->Date_Of_Birth; }
    public function setDate_Of_Birth($dob) { $this->Date_Of_Birth = $dob; return $this; }

    public function getUserType() { return $this->UserType; }
    public function setUserType($type) { $this->UserType = $type; return $this; }

    public function getPhone() { return $this->Phone; }
    public function setPhone($Phone) { $this->Phone = $Phone; return $this; }
}
?>