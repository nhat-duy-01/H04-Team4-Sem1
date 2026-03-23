<?php
require_once '../../models/Patients.php';
session_start();

$patientObj = new Patient();
$error = ""; 
$success = "";

// --- 1. XỬ LÝ THÊM MỚI ---
if (isset($_POST['btnSave'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];

    // Ràng buộc dữ liệu phía Server
    if ($patientObj->isDuplicate($username, $email)) {
        $error = "Tên đăng nhập hoặc Email đã tồn tại!";
    } elseif (strlen($username) < 5) {
        $error = "Tên đăng nhập phải có ít nhất 5 ký tự!";
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $error = "Số điện thoại phải đúng 10 chữ số!";
    } elseif (strlen($password) < 6) {
        $error = "Mật khẩu phải có ít nhất 6 ký tự!";
    } else {
        $patientObj->setUserName($username);
        $patientObj->setPassword(password_hash($password, PASSWORD_DEFAULT));
        $patientObj->setFullName($_POST['fullname']);
        $patientObj->setEmail($email);
        $patientObj->setPhone($phone);
        $patientObj->setDate_Of_Birth($_POST['dob']);
        $patientObj->setAddress($_POST['address']);
        
        if ($patientObj->insertPatient()) {
            $success = "Thêm bệnh nhân mới thành công!";
        } else {
            $error = "Lỗi hệ thống khi lưu dữ liệu.";
        }
    }
}

// --- 2. XỬ LÝ CẬP NHẬT ---
if (isset($_POST['btnEdit'])) {
    $id = $_POST['id'];
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    if ($patientObj->isDuplicate("", $email, $id)) {
        $error = "Email này đã được sử dụng bởi người khác!";
    } else {
        $patientObj->setUser_ID($id);
        $patientObj->setFullName($_POST['fullname']);
        $patientObj->setEmail($email);
        $patientObj->setPhone($phone);
        $patientObj->setDate_Of_Birth($_POST['dob']);
        $patientObj->setAddress($_POST['address']);
        
        if ($patientObj->updatePatient()) {
            $success = "Cập nhật thông tin thành công!";
        }
    }
}

// --- 3. XỬ LÝ XÓA ---
if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    $patientObj->deletePatient($_GET['id']);
    header("Location: manage_patients.php?msg=deleted"); exit;
}

$row_edit = (isset($_GET['action']) && $_GET['action'] == 'edit') ? $patientObj->findPatientById($_GET['id']) : null;
$keyword = $_GET['txtSearch'] ?? "";
$result = !empty($keyword) ? $patientObj->searchPatients($keyword) : $patientObj->getAllPatients();

if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') $success = "Đã xóa hồ sơ bệnh nhân.";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Bệnh nhân | MediConnect</title>
    <style>
        :root { --primary: #2563eb; --bg: #f8fafc; --text: #1e293b; --border: #e2e8f0; --danger: #ef4444; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: var(--bg); color: var(--text); padding: 30px 20px; }
        .container { max-width: 1200px; margin: auto; }

        /* Thông báo */
        .alert { padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; border: 1px solid transparent; }
        .alert-error { background: #fee2e2; color: #b91c1c; border-color: #fecaca; }
        .alert-success { background: #dcfce7; color: #15803d; border-color: #bbf7d0; }

        /* Card Form */
        .card { background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); margin-bottom: 25px; }
        h2 { margin-bottom: 20px; font-size: 1.3rem; border-left: 4px solid var(--primary); padding-left: 15px; }

        .form-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
        .input-group { display: flex; flex-direction: column; gap: 5px; }
        label { font-size: 0.85rem; font-weight: 600; color: #64748b; }
        input { padding: 10px; border: 1px solid var(--border); border-radius: 6px; font-size: 0.95rem; }
        input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }

        .btn-submit { background: var(--primary); color: #fff; border: none; padding: 12px 30px; border-radius: 6px; cursor: pointer; font-weight: bold; margin-top: 15px; transition: 0.2s; }
        .btn-submit:hover { background: #1d4ed8; }

        /* Table & Search */
        .search-area { display: flex; justify-content: flex-end; margin-bottom: 15px; gap: 8px; }
        .search-area input { width: 250px; }
        
        .table-box { background: #fff; border-radius: 12px; border: 1px solid var(--border); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f1f5f9; padding: 15px; text-align: left; font-size: 0.75rem; color: #64748b; text-transform: uppercase; border-bottom: 1px solid var(--border); }
        td { padding: 15px; border-bottom: 1px solid #f8fafc; font-size: 0.9rem; vertical-align: middle; }
        tr:hover { background: #fcfcfc; }

        .btn-icon { text-decoration: none; padding: 6px 10px; border-radius: 6px; font-size: 1rem; }
        .edit { color: var(--primary); background: #eff6ff; }
        .delete { color: var(--danger); background: #fef2f2; }
    </style>
</head>
<body>

<div class="container">
    <?php if ($error): ?><div class="alert alert-error">❌ <?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>

    <div class="card">
        <h2><?= $row_edit ? "Cập nhật hồ sơ bệnh nhân" : "Đăng ký bệnh nhân mới" ?></h2>
        <form action="" method="POST">
            <?php if ($row_edit): ?><input type="hidden" name="id" value="<?= $row_edit['User_ID'] ?>"><?php endif; ?>
            
            <div class="form-grid">
                <div class="input-group">
                    <label>Họ và tên</label>
                    <input type="text" name="fullname" value="<?= @$row_edit['FullName'] ?>" required>
                </div>
                <div class="input-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= @$row_edit['Email'] ?>" required>
                </div>
                <div class="input-group">
                    <label>Số điện thoại</label>
                    <input type="text" name="phone" value="<?= @$row_edit['Phone'] ?>" required pattern="[0-9]{10}" title="Nhập đúng 10 chữ số">
                </div>

                <?php if (!$row_edit): ?>
                <div class="input-group">
                    <label>Tên đăng nhập (Ít nhất 5 ký tự)</label>
                    <input type="text" name="username" required minlength="5">
                </div>
                <div class="input-group">
                    <label>Mật khẩu</label>
                    <input type="password" name="password" required minlength="6">
                </div>
                <?php endif; ?>

                <div class="input-group">
                    <label>Ngày sinh</label>
                    <input type="date" name="dob" value="<?= @$row_edit['Date_Of_Birth'] ?>" required>
                </div>
                <div class="input-group" style="grid-column: span <?= $row_edit ? '2' : '1' ?>;">
                    <label>Địa chỉ</label>
                    <input type="text" name="address" value="<?= @$row_edit['Address'] ?>" required>
                </div>
            </div>

            <div style="text-align: center;">
                <button type="submit" name="<?= $row_edit ? 'btnEdit' : 'btnSave' ?>" class="btn-submit">
                    <?= $row_edit ? 'LƯU THAY ĐỔI' : 'TẠO HỒ SƠ MỚI' ?>
                </button>
                <?php if ($row_edit): ?><a href="manage_patients.php" style="margin-left:15px; font-size: 0.9rem; color: #64748b;">Hủy bỏ</a><?php endif; ?>
            </div>
        </form>
    </div>

    <div class="search-area">
        <form action="" method="GET" style="display:flex; gap:5px;">
            <input type="text" name="txtSearch" placeholder="Tìm kiếm bệnh nhân..." value="<?= htmlspecialchars($keyword) ?>">
            <button type="submit" class="btn-submit" style="margin:0; padding:10px 15px;">Tìm</button>
        </form>
    </div>

    <div class="table-box">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Họ và Tên</th>
                    <th>Email</th>
                    <th>Số điện thoại</th>
                    <th>Địa chỉ</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td style="font-weight: bold; color: #94a3b8;">#<?= $row['User_ID'] ?></td>
                        <td>
                            <div style="font-weight: 600;"><?= $row['FullName'] ?></div>
                            <div style="font-size: 0.75rem; color: #64748b;">ID: <?= $row['UserName'] ?></div>
                        </td>
                        <td><?= $row['Email'] ?></td>
                        <td style="font-weight: 600;"><?= $row['Phone'] ?></td>
                        <td><?= $row['Address'] ?></td>
                        <td>
                            <a href="manage_patients.php?id=<?= $row['User_ID'] ?>&action=edit" class="btn-icon edit" title="Sửa">📝</a>
                            <a href="manage_patients.php?id=<?= $row['User_ID'] ?>&action=delete" class="btn-icon delete" title="Xóa" onclick="return confirm('Bạn có chắc muốn xóa bệnh nhân này?')">🗑️</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align: center; padding: 30px; color: #94a3b8;">Không có dữ liệu bệnh nhân.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>