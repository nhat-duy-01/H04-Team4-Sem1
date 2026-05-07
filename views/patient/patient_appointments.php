<?php
session_start();
require_once '../../config/connectDB.php';

// Login Check
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Patient') {
    header("Location: ../authentication.php");
    exit();
}

$conn = (new ConnectDB())->connection();
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Patient'; // Get name from session

// 1. Get Patient_ID from User_ID
$stmt_p = $conn->prepare("SELECT Patient_ID FROM Patients WHERE User_ID = ?");
$stmt_p->bind_param("i", $user_id);
$stmt_p->execute();
$res_p = $stmt_p->get_result();
$patient_data = $res_p->fetch_assoc();

if (!$patient_data) {
    die("Error: Patient information not found. Please check the Patients table.");
}
$patient_id = $patient_data['Patient_ID'];

$message = "";

// --- 2. HANDLE APPOINTMENT CANCELLATION ---
if (isset($_POST['cancel_appointment'])) {
    $appointment_id = $_POST['appointment_id'];
    
    // Check appointment and get Doctor_ID, Schedule_ID before cancelling
    $check_sql = "SELECT Doctor_ID, Schedule_ID FROM Appointments WHERE Appointment_ID = ? AND Patient_ID = ?";
    $stmt_check = $conn->prepare($check_sql);
    $stmt_check->bind_param("ii", $appointment_id, $patient_id);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();
    
    if ($res_check->num_rows > 0) {
        $app_data = $res_check->fetch_assoc();
        $doctor_id = $app_data['Doctor_ID'];
        $schedule_id = $app_data['Schedule_ID'];

        // A. Update Appointment status to Cancelled
        $sql_cancel = "UPDATE Appointments SET Status = 'Cancelled' WHERE Appointment_ID = ?";
        $stmt_c = $conn->prepare($sql_cancel);
        $stmt_c->bind_param("i", $appointment_id);
        
        if ($stmt_c->execute()) {
            // B. Release the time slot (Available)
            $conn->query("UPDATE DoctorSchedules SET Status = 'Available' WHERE Schedule_ID = $schedule_id");

            // C. Send notification (Using system notification format)
            $noti_content = "[DR_ID:$doctor_id] Appointment #$appointment_id has been cancelled by patient $user_name.";
            $noti_type = "Cancel_Alert";

            $sql_noti = "INSERT INTO notifications (Message, Type, Created_at) VALUES (?, ?, NOW())";
            $stmt_noti = $conn->prepare($sql_noti);
            $stmt_noti->bind_param("ss", $noti_content, $noti_type);
            $stmt_noti->execute();

            $message = "<div class='alert success'>Appointment cancelled and doctor notified successfully!</div>";
        }
    }
}

// --- 3. HANDLE NOTES UPDATE ---
if (isset($_POST['update_notes'])) {
    $appointment_id = $_POST['appointment_id'];
    $new_notes = trim($_POST['notes']);

    $sql_update = "UPDATE Appointments SET Notes = ? WHERE Appointment_ID = ? AND Patient_ID = ?";
    $stmt_un = $conn->prepare($sql_update);
    $stmt_un->bind_param("sii", $new_notes, $appointment_id, $patient_id);
    
    if ($stmt_un->execute()) {
        $message = "<div class='alert success'>Notes updated successfully!</div>";
    }
}

// --- 4. QUERY APPOINTMENT LIST ---
$sql_list = "SELECT 
                a.Appointment_ID, a.Status, a.Notes, 
                u_d.FullName AS DoctorName,
                s.AvailableDate, s.StartTime, s.EndTime
            FROM Appointments a
            JOIN Doctors d ON a.Doctor_ID = d.Doctor_ID
            JOIN Users u_d ON d.User_ID = u_d.User_ID
            JOIN DoctorSchedules s ON a.Schedule_ID = s.Schedule_ID
            WHERE a.Patient_ID = ? 
            ORDER BY s.AvailableDate DESC, s.StartTime DESC";

$stmt_l = $conn->prepare($sql_list);
$stmt_l->bind_param("i", $patient_id);
$stmt_l->execute();
$result = $stmt_l->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Appointments | MediConnect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #3498db; --danger: #e74c3c; --success: #2ecc71; --bg: #f8fafc; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: var(--bg); color: #334155; margin: 0; padding: 20px; }
        .container { max-width: 1100px; margin: auto; }
        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .table-box { background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8fafc; padding: 15px; text-align: left; font-size: 13px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 18px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        
        .badge { padding: 5px 12px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .badge-pending { background: #fef9c3; color: #854d0e; }
        .badge-confirmed { background: #dcfce7; color: #166534; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }
        .badge-completed { background: #e0f2fe; color: #075985; }

        .btn { border: none; padding: 10px 15px; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-edit { background: #3498db; color: white; }
        .btn-edit:hover { background: #2980b9; }
        .btn-cancel { background: #fff1f0; color: #e74c3c; border: 1px solid #ffa39e; }
        .btn-cancel:hover { background: #e74c3c; color: white; }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 25px; font-weight: 500; border-left: 5px solid; }
        .success { background: #f0fdf4; color: #166534; border-left-color: #2ecc71; }
        
        textarea { width: 100%; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; font-family: inherit; resize: none; transition: 0.3s; }
        textarea:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1); }
        .back-link { color: #64748b; text-decoration: none; font-size: 14px; transition: 0.2s; }
        .back-link:hover { color: var(--primary); }
    </style>
</head>
<body>

<div class="container">
    <div class="header-flex">
        <h2 style="margin:0;"><i class="fas fa-calendar-check" style="color: var(--primary);"></i> My Appointments</h2>
        <a href="patient_dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <?= $message ?>

    <div class="table-box">
        <table>
            <thead>
                <tr>
                    <th width="20%">Doctor</th>
                    <th width="20%">Date & Time</th>
                    <th width="30%">My Notes</th>
                    <th width="15%">Status</th>
                    <th width="15%">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 700; color: #1e293b;">Dr. <?= htmlspecialchars($row['DoctorName']) ?></div>
                        </td>
                        <td>
                            <div style="font-weight: 600;"><i class="far fa-calendar-alt"></i> <?= date('m/d/Y', strtotime($row['AvailableDate'])) ?></div>
                            <div style="color: #64748b; font-size: 12px; margin-top: 4px;">
                                <i class="far fa-clock"></i> <?= substr($row['StartTime'], 0, 5) ?> - <?= substr($row['EndTime'], 0, 5) ?>
                            </div>
                        </td>
                        <td>
                            <form method="POST" style="display:flex; flex-direction: column; gap:8px;">
                                <input type="hidden" name="appointment_id" value="<?= $row['Appointment_ID'] ?>">
                                <textarea name="notes" placeholder="Enter symptoms or notes..."><?= htmlspecialchars($row['Notes']) ?></textarea>
                                <button type="submit" name="update_notes" class="btn btn-edit" style="width: fit-content; padding: 5px 10px; font-size: 11px;">
                                    <i class="fas fa-save"></i> Update Notes
                                </button>
                            </form>
                        </td>
                        <td>
                            <span class="badge badge-<?= strtolower($row['Status']) ?>">
                                <?php 
                                    switch($row['Status']) {
                                        case 'Pending': echo 'Pending'; break;
                                        case 'Confirmed': echo 'Confirmed'; break;
                                        case 'Completed': echo 'Completed'; break;
                                        case 'Cancelled': echo 'Cancelled'; break;
                                        default: echo $row['Status'];
                                    }
                                ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($row['Status'] === 'Pending' || $row['Status'] === 'Confirmed'): ?>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this appointment? This will notify the doctor.')">
                                    <input type="hidden" name="appointment_id" value="<?= $row['Appointment_ID'] ?>">
                                    <button type="submit" name="cancel_appointment" class="btn btn-cancel">
                                        <i class="fas fa-trash-alt"></i> Cancel
                                    </button>
                                </form>
                            <?php else: ?>
                                <span style="color: #cbd5e1; font-style: italic;">Not Available</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center; padding: 50px; color: #94a3b8;">
                            <i class="fas fa-folder-open fa-3x" style="display:block; margin-bottom:10px;"></i>
                            You have no appointments.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>