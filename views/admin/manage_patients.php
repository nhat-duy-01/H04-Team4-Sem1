<?php
require_once '../../models/admin/Patients.php';
session_start();

$patientObj = new Patient();
$error = ""; 
$success = "";

if (isset($_POST['btnSave'])) {
    $patientObj->setUserName($_POST['username']);
    $patientObj->setPassword(($_POST['password'])); 
    $patientObj->setFullName($_POST['fullname']);
    $patientObj->setEmail($_POST['email']);
    $patientObj->setDate_Of_Birth($_POST['dob']);
    $patientObj->setAddress($_POST['address']);
    $patientObj->setPhone($_POST['phone']);

    if ($patientObj->isDuplicate($_POST['username'], $_POST['email'])) {
        $error = "Username or Email already exists!";
    } else {
        if ($patientObj->insertPatient()) {
            $success = "New patient profile created successfully!";
        } else {
            // In lỗi ra để kiểm tra nếu vẫn thất bại
            $error = "Failed to create patient profile: " . $this->conn->error;
        }
    }
}

// 2. Xử lý CẬP NHẬT (btnEdit)
if (isset($_POST['btnEdit'])) {
    $patientObj->setUser_ID($_POST['id']);
    $patientObj->setFullName($_POST['fullname']);
    $patientObj->setEmail($_POST['email']);
    $patientObj->setDate_Of_Birth($_POST['dob']);
    $patientObj->setAddress($_POST['address']);
    $patientObj->setPhone($_POST['phone']);

    if ($patientObj->isDuplicate($_POST['username'] ?? '', $_POST['email'], $_POST['id'])) {
        $error = "Email already exists in another record!";
    } else {
        if ($patientObj->updatePatient()) {
            $success = "Patient information updated!";
        } else {
            $error = "Update failed.";
        }
    }
}

// 3. Xử lý XÓA (delete)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    if ($patientObj->deletePatient($_GET['id'])) {
        header("Location: manage_patients.php?msg=deleted");
        exit();
    } else {
        $error = "Could not delete the record.";
    }
}

$row_edit = (isset($_GET['action']) && $_GET['action'] == 'edit') ? $patientObj->findPatientById($_GET['id']) : null;
$keyword = $_GET['txtSearch'] ?? "";
$result = !empty($keyword) ? $patientObj->searchPatients($keyword) : $patientObj->getAllPatients();

if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') $success = "Patient record has been deleted.";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Patients | MediConnect</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <div class="header-flex">
        <h2>MediConnect Admin</h2>
        <a href="admin_dashboard.php" class="back-dashboard">⬅️ Back to Dashboard</a>
    </div>

    <?php if ($error): ?><div class="alert alert-error">❌ <?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>

    <div class="card">
        <h3><?= $row_edit ? '✏️ Edit Patient Information' : '➕ Add New Patient' ?></h3>
        <form action="" method="POST">
            <?php if ($row_edit): ?><input type="hidden" name="id" value="<?= $row_edit['User_ID'] ?>"><?php endif; ?>
            
            <div class="form-grid">
                <div class="input-group">
                    <label>Full Name</label>
                    <input type="text" name="fullname" value="<?= @$row_edit['FullName'] ?>" required>
                </div>
                <div class="input-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= @$row_edit['Email'] ?>" required>
                </div>
                <div class="input-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" value="<?= @$row_edit['Phone'] ?>" required pattern="[0-9]{10}">
                </div>

                <?php if (!$row_edit): ?>
                <div class="input-group">
                    <label>Username</label>
                    <input type="text" name="username" required minlength="5">
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" required minlength="6">
                </div>
                <?php endif; ?>

                <div class="input-group">
                    <label>Date of Birth</label>
                    <input type="date" name="dob" value="<?= @$row_edit['Date_Of_Birth'] ?>" required>
                </div>
                
                <div class="input-group" style="grid-column: span <?= $row_edit ? '2' : '1' ?>;">
                    <label>Address</label>
                    <input type="text" name="address" value="<?= @$row_edit['Address'] ?>" required>
                </div>
            </div>

            <div style="text-align: center; margin-top: 10px;">
                <button type="submit" name="<?= $row_edit ? 'btnEdit' : 'btnSave' ?>" class="btn-submit">
                    <?= $row_edit ? 'SAVE CHANGES' : 'CREATE NEW PROFILE' ?>
                </button>
                <?php if ($row_edit): ?>
                    <a href="manage_patients.php" style="margin-left:15px; color: var(--text-muted); text-decoration: none;">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="search-container">
        <h3>Patient List</h3>
        <div class="search-area">
            <form action="" method="GET">
                <input type="text" name="txtSearch" placeholder="Search by name, email..." value="<?= htmlspecialchars($keyword) ?>">
                <button type="submit" class="btn-submit" style="padding: 8px 15px;">Search</button>
            </form>
        </div>
    </div>

    <div class="table-box">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Contact</th>
                    <th>Address</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td style="font-weight: bold; color: #94a3b8;">#<?= $row['User_ID'] ?></td>
                        <td>
                            <div style="font-weight: 600;"><?= $row['FullName'] ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">User: <?= $row['UserName'] ?></div>
                        </td>
                        <td>
                            <div><?= $row['Email'] ?></div>
                            <div style="font-weight: 600; font-size: 0.85rem;"><?= $row['Phone'] ?></div>
                        </td>
                        <td><?= $row['Address'] ?></td>
                        <td style="text-align: center;">
                            <a href="manage_patients.php?id=<?= $row['User_ID'] ?>&action=edit" class="btn-icon edit" title="Edit">📝</a>
                            <form method="GET" style="display:inline;" onsubmit="return confirm('Delete this record?')">
                                <input type="hidden" name="id" value="<?= $row['User_ID'] ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn-icon delete" title="Delete">🗑️</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align: center; padding: 30px;">No patient data found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>