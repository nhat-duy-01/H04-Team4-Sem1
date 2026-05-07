<?php
session_start();
require_once('../../config/connectDB.php');
require_once('../../controller/admin/DashboardController.php');

// 1. Initialize DB connection first
$db = new ConnectDB();
$conn = $db->connection();

// 2. Initialize Controller and fetch data
$dashboard = new DashboardController($conn);
$stats = $dashboard->getStats();

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM Users WHERE UserName = ? AND Password = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {

        $_SESSION['user_id'] = $user['User_ID'];
        $_SESSION['username'] = $user['UserName'];
        $_SESSION['role'] = $user['UserType'];

        if ($user['UserType'] === 'Admin') {
            header("Location: admin/admin_dashboard.php");
        } else {
            header("Location: ../index.php");
        }
        exit();

    } else {
        echo "Incorrect username or password!";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="style.css">
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
</head>
<body>
<header>
    <h1>Admin Panel</h1>
    <p>Welcome, <?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest' ?> to MediConnect</p>
</header>

<nav>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="manage_specializations.php">Specializations</a>
    <a href="manage_doctors.php">Doctors</a>
    <a href="manage_patients.php">Patients</a>
    <a href="manage_cities.php">Cities</a>
    <a href="manage_schedule.php">Schedule</a>
    <a href="manage_feedback.php">Feedback</a>
    <a href="manage_hospital.php">Hospital</a>

    <a href="../logout.php" class="logout-link">Logout</a>
</nav>

<div class="container">
    <h2>System Statistics</h2>
    <div class="stats">
        <div class="card">
            <h3>Users</h3>
            <h2><?= $stats['users'] ?></h2>
        </div>
        <div class="card">
            <h3>Doctors</h3>
            <h2><?= $stats['doctors'] ?></h2>
        </div>
        <div class="card">
            <h3>Patients</h3>
            <h2><?= $stats['patients'] ?></h2>
        </div>
        <div class="card">
            <h3>Appointments</h3>
            <h2><?= $stats['appointments'] ?></h2>
        </div>
    </div>
</div>

</body>
</html>