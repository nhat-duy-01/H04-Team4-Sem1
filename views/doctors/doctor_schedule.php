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

// 1. Get Doctor_ID
$stmt_d = $conn->prepare("SELECT Doctor_ID FROM Doctors WHERE User_ID = ?");
$stmt_d->bind_param("i", $doctor_user_id);
$stmt_d->execute();
$doctor_id = $stmt_d->get_result()->fetch_assoc()['Doctor_ID'];

// 2. Handle Save/Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_schedule'])) {
    $date = $_POST['available_date'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];
    $id = $_POST['schedule_id']; 

    if ($start < $end) {
        if (!empty($id)) {
            // Update existing schedule
            $stmt = $conn->prepare("UPDATE DoctorSchedules SET AvailableDate=?, StartTime=?, EndTime=? WHERE Schedule_ID=? AND Doctor_ID=?");
            $stmt->bind_param("sssii", $date, $start, $end, $id, $doctor_id);
            $stmt->execute();
        } else {
            // Insert new schedule
            $stmt = $conn->prepare("INSERT INTO DoctorSchedules (Doctor_ID, AvailableDate, StartTime, EndTime, Status) VALUES (?, ?, ?, ?, 'Available')");
            $stmt->bind_param("isss", $doctor_id, $date, $start, $end);
            $stmt->execute();
        }
        header("Location: doctor_schedule.php?status=success");
        exit();
    } else {
        header("Location: doctor_schedule.php?status=error_time");
        exit();
    }
}

// 3. Handle Delete
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    // Only allow deletion if the slot is still 'Available'
    $conn->query("DELETE FROM DoctorSchedules WHERE Schedule_ID = $del_id AND Doctor_ID = $doctor_id AND Status = 'Available'");
    header("Location: doctor_schedule.php");
    exit();
}

$edit_data = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $edit_res = $conn->query("SELECT * FROM DoctorSchedules WHERE Schedule_ID = $edit_id AND Doctor_ID = $doctor_id");
    $edit_data = $edit_res->fetch_assoc();
}

$schedules = $conn->query("SELECT * FROM DoctorSchedules WHERE Doctor_ID = $doctor_id ORDER BY AvailableDate DESC, StartTime ASC");
$count_noti = $conn->query("SELECT COUNT(*) as total FROM User_Notifications WHERE User_ID = $doctor_user_id AND IsRead = FALSE")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Schedule | MediConnect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #3498db; --success: #27ae60; --danger: #e74c3c; --dark: #2c3e50; --bg: #f4f7f6; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); margin: 0; display: flex; }

        /* Sidebar */
        .sidebar { width: 260px; background: var(--dark); height: 100vh; color: white; padding: 20px; position: fixed; left: 0; top: 0; box-sizing: border-box; }
        .sidebar h2 { text-align: center; color: var(--primary); margin-bottom: 30px; }
        .nav-item { padding: 15px; display: block; color: #bdc3c7; text-decoration: none; border-radius: 8px; margin-bottom: 5px; transition: 0.3s; }
        .nav-item:hover { background: #34495e; color: white; }
        .nav-item.active { background: var(--primary); color: white; }
        .nav-item i { margin-right: 10px; width: 20px; }

        /* Main Content */
        .main-content { margin-left: 260px; flex: 1; padding: 30px; box-sizing: border-box; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        
        .card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; align-items: flex-end; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; background: #f8f9fa; color: #7f8c8d; border-bottom: 2px solid #eee; }
        td { padding: 15px; border-bottom: 1px solid #eee; }

        .btn-save { background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; }
        .bg-success { background: #e8f5e9; color: #2e7d32; }
        .bg-danger { background: #ffebee; color: #c62828; }
        .btn-back { text-decoration: none; color: var(--primary); font-weight: 600; display: flex; align-items: center; gap: 5px; }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 15px; font-size: 0.9rem; }
        .alert-success { background: #d4edda; color: #155724; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>MediConnect</h2>
        <div class="nav-menu">
            <a href="doctor_dashboard.php" class="nav-item"><i class="fas fa-home"></i> Overview</a>
            <a href="manage_appointments.php" class="nav-item"><i class="fas fa-calendar-check"></i> Appointments</a>
            <a href="doctor_schedule.php" class="nav-item active"><i class="fas fa-clock"></i> Schedule Management</a>
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
            <h2><i class="fas fa-business-time" style="color: var(--primary);"></i> Clinical Hours Management</h2>
            <a href="doctor_dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
            <div class="alert alert-success">Action performed successfully!</div>
        <?php endif; ?>

        <?php if(isset($_GET['status']) && $_GET['status'] == 'error_time'): ?>
            <div class="alert" style="background: #f8d7da; color: #721c24;">Error: Start time must be before end time.</div>
        <?php endif; ?>

        <div class="card">
            <form method="POST">
                <input type="hidden" name="schedule_id" value="<?= $edit_data['Schedule_ID'] ?? '' ?>">
                <div class="form-grid">
                    <div>
                        <label>Date</label>
                        <input type="date" name="available_date" required min="<?= date('Y-m-d') ?>" value="<?= $edit_data['AvailableDate'] ?? '' ?>">
                    </div>
                    <div>
                        <label>Start Time</label>
                        <input type="time" name="start_time" required value="<?= $edit_data['StartTime'] ?? '' ?>">
                    </div>
                    <div>
                        <label>End Time</label>
                        <input type="time" name="end_time" required value="<?= $edit_data['EndTime'] ?? '' ?>">
                    </div>
                    <button type="submit" name="save_schedule" class="btn-save">Save Slot</button>
                </div>
            </form>
        </div>

        <div class="card" style="padding:0;">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time Slot</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $schedules->fetch_assoc()): ?>
                    <tr>
                        <td><b><?= date('M d, Y', strtotime($row['AvailableDate'])) ?></b></td>
                        <td><?= substr($row['StartTime'], 0, 5) ?> - <?= substr($row['EndTime'], 0, 5) ?></td>
                        <td>
                            <span class="badge <?= $row['Status'] == 'Available' ? 'bg-success' : 'bg-danger' ?>">
                                <?= $row['Status'] == 'Available' ? 'Available' : 'Booked' ?>
                            </span>
                        </td>
                        <td>
                            <?php if($row['Status'] == 'Available'): ?>
                                <a href="?edit_id=<?= $row['Schedule_ID'] ?>" style="color: var(--primary); margin-right: 10px;" title="Edit"><i class="fas fa-edit"></i></a>
                                <a href="?delete_id=<?= $row['Schedule_ID'] ?>" style="color: var(--danger);" onclick="return confirm('Delete this slot?')" title="Delete"><i class="fas fa-trash"></i></a>
                            <?php else: ?>
                                <small style="color: #bdc3c7;">Locked</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>