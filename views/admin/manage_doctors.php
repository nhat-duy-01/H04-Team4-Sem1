<?php
session_start();
$username_session = $_SESSION['UserName'] ?? 'Admin';

require_once('../../config/connectDB.php');
require_once('../../controller/admin/doctorsController.php');

$db = new ConnectDB();
$conn = $db->connection();

$doctor_edit = handleDoctorsRequest($conn);
$specializations = getAllSpecializations($conn);
$keyword = $_GET['keyword'] ?? '';
$doctors = !empty($keyword) ? searchDoctors($conn, $keyword) : getAllDoctors($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Doctors | MediConnect</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <div class="header-flex">
        <h2>MediConnect Admin</h2>
        <a href="admin_dashboard.php" class="back-dashboard">Back to Dashboard</a>
    </div>

    <div class="card">
        <h3><?= $doctor_edit ? 'Edit Doctor Information' : 'Add New Doctor' ?></h3>
        <form method="post">
            <input type="hidden" name="doctor_id" value="<?= $doctor_edit['Doctor_ID'] ?? '' ?>">
            
            <div class="form-grid">
                <div class="input-group">
                    <label>Username</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($doctor_edit['UserName'] ?? '') ?>" required>
                </div>
                <div class="input-group">
                    <label>Full Name</label>
                    <input type="text" name="fullname" value="<?= htmlspecialchars($doctor_edit['FullName'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-grid">
                <div class="input-group">
                    <label>Doctor Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($doctor_edit['Email'] ?? '') ?>" required>
                </div>
                
                <div class="input-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($doctor_edit['Phone'] ?? '') ?>" required>
                </div>

                <?php if (!$doctor_edit): ?>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <?php endif; ?>
            </div>

            <div class="input-group">
            <label>Specializatio</label>
            <?php foreach ($specializations as $spec): ?>
            <div>
            <input type="checkbox" name="specializations[]"  value="<?= $spec['Specialization_ID'] ?>"  
            <?php
                if (isset($doctor_specs) && in_array($spec['Specialization_ID'], $doctor_specs)) {
                    echo 'checked';
                    }?>><?= htmlspecialchars($spec['Name']) ?>
                </div>
             <?php endforeach; ?>

        </div>
            <div style="margin-top: 10px;">
                <?php if (!$doctor_edit): ?>
                    <button type="submit" name="btnAdd" class="btn-submit"><img src="images/add.jpg" alt="Search" style="width:16px;height:16px"/>Add</button>
                <?php else: ?>
                    <button type="submit" name="btnEdit" class="btn-submit">Update Changes</button>
                    <a href="manage_doctors.php" style="margin-left:15px; color: var(--text-muted); text-decoration: none;">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="search-container">
        <h3>Doctor List</h3>
        <div class="search-area">
            <form method="get">
                <input type="text" name="keyword" placeholder="Search by name or email..." value="<?= htmlspecialchars($keyword) ?>">
                <button type="submit" name="search" class="btn-submit" style="padding: 8px 15px;"><img src="images/search.jpg" alt="Search" style="width:16px;height:16px"/></button>
            </form>
        </div>
    </div>

    <div class="table-box">
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($doctors)): ?>
                    <?php foreach ($doctors as $row): ?>
                        <tr>
                            <td style="font-weight: 600; color: var(--primary);">
                                <?= htmlspecialchars($row['UserName'] ?? '') ?>
                            </td>
                            <td><?= htmlspecialchars($row['FullName'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['Email'] ?? '') ?></td>
                            <td style="font-weight: 600;"><?= htmlspecialchars($row['Phone'] ?? '') ?></td>
                            <td style="text-align: center;">
                                <a href="?action=edit&id=<?= $row['Doctor_ID'] ?>" class="btn-icon edit" title="Edit"><img src="images/edit.jpg" alt="Search" style="width:16px;height:16px"/></a>
                                
                                <form method="post" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this doctor?')">
                                    <input type="hidden" name="doctor_id" value="<?= $row['Doctor_ID'] ?>">
                                    <button type="submit" name="btnDelete" class="btn-icon delete" title="Delete"><img src="images/delete.jpg" alt="Search" style="width:16px;height:16px"/></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 30px; color: var(--text-muted);">
                            No doctor data found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>