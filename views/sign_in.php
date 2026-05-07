<?php
    require_once '../models/admin/Users.php'; 
    session_start();
    $users = new Users();
    $error_msg = "";

    if(isset($_POST['btnRegister'])){
        $userName = trim($_POST['username']);
        $password = $_POST['password']; 
        $fullName = trim($_POST['fullname']);
        $email    = trim($_POST['email']);
        $phone    = trim($_POST['phone']);
        $address  = trim($_POST['address']);
        $userType = $_POST['usertype']; 
        $dob      = $_POST['dob'];

        // --- CONSTRAINTS / VALIDATION ---
        
        if (strlen($userName) < 5) {
            $error_msg = "Username must be at least 5 characters long.";
        } 
        elseif (strlen($password) < 6) {
            $error_msg = "Password must be at least 6 characters long.";
        }
        // Kiểm tra định dạng số điện thoại (Ví dụ: 0912345678)
        elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
            $error_msg = "Invalid phone number. It must be 10 digits.";
        }
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_msg = "Invalid email format.";
        }
        elseif (strtotime($dob) > time()) {
            $error_msg = "Date of birth cannot be in the future.";
        }
        elseif ($users->checkSignIn($userName)) {
            $error_msg = "This username is already taken!";
        } 
        else {
            $result = $users->insertUsers($userName, $password, $fullName, $email, $phone, $address, $userType, $dob);
            
            if($result) {
                $newUser = $users->checkSignIn($userName);
                $_SESSION['user_id'] = $newUser['User_ID'];
                $_SESSION['user_name'] = $newUser['UserName'];
                
                header('location: upload_avatar.php'); 
                exit();
            } else {
                $error_msg = "System error, please try again later!";
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f7f6; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .container { background: #fff; width: 100%; max-width: 400px; padding: 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #2c3e50; margin-bottom: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 14px; text-align: center; border: 1px solid #f5c6cb; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 14px; color: #34495e; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        input:focus { border-color: #3498db; outline: none; }
        .btn { width: 100%; padding: 12px; background: #3498db; color: white; border: none; border-radius: 5px; font-size: 16px; font-weight: bold; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn:hover { background: #2980b9; }
        .footer-link { text-align: center; margin-top: 15px; font-size: 14px; }
        .footer-link a { color: #3498db; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <h2>Sign Up</h2>

    <?php if($error_msg != ""): ?>
        <div class="error"><?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="form-group">
            <label>Register as:</label>
            <select name="usertype" required>
                <option value="Patient">Patient</option>
                <option value="Doctor">Doctor</option>
            </select>
        </div>

        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" placeholder="Min 5 characters" required 
                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Min 6 characters" required>
        </div>

        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="fullname" placeholder="John Doe" required
                   value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>">
        </div>

        <div class="form-group">
            <label>Phone Number</label>
            <input type="tel" name="phone" placeholder="09xxxxxxxx" required
                value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
        </div>

        <div class="form-group">
            <label>Date of Birth</label>
            <input type="date" name="dob" required
                   value="<?php echo isset($_POST['dob']) ? htmlspecialchars($_POST['dob']) : ''; ?>">
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="example@mail.com" required
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>

        <div class="form-group">
            <label>Address</label>
            <input type="text" name="address" placeholder="City, Country" required
                   value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
        </div>

        <button type="submit" name="btnRegister" class="btn">REGISTER NOW</button>
    </form>
    
    <div class="footer-link">
        Already have an account? <a href="authentication.php">Login here</a>
    </div>
</div>

</body>
</html>