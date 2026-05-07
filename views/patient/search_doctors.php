<?php
session_start();
require_once '../../config/connectDB.php';

$conn = (new ConnectDB())->connection();

// 1. Lấy từ khóa và ID thành phố từ Form
$keyword = isset($_GET['keyword']) ? $conn->real_escape_string($_GET['keyword']) : '';
$city_filter = isset($_GET['city_id']) ? (int)$_GET['city_id'] : 0;

// 2. Lấy danh sách thành phố để hiển thị trong Dropdown
$cities_list = $conn->query("SELECT * FROM Cities ORDER BY Name ASC");

// 3. Xây dựng câu lệnh SQL nâng cao
$sql = "SELECT 
            d.Doctor_ID, 
            u.FullName, 
            u.ProfilePicture, 
            c.Name as CityName,
            GROUP_CONCAT(DISTINCT s.Name SEPARATOR ', ') as Specializations
        FROM Doctors d
        JOIN Users u ON d.User_ID = u.User_ID
        LEFT JOIN Cities c ON u.City_ID = c.City_ID
        LEFT JOIN Doctor_Specializations ds ON d.Doctor_ID = ds.Doctor_ID
        LEFT JOIN Specialization s ON ds.Specialization_ID = s.Specialization_ID
        WHERE 1=1";

// Thêm điều kiện tìm kiếm theo từ khóa (Tên hoặc Chuyên khoa)
if (!empty($keyword)) {
    $sql .= " AND (u.FullName LIKE '%$keyword%' OR s.Name LIKE '%$keyword%')";
}

// Thêm điều kiện lọc theo thành phố nếu người dùng chọn
if ($city_filter > 0) {
    $sql .= " AND u.City_ID = $city_filter";
}

$sql .= " GROUP BY d.Doctor_ID";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Doctors | MediConnect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; color: #1f2937; padding: 20px; }
        .container { max-width: 850px; margin: 0 auto; }
        .back-link { text-decoration: none; color: #3b82f6; font-weight: 500; display: inline-block; margin-bottom: 20px; }
        
        /* Search Bar Style */
        .search-box { 
            background: white; padding: 20px; border-radius: 12px; 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); margin-bottom: 30px;
            display: flex; gap: 10px;
        }
        .search-box input, .search-box select { 
            padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; flex: 1; outline: none;
        }
        .search-box button { 
            background: #2563eb; color: white; border: none; padding: 0 25px; 
            border-radius: 8px; cursor: pointer; font-weight: 600;
        }

        .search-card { 
            background: #ffffff; border-radius: 12px; padding: 20px; 
            display: flex; gap: 20px; align-items: center; 
            margin-bottom: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: 0.2s;
        }
        .search-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .avatar { width: 85px; height: 85px; border-radius: 50%; object-fit: cover; border: 2px solid #e5e7eb; }
        .info { flex: 1; }
        .info h3 { margin: 0; font-size: 1.15rem; color: #111827; }
        .spec-tags { color: #2563eb; font-size: 0.85rem; font-weight: 600; margin-bottom: 5px; text-transform: uppercase; }
        .location { font-size: 0.9rem; color: #6b7280; margin-top: 5px; }
        .btn-book { 
            background-color: #2563eb; color: white; padding: 10px 20px; 
            border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem;
        }
        .no-results { text-align: center; padding: 60px; color: #9ca3af; }
    </style>
</head>
<body>

<div class="container">
    <a href="patient_dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    
    <form class="search-box" method="GET" action="">
        <input type="text" name="keyword" placeholder="Search doctor name or specialization..." value="<?= htmlspecialchars($keyword) ?>">
        
        <select name="city_id">
            <option value="0">All Cities</option>
            <?php while($city = $cities_list->fetch_assoc()): ?>
                <option value="<?= $city['City_ID'] ?>" <?= ($city_filter == $city['City_ID']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($city['Name']) ?>
                </option>
            <?php endwhile; ?>
        </select>
        
        <button type="submit"><i class="fas fa-search"></i> Search</button>
    </form>

    <div style="margin-bottom: 20px;">
        <h3>
            <?php if(!empty($keyword) || $city_filter > 0): ?>
                Results for: <span style="color: #2563eb;">"<?= htmlspecialchars($keyword) ?>"</span> 
                <?= $city_filter > 0 ? " in selected city" : "" ?>
            <?php else: ?>
                Showing all doctors
            <?php endif; ?>
        </h3>
    </div>

    <?php if ($result && $result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <div class="search-card">
                <img src="<?= !empty($row['ProfilePicture']) ? '../../public/uploads/avatars/'.$row['ProfilePicture'] : 'https://ui-avatars.com/api/?name='.urlencode($row['FullName']).'&background=random' ?>" class="avatar">
                
                <div class="info">
                    <div class="spec-tags">
                        <i class="fas fa-stethoscope"></i> <?= htmlspecialchars($row['Specializations'] ?? 'General Practitioner') ?>
                    </div>
                    <h3>Dr. <?= htmlspecialchars($row['FullName']) ?></h3>
                    <div class="location">
                        <i class="fas fa-map-marker-alt"></i> <strong><?= htmlspecialchars($row['CityName'] ?? 'National') ?></strong>
                    </div>
                </div>

                <a href="booking.php?id=<?= $row['Doctor_ID'] ?>" class="btn-book">View Schedule</a>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="no-results">
            <i class="fas fa-search-minus fa-3x" style="margin-bottom: 15px;"></i>
            <p>No doctors found matching your criteria. Try adjusting your filters.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>