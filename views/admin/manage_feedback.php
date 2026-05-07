<?php
session_start();
require_once '../../config/connectDB.php';
$conn = (new ConnectDB())->connection();

$message_status = "";

// 1. HANDLE DELETE FEEDBACK
if (isset($_POST['delete_feedback'])) {
    $fb_id = (int)$_POST['feedback_id'];
    $sql_del = "DELETE FROM feedback WHERE Feedback_ID = $fb_id";
    if ($conn->query($sql_del)) {
        $message_status = "✅ Review deleted successfully!";
    }
}

// 2. QUERY FEEDBACK LIST (Joined to get Patient and Doctor names)
$sql_fb = "SELECT f.*, 
                  u_p.FullName AS PatientName, 
                  u_d.FullName AS DoctorName 
           FROM feedback f
           JOIN Patients p ON f.Patient_ID = p.Patient_ID
           JOIN Users u_p ON p.User_ID = u_p.User_ID
           JOIN Appointments a ON f.Appointment_ID = a.Appointment_ID
           JOIN Doctors d ON a.Doctor_ID = d.Doctor_ID
           JOIN Users u_d ON d.User_ID = u_d.User_ID
           ORDER BY f.Created_at DESC LIMIT 20";

$res_fb = $conn->query($sql_fb);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Feedback | MediConnect</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Bổ sung một vài style đặc thù cho phần hiển thị nội dung feedback */
        .rating-stars { color: #f59e0b; margin-bottom: 5px; font-size: 14px; }
        .comment-text { 
            font-style: italic; 
            color: #64748b; 
            display: block; 
            margin-top: 5px;
            font-size: 13px;
            line-height: 1.4;
        }
        .status-msg {
            background: #dcfce7;
            color: #166534;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header-flex">
        <h2>MediConnect Admin</h2>
        <a href="admin_dashboard.php" class="back-dashboard">⬅️ Back to Dashboard</a>
    </div>

    <?php if ($message_status): ?>
        <div class="status-msg"><?= $message_status ?></div>
    <?php endif; ?>

    <div class="search-container">
        <h3>⭐ Patient Feedback Management</h3>
        <p style="color: var(--text-muted); font-size: 14px;">Review and moderate patient evaluations.</p>
    </div>

    <div class="table-box">
        <table>
            <thead>
                <tr>
                    <th>Patient Name</th>
                    <th>Doctor Name</th>
                    <th>Rating & Review</th>
                    <th>Date</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($res_fb && $res_fb->num_rows > 0): ?>
                    <?php while($row = $res_fb->fetch_assoc()): ?>
                        <tr>
                            <td style="font-weight: 600; color: var(--primary);">
                                <?= htmlspecialchars($row['PatientName']) ?>
                            </td>
                            <td>
                                <span style="font-weight: 600;">Dr. <?= htmlspecialchars($row['DoctorName']) ?></span>
                            </td>
                            <td>
                                <div class="rating-stars">
                                    <?php 
                                    for($i=1; $i<=5; $i++) {
                                        echo ($i <= $row['Rating']) ? '⭐' : '☆';
                                    }
                                    ?>
                                    <small>(<?= $row['Rating'] ?>/5)</small>
                                </div>
                                <span class="comment-text">"<?= htmlspecialchars($row['Message']) ?>"</span>
                            </td>
                            <td style="font-size: 12px; color: var(--text-muted);">
                                <?= date('M d, Y', strtotime($row['Created_at'])) ?>
                            </td>
                            <td style="text-align: center;">
                                <form method="post" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this feedback?')">
                                    <input type="hidden" name="feedback_id" value="<?= $row['Feedback_ID'] ?>">
                                    <button type="submit" name="delete_feedback" class="btn-icon delete" title="Delete">🗑️ Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 30px; color: var(--text-muted);">
                            No feedback data found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>