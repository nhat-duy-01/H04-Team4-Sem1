<?php
session_start();
require_once '../../config/connectDB.php';

// 1. Doctor Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Doctor') {
    header("Location: ../authentication.php");
    exit();
}

$conn = (new ConnectDB())->connection();
$user_id = $_SESSION['user_id'];

// 2. Get current Doctor_ID
$stmt_d = $conn->prepare("SELECT Doctor_ID FROM Doctors WHERE User_ID = ?");
$stmt_d->bind_param("i", $user_id);
$stmt_d->execute();
$doctor_data = $stmt_d->get_result()->fetch_assoc();
$my_doctor_id = $doctor_data['Doctor_ID'];

// 3. Query Notifications
$search_tag = "%[DR_ID:$my_doctor_id]%";
$sql_noti = "SELECT * FROM notifications 
             WHERE Message LIKE ? 
             ORDER BY Created_at DESC";

$stmt_n = $conn->prepare($sql_noti);
$stmt_n->bind_param("s", $search_tag);
$stmt_n->execute();
$result = $stmt_n->get_result();

// Count unread notifications for Sidebar
$count_noti_res = $conn->query("SELECT COUNT(*) as total FROM User_Notifications WHERE User_ID = $user_id AND IsRead = FALSE");
$count_noti = $count_noti_res ? $count_noti_res->fetch_assoc()['total'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Notifications | MediConnect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #3498db; --success: #27ae60; --danger: #e74c3c; --dark: #2c3e50; --bg: #f4f7f6; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); margin: 0; display: flex; }

        /* Sidebar Styles */
        .sidebar { width: 260px; background: var(--dark); height: 100vh; color: white; padding: 20px; position: fixed; left: 0; top: 0; box-sizing: border-box; }
        .sidebar h2 { text-align: center; color: var(--primary); margin-bottom: 30px; }
        .nav-item { padding: 15px; display: block; color: #bdc3c7; text-decoration: none; border-radius: 8px; margin-bottom: 5px; transition: 0.3s; }
        .nav-item:hover { background: #34495e; color: white; }
        .nav-item.active { background: var(--primary); color: white; }
        .nav-item i { margin-right: 10px; width: 20px; }

        /* Main Content */
        .main-content { margin-left: 260px; flex: 1; padding: 30px; box-sizing: border-box; width: calc(100% - 260px); }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }

        .noti-container { max-width: 900px; margin: 0 auto; }
        
        .noti-card { 
            background: white; 
            padding: 20px; 
            border-radius: 12px; 
            margin-bottom: 15px; 
            border-left: 5px solid #cbd5e1;
            display: flex;
            align-items: flex-start;
            gap: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            transition: 0.3s;
        }
        .noti-card:hover { transform: translateX(5px); box-shadow: 0 6px 12px rgba(0,0,0,0.05); }
        
        /* Category Colors */
        .noti-card.Cancel_Alert { border-left-color: var(--danger); }
        .noti-card.Appointment_Update { border-left-color: var(--success); }
        .noti-card.System_Alert { border-left-color: var(--primary); }

        .icon-box { 
            background: #f1f5f9; 
            width: 45px; height: 45px; 
            display: flex; align-items: center; justify-content: center; 
            border-radius: 50%; color: #64748b; font-size: 1.2rem;
        }
        .Cancel_Alert .icon-box { color: var(--danger); background: #fef2f2; }
        .Appointment_Update .icon-box { color: var(--success); background: #f0fdf4; }

        .noti-content { flex-grow: 1; }
        .noti-msg { font-size: 16px; color: #1e293b; margin: 0 0 8px 0; line-height: 1.5; font-weight: 500; }
        .noti-time { font-size: 13px; color: #94a3b8; display: flex; align-items: center; gap: 5px; }
        
        .empty-state { text-align: center; padding: 80px 0; color: #94a3b8; background: white; border-radius: 12px; }
        .btn-back { text-decoration: none; color: var(--primary); font-weight: 600; display: flex; align-items: center; gap: 8px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>MediConnect</h2>
        <div class="nav-menu">
            <a href="doctor_dashboard.php" class="nav-item"><i class="fas fa-home"></i> Overview</a>
            <a href="manage_appointments.php" class="nav-item"><i class="fas fa-calendar-check"></i> Appointments</a>
            <a href="doctor_schedule.php" class="nav-item"><i class="fas fa-clock"></i> Schedule Management</a>
            <a href="notifications.php" class="nav-item active"><i class="fas fa-bell"></i> Notifications (<?= $count_noti ?>)</a>
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
            <h2><i class="fas fa-bell" style="color: var(--primary);"></i> Your Notifications</h2>
            <a href="doctor_dashboard.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <div class="noti-container">
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="noti-card <?= htmlspecialchars($row['Type']) ?>">
                        <div class="icon-box">
                            <i class="fas <?= $row['Type'] == 'Cancel_Alert' ? 'fa-calendar-times' : ($row['Type'] == 'Appointment_Update' ? 'fa-calendar-check' : 'fa-info-circle') ?>"></i>
                        </div>
                        <div class="noti-content">
                            <p class="noti-msg">
                                <?php 
                                    // Hide the internal [DR_ID:...] tag for a cleaner English UI
                                    $display_msg = preg_replace('/\[DR_ID:\d+\]\s*/', '', $row['Message']);
                                    echo htmlspecialchars($display_msg);
                                ?>
                            </p>
                            <span class="noti-time">
                                <i class="far fa-clock"></i> <?= date('H:i - M d, Y', strtotime($row['Created_at'])) ?>
                            </span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-comment-slash fa-4x" style="margin-bottom: 20px; opacity: 0.3;"></i>
                    <p style="font-size: 1.1rem;">You have not received any notifications yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>