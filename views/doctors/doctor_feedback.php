<?php
session_start();
require_once '../../config/connectDB.php';

// Access Control (Doctor only)
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Doctor') {
    header("Location: ../authentication.php");
    exit();
}

$db = new ConnectDB();
$conn = $db->connection();
$doctor_user_id = $_SESSION['user_id'];

// 1. Get Doctor_ID
$stmt_d = $conn->prepare("SELECT Doctor_ID FROM Doctors WHERE User_ID = ?");
$stmt_d->bind_param("i", $doctor_user_id);
$stmt_d->execute();
$doctor_data = $stmt_d->get_result()->fetch_assoc();
$doctor_id = $doctor_data['Doctor_ID'];

// 2. Statistics
$stats = $conn->query("SELECT AVG(Rating) as avg_r, COUNT(*) as total FROM feedback f 
                       JOIN Appointments a ON f.Appointment_ID = a.Appointment_ID 
                       WHERE a.Doctor_ID = $doctor_id")->fetch_assoc();

$count_noti = $conn->query("SELECT COUNT(*) as total FROM User_Notifications WHERE User_ID = $doctor_user_id AND IsRead = FALSE")->fetch_assoc()['total'];

// 3. Get Feedback
$sql_fb = "SELECT f.*, u.FullName AS PatientName, u.ProfilePicture 
           FROM feedback f
           JOIN Patients p ON f.Patient_ID = p.Patient_ID
           JOIN Users u ON p.User_ID = u.User_ID
           JOIN Appointments a ON f.Appointment_ID = a.Appointment_ID
           WHERE a.Doctor_ID = ?
           ORDER BY f.Created_at DESC";

$stmt_fb = $conn->prepare($sql_fb);
$stmt_fb->bind_param("i", $doctor_id);
$stmt_fb->execute();
$res_fb = $stmt_fb->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Feedback | MediConnect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { 
            --primary: #3498db; 
            --success: #27ae60; 
            --warning: #f1c40f; 
            --danger: #e74c3c; 
            --dark: #2c3e50; 
            --bg: #f4f7f6; 
        }

        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); margin: 0; display: flex; }

        /* SIDEBAR - Fixed Width 260px */

.sidebar { 
    width: 260px; 
    background: var(--dark); 
    height: 100vh; 
    color: white; 
    padding: 20px; 
    position: fixed; 
    left: 0; top: 0; 
    z-index: 1000; 
    box-sizing: border-box;
    /* Kỹ thuật Flexbox để quản lý không gian dọc */
    display: flex;
    flex-direction: column;
}

.sidebar h2 { text-align: center; color: var(--primary); margin-bottom: 30px; flex-shrink: 0; }

/* Container chứa các mục điều hướng */
.nav-menu {
    flex: 1;
    overflow-y: auto; /* Cuộn nội bộ nếu danh sách quá dài */
}
.nav-menu::-webkit-scrollbar { display: none; } /* Ẩn thanh cuộn cho đẹp */

.nav-item { 
    padding: 15px; 
    display: block; 
    color: #bdc3c7; 
    text-decoration: none; 
    border-radius: 8px; 
    margin-bottom: 5px; 
    transition: 0.3s; 
}

.nav-item:hover { background: #34495e; color: white; }

/* Trạng thái sáng lên khi đang ở trang hiện tại */
.nav-item.active { 
    background: var(--primary) !important; 
    color: white !important; 
}

.nav-item i { margin-right: 10px; width: 20px; }

/* Phần Logout luôn nằm đáy */
.logout-section {
    margin-top: auto;
    padding-top: 20px;
    flex-shrink: 0;
}

        /* MAIN CONTENT */
        .main-content { margin-left: 260px; width: calc(100% - 260px); padding: 30px; box-sizing: border-box; }

        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #e0e4e8; }
        .header h1 { margin: 0; font-size: 1.8rem; color: var(--dark); font-weight: 700; }

        .stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); display: flex; align-items: center; }
        .stat-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-right: 15px; }
        .stat-info h3 { margin: 0; font-size: 1.5rem; }
        .stat-info p { margin: 0; color: #7f8c8d; font-size: 0.9rem; }

        .table-container { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; color: #7f8c8d; border-bottom: 2px solid #f4f7f6; }
        td { padding: 15px 12px; border-bottom: 1px solid #f4f7f6; vertical-align: middle; }
        
        .patient-cell { display: flex; align-items: center; gap: 10px; }
        .patient-img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        
        .rating-stars { color: #f1c40f; font-size: 0.9rem; }
        .message-preview { color: #475569; font-style: italic; background: #f8fafc; padding: 10px; border-radius: 8px; font-size: 0.9rem; border-left: 3px solid var(--primary); }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>MediConnect</h2>
        <div class="nav-menu">
            <a href="doctor_dashboard.php" class="nav-item"><i class="fas fa-home"></i> Overview</a>
            <a href="manage_appointments.php" class="nav-item"><i class="fas fa-calendar-check"></i> Appointments</a>
            <a href="doctor_schedule.php" class="nav-item"><i class="fas fa-clock"></i> Schedule Management</a>
            <a href="notifications.php" class="nav-item"><i class="fas fa-bell"></i> Notifications (<?= $count_noti ?>)</a>
            <a href="medicontent.php" class="nav-item"><i class="fas fa-feather-alt"></i> Medical Articles</a>
            <a href="doctor_feedback.php" class="nav-item active"><i class="fas fa-star"></i> Feedback</a>
            <a href="profile.php" class="nav-item"><i class="fas fa-user-circle"></i> Profile</a>
        </div>
        
        <div class="logout-section">
            <a href="../logout.php" class="nav-item" style="color: var(--danger); margin-bottom: 0;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Patient Feedback 👋</h1>
            <div class="user-info">
                <span><i class="far fa-star"></i> Your Reputation Score</span>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: #fff9db; color: #f1c40f;"><i class="fas fa-star"></i></div>
                <div class="stat-info">
                    <h3><?= number_format($stats['avg_r'], 1) ?> / 5.0</h3>
                    <p>Average Rating</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #e8f5e9; color: #4caf50;"><i class="fas fa-comments"></i></div>
                <div class="stat-info">
                    <h3><?= $stats['total'] ?></h3>
                    <p>Total Reviews</p>
                </div>
            </div>
        </div>

        <div class="table-container">
            <div class="table-header" style="margin-bottom: 20px;">
                <h2>Detailed Patient Reviews</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Rating</th>
                        <th>Review Message</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($res_fb->num_rows > 0): ?>
                        <?php while($row = $res_fb->fetch_assoc()): ?>
                        <tr>
                            <td class="patient-cell">
                                <img src="<?= !empty($row['ProfilePicture']) ? '../../public/uploads/avatars/'.$row['ProfilePicture'] : 'https://ui-avatars.com/api/?name='.urlencode($row['PatientName']).'&background=random' ?>" class="patient-img">
                                <strong><?= htmlspecialchars($row['PatientName']) ?></strong>
                            </td>
                            <td>
                                <div class="rating-stars">
                                    <?php for($i=1; $i<=5; $i++) echo ($i <= $row['Rating']) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                                </div>
                            </td>
                            <td><div class="message-preview">"<?= htmlspecialchars($row['Message']) ?>"</div></td>
                            <td><span style="color: #7f8c8d; font-size: 0.85rem;"><?= date('M d, Y', strtotime($row['Created_at'])) ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align: center; color: #95a5a6; padding: 40px;">No feedback received yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>