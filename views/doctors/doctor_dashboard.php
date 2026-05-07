<?php
session_start();
require_once '../../config/connectDB.php';

// Access Control (Doctor only)
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Doctor') {
    header("Location: ../authentication.php");
    exit();
}

$conn = (new ConnectDB())->connection();
$doctor_user_id = $_SESSION['user_id'];

// --- XỬ LÝ XÁC NHẬN / TỪ CHỐI NHANH ---
if (isset($_GET['action']) && isset($_GET['app_id'])) {
    $app_id = intval($_GET['app_id']);
    $action = $_GET['action'];
    $new_status = '';

    if ($action == 'confirm') {
        $new_status = 'Confirmed';
    } elseif ($action == 'reject') {
        $new_status = 'Cancelled';
    }

    if ($new_status != '') {
        $sql_update = "UPDATE Appointments SET Status = ? WHERE Appointment_ID = ? AND Doctor_ID = (SELECT Doctor_ID FROM Doctors WHERE User_ID = ?)";
        $stmt_up = $conn->prepare($sql_update);
        $stmt_up->bind_param("sii", $new_status, $app_id, $doctor_user_id);
        if ($stmt_up->execute()) {
            $msg = ($action == 'confirm') ? "confirmed" : "rejected";
            header("Location: doctor_dashboard.php?msg=$msg");
            exit();
        }
    }
}

// Lấy thông tin bác sĩ
if (!isset($_SESSION['full_name']) || empty($_SESSION['full_name'])) {
    $stmt_name = $conn->prepare("SELECT FullName FROM Users WHERE User_ID = ?");
    $stmt_name->bind_param("i", $doctor_user_id);
    $stmt_name->execute();
    $res_name = $stmt_name->get_result()->fetch_assoc();
    $_SESSION['full_name'] = $res_name['FullName'] ?? 'Doctor';
}

// 1. Get Doctor_ID
$stmt_d = $conn->prepare("SELECT Doctor_ID FROM Doctors WHERE User_ID = ?");
$stmt_d->bind_param("i", $doctor_user_id);
$stmt_d->execute();
$doctor_data = $stmt_d->get_result()->fetch_assoc();
$doctor_id = $doctor_data['Doctor_ID'];

// 2. Quick Statistics
$count_pending = $conn->query("SELECT COUNT(*) as total FROM Appointments WHERE Doctor_ID = $doctor_id AND Status = 'Scheduled'")->fetch_assoc()['total'];
$count_today = $conn->query("SELECT COUNT(*) as total FROM Appointments WHERE Doctor_ID = $doctor_id AND DATE(BookingDate) = CURDATE() AND Status = 'Confirmed'")->fetch_assoc()['total'];
$count_noti = $conn->query("SELECT COUNT(*) as total FROM User_Notifications WHERE User_ID = $doctor_user_id AND IsRead = FALSE")->fetch_assoc()['total'];
$count_patients = $conn->query("SELECT COUNT(DISTINCT Patient_ID) as total FROM Appointments WHERE Doctor_ID = $doctor_id AND Status = 'Confirmed'")->fetch_assoc()['total'];

// 3. Get 5 most recent BOOKED (Scheduled) appointments
$sql_recent = "SELECT a.*, u.FullName as PatientName, u.ProfilePicture, s.AvailableDate, s.StartTime 
                FROM Appointments a
                JOIN Patients p ON a.Patient_ID = p.Patient_ID
                JOIN Users u ON p.User_ID = u.User_ID
                LEFT JOIN DoctorSchedules s ON a.Schedule_ID = s.Schedule_ID
                WHERE a.Doctor_ID = ? AND a.Status = 'Scheduled'
                ORDER BY a.Created_at DESC LIMIT 5";
$stmt_recent = $conn->prepare($sql_recent);
$stmt_recent->bind_param("i", $doctor_id);
$stmt_recent->execute();
$recent_apps = $stmt_recent->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor Dashboard | MediConnect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #3498db; --success: #27ae60; --warning: #f1c40f; --danger: #e74c3c; --dark: #2c3e50; --bg: #f4f7f6; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); margin: 0; display: flex; }
        .sidebar { width: 260px; background: var(--dark); height: 100vh; color: white; padding: 20px; position: fixed; left: 0; top: 0; z-index: 1000; box-sizing: border-box; display: flex; flex-direction: column; }
        .sidebar h2 { text-align: center; font-size: 1.5rem; margin-bottom: 30px; color: var(--primary); flex-shrink: 0; }
        .nav-menu { flex: 1; overflow-y: auto; }
        .nav-menu::-webkit-scrollbar { display: none; }
        .nav-item { padding: 12px 15px; display: block; color: #bdc3c7; text-decoration: none; border-radius: 8px; margin-bottom: 5px; transition: 0.3s; }
        .nav-item:hover, .nav-item.active { background: #34495e; color: white; }
        .nav-item i { margin-right: 10px; width: 20px; }
        
        .logout-section { margin-top: auto; padding-top: 15px; border-top: 1px solid #3e4f5f; }
        
        .main-content { margin-left: 260px; width: calc(100% - 260px); padding: 30px; min-height: 100vh; box-sizing: border-box; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #e0e4e8; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); display: flex; align-items: center; }
        .stat-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-right: 15px; }
        
        .table-container { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; color: #7f8c8d; border-bottom: 2px solid #f4f7f6; }
        td { padding: 15px 12px; border-bottom: 1px solid #f4f7f6; vertical-align: middle; }
        
        .patient-cell { display: flex; align-items: center; gap: 10px; }
        .patient-img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; background: #fff4e6; color: #d97706; }
        
        /* Actions Buttons */
        .action-group { display: flex; gap: 8px; }
        .btn-action { padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.75rem; font-weight: 600; transition: 0.2s; border: none; cursor: pointer; }
        .btn-confirm { background: var(--success); color: white; }
        .btn-confirm:hover { background: #219150; }
        .btn-reject { background: #f8d7da; color: var(--danger); }
        .btn-reject:hover { background: var(--danger); color: white; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>MediConnect</h2>
        <div class="nav-menu">
            <a href="doctor_dashboard.php" class="nav-item active"><i class="fas fa-home"></i> Overview</a>
            <a href="manage_appointments.php" class="nav-item"><i class="fas fa-calendar-check"></i> Appointments</a>
            <a href="doctor_schedule.php" class="nav-item"><i class="fas fa-clock"></i> Schedule Management</a>
            <a href="notifications.php" class="nav-item"><i class="fas fa-bell"></i> Notifications (<?= $count_noti ?>)</a>
            <a href="medicontent.php" class="nav-item"><i class="fas fa-feather-alt"></i> Medical Articles</a>
            <a href="doctor_feedback.php" class="nav-item"><i class="fas fa-star"></i> Feedback</a>
            <a href="profile.php" class="nav-item"><i class="fas fa-user-circle"></i> Profile</a>
        </div>
        
        <div class="logout-section">
            <a href="../logout.php" class="nav-item" style="color: var(--danger);"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Welcome Doctor, <?= htmlspecialchars($_SESSION['full_name']) ?>! 👋</h1>
            <span><i class="far fa-calendar-alt"></i> Today: <?= date('M d, Y') ?></span>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: #e1f5fe; color: #03a9f4;"><i class="fas fa-hourglass-half"></i></div>
                <div class="stat-info"><h3><?= $count_pending ?></h3><p>Pending</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #e8f5e9; color: #4caf50;"><i class="fas fa-calendar-day"></i></div>
                <div class="stat-info"><h3><?= $count_today ?></h3><p>Today's List</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #fff3e0; color: #ff9800;"><i class="fas fa-bell"></i></div>
                <div class="stat-info"><h3><?= $count_noti ?></h3><p>Notifications</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #f3e5f5; color: #9c27b0;"><i class="fas fa-users"></i></div>
                <div class="stat-info"><h3><?= $count_patients ?></h3><p>Total Patients</p></div>
            </div>
        </div>

        <div class="table-container">
            <div class="table-header">
                <h2>Pending Booking Requests</h2>
                <a href="manage_appointments.php" style="color: var(--primary); text-decoration: none; font-size: 0.85rem;">View All <i class="fas fa-chevron-right"></i></a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Appointment Date</th>
                        <th>Notes</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($recent_apps->num_rows > 0): ?>
                        <?php while($row = $recent_apps->fetch_assoc()): ?>
                        <tr>
                            <td class="patient-cell">
                                <img src="<?= !empty($row['ProfilePicture']) ? '../../public/uploads/avatars/'.$row['ProfilePicture'] : 'https://ui-avatars.com/api/?name='.urlencode($row['PatientName']) ?>" class="patient-img">
                                <strong><?= htmlspecialchars($row['PatientName']) ?></strong>
                            </td>
                            <td>
                                <b><?= date('M d, Y', strtotime($row['BookingDate'])) ?></b><br>
                                <small style="color: #95a5a6;"><i class="far fa-clock"></i> <?= $row['StartTime'] ? substr($row['StartTime'], 0, 5) : '--:--' ?></small>
                            </td>
                            <td><div style="max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($row['Notes']) ?>"><?= htmlspecialchars($row['Notes']) ?></div></td>
                            <td><span class="status-badge">Scheduled</span></td>
                            <td>
                                <div class="action-group">
                                    <a href="doctor_dashboard.php?action=confirm&app_id=<?= $row['Appointment_ID'] ?>" 
                                       class="btn-action btn-confirm" title="Approve">
                                       <i class="fas fa-check"></i>
                                    </a>
                                    <a href="doctor_dashboard.php?action=reject&app_id=<?= $row['Appointment_ID'] ?>" 
                                       class="btn-action btn-reject" title="Reject"
                                       onclick="return confirm('Are you sure you want to reject this appointment?')">
                                       <i class="fas fa-times"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align: center; color: #95a5a6; padding: 40px;">No new requests found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>