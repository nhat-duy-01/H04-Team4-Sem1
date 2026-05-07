<?php
session_start();
require_once('../../config/connectDB.php');

// 1. Access Control Check
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Patient') {
    header("Location: ../authentication.php");
    exit();
}

// Initialize secure connection
$db = new ConnectDB();
$conn = $db->connection();

if (!$conn) {
    die("Database connection error.");
}

$user_id = $_SESSION['user_id'];

// 2. Fetch User Information
$sql_user = "SELECT FullName, ProfilePicture FROM Users WHERE User_ID = ?";
$stmt_u = $conn->prepare($sql_user);
$stmt_u->bind_param("i", $user_id);
$stmt_u->execute();
$user_info = $stmt_u->get_result()->fetch_assoc();
$fullname = $user_info['FullName'] ?? 'Patient';

$avatar_url = !empty($user_info['ProfilePicture']) 
              ? "../../public/uploads/avatars/" . $user_info['ProfilePicture'] 
              : "https://ui-avatars.com/api/?name=" . urlencode($fullname) . "&background=007bff&color=fff";

// 3. DIVERSE DATA QUERIES
$specs = $conn->query("SELECT * FROM Specialization LIMIT 8");
$hospitals = $conn->query("SELECT * FROM Hospitals LIMIT 4");
$docs = $conn->query("SELECT d.*, u.FullName, u.ProfilePicture FROM Doctors d JOIN Users u ON d.User_ID = u.User_ID LIMIT 4");
$news = $conn->query("SELECT n.*, u.FullName as Author FROM MedicalContent n JOIN Doctors d ON n.Doctor_ID = d.Doctor_ID JOIN Users u ON d.User_ID = u.User_ID ORDER BY n.PublishedDate DESC LIMIT 3");

// Lấy danh sách thành phố cho thanh tìm kiếm
$cities = $conn->query("SELECT * FROM Cities ORDER BY Name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediConnect | Patient Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #007bff; --bg: #f4f7f6; --dark: #2c3e50; --white: #ffffff; --shadow: 0 4px 15px rgba(0,0,0,0.05); }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); margin: 0; color: var(--dark); line-height: 1.6; }
        .container { max-width: 1200px; margin: auto; padding: 0 20px; }
        
        /* Nav & Hero */
        .top-nav { background: var(--white); padding: 12px 0; box-shadow: var(--shadow); position: sticky; top: 0; z-index: 1000; }
        .nav-flex { display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 22px; font-weight: 800; text-decoration: none; color: var(--dark); }
        .logo span { color: var(--primary); }
        .user-info { display: flex; align-items: center; gap: 10px; cursor: pointer; text-decoration: none; color: var(--dark); }
        .nav-avatar { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary); }

        .hero { background: linear-gradient(135deg, #007bff 0%, #00d2ff 100%); color: white; padding: 60px 0; text-align: center; border-radius: 0 0 30px 30px; }
        
        /* Updated Search Box with City Select */
        .search-box { 
            max-width: 800px; margin: 25px auto 0; background: white; padding: 5px; 
            border-radius: 50px; display: flex; box-shadow: 0 10px 20px rgba(0,0,0,0.1); 
            overflow: hidden;
        }
        .search-box input { flex: 2; border: none; padding: 12px 25px; outline: none; font-size: 15px; }
        .search-box select { 
            flex: 1; border: none; border-left: 1px solid #eee; padding: 0 15px; 
            outline: none; color: #666; cursor: pointer; background: white;
        }
        .search-box button { background: var(--primary); color: white; border: none; padding: 12px 30px; border-radius: 50px; cursor: pointer; font-weight: 600; }

        /* Mobile Responsive for Search Box */
        @media (max-width: 600px) {
            .search-box { flex-direction: column; border-radius: 20px; }
            .search-box input, .search-box select, .search-box button { width: 100%; border-left: none; border-bottom: 1px solid #eee; }
        }

        /* Sections */
        .section { padding: 40px 0; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .section-header h2 { font-size: 22px; position: relative; padding-left: 15px; margin: 0; }
        .section-header h2::before { content: ''; position: absolute; left: 0; top: 5px; bottom: 5px; width: 4px; background: var(--primary); border-radius: 2px; }
        .view-all { text-decoration: none; color: var(--primary); font-weight: 600; font-size: 14px; }

        .spec-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 15px; }
        .spec-card { background: white; padding: 20px; text-align: center; border-radius: 15px; text-decoration: none; color: var(--dark); transition: 0.3s; box-shadow: var(--shadow); }
        .spec-card:hover { transform: translateY(-5px); background: var(--primary); color: white; }
        .spec-card i { font-size: 24px; margin-bottom: 10px; display: block; }

        .grid-4 { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; }
        .card-item { background: white; border-radius: 18px; overflow: hidden; box-shadow: var(--shadow); transition: 0.3s; }
        .card-item:hover { transform: translateY(-5px); }
        .card-body { padding: 20px; }
        .card-img-circle { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin: 0 auto 15px; display: block; border: 3px solid #f0f4f8; }
        .btn-book { display: block; text-align: center; background: #eef5ff; color: var(--primary); padding: 10px; border-radius: 10px; text-decoration: none; font-weight: 600; margin-top: 15px; }
        .btn-book:hover { background: var(--primary); color: white; }

        .news-full-card { background: white; border-radius: 20px; padding: 30px; margin-bottom: 30px; box-shadow: var(--shadow); border-left: 6px solid var(--primary); }
        .news-tag { background: #e1f5fe; color: #03a9f4; padding: 5px 15px; border-radius: 50px; font-size: 11px; font-weight: 800; text-transform: uppercase; }
        .news-content { margin-top: 20px; color: #4a5568; font-size: 16px; line-height: 1.8; white-space: pre-line; }
    </style>
</head>
<body>

<nav class="top-nav">
    <div class="container nav-flex">
        <a href="patient_dashboard.php" class="logo">Medi<span>Connect</span></a>
        <div class="nav-right" style="display: flex; gap: 20px; align-items: center;">
            <a href="patient_appointments.php" class="view-all"><i class="fas fa-calendar-alt"></i> Appointments</a>
            <a href="profile.php" class="user-info">
                <img src="<?= $avatar_url ?>" class="nav-avatar">
                <span class="nav-name"><?= htmlspecialchars($fullname) ?></span>
            </a>
            <a href="../logout.php" style="color: #e74c3c; text-decoration: none; font-weight: 600;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</nav>

<header class="hero">
    <div class="container">
        <h1>Book your check-up now, no waiting</h1>
        <p>Search over 1000+ top-rated doctors and reputable hospitals</p>
        
        <form action="search_doctors.php" method="GET" class="search-box">
            <input type="text" name="keyword" placeholder="Doctor name, symptom, or hospital...">
            
            <select name="city_id">
                <option value="0">All Cities</option>
                <?php if($cities): ?>
                    <?php while($c = $cities->fetch_assoc()): ?>
                        <option value="<?= $c['City_ID'] ?>"><?= htmlspecialchars($c['Name']) ?></option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
            
            <button type="submit">Search Now</button>
        </form>
    </div>
</header>

<main class="container">
    <section class="section">
        <div class="section-header">
            <h2>Popular Specializations</h2>
            <a href="search_doctors.php" class="view-all">View All ></a>
        </div>
        <div class="spec-grid">
            <?php while($s = $specs->fetch_assoc()): ?>
            <a href="search_doctors.php?keyword=<?= urlencode($s['Name']) ?>" class="spec-card">
                <i class="fas fa-stethoscope"></i>
                <span><?= htmlspecialchars($s['Name']) ?></span>
            </a>
            <?php endwhile; ?>
        </div>
    </section>

    <section class="section" style="background: #fff; border-radius: 30px; padding: 30px; margin-bottom: 30px;">
        <div class="section-header">
            <h2>Featured Hospitals</h2>
            <a href="#" class="view-all">View Map ></a>
        </div>
        <div class="grid-4">
            <?php while($h = $hospitals->fetch_assoc()): ?>
            <div class="card-item">
                <div class="card-body">
                    <i class="fas fa-hospital-alt" style="font-size: 30px; color: var(--primary); margin-bottom: 10px;"></i>
                    <h4 style="margin: 10px 0;"><?= htmlspecialchars($h['Name']) ?></h4>
                    <p style="font-size: 13px; color: #7f8c8d;"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($h['Address']) ?></p>
                    <a href="booking_hosp.php?id=<?= $h['Hospital_ID'] ?>" class="btn-book">Hospital Booking</a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </section>

    <section class="section">
        <div class="section-header">
            <h2>Featured Doctors</h2>
            <a href="search_doctors.php" class="view-all">Find more doctors ></a>
        </div>
        <div class="grid-4">
            <?php while($d = $docs->fetch_assoc()): ?>
            <div class="card-item">
                <div class="card-body" style="text-align: center;">
                    <img src="<?= !empty($d['ProfilePicture']) ? '../../public/uploads/avatars/'.$d['ProfilePicture'] : 'https://ui-avatars.com/api/?name='.urlencode($d['FullName']) ?>" class="card-img-circle">
                    <h4 style="margin: 5px 0;">Dr. <?= htmlspecialchars($d['FullName']) ?></h4>
                    <a href="booking.php?id=<?= $d['Doctor_ID'] ?>" class="btn-book">Book Appointment</a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </section>

    <section class="section">
        <div class="section-header">
            <h2>Medical Knowledge for You</h2>
        </div>
        <div class="news-list">
            <?php if($news && $news->num_rows > 0): ?>
                <?php while($n = $news->fetch_assoc()): ?>
                <article class="news-full-card">
                    <span class="news-tag"><?= htmlspecialchars($n['Category']) ?></span>
                    <h2 style="margin: 15px 0; color: var(--dark);"><?= htmlspecialchars($n['Title']) ?></h2>
                    <div class="news-content">
                        <?= nl2br(htmlspecialchars($n['Body'])) ?>
                    </div>
                    <div class="news-author" style="display: flex; align-items: center; gap: 10px; margin-top: 20px; padding-top: 15px; border-top: 1px solid #f1f1f1; font-size: 13px; color: #718096;">
                        <i class="fas fa-user-md"></i> 
                        <strong>Dr. <?= htmlspecialchars($n['Author']) ?></strong>
                        <span style="margin-left: auto;"><i class="far fa-calendar-alt"></i> <?= date('M d, Y', strtotime($n['PublishedDate'])) ?></span>
                    </div>
                </article>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align: center; color: #95a5a6;">No articles posted yet.</p>
            <?php endif; ?>
        </div>
    </section>
</main>

<footer style="text-align: center; padding: 40px; color: #95a5a6; font-size: 13px;">
    &copy; 2026 MediConnect - Smart Healthcare Management System.
</footer>

</body>
</html>