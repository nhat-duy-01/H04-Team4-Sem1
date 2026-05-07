<?php
require_once '../models/admin/Users.php';
session_start();
$users = new Users();
$msg = "";
$error = "";

if (isset($_POST['btnReset'])) {
    $user = trim($_POST['username']);
    $email = trim($_POST['email']);
    $new_pass = $_POST['new_password'];

    $check = $users->checkResetPassword($user, $email);

    if ($check) {
        $users->updatePassword($user, $new_pass);
        $msg = "Password updated successfully! <a href='authentication.php' style='color: #1877f2; font-weight: bold;'>Log in now</a>";
    } else {
        $error = "The information provided does not match our records!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Medical Portal</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Helvetica, Arial, sans-serif; background-color: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .box { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        
        h2 { color: #1c1e21; font-size: 24px; margin-bottom: 10px; text-align: center; }
        p.subtitle { color: #606770; font-size: 15px; text-align: center; margin-bottom: 20px; line-height: 1.4; }
        
        input { width: 100%; padding: 14px; margin-bottom: 12px; border: 1px solid #dddfe2; border-radius: 6px; font-size: 16px; }
        input:focus { border-color: #1877f2; outline: none; box-shadow: 0 0 0 2px #e7f3ff; }
        
        .btn { width: 100%; padding: 12px; background: #1877f2; color: white; border: none; cursor: pointer; border-radius: 6px; font-size: 18px; font-weight: bold; transition: 0.2s; }
        .btn:hover { background: #166fe5; }
        
        .success { background: #e7f3ff; color: #1877f2; padding: 12px; border-radius: 6px; font-size: 14px; margin-bottom: 15px; border: 1px solid #1877f2; }
        .error { background: #ffebe8; color: #f02849; padding: 12px; border-radius: 6px; font-size: 14px; margin-bottom: 15px; border: 1px solid #f02849; }
        
        .footer-link { text-align: center; margin-top: 20px; border-top: 1px solid #dddfe2; padding-top: 15px; }
        .footer-link a { color: #1877f2; text-decoration: none; font-size: 14px; font-weight: 500; }
        .footer-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Find Your Account</h2>
        <p class="subtitle">Please enter your username and registered email to reset your password.</p>
        
        <?php if($msg): ?>
            <div class="success"><?php echo $msg; ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form action="" method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Registered Email Address" required>
            <input type="password" name="new_password" placeholder="New Password" required>
            <button type="submit" name="btnReset" class="btn">Reset Password</button>
        </form>
        
        <div class="footer-link">
            <a href="authentication.php">Back to Login</a>
        </div>
    </div>
</body>
</html>