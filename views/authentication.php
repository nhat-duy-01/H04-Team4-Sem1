<?php
require_once '../models/admin/Users.php';
session_start();
$users = new Users();
$error_msg = "";

if (isset($_POST['btnLogin'])) {
    $userName = trim($_POST['username']);
    $password = $_POST['password'];

    $userFound = $users->checkLogin($userName, $password);

    if ($userFound) {
        $_SESSION['user_id']   = $userFound['User_ID'];
        $_SESSION['user_type'] = $userFound['UserType'];
        $_SESSION['user_name'] = $userFound['UserName'];
        $_SESSION['full_name'] = $userFound['FullName'];
 if ($userFound['UserType'] == 'Admin') {
            header('location: admin/admin_dashboard.php');
 }
        elseif ($userFound['UserType'] == 'Doctor') {
            header('location: doctors/doctor_dashboard.php');
        } else {
            header('location: patient/patient_dashboard.php');
        }
        exit();
    } else {
        $error_msg = "Invalid username or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Medical Portal</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .container { background: #fff; width: 100%; max-width: 380px; padding: 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #1c1e21; margin-bottom: 20px; font-size: 24px; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 14px; text-align: center; border: 1px solid #f5c6cb; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 14px; color: #34495e; }
        input { width: 100%; padding: 14px; border: 1px solid #dddfe2; border-radius: 6px; font-size: 16px; }
        input:focus { border-color: #1877f2; outline: none; box-shadow: 0 0 0 2px #e7f3ff; }
        
        .btn { width: 100%; padding: 12px; background: #1877f2; color: white; border: none; border-radius: 6px; font-size: 18px; font-weight: bold; cursor: pointer; transition: 0.2s; margin-bottom: 15px; }
        .btn:hover { background: #166fe5; }
        
        /* NEW STYLING FOR FORGOT PASSWORD */
        .forgot-pw-container { text-align: center; margin-bottom: 20px; }
        .forgot-pw-container a { color: #1877f2; text-decoration: none; font-size: 14px; font-weight: 500; }
        .forgot-pw-container a:hover { text-decoration: underline; }

        .divider { border-bottom: 1px solid #dddfe2; margin-bottom: 20px; }

        .footer-link { text-align: center; font-size: 14px; }
        .footer-link a { 
            display: inline-block;
            background: #42b72a; 
            color: white; 
            text-decoration: none; 
            padding: 10px 16px; 
            border-radius: 6px; 
            font-weight: bold; 
            margin-top: 5px;
            transition: 0.2s;
        }
        .footer-link a:hover { background: #36a420; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        
        <?php if($error_msg != ""): ?>
            <div class="error"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Enter username" required 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter password" required>
            </div>

            <button type="submit" name="btnLogin" class="btn">Log In</button>
            
            <div class="forgot-pw-container">
                <a href="forgot_password.php">Forgotten password?</a>
            </div>
        </form>

        <div class="divider"></div>

        <div class="footer-link">
            <a href="sign_in.php">Create new account</a>
        </div>
    </div>
</body>
</html>