<?php
session_start();
require_once '../../config/connectDB.php';

// 1. Check Access Rights
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Doctor') {
    header("Location: ../authentication.php");
    exit();
}

$conn = (new ConnectDB())->connection();
$doctor_user_id = $_SESSION['user_id'];
$current_page = basename($_SERVER['PHP_SELF']);

// 2. Get Doctor_ID
$stmt_d = $conn->prepare("SELECT Doctor_ID FROM Doctors WHERE User_ID = ?");
$stmt_d->bind_param("i", $doctor_user_id);
$stmt_d->execute();
$doctor_id = $stmt_d->get_result()->fetch_assoc()['Doctor_ID'];

// 3. Process Approval / Cancellation
if (isset($_GET['action']) && isset($_GET['app_id'])) {
    $app_id = (int)$_GET['app_id'];
    $action = $_GET['action'];
    $new_status = ($action == 'confirm') ? 'Confirmed' : 'Cancelled';
    
    $conn->begin_transaction();
    try {
        $stmt_up = $conn->prepare("UPDATE Appointments SET Status = ? WHERE Appointment_ID = ? AND Doctor_ID = ?");
        $stmt_up->bind_param("sii", $new_status, $app_id, $doctor_id);
        $stmt_up->execute();

        if ($new_status == 'Cancelled') {
            $conn->query("UPDATE DoctorSchedules SET Status = 'Available' 
                          WHERE Schedule_ID = (SELECT Schedule_ID FROM Appointments WHERE Appointment_ID = $app_id)");
        }
        $conn->commit();
    } catch (Exception $e) { $conn->rollback(); }
    header("Location: manage_appointments.php");
    exit();
}

// 4. Get Appointment List & Count Notifications
$sql = "SELECT a.*, u.FullName as PatientName, s.AvailableDate, s.StartTime, s.EndTime 
        FROM Appointments a
        JOIN Patients p ON a.Patient_ID = p.Patient_ID
        JOIN Users u ON p.User_ID = u.User_ID
        JOIN DoctorSchedules s ON a.Schedule_ID = s.Schedule_ID
        WHERE a.Doctor_ID = ? 
        ORDER BY CASE WHEN a.Status = 'Scheduled' THEN 1 ELSE 2 END, a.Created_at DESC";
$stmt_list = $conn->prepare($sql);
$stmt_list->bind_param("i", $doctor_id);
$stmt_list->execute();
$list = $stmt_list->get_result();

$noti_res = $conn->query("SELECT COUNT(*) as total FROM User_Notifications WHERE User_ID = $doctor_user_id AND IsRead = FALSE");
$count_noti = ($noti_res) ? $noti_res->fetch_assoc()['total'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Appointments | MediConnect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #3498db; --success: #27ae60; --warning: #f1c40f; --danger: #e74c3c; --dark: #2c3e50; --bg: #f4f7f6; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); margin: 0; display: flex; }

        /* Sidebar */
        .sidebar { width: 260px; background: var(--dark); min-height: 100vh; color: white; padding: 20px; position: fixed; left: 0; top: 0; box-sizing: border-box; }
        .sidebar h2 { text-align: center; color: var(--primary); margin-bottom: 30px; }
        .nav-item { padding: 15px; display: block; color: #bdc3c7; text-decoration: none; border-radius: 8px; margin-bottom: 5px; transition: 0.3s; }
        .nav-item:hover { background: #34495e; color: white; }
        .nav-item.active { 
            background: var(--primary) !important; 
            color: white !important; 
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.4); 
            font-weight: 600;
        }
        .nav-item i { margin-right: 10px; width: 20px; }

        /* Main Content */
        .main-content { margin-left: 260px; flex: 1; padding: 30px; box-sizing: border-box; width: calc(100% - 260px); }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 25px; }
        
        /* Table Style */
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; background: #f8f9fa; color: #7f8c8d; border-bottom: 2px solid #eee; }
        td { padding: 15px; border-bottom: 1px solid #eee; vertical-align: middle; }

        /* Badge Style */
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; }
        .status-scheduled { background: #e1f5fe; color: #03a9f4; }
        .status-confirmed { background: #e8f5e9; color: #2e7d32; }
        .status-cancelled { background: #ffebee; color: #c62828; }

        /* Button Style */
        .btn-action { padding: 8px 12px; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: 600; transition: 0.2s; border: none; cursor: pointer; display: inline-block; }
        .btn-confirm { background: var(--success); color: white; margin-right: 5px; }
        .btn-cancel { background: #f1f1f1; color: var(--danger); }
        .btn-back { text-decoration: none; color: var(--primary); font-weight: 600; display: flex; align-items: center; gap: 8px; }
        
        .patient-box { display: flex; align-items: center; gap: 12px; }
        .patient-img { width: 40px; height: 40px; border-radius: 50%; background: #eee; object-fit: cover; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>MediConnect</h2>
        <div class="nav-menu">
            <a href="doctor_dashboard.php" class="nav-item"><i class="fas fa-home"></i> Overview</a>
            <a href="manage_appointments.php" class="nav-item active"><i class="fas fa-calendar-check"></i> Appointments</a>
            <a href="doctor_schedule.php" class="nav-item"><i class="fas fa-clock"></i> Schedule Management</a>
            <a href="notifications.php" class="nav-item"><i class="fas fa-bell"></i> Notifications (<?= $count_noti ?>)</a>
            <a href="medicontent.php" class="nav-item"><i class="fas fa-feather-alt"></i> Medical Articles</a>
            <a href="doctor_feedback.php" class="nav-item"><i class="fas fa-star"></i> Feedback</a>
            <a href="profile.php" class="nav-item"><i class="fas fa-user-circle"></i> Profile</a>
        </div>
        
        <div class="logout-section">
            <a href="../logout.php" class="nav-item" style="color: var(--danger); margin-bottom: 0;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="page-header">
            <h2><i class="fas fa-calendar-alt" style="color: var(--primary);"></i> Manage Appointments</h2>
            <a href="doctor_dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Appointment Time</th>
                        <th>Notes</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($list->num_rows > 0): ?>
                        <?php while($row = $list->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="patient-box">
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($row['PatientName']) ?>&background=random" class="patient-img">
                                    <strong><?= htmlspecialchars($row['PatientName']) ?></strong>
                                </div>
                            </td>
                            <td>
                                <div><i class="far fa-calendar"></i> <?= date('m/d/Y', strtotime($row['AvailableDate'])) ?></div>
                                <small style="color: #7f8c8d;"><i class="far fa-clock"></i> <?= substr($row['StartTime'], 0, 5) ?> - <?= substr($row['EndTime'], 0, 5) ?></small>
                            </td>
                            <td><small><?= !empty($row['Notes']) ? htmlspecialchars($row['Notes']) : '<em>No notes</em>' ?></small></td>
                            <td>
                                <?php 
                                    $s = $row['Status'];
                                    $class = "status-" . strtolower($s);
                                    $text = ($s == 'Scheduled') ? 'Pending' : (($s == 'Confirmed') ? 'Approved' : 'Cancelled');
                                    echo "<span class='badge $class'>$text</span>";
                                ?>
                            </td>
                            <td>
                                <?php if($row['Status'] == 'Scheduled'): ?>
                                    <a href="?action=confirm&app_id=<?= $row['Appointment_ID'] ?>" class="btn-action btn-confirm">Approve</a>
                                    <a href="?action=cancel&app_id=<?= $row['Appointment_ID'] ?>" class="btn-action btn-cancel" onclick="return confirm('Cancel this appointment?')">Cancel</a>
                                <?php else: ?>
                                    <span style="color: #ccc; font-style: italic; font-size: 0.85rem;">Processed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align: center; padding: 50px; color: #95a5a6;">No appointments found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>