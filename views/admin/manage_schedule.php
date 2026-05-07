<?php
session_start();
require_once '../../config/connectDB.php';

$conn = (new ConnectDB())->connection();
$message = "";

// --- 1. HANDLE CREATE (btnAdd) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btnAdd'])) {
    $doctor_id = $_POST['doctor_id']; // Doctor ID selected from dropdown
    $date = $_POST['work_date'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];

    $sql = "INSERT INTO doctorschedules (AvailableDate, StartTime, EndTime, Status, Doctor_ID) 
            VALUES (?, ?, ?, 'Available', ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $date, $start, $end, $doctor_id);
    if ($stmt->execute()) {
        $message = "<div class='alert success'>New schedule added successfully!</div>";
    }
}

// --- 2. HANDLE UPDATE (btnUpdate) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btnUpdate'])) {
    $id = $_POST['schedule_id'];
    $date = $_POST['work_date'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];
    $status = $_POST['status'];

    $sql = "UPDATE doctorschedules SET AvailableDate = ?, StartTime = ?, EndTime = ?, Status = ? WHERE Schedule_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $date, $start, $end, $status, $id);
    if ($stmt->execute()) {
        $message = "<div class='alert success'>Schedule updated successfully!</div>";
    }
}

// --- 3. HANDLE DELETE (btnDelete) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btnDelete'])) {
    $id = $_POST['schedule_id'];
    $sql = "DELETE FROM doctorschedules WHERE Schedule_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "<div class='alert success'>Time slot deleted successfully!</div>";
    }
}

$sql_list = "SELECT s.*, u.FullName, u.Email, u.Phone 
             FROM doctorschedules s
             JOIN Doctors d ON s.Doctor_ID = d.Doctor_ID
             JOIN Users u ON d.User_ID = u.User_ID
             ORDER BY s.AvailableDate DESC, s.StartTime ASC";
$result = $conn->query($sql_list);

$doctors_list = $conn->query("SELECT d.Doctor_ID, u.FullName FROM Doctors d JOIN Users u ON d.User_ID = u.User_ID");

$edit_data = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_id = $_GET['id'];
    $edit_data = $conn->query("SELECT * FROM doctorschedules WHERE Schedule_ID = $edit_id")->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Schedules | MediConnect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <div class="header-flex">
        <h2><i class="fas fa-calendar-alt"></i> Manage Doctor Schedules</h2>
        <a href="admin_dashboard.php" class="back-dashboard">⬅️ Back to Dashboard</a>
    </div>

    <?= $message ?>

    <div class="card">
        <?php if ($edit_data): ?>
            <h3>✏️ Edit Work Schedule</h3>
            <form method="post" class="form-grid">
                <input type="hidden" name="schedule_id" value="<?= $edit_data['Schedule_ID'] ?>">
                <div class="input-group">
                    <label>Work Date</label>
                    <input type="date" name="work_date" value="<?= $edit_data['AvailableDate'] ?>" required>
                </div>
                <div class="input-group">
                    <label>Start Time</label>
                    <input type="time" name="start_time" value="<?= $edit_data['StartTime'] ?>" required>
                </div>
                <div class="input-group">
                    <label>End Time</label>
                    <input type="time" name="end_time" value="<?= $edit_data['EndTime'] ?>" required>
                </div>
                <div class="input-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="Available" <?= $edit_data['Status']=='Available'?'selected':'' ?>>Available</option>
                        <option value="Booked" <?= $edit_data['Status']=='Booked'?'selected':'' ?>>Booked</option>
                    </select>
                </div>
                <div style="grid-column: 1/-1;">
                    <button type="submit" name="btnUpdate" class="btn-submit">Update</button>
                    <a href="manage_schedule.php" class="btn-cancel">Cancel</a>
                </div>
            </form>
        <?php else: ?>
            <h3>➕ Add New Work Slot</h3>
            <form method="post" class="form-grid">
                <div class="input-group">
                    <label>Select Doctor</label>
                    <select name="doctor_id" required>
                        <option value="">-- Select a doctor --</option>
                        <?php while($d = $doctors_list->fetch_assoc()): ?>
                            <option value="<?= $d['Doctor_ID'] ?>"><?= $d['FullName'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="input-group">
                    <label>Work Date</label>
                    <input type="date" name="work_date" min="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="input-group">
                    <label>Start Time</label>
                    <input type="time" name="start_time" required>
                </div>
                <div class="input-group">
                    <label>End Time</label>
                    <input type="time" name="end_time" required>
                </div>
                <div style="grid-column: 1/-1;">
                    <button type="submit" name="btnAdd" class="btn-submit">Add to Schedule</button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <div class="table-box">
        <table>
            <thead>
                <tr>
                    <th>Doctor</th>
                    <th>Contact Info</th>
                    <th>Schedule Time</th>
                    <th>Status</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td>
                        <strong>Dr. <?= htmlspecialchars($row['FullName']) ?></strong>
                        <div style="font-size: 11px; color: #94a3b8;">Doctor ID: #<?= $row['Doctor_ID'] ?></div>
                    </td>
                    <td>
                        <div style="font-size: 13px;"><i class="fas fa-envelope"></i> <?= $row['Email'] ?></div>
                        <div style="font-size: 13px;"><i class="fas fa-phone"></i> <?= $row['Phone'] ?></div>
                    </td>
                    <td>
                        <div style="font-weight: 600;"><?= date('M d, Y', strtotime($row['AvailableDate'])) ?></div>
                        <small><?= substr($row['StartTime'],0,5) ?> - <?= substr($row['EndTime'],0,5) ?></small>
                    </td>
                    <td>
                        <span class="badge badge-<?= strtolower($row['Status']) ?>">
                            <?= $row['Status'] == 'Available' ? 'Available' : 'Booked' ?>
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <a href="?action=edit&id=<?= $row['Schedule_ID'] ?>" class="btn-icon edit">Edit</a>
                        
                        <form method="post" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this work slot?')">
                            <input type="hidden" name="schedule_id" value="<?= $row['Schedule_ID'] ?>">
                            <button type="submit" name="btnDelete" class="btn-icon delete">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>