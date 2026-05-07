<?php
session_start();
require_once '../../config/connectDB.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../authentication.php");
    exit();
}

$db = new ConnectDB();
$conn = $db->connection();
$schedule_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch information to display confirmation
$sql = "SELECT s.*, u.FullName, d.Doctor_ID 
        FROM DoctorSchedules s 
        JOIN Doctors d ON s.Doctor_ID = d.Doctor_ID 
        JOIN Users u ON d.User_ID = u.User_ID 
        WHERE s.Schedule_ID = ? AND s.Status = 'Available'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $schedule_id);
$stmt->execute();
$booking_info = $stmt->get_result()->fetch_assoc();

if (!$booking_info) {
    die("Schedule does not exist or has already been booked. <a href='javascript:history.back()'>Go back</a>");
}

if (isset($_POST['confirm_booking'])) {
    $user_id = $_SESSION['user_id'];
    
    // Get Patient_ID
    $stmt_p = $conn->prepare("SELECT Patient_ID FROM Patients WHERE User_ID = ?");
    $stmt_p->bind_param("i", $user_id);
    $stmt_p->execute();
    $patient = $stmt_p->get_result()->fetch_assoc();
    
    if ($patient) {
        $patient_id = $patient['Patient_ID'];
        $doctor_id  = $booking_info['Doctor_ID'];
        $booking_datetime = $booking_info['AvailableDate'] . ' ' . $booking_info['StartTime'];
        $notes = trim($_POST['notes'] ?? 'New registration');

        $conn->begin_transaction();
        try {
            // SAVE STATUS AS 'Scheduled' (Awaiting doctor approval)
            $sql_app = "INSERT INTO Appointments 
                        (BookingDate, Status, Notes, Created_at, Patient_ID, Doctor_ID, Schedule_ID) 
                        VALUES (?, 'Scheduled', ?, NOW(), ?, ?, ?)";
            $stmt_app = $conn->prepare($sql_app);
            $stmt_app->bind_param("ssiii", $booking_datetime, $notes, $patient_id, $doctor_id, $schedule_id);
            $stmt_app->execute();

            // Mark this time slot as "Booked"
            $conn->query("UPDATE DoctorSchedules SET Status = 'Booked' WHERE Schedule_ID = $schedule_id");

            $conn->commit();
            header("Location: patient_appointments.php?status=success");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Confirm Appointment | MediConnect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #007bff; --bg: #f4f7f6; --dark: #2c3e50; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); padding: 20px; color: var(--dark); }
        .card-confirm { max-width: 550px; margin: 40px auto; background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .doctor-brief { display: flex; align-items: center; gap: 15px; background: #f8f9fa; padding: 15px; border-radius: 12px; margin-bottom: 20px; }
        .info-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #eee; }
        .info-row span:first-child { color: #7f8c8d; font-weight: 500; }
        .info-row span:last-child { font-weight: 700; color: var(--dark); }
        textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 10px; margin-top: 15px; box-sizing: border-box; resize: none; }
        .btn-group { display: flex; gap: 10px; margin-top: 25px; }
        .btn { flex: 1; padding: 13px; border-radius: 10px; border: none; font-weight: 700; cursor: pointer; transition: 0.3s; text-align: center; text-decoration: none; }
        .btn-main { background: var(--primary); color: white; }
        .btn-main:hover { background: #0056b3; }
        .btn-sub { background: #e9ecef; color: #495057; }
    </style>
</head>
<body>

<div class="card-confirm">
    <h2 style="text-align:center; margin-bottom:25px;">Confirm Appointment</h2>
    
    <div class="doctor-brief">
        <i class="fas fa-user-md" style="font-size: 30px; color: var(--primary);"></i>
        <div>
            <div style="font-size: 14px; color: #7f8c8d;">Attending Doctor</div>
            <div style="font-weight: 700; font-size: 18px;">Dr. <?= htmlspecialchars($booking_info['FullName']) ?></div>
        </div>
    </div>

    <div class="info-row">
        <span>Appointment Date</span>
        <span><?= date('M d, Y', strtotime($booking_info['AvailableDate'])) ?></span>
    </div>
    <div class="info-row">
        <span>Time Slot</span>
        <span><?= substr($booking_info['StartTime'], 0, 5) ?> - <?= substr($booking_info['EndTime'], 0, 5) ?></span>
    </div>

    <form method="POST">
        <p style="margin-bottom: 5px; font-size: 14px; font-weight: 600;">Notes for the doctor (optional):</p>
        <textarea name="notes" rows="3" placeholder="Example: I have had a sore throat since yesterday..."></textarea>

        <div class="btn-group">
            <a href="javascript:history.back()" class="btn btn-sub">Go Back</a>
            <button type="submit" name="confirm_booking" class="btn btn-main">Confirm Booking</button>
        </div>
    </form>
</div>

</body>
</html>