<?php
session_start();
require_once '../../config/connectDB.php';
require_once '../../controller/admin/SpecializationsController.php';

$conn = (new ConnectDB())->connection();
$specialization_edit = handleSpecializationsRequest($conn);
$specializations = getSpecializations($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Specializations | MediConnect</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <div class="header-flex">
        <h2>MediConnect Admin</h2>
        <a href="admin_dashboard.php" class="back-dashboard">Back to Dashboard</a>
    </div>

    <div class="card">
        <?php if ($specialization_edit): ?>
            <h3>Edit Specialization</h3>
            <form method="post">
                <input type="hidden" name="specialization_id" value="<?= $specialization_edit['Specialization_ID'] ?>">
                <div class="input-group">
                    <label>Specialization Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($specialization_edit['Name']) ?>" required>
                </div>
                <div class="input-group">
                    <label>Description</label>
                    <textarea name="description" rows="3"><?= htmlspecialchars($specialization_edit['Description']) ?></textarea>
                </div>
                <button type="submit" name="btnEdit" class="btn-submit">Update</button>
                <a href="manage_specializations.php" style="margin-left:10px; color:var(--text-muted); text-decoration:none;">Cancel</a>
            </form>
        <?php else: ?>
            <h3>Add New Specialization</h3>
            <form method="post">
                <div class="input-group">
                    <label>Specialization Name</label>
                    <input type="text" name="name" placeholder="e.g., Cardiology, Pediatrics" required>
                </div>
                <div class="input-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Enter specialization description..."></textarea>
                </div>
                <button type="submit" name="btnAdd" class="btn-submit">Add New</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="search-container">
        <h3>Current Specializations</h3>
        <div class="search-area">
            <form method="get">
                <input type="text" name="keyword" placeholder="Search..." 
                       value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
                <button type="submit" class="btn-submit" style="padding: 8px 15px;"><img src="images/search.jpg" alt="Search" style="width:16px;height:16px"/></button>
            </form>
        </div>
    </div>

    <div class="table-box">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Specialization Name</th>
                    <th>Description</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($specializations)): ?>
                    <?php foreach ($specializations as $row): ?>
                        <tr>
                            <td style="color: #94a3b8; font-weight: bold;">#<?= $row['Specialization_ID'] ?></td>
                            <td style="font-weight: 600;"><?= htmlspecialchars($row['Name']) ?></td>
                            <td><?= htmlspecialchars($row['Description']) ?></td>
                            <td style="text-align: center;">
                                <a href="?action=edit&id=<?= $row['Specialization_ID'] ?>" class="btn-icon edit" title="Edit"><img src="images/edit.jpg" alt="Search" style="width:16px;height:16px"/></a>
                                <form method="post" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this?')">
                                    <input type="hidden" name="specialization_id" value="<?= $row['Specialization_ID'] ?>">
                                    <button type="submit" name="btnDelete" class="btn-icon delete" title="Delete"><img src="images/delete.jpg" alt="Search" style="width:16px;height:16px"/></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align:center; padding: 20px;">No data found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>