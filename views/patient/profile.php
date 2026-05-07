<?php
session_start();
require_once('../../config/connectDB.php');

// 1. Access Control Check
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Patient') {
    header("Location: ../authentication.php");
    exit();
}

$db = new ConnectDB();
$conn = $db->connection();
$user_id = $_SESSION['user_id'];
$message = "";

// --- FETCH PATIENT_ID BEFORE OTHER TASKS ---
$stmt_p_id = $conn->prepare("SELECT Patient_ID FROM Patients WHERE User_ID = ?");
$stmt_p_id->bind_param("i", $user_id);
$stmt_p_id->execute();
$res_p_id = $stmt_p_id->get_result()->fetch_assoc();
$patient_ID = $res_p_id['Patient_ID'] ?? "N/A";

// 2. UPDATE PROCESSING
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $fullname = $_POST['fullname'];
    $phone = $_POST['phone'];
    $dob = $_POST['dob'];
    $address = $_POST['address'];

   // --- IMAGE UPLOAD HANDLING ---
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
    // Determine the root path accurately
    $target_dir = __DIR__ . "/../../public/uploads/avatars/";
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_ext = strtolower(pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION));
    $allowed_ext = array("jpg", "jpeg", "png", "gif");

    if (in_array($file_ext, $allowed_ext)) {
        $new_filename = $user_id . "_" . time() . "." . $file_ext;
        $target_file = $target_dir . $new_filename;

        // Move the file
        if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
            $new_avatar = $new_filename;
            
            // Update Database
            $sql_update_img = "UPDATE Users SET ProfilePicture = ? WHERE User_ID = ?";
            $stmt_img = $conn->prepare($sql_update_img);
            $stmt_img->bind_param("si", $new_avatar, $user_id);
            $stmt_img->execute();
        } else {
            $message = "<div class='alert error'>Could not save file to folder. Please check Write Permissions.</div>";
        }
    } else {
        $message = "<div class='alert error'>Invalid file format.</div>";
    }
}

    // Update text information
    $sql_u = "UPDATE Users SET FullName = ?, Phone = ?, Date_Of_Birth = ? WHERE User_ID = ?";
    $stmt_u = $conn->prepare($sql_u);
    $stmt_u->bind_param("sssi", $fullname, $phone, $dob, $user_id);
    
    $sql_p = "UPDATE Patients SET Address = ? WHERE User_ID = ?";
    $stmt_p = $conn->prepare($sql_p);
    $stmt_p->bind_param("si", $address, $user_id);

    if ($stmt_u->execute() && $stmt_p->execute()) {
        $message = "<div class='alert success'>Profile updated successfully!</div>";
    }
}

// 3. FETCH DISPLAY DATA (After update)
$sql_info = "SELECT u.*, p.Address as PatientAddress FROM Users u 
             LEFT JOIN Patients p ON u.User_ID = p.User_ID WHERE u.User_ID = ?";
$stmt_info = $conn->prepare($sql_info);
$stmt_info->bind_param("i", $user_id);
$stmt_info->execute();
$user = $stmt_info->get_result()->fetch_assoc();

$avatar_url = !empty($user['ProfilePicture']) 
              ? "../../public/uploads/avatars/" . $user['ProfilePicture'] 
              : "https://ui-avatars.com/api/?name=" . urlencode($user['FullName']) . "&background=007bff&color=fff";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Personal Profile | MediConnect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .profile-wrapper { display: grid; grid-template-columns: 350px 1fr; gap: 30px; margin-top: 30px; }
        .card { background: white; border-radius: 15px; padding: 25px; border: 1px solid #e2e8f0; }
        .avatar-section { text-align: center; }
        .profile-img-big { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid #eef2ff; margin-bottom: 15px; }
        .btn-upload-label { display: inline-block; padding: 8px 20px; background: #f1f5f9; color: #475569; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #334155; font-size: 14px; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; outline: none; }
        .form-group input:focus { border-color: #007bff; }
        .btn-submit { background: #007bff; color: white; border: none; padding: 15px; border-radius: 8px; font-weight: 700; width: 100%; cursor: pointer; margin-top: 10px; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; text-align: center; }
        .success { background: #dcfce7; color: #166534; }
        .error { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>

<nav class="top-nav">
    <div class="container nav-flex">
        <a href="patient_dashboard.php" class="logo">Medi<span>Connect</span></a>
        <a href="patient_dashboard.php" style="text-decoration:none; color:#64748b;"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
</nav>

<div class="container profile-wrapper">
    <aside class="card">
        <div class="avatar-section">
            <img src="<?= $avatar_url ?>" alt="Avatar" class="profile-img-big" id="previewImg">
            <h3><?= htmlspecialchars($user['FullName']) ?></h3>
            <p style="color:#64748b; margin-bottom: 20px;">Patient ID: #<?= $patient_ID ?></p>
        </div>
        <hr style="border:0; border-top:1px solid #f1f5f9; margin: 20px 0;">
        <div style="font-size: 14px; color: #475569;">
            <p style="margin-bottom:10px;"><i class="fas fa-envelope"></i> <?= $user['Email'] ?></p>
            <p><i class="fas fa-calendar-alt"></i> Date Joined: <?= date('M d, Y', strtotime($user['Created_at'])) ?></p>
        </div>
    </aside>

    <section class="card">
        <h2 style="margin-bottom: 20px;">Profile Settings</h2>
        <?= $message ?>

        <form action="" method="POST" enctype="multipart/form-data">
            
            <div class="form-group">
                <label>New Profile Picture</label>
                <label for="avatar" class="btn-upload-label"><i class="fas fa-camera"></i> Choose from Computer</label>
                <input type="file" name="avatar" id="avatar" hidden onchange="showPreview(event)">
            </div>

            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="fullname" value="<?= htmlspecialchars($user['FullName']) ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($user['Phone']) ?>">
                </div>
                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" name="dob" value="<?= $user['Date_Of_Birth'] ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Permanent Address</label>
                <input type="text" name="address" value="<?= htmlspecialchars($user['PatientAddress']) ?>">
            </div>

            <button type="submit" name="update_profile" class="btn-submit">Save All Changes</button>
        </form>
    </section>
</div>

<script>
    // Preview image as soon as file is selected
    function showPreview(event){
        if(event.target.files.length > 0){
            var src = URL.createObjectURL(event.target.files[0]);
            var preview = document.getElementById("previewImg");
            preview.src = src;
        }
    }
</script>

</body>
</html>