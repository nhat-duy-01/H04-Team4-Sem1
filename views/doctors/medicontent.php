<?php
session_start();
require_once '../../config/connectDB.php';

// 1. Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Doctor') {
    header("Location: ../authentication.php");
    exit();
}

$conn = (new ConnectDB())->connection();
$doctor_user_id = $_SESSION['user_id'];
$message = "";

// 2. Get Doctor_ID from User_ID
$stmt_d = $conn->prepare("SELECT Doctor_ID FROM Doctors WHERE User_ID = ?");
$stmt_d->bind_param("i", $doctor_user_id);
$stmt_d->execute();
$doctor_id = $stmt_d->get_result()->fetch_assoc()['Doctor_ID'];

// 3. Handle Add/Update/Delete Logic
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['btnAdd'])) {
        $title = $_POST['title'];
        $category = $_POST['category'];
        $body = $_POST['body'];
        $publish_date = date('Y-m-d H:i:s');

        $sql = "INSERT INTO medicalcontent (Title, Category, Body, PublishedDate, Doctor_ID) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $title, $category, $body, $publish_date, $doctor_id);
        if ($stmt->execute()) $message = "<div class='alert success'>New article published successfully!</div>";
    }

    if (isset($_POST['btnUpdate'])) {
        $id = $_POST['content_id'];
        $title = $_POST['title'];
        $category = $_POST['category'];
        $body = $_POST['body'];

        $sql = "UPDATE medicalcontent SET Title = ?, Category = ?, Body = ? WHERE Content_ID = ? AND Doctor_ID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssii", $title, $category, $body, $id, $doctor_id);
        if ($stmt->execute()) $message = "<div class='alert success'>Article updated successfully!</div>";
    }

    if (isset($_POST['btnDelete'])) {
        $id = $_POST['content_id'];
        $sql = "DELETE FROM medicalcontent WHERE Content_ID = ? AND Doctor_ID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $id, $doctor_id);
        if ($stmt->execute()) $message = "<div class='alert success'>Article has been deleted!</div>";
    }
}

// 4. Fetch data for editing
$edit_data = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_id = (int)$_GET['id'];
    $edit_data = $conn->query("SELECT * FROM medicalcontent WHERE Content_ID = $edit_id AND Doctor_ID = $doctor_id")->fetch_assoc();
}

// 5. Fetch article list
$list = $conn->query("SELECT * FROM medicalcontent WHERE Doctor_ID = $doctor_id ORDER BY PublishedDate DESC");

// 6. Count unread notifications
$count_noti = $conn->query("SELECT COUNT(*) as total FROM User_Notifications WHERE User_ID = $doctor_user_id AND IsRead = FALSE")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Medical Articles | MediConnect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #3498db; --success: #27ae60; --warning: #f1c40f; --danger: #e74c3c; --dark: #2c3e50; --bg: #f4f7f6; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); margin: 0; display: flex; }

        /* Sidebar Style */
        .sidebar { width: 260px; background: var(--dark); height: 100vh; color: white; padding: 20px; position: fixed; left: 0; top: 0; box-sizing: border-box; }
        .sidebar h2 { text-align: center; color: var(--primary); margin-bottom: 30px; }
        .nav-item { padding: 15px; display: block; color: #bdc3c7; text-decoration: none; border-radius: 8px; margin-bottom: 5px; transition: 0.3s; }
        .nav-item:hover { background: #34495e; color: white; }
        .nav-item.active { background: var(--primary); color: white; }
        .nav-item i { margin-right: 10px; width: 20px; }

        /* Main Content Style */
        .main-content { margin-left: 260px; flex: 1; padding: 30px; box-sizing: border-box; width: calc(100% - 260px); }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        
        .card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 20px; }
        
        /* Table Style */
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; background: #f8f9fa; color: #7f8c8d; border-bottom: 2px solid #eee; }
        td { padding: 15px; border-bottom: 1px solid #eee; vertical-align: middle; }

        /* Badge Style */
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; background: #e1f5fe; color: #03a9f4; }

        /* Form Controls Style */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .input-group { margin-bottom: 15px; }
        .input-group label { display: block; font-weight: 600; margin-bottom: 5px; color: #444; }
        .input-group input, .input-group select, .input-group textarea { 
            width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; font-family: inherit;
        }
        .full-width { grid-column: 1 / -1; }

        /* Button Style */
        .btn-action { padding: 8px 12px; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: 600; transition: 0.2s; border: none; cursor: pointer; }
        .btn-confirm { background: var(--success); color: white; margin-right: 5px; }
        .btn-cancel { background: #f1f1f1; color: var(--danger); }
        .btn-submit { background: var(--primary); color: white; padding: 10px 20px; font-size: 1rem; }
        
        .alert { padding: 15px; background: #d4edda; color: #155724; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb; }
        .btn-back { text-decoration: none; color: var(--primary); font-weight: 600; display: flex; align-items: center; gap: 8px; }
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
            <a href="medicontent.php" class="nav-item active"><i class="fas fa-feather-alt"></i> Medical Articles</a>
            <a href="doctor_feedback.php" class="nav-item"><i class="fas fa-star"></i> Feedback</a>
            <a href="profile.php" class="nav-item"><i class="fas fa-user-circle"></i> Profile</a>
        </div>
        
        <div class="logout-section">
            <a href="../logout.php" class="nav-item" style="color: var(--danger); margin-bottom: 0;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="page-header">
            <h2><i class="fas fa-feather-alt" style="color: var(--primary);"></i> Medical Content Management</h2>
            <a href="doctor_dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <?= $message ?>

        <div class="card">
            <h3 style="margin-top: 0;"><?= $edit_data ? '✏️ Edit Article' : '➕ Publish New Article' ?></h3>
            <form method="POST" class="form-grid">
                <?php if($edit_data): ?>
                    <input type="hidden" name="content_id" value="<?= $edit_data['Content_ID'] ?>">
                <?php endif; ?>

                <div class="input-group">
                    <label>Article Title</label>
                    <input type="text" name="title" value="<?= $edit_data ? htmlspecialchars($edit_data['Title']) : '' ?>" required placeholder="e.g., Guide to Newborn Care...">
                </div>

                <div class="input-group">
                    <label>Category</label>
                    <select name="category" required>
                        <option value="General Health" <?= ($edit_data && $edit_data['Category'] == 'General Health') ? 'selected' : '' ?>>General Health</option>
                        <option value="Nutrition" <?= ($edit_data && $edit_data['Category'] == 'Nutrition') ? 'selected' : '' ?>>Nutrition</option>
                        <option value="Modern Medicine" <?= ($edit_data && $edit_data['Category'] == 'Modern Medicine') ? 'selected' : '' ?>>Modern Medicine</option>
                    </select>
                </div>

                <div class="input-group full-width">
                    <label>Article Body</label>
                    <textarea name="body" rows="6" required placeholder="Write your article content here..."><?= $edit_data ? htmlspecialchars($edit_data['Body']) : '' ?></textarea>
                </div>

                <div class="full-width">
                    <button type="submit" name="<?= $edit_data ? 'btnUpdate' : 'btnAdd' ?>" class="btn-action btn-submit">
                        <?= $edit_data ? 'Update Article' : 'Publish Now' ?>
                    </button>
                    <?php if($edit_data): ?>
                        <a href="medicontent.php" class="btn-action btn-cancel" style="text-decoration: none; padding-top: 11px;">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Article</th>
                        <th>Category</th>
                        <th>Published Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($list->num_rows > 0): ?>
                        <?php while($row = $list->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($row['Title']) ?></strong><br>
                                <small style="color: #95a5a6;">ID: #<?= $row['Content_ID'] ?></small>
                            </td>
                            <td><span class="badge"><?= htmlspecialchars($row['Category']) ?></span></td>
                            <td>
                                <i class="far fa-calendar"></i> <?= date('M d, Y', strtotime($row['PublishedDate'])) ?><br>
                                <small style="color: #7f8c8d;"><i class="far fa-clock"></i> <?= date('H:i', strtotime($row['PublishedDate'])) ?></small>
                            </td>
                            <td>
                                <a href="?action=edit&id=<?= $row['Content_ID'] ?>" class="btn-action btn-confirm">Edit</a>
                                
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this article?')">
                                    <input type="hidden" name="content_id" value="<?= $row['Content_ID'] ?>">
                                    <button type="submit" name="btnDelete" class="btn-action btn-cancel">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 40px; color: #95a5a6;">
                                <i class="fas fa-feather fa-3x"></i><br><br>
                                No articles found. Start sharing your medical knowledge!
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>