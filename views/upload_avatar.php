<?php
require_once '../models/admin/Users.php';
session_start();

// Security: Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('location: authentication.php');
    exit();
}

$users = new Users();
$message = "";

if (isset($_POST['btnUpload'])) {
    $target_dir = "../uploads/";
    if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

    // Create a unique filename to prevent overwriting
    $file_extension = strtolower(pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION));
    $file_name = "user_" . $_SESSION['user_id'] . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $file_name;

    // Validate file type
    $allowed_types = ["jpg", "jpeg", "png", "gif"];
    
    if (in_array($file_extension, $allowed_types)) {
        if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
            $users->updateAvatar($_SESSION['user_id'], $file_name);
            header('location: authentication.php?reg=success');
            exit();
        } else {
            $message = "Sorry, there was an error uploading your file.";
        }
    } else {
        $message = "Invalid file format! Only JPG, JPEG, PNG, and GIF are allowed.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Profile Picture</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f7f6; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 350px; text-align: center; }
        .default-avatar { width: 100px; height: 100px; background: #ddd; border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; font-size: 50px; color: #fff; }
        .error { color: #e74c3c; font-size: 13px; margin-bottom: 15px; }
        input[type="file"] { margin-bottom: 20px; font-size: 14px; }
        .btn-upload { background: #3498db; color: white; padding: 12px; border: none; width: 100%; border-radius: 5px; cursor: pointer; font-weight: bold; transition: 0.3s; }
        .btn-upload:hover { background: #2980b9; }
        .skip-link { display: block; margin-top: 15px; color: #7f8c8d; text-decoration: none; font-size: 14px; }
        .skip-link:hover { color: #3498db; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Add Profile Picture</h2>
        <p style="color: #65676b; font-size: 14px; margin-bottom: 20px;">Help others recognize you.</p>
        
        <div class="default-avatar">👤</div>

        <?php if($message != ""): ?>
            <div class="error"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <input type="file" name="avatar" accept="image/*" required>
            <button type="submit" name="btnUpload" class="btn-upload">USE THIS IMAGE</button>
        </form>

        <a href="authentication.php" class="skip-link">Skip for now (Use default)</a>
    </div>
</body>
</html>