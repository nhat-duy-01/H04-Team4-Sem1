<?php
session_start();
$username_session = $_SESSION['UserName'] ?? 'Admin';

require_once('../../config/connectDB.php');
require_once('../../controller/admin/CitiesController.php');

$conn = (new ConnectDB())->connection();

$city_edit = handleCitiesRequest($conn);
$cities = getCities($conn);  

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Cities | MediConnect</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <div class="header-flex">
        <h2>MediConnect Admin</h2>
        <a href="admin_dashboard.php" class="back-dashboard">Back to Dashboard</a>
    </div>

    <div class="card">
        <h3><?= $city_edit ? 'Edit City' : 'Add New City' ?></h3>
        <form method="post">
            <?php if ($city_edit): ?>
                <input type="hidden" name="city_id" value="<?= $city_edit['City_ID'] ?>">
            <?php endif; ?>
            
            <div class="form-grid">
                <div class="input-group">
                    <label>City Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($city_edit['Name'] ?? '') ?>" placeholder="e.g., London, New York" required>
                </div>
                <div class="input-group">
                    <label>Region / Area</label>
                    <input type="text" name="region" value="<?= htmlspecialchars($city_edit['Region'] ?? '') ?>" placeholder="e.g., North, West Coast" required>
                </div>
                <div class="input-group" style="justify-content: flex-end; display: flex;">
                     </div>
            </div>

            <div style="margin-top: 10px;">
                <?php if ($city_edit): ?>
                    <button type="submit" name="btnEdit" class="btn-submit">Update Changes</button>
                    <a href="manage_cities.php" style="margin-left: 15px; color: var(--text-muted); text-decoration: none;">Cancel</a>
                <?php else: ?>
                    <button type="submit" name="btnAdd" class="btn-submit"><img src="images/add.jpg" alt="Search" style="width:16px;height:16px"/>Add </button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="search-container">
        <h3>City List</h3>
        <div class="search-area">
            <form method="get">
                <input type="text" name="keyword" placeholder="Search by name or region..." value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
                <button type="submit" name="search" class="btn-submit" style="padding: 8px 15px;"><img src="images/search.jpg" alt="Search" style="width:16px;height:16px"/></button>
            </form>
        </div>
    </div>

    <div class="table-box">
        <table>
            <thead>
                <tr>
                    <th>City Name</th>
                    <th>Region</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($cities)): ?>
                    <?php foreach ($cities as $city): ?>
                        <tr>

                            <td style="font-weight: 600; color: var(--primary);">
                                <?= htmlspecialchars($city['Name']) ?>
                            </td>
                            <td><?= htmlspecialchars($city['Region']) ?></td>
                            <td style="text-align: center;">
                                <a href="?action=edit&id=<?= $city['City_ID'] ?>" class="btn-icon edit" title="Edit"><img src="images/edit.jpg" alt="Search" style="width:16px;height:16px"/></a>
                                
                                <form method="post" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this city?')">
                                    <input type="hidden" name="city_id" value="<?= $city['City_ID'] ?>">
                                    <button type="submit" name="btnDelete" class="btn-icon delete" title="Delete"><img src="images/delete.jpg" alt="Search" style="width:16px;height:16px"/></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 30px; color: var(--text-muted);">
                            No city data found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>