<?php
// 1. Database Connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "mediconnect";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// 2. Handle DELETE Action
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM hospitals WHERE Hospital_ID = $id");
    header("Location: manage_hospital.php?msg=deleted");
}

// 3. Handle CREATE & UPDATE Action
if (isset($_POST['save_hospital'])) {
    $id = $_POST['hospital_id'];
    $name = $conn->real_escape_string($_POST['name']);
    $address = $conn->real_escape_string($_POST['address']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $email = $conn->real_escape_string($_POST['email']);

    if (empty($id)) {
        // INSERT new record
        $sql = "INSERT INTO hospitals (Name, Address, Phone, Email) VALUES ('$name', '$address', '$phone', '$email')";
    } else {
        // UPDATE existing record
        $sql = "UPDATE hospitals SET Name='$name', Address='$address', Phone='$phone', Email='$email' WHERE Hospital_ID=$id";
    }
    
    if ($conn->query($sql)) {
        header("Location: manage_hospital.php?msg=success");
    }
}

// 4. Fetch data for EDIT mode
$edit_data = ['Hospital_ID' => '', 'Name' => '', 'Address' => '', 'Phone' => '', 'Email' => ''];
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $res = $conn->query("SELECT * FROM hospitals WHERE Hospital_ID = $id");
    if ($res->num_rows > 0) {
        $edit_data = $res->fetch_assoc();
    }
}

// 5. Fetch all records for the Table
$result = $conn->query("SELECT * FROM hospitals ORDER BY Hospital_ID DESC");

// 5. SEARCH LOGIC
$search_keyword = "";
if (isset($_GET['search'])) {
    $search_keyword = $conn->real_escape_string($_GET['search']);
    $query = "SELECT * FROM hospitals WHERE Name LIKE '%$search_keyword%' OR Address LIKE '%$search_keyword%' ORDER BY Hospital_ID DESC";
} else {
    $query = "SELECT * FROM hospitals ORDER BY Hospital_ID DESC";
}
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MediConnect | Admin Hospital Management</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f8f9fa; padding: 30px; }
        .container { max-width: 1000px; margin: auto; background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h1 { color: #1a73e8; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .form-section { background: #f1f3f4; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        input { padding: 10px; border: 1px solid #ccc; border-radius: 4px; width: 100%; box-sizing: border-box; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block; }
        .btn-save { background: #28a745; color: white; }
        .btn-edit { background: #ffc107; color: #333; font-size: 13px; }
        .btn-delete { background: #dc3545; color: white; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background: #1a73e8; color: white; }
    </style>
        <link rel="stylesheet" href="style.css">

</head>
<body>

<div class="container">
    <div class="header-flex">
        <h2>MediConnect Admin</h2>
        <a href="admin_dashboard.php" class="back-dashboard">⬅️ Back to Dashboard</a>
    </div>

<div class="container">
    <h1>Hospital Administration</h1>

    <div class="form-section">
        <h3><?= $edit_data['Hospital_ID'] ? "Update Hospital: " . $edit_data['Name'] : "Register New Hospital" ?></h3>
        <form method="POST">
            <input type="hidden" name="hospital_id" value="<?= $edit_data['Hospital_ID'] ?>">
            <div class="form-grid">
                <div>
                    <label>Hospital Name</label>
                    <input type="text" name="name" value="<?= $edit_data['Name'] ?>" required>
                </div>
                <div>
                    <label>Address</label>
                    <input type="text" name="address" value="<?= $edit_data['Address'] ?>" required>
                </div>
                <div>
                    <label>Phone Number</label>
                    <input type="text" name="phone" value="<?= $edit_data['Phone'] ?>" required>
                </div>
                <div>
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?= $edit_data['Email'] ?>" required>
                </div>
            </div>
            <div style="margin-top: 15px;">
                <button type="submit" name="save_hospital" class="btn btn-save">
                    <?= $edit_data['Hospital_ID'] ? "Save Changes" : "Add Hospital" ?>
                </button>
                <?php if($edit_data['Hospital_ID']): ?>
                    <a href="manage_hospital.php" class="btn" style="background:#6c757d; color:white;">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

     <div class="search-section">
        <form method="GET" style="display: flex; width: 100%; gap: 10px;">
            <input type="text" name="search" placeholder="Search by name or address..." value="<?= htmlspecialchars($search_keyword) ?>">
            <button type="submit" class="btn btn-search">Search</button>
            <?php if($search_keyword != ""): ?>
                <a href="manage_hospital.php" class="btn" style="background:#95a5a6; color:white;">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <h2>Hospital Records</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Address</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['Hospital_ID'] ?></td>
                <td><strong><?= $row['Name'] ?></strong></td>
                <td><?= $row['Address'] ?></td>
                <td><?= $row['Phone'] ?></td>
                <td><?= $row['Email'] ?></td>
                <td>
                    <a href="?edit=<?= $row['Hospital_ID'] ?>" class="btn btn-edit">Edit</a>
                    <a href="?delete=<?= $row['Hospital_ID'] ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this record?')">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>