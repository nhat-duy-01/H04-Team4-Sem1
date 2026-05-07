<?php
class DashboardController {
    private $conn;

    /**
     * Initialize the controller with a database connection
     */
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    /**
     * Retrieve all statistical metrics for the Dashboard
     */
    public function getStats() {
        return [
            'users'        => $this->countTable('Users'),
            'doctors'      => $this->countByRole('Doctor'),
            'patients'     => $this->countByRole('Patient'),
            'appointments' => $this->countTable('Appointments')
        ];
    }

    /**
     * Helper function to count total rows in a specific table
     */
    private function countTable($tableName) {
        // Sanitize table name to prevent basic SQL injection if it comes from user input
        // though usually $tableName is hardcoded in the methods above.
        $sql = "SELECT COUNT(*) as total FROM $tableName";
        $result = $this->conn->query($sql);
        
        if ($result) {
            $row = $result->fetch_assoc();
            return $row['total'];
        }
        return 0; // Returns 0 if the table does not exist or an error occurs
    }

    /**
     * Function to count users based on their role (UserType)
     */
    private function countByRole($roleName) {
        $sql = "SELECT COUNT(*) as total FROM Users WHERE UserType = ?";
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("s", $roleName);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            return $row['total'];
        }
        return 0;
    }
}
?>