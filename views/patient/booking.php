<?php
session_start();
require_once '../../config/connectDB.php';

$db = new ConnectDB();
$conn = $db->connection();

// 1. Get Doctor ID from URL
$doctor_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($doctor_id <= 0) {
    die("<div style='text-align:center; padding:50px;'><h2>Doctor not found.</h2><a href='../patient_dashboard.php'>Back to Dashboard</a></div>");
}

$message_status = "";

// --- 2. HANDLE FEEDBACK ACTIONS ---

// 2.1 THÊM MỚI FEEDBACK
if (isset($_POST['submit_feedback'])) {
    if (!isset($_SESSION['user_id'])) {
        $message_status = "<div class='alert error'><i class='fas fa-exclamation-circle'></i> Please log in to submit a review!</div>";
    } else {
        $rating = (int)$_POST['rating'];
        $message_text = trim($_POST['message_text']);
        $u_id = $_SESSION['user_id'];

        $stmt_p = $conn->prepare("SELECT Patient_ID FROM Patients WHERE User_ID = ?");
        $stmt_p->bind_param("i", $u_id);
        $stmt_p->execute();
        $patient = $stmt_p->get_result()->fetch_assoc();
        
        if ($patient && !empty($message_text)) {
            $p_id = $patient['Patient_ID'];
            $stmt_check = $conn->prepare("SELECT Appointment_ID FROM Appointments WHERE Patient_ID = ? AND Doctor_ID = ? LIMIT 1");
            $stmt_check->bind_param("ii", $p_id, $doctor_id);
            $stmt_check->execute();
            $appt = $stmt_check->get_result()->fetch_assoc();

            if ($appt) {
                $a_id = $appt['Appointment_ID'];
                $sql_ins = "INSERT INTO feedback (Appointment_ID, Patient_ID, Rating, Message, Created_at) VALUES (?, ?, ?, ?, NOW())";
                $stmt_ins = $conn->prepare($sql_ins);
                $stmt_ins->bind_param("iiis", $a_id, $p_id, $rating, $message_text);
                
                if ($stmt_ins->execute()) {
                    $message_status = "<div class='alert success'><i class='fas fa-check-circle'></i> Review submitted successfully!</div>";
                }
            } else {
                $message_status = "<div class='alert error'><i class='fas fa-info-circle'></i> You must have an appointment to leave a review.</div>";
            }
        }
    }
}

// 2.2 XÓA FEEDBACK (Chỉ xóa được của bản thân)
if (isset($_POST['delete_feedback'])) {
    $f_id = (int)$_POST['feedback_id'];
    $u_id = $_SESSION['user_id'];
    
    $sql_del = "DELETE f FROM feedback f JOIN Patients p ON f.Patient_ID = p.Patient_ID WHERE f.Feedback_ID = ? AND p.User_ID = ?";
    $stmt_del = $conn->prepare($sql_del);
    $stmt_del->bind_param("ii", $f_id, $u_id);
    if ($stmt_del->execute()) {
        $message_status = "<div class='alert success'>Review deleted successfully!</div>";
    }
}

// 2.3 CẬP NHẬT FEEDBACK
if (isset($_POST['update_feedback'])) {
    $f_id = (int)$_POST['feedback_id'];
    $new_rating = (int)$_POST['rating'];
    $new_message = trim($_POST['message_text']);
    $u_id = $_SESSION['user_id'];

    $sql_upd = "UPDATE feedback f JOIN Patients p ON f.Patient_ID = p.Patient_ID SET f.Rating = ?, f.Message = ? WHERE f.Feedback_ID = ? AND p.User_ID = ?";
    $stmt_upd = $conn->prepare($sql_upd);
    $stmt_upd->bind_param("isii", $new_rating, $new_message, $f_id, $u_id);
    if ($stmt_upd->execute()) {
        $message_status = "<div class='alert success'>Review updated successfully!</div>";
    }
}

// --- 3. QUERY DOCTOR INFORMATION ---
$sql_doc = "SELECT u.*, d.ContactNumber, c.Name as CityName FROM Doctors d JOIN Users u ON d.User_ID = u.User_ID LEFT JOIN Cities c ON u.City_ID = c.City_ID WHERE d.Doctor_ID = ?";
$stmt_doc = $conn->prepare($sql_doc);
$stmt_doc->bind_param("i", $doctor_id);
$stmt_doc->execute();
$doctor = $stmt_doc->get_result()->fetch_assoc();

if (!$doctor) die("Doctor does not exist.");

// 4. Get Specializations
$sql_specs = "SELECT s.Name FROM Doctor_Specializations ds JOIN Specialization s ON ds.Specialization_ID = s.Specialization_ID WHERE ds.Doctor_ID = ?";
$stmt_spec = $conn->prepare($sql_specs);
$stmt_spec->bind_param("i", $doctor_id);
$stmt_spec->execute();
$specs_res = $stmt_spec->get_result();
$specs = [];
while($s = $specs_res->fetch_assoc()) { $specs[] = $s['Name']; }

// 5. Get Schedules
$schedules = $conn->query("SELECT * FROM DoctorSchedules WHERE Doctor_ID = $doctor_id AND Status = 'Available' AND AvailableDate >= CURDATE() ORDER BY AvailableDate ASC");

// 6. Get Feedback (Thêm u.User_ID để kiểm tra quyền)
$reviews = $conn->query("SELECT f.*, u.FullName, u.ProfilePicture, u.User_ID FROM feedback f 
                         JOIN Patients p ON f.Patient_ID = p.Patient_ID 
                         JOIN Users u ON p.User_ID = u.User_ID 
                         JOIN Appointments a ON (f.Appointment_ID = a.Appointment_ID OR f.Appointment_ID = 0)
                         WHERE a.Doctor_ID = $doctor_id OR f.Appointment_ID = 0
                         GROUP BY f.Feedback_ID ORDER BY f.Created_at DESC");

$stats = $conn->query("SELECT AVG(Rating) as avg_r, COUNT(*) as total FROM feedback f 
                       JOIN Appointments a ON (f.Appointment_ID = a.Appointment_ID OR f.Appointment_ID = 0)
                       WHERE a.Doctor_ID = $doctor_id OR f.Appointment_ID = 0")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dr. <?= htmlspecialchars($doctor['FullName']) ?> | Booking</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #3498db; --dark: #1e293b; --success: #27ae60; --bg: #f1f5f9; --warning: #f59f00; --danger: #e74c3c; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); margin: 0; padding: 20px; color: #334155; }
        .container { max-width: 1100px; margin: auto; }
        .top-nav { display: flex; gap: 12px; margin-bottom: 20px; align-items: center; }
        .btn-nav { text-decoration: none; padding: 10px 18px; border-radius: 8px; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; }
        .btn-dash { background: var(--dark); color: white; border: none; cursor: pointer; }
        .btn-back { background: #fff; color: #64748b; border: 1px solid #e2e8f0; }
        .main-layout { display: grid; grid-template-columns: 350px 1fr; gap: 25px; }
        .card { background: white; padding: 25px; border-radius: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .section-title { border-left: 4px solid var(--primary); padding-left: 15px; margin-bottom: 20px; font-weight: 700; }
        .profile-card { text-align: center; }
        .profile-img { width: 140px; height: 140px; border-radius: 50%; object-fit: cover; border: 4px solid #fff; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .badge { background: #e0f2fe; color: #0369a1; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; margin: 3px; display: inline-block; }
        .info-list { text-align: left; margin-top: 20px; font-size: 14px; border-top: 1px solid #f1f5f9; padding-top: 15px; }
        .grid-time { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .slot { border: 1px solid #e2e8f0; padding: 12px; border-radius: 12px; text-decoration: none; color: #1e293b; text-align: center; transition: 0.2s; background: #fff; }
        .slot:hover:not(.disabled) { background: var(--primary); color: white; transform: translateY(-3px); }
        .slot.disabled { cursor: not-allowed; opacity: 0.6; background: #f8fafc; color: #94a3b8; }
        .stars-input { font-size: 26px; color: #e2e8f0; cursor: pointer; display: flex; gap: 8px; justify-content: center; margin: 15px 0; }
        .stars-input i.active { color: #f1c40f; }
        textarea { width: 100%; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; box-sizing: border-box; font-family: inherit; }
        .btn-submit { background: var(--success); color: white; border: none; padding: 12px; width: 100%; border-radius: 10px; font-weight: 700; cursor: pointer; margin-top: 10px; }
        .login-msg { background: #fff9db; border: 1px dashed var(--warning); padding: 15px; border-radius: 12px; text-align: center; margin-bottom: 20px; }
        .alert { padding: 12px; border-radius: 10px; margin-bottom: 15px; font-size: 14px; }
        .success { background: #dcfce7; color: #166534; }
        .error { background: #fee2e2; color: #991b1b; }
        
        /* New styles for edit/delete */
        .review-header { display: flex; justify-content: space-between; align-items: center; }
        .review-actions button { background: none; border: none; cursor: pointer; font-size: 14px; margin-left: 10px; transition: 0.2s; }
        .btn-edit { color: var(--primary); }
        .btn-delete { color: var(--danger); }
        .edit-mode-form { display: none; background: #f8fafc; padding: 15px; border-radius: 12px; margin-top: 10px; border: 1px solid #e2e8f0; }
    </style>
</head>
<body>

<div class="container">
    <div class="top-nav">
        <a href="javascript:history.back()" class="btn-nav btn-back">
            <i class="fas fa-chevron-left"></i> Back
        </a>
    </div>

    <div class="main-layout">
        <aside>
            <div class="card profile-card">
                <img src="<?= !empty($doctor['ProfilePicture']) ? '../../public/uploads/avatars/'.$doctor['ProfilePicture'] : 'https://ui-avatars.com/api/?name='.urlencode($doctor['FullName']) ?>" class="profile-img">
                <h2 style="margin: 15px 0 8px;">Dr. <?= htmlspecialchars($doctor['FullName']) ?></h2>
                <div>
                    <?php foreach($specs as $s): ?>
                        <span class="badge"><?= htmlspecialchars($s) ?></span>
                    <?php endforeach; ?>
                </div>
                <div class="info-list">
                    <p><i class="fas fa-phone-alt"></i> <?= htmlspecialchars($doctor['ContactNumber']) ?></p>
                    <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($doctor['Email']) ?></p>
                    <p><i class="fas fa-star" style="color:#f1c40f"></i> <b><?= round($stats['avg_r'], 1) ?></b>/5 (<?= $stats['total'] ?> reviews)</p>
                </div>
            </div>

            <div class="card">
                <h4 class="section-title">Leave a Review</h4>
                <?= $message_status ?>
                <form method="POST">
                    <div class="stars-input" id="star-selector">
                        <?php for($i=1; $i<=5; $i++): ?> <i class="fas fa-star active" data-v="<?= $i ?>"></i> <?php endfor; ?>
                    </div>
                    <input type="hidden" name="rating" id="rating_val" value="5">
                    <textarea name="message_text" rows="3" placeholder="Share your check-up experience..." required></textarea>
                    <button type="submit" name="submit_feedback" class="btn-submit">Post Review</button>
                </form>
            </div>
        </aside>

        <main>
            <div class="card">
                <h4 class="section-title">Select Appointment Slot</h4>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <div class="login-msg">
                        <p style="margin: 0 0 10px; color: #d9480f; font-weight: 600;"><i class="fas fa-lock"></i> Please log in to book.</p>
                        <a href="../authentication.php" class="btn-nav btn-dash">Log In Now</a>
                    </div>
                <?php endif; ?>

                <?php if ($schedules->num_rows > 0): ?>
                    <div class="grid-time">
                        <?php while($s = $schedules->fetch_assoc()): ?>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="confirm_booking.php?id=<?= $s['Schedule_ID'] ?>" class="slot">
                                    <strong style="display:block; color:var(--primary);"><?= date('m/d/Y', strtotime($s['AvailableDate'])) ?></strong>
                                    <span><?= substr($s['StartTime'], 0, 5) ?> - <?= substr($s['EndTime'], 0, 5) ?></span>
                                </a>
                            <?php else: ?>
                                <div class="slot disabled">
                                    <strong style="display:block;"><?= date('m/d/Y', strtotime($s['AvailableDate'])) ?></strong>
                                    <span><?= substr($s['StartTime'], 0, 5) ?> - <?= substr($s['EndTime'], 0, 5) ?></span>
                                </div>
                            <?php endif; ?>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p style="text-align:center; color:#94a3b8;">No available slots at the moment.</p>
                <?php endif; ?>
            </div>

            <div class="card">
                <h4 class="section-title">Patient Reviews</h4>
                <?php if ($reviews->num_rows > 0): ?>
                    <?php while($r = $reviews->fetch_assoc()): ?>
                        <div class="review-block" id="rev-<?= $r['Feedback_ID'] ?>" style="border-bottom: 1px solid #f1f5f9; padding: 15px 0;">
                            <div class="review-header">
                                <div>
                                    <strong><?= htmlspecialchars($r['FullName']) ?></strong>
                                    <span style="color:#f1c40f; margin-left:8px;"><?= str_repeat('★', $r['Rating']) ?></span>
                                </div>
                                
                                <?php if (isset($_SESSION['user_id']) && $r['User_ID'] == $_SESSION['user_id']): ?>
                                    <div class="review-actions">
                                        <button class="btn-edit" onclick="toggleEdit(<?= $r['Feedback_ID'] ?>)"><i class="fas fa-edit"></i> Edit</button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this review?')">
                                            <input type="hidden" name="feedback_id" value="<?= $r['Feedback_ID'] ?>">
                                            <button type="submit" name="delete_feedback" class="btn-delete"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <p class="display-text" style="font-size: 14px; margin: 10px 0;"><?= nl2br(htmlspecialchars($r['Message'])) ?></p>

                            <?php if (isset($_SESSION['user_id']) && $r['User_ID'] == $_SESSION['user_id']): ?>
                                <form method="POST" class="edit-mode-form" id="edit-form-<?= $r['Feedback_ID'] ?>">
                                    <input type="hidden" name="feedback_id" value="<?= $r['Feedback_ID'] ?>">
                                    <div class="stars-input edit-stars" data-fid="<?= $r['Feedback_ID'] ?>" style="justify-content:flex-start; font-size:18px;">
                                        <?php for($i=1; $i<=5; $i++): ?> 
                                            <i class="fas fa-star <?= $i <= $r['Rating'] ? 'active' : '' ?>" data-v="<?= $i ?>"></i> 
                                        <?php endfor; ?>
                                    </div>
                                    <input type="hidden" name="rating" id="rating-edit-<?= $r['Feedback_ID'] ?>" value="<?= $r['Rating'] ?>">
                                    <textarea name="message_text" rows="2" required><?= htmlspecialchars($r['Message']) ?></textarea>
                                    <div style="margin-top:10px;">
                                        <button type="submit" name="update_feedback" class="btn-submit" style="width:auto; display:inline-block; padding:8px 20px;">Update</button>
                                        <button type="button" onclick="toggleEdit(<?= $r['Feedback_ID'] ?>)" style="background:#cbd5e1; color:#475569; border:none; padding:8px 20px; border-radius:10px; cursor:pointer;">Cancel</button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align:center; color:#94a3b8;">No reviews yet.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<script>
    // Logic cho star selector (form thêm mới)
    const stars = document.querySelectorAll('#star-selector i');
    const input = document.getElementById('rating_val');
    stars.forEach(s => {
        s.addEventListener('click', () => {
            const v = s.getAttribute('data-v');
            input.value = v;
            stars.forEach(st => st.classList.toggle('active', st.getAttribute('data-v') <= v));
        });
    });

    // Logic toggle form Edit
    function toggleEdit(id) {
        const form = document.getElementById('edit-form-' + id);
        const text = document.querySelector(`#rev-${id} .display-text`);
        form.style.display = (form.style.display === 'none' || form.style.display === '') ? 'block' : 'none';
        text.style.display = (form.style.display === 'block') ? 'none' : 'block';
    }

    // Logic star selector trong form Edit
    document.querySelectorAll('.edit-stars i').forEach(star => {
        star.addEventListener('click', function() {
            const val = this.getAttribute('data-v');
            const container = this.parentElement;
            const fid = container.getAttribute('data-fid');
            
            container.querySelectorAll('i').forEach(s => {
                s.classList.toggle('active', s.getAttribute('data-v') <= val);
            });
            document.getElementById('rating-edit-' + fid).value = val;
        });
    });
</script>

</body>
</html>