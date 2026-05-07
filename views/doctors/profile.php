<?php
session_start();
require_once '../../config/connectDB.php';

// 1. Check Login
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Doctor') {
    header("Location: ../authentication.php");
    exit();
}

$db = new ConnectDB();
$conn = $db->connection();
$user_id = $_SESSION['user_id'];
$message = "";

// --- 2. HANDLE PROFILE & AVATAR UPDATE ---
if (isset($_POST['update_profile'])) {
    $fullName = $_POST['fullName'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $dob = $_POST['dob'];
    $selected_specs = $_POST['specs'] ?? [];

    $conn->begin_transaction();
    try {
        // --- Handle Avatar ---
        $avatarFileName = null;
        if (!empty($_FILES['avatar']['name']) && $_FILES['avatar']['error'] === 0) {
            $targetDir = "../../public/uploads/avatars/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            $avatarFileName = "doctor_{$user_id}_" . time() . ".$ext";
            $targetFile = $targetDir . $avatarFileName;

            $allowed = ['jpg','jpeg','png','gif'];
            if (!in_array($ext, $allowed)) throw new Exception("Invalid file type");

            if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $targetFile)) {
                throw new Exception("Failed to upload avatar");
            }
        }

        // --- Update Users ---
        $sql = "UPDATE Users SET FullName = ?, Phone = ?, Address = ?, Date_Of_Birth = ?"
             . ($avatarFileName ? ", ProfilePicture = ?" : "")
             . " WHERE User_ID = ?";
        $stmt = $conn->prepare($sql);
        if ($avatarFileName) {
            $stmt->bind_param("sssssi", $fullName, $phone, $address, $dob, $avatarFileName, $user_id);
        } else {
            $stmt->bind_param("ssssi", $fullName, $phone, $address, $dob, $user_id);
        }
        $stmt->execute();

        // --- Update Doctors ---
        $stmt_d = $conn->prepare("UPDATE Doctors SET ContactNumber = ? WHERE User_ID = ?");
        $stmt_d->bind_param("si", $phone, $user_id);
        $stmt_d->execute();

        // --- Get Doctor_ID ---
        $stmt_doc = $conn->prepare("SELECT Doctor_ID FROM Doctors WHERE User_ID = ?");
        $stmt_doc->bind_param("i", $user_id);
        $stmt_doc->execute();
        $doctor_id = $stmt_doc->get_result()->fetch_assoc()['Doctor_ID'];

        // --- Update Specializations ---
        $stmt_del = $conn->prepare("DELETE FROM Doctor_Specializations WHERE Doctor_ID = ?");
        $stmt_del->bind_param("i", $doctor_id);
        $stmt_del->execute();

        if (!empty($selected_specs)) {
            $stmt_ins = $conn->prepare("INSERT INTO Doctor_Specializations (Doctor_ID, Specialization_ID) VALUES (?, ?)");
            foreach ($selected_specs as $spec_id) {
                $stmt_ins->bind_param("ii", $doctor_id, $spec_id);
                $stmt_ins->execute();
            }
        }

        $conn->commit();
        $message = "<div class='alert success'><i class='fas fa-check-circle'></i> Profile updated successfully!</div>";
    } catch (Exception $e) {
        $conn->rollback();
        $message = "<div class='alert error'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// --- 3. DATA QUERY ---
$sql_info = "SELECT u.*, d.Doctor_ID, 
            (SELECT GROUP_CONCAT(Specialization_ID) FROM Doctor_Specializations WHERE Doctor_ID = d.Doctor_ID) as current_specs
            FROM Users u 
            JOIN Doctors d ON u.User_ID = d.User_ID 
            WHERE u.User_ID = ?";
$stmt_info = $conn->prepare($sql_info);
$stmt_info->bind_param("i", $user_id);
$stmt_info->execute();
$doctor = $stmt_info->get_result()->fetch_assoc();

$my_specs = explode(',', $doctor['current_specs'] ?? '');
$all_specs = $conn->query("SELECT * FROM Specialization ORDER BY Name ASC");
$count_noti = $conn->query("SELECT COUNT(*) as total FROM User_Notifications WHERE User_ID = $user_id AND IsRead = FALSE")->fetch_assoc()['total'];

// Avatar path
$current_avatar = !empty($doctor['ProfilePicture']) 
                  ? "../../public/uploads/avatars/" . $doctor['ProfilePicture'] 
                  : "https://ui-avatars.com/api/?name=" . urlencode($doctor['FullName']) . "&background=3498db&color=fff";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor Profile | MediConnect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #3498db; --success: #27ae60; --danger: #e74c3c; --dark: #2c3e50; --bg: #f4f7f6; --text: #1e293b; --white: #ffffff; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); margin: 0; display: flex; color: var(--text); }

        .sidebar { width: 260px; background: var(--dark); height: 100vh; color: white; padding: 20px; position: fixed; left: 0; top: 0; box-sizing: border-box; }
        .sidebar h2 { text-align: center; color: var(--primary); margin-bottom: 30px; }
        .nav-item { padding: 15px; display: block; color: #bdc3c7; text-decoration: none; border-radius: 8px; margin-bottom: 5px; transition: 0.3s; }
        .nav-item:hover { background: #34495e; color: white; }
        .nav-item.active { background: var(--primary); color: white; }
        .nav-item i { margin-right: 10px; width: 20px; }

        .main-content { margin-left: 260px; flex: 1; padding: 30px; box-sizing: border-box; width: calc(100% - 260px); }
        .card { background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; }
        
        /* Avatar Upload Style */
        .card-header { background: linear-gradient(135deg, var(--primary), #2980b9); color: white; padding: 30px; display: flex; align-items: center; gap: 20px; }
        .avatar-wrapper { position: relative; width: 100px; height: 100px; }
        .avatar-img { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid rgba(255,255,255,0.5); background: #eee; }
        .upload-label { position: absolute; bottom: 0; right: 0; background: var(--white); color: var(--primary); width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.2); transition: 0.3s; }
        .upload-label:hover { background: #f0f0f0; transform: scale(1.1); }
        
        .form-body { padding: 30px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .group { margin-bottom: 20px; }
        .full { grid-column: span 2; }

        label { display: block; font-weight: 600; margin-bottom: 8px; color: #64748b; font-size: 0.9rem; }
        input { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 14px; transition: 0.3s; }
        input:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1); }
        input[readonly] { background: #f8fafc; color: #94a3b8; cursor: not-allowed; }

        .specs-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; background: #f8fafc; padding: 20px; border-radius: 10px; border: 1px solid #e2e8f0; }
        .spec-item { display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 14px; }
        .btn-save { background: var(--primary); color: white; border: none; padding: 15px 30px; border-radius: 8px; font-weight: 700; cursor: pointer; transition: 0.3s; font-size: 16px; display: inline-flex; align-items: center; gap: 10px; }
        .btn-save:hover { background: #2980b9; transform: translateY(-2px); }

        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
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
            <a href="doctor_feedback.php" class="nav-item"><i class="fas fa-star"></i> Feedback</a>
            <a href="profile.php" class="nav-item active"><i class="fas fa-user-circle"></i> Profile</a>
        </div>
        
        <div class="logout-section">
            <a href="../logout.php" class="nav-item" style="color: var(--danger); margin-bottom: 0;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="page-header">
            <h2>Account Settings</h2>
            <p style="color: #64748b;">Manage your personal information and profile picture</p>
        </div>

        <div class="card">
            <form method="POST" enctype="multipart/form-data">
                <div class="card-header">
                    <div class="avatar-wrapper">
                        <img src="<?= $current_avatar ?>" alt="Avatar" class="avatar-img" id="avatarPreview">
                        <label for="avatarInput" class="upload-label" title="Change profile picture">
                            <i class="fas fa-camera"></i>
                        </label>
                        <input type="file" name="avatar" id="avatarInput" hidden accept="image/*">
                    </div>
                    <div>
                        <h3 style="margin:0; font-size: 1.5rem;"><?= htmlspecialchars($doctor['FullName']) ?></h3>
                        <p style="margin: 5px 0 0 0; opacity: 0.9;">System Doctor • ID: DR-<?= $doctor['Doctor_ID'] ?></p>
                    </div>
                </div>

                <div class="form-body">
                    <?= $message ?>

                    <div class="grid">
                        <div class="group">
                            <label>Username</label>
                            <input type="text" value="<?= $doctor['UserName'] ?>" readonly>
                        </div>
                        <div class="group">
                            <label>System Email</label>
                            <input type="text" value="<?= $doctor['Email'] ?>" readonly>
                        </div>
                        
                        <div class="group">
                            <label>Full Name Displayed</label>
                            <input type="text" name="fullName" value="<?= htmlspecialchars($doctor['FullName']) ?>" required>
                        </div>
                        <div class="group">
                            <label>Contact Phone Number</label>
                            <input type="text" name="phone" value="<?= htmlspecialchars($doctor['Phone']) ?>" required>
                        </div>

                        <div class="group">
                            <label>Date of Birth</label>
                            <input type="date" name="dob" value="<?= $doctor['Date_Of_Birth'] ?>">
                        </div>
                        <div class="group">
                            <label>Work Address</label>
                            <input type="text" name="address" value="<?= htmlspecialchars($doctor['Address']) ?>">
                        </div>

                        <div class="group full">
                            <label><i class="fas fa-stethoscope"></i> Medical Specializations</label>
                            <div class="specs-container">
                                <?php 
                                $all_specs->data_seek(0); // Reset pointer
                                while($s = $all_specs->fetch_assoc()): 
                                ?>
                                    <label class="spec-item">
                                        <input type="checkbox" name="specs[]" value="<?= $s['Specialization_ID'] ?>" 
                                            <?= in_array($s['Specialization_ID'], $my_specs) ? 'checked' : '' ?>>
                                        <?= htmlspecialchars($s['Name']) ?>
                                    </label>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>

                    <div style="text-align: right; margin-top: 20px;">
                        <button type="submit" name="update_profile" class="btn-save">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Preview image immediately when choosing a file
        document.getElementById('avatarInput').onchange = function (evt) {
            const [file] = this.files;
            if (file) {
                document.getElementById('avatarPreview').src = URL.createObjectURL(file);
            }
        }
    </script>
</body>
</html>