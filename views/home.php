<?php
require_once('../config/connectDB.php');

$db = new ConnectDB();
$conn = $db->connection();

// 1. Get Specializations
$specs = $conn->query("SELECT * FROM Specialization LIMIT 6");

// 2. Get Featured Hospitals
$hospitals = $conn->query("SELECT * FROM Hospitals LIMIT 4");

// 3. Get Featured Doctors
$docs = $conn->query("SELECT d.*, u.FullName, u.ProfilePicture FROM Doctors d 
                      JOIN Users u ON d.User_ID = u.User_ID 
                      LIMIT 4");

// 4. Get Latest Medical News
$news = $conn->query("SELECT n.*, u.FullName as Author FROM MedicalContent n 
                      JOIN Doctors d ON n.Doctor_ID = d.Doctor_ID 
                      JOIN Users u ON d.User_ID = u.User_ID 
                      ORDER BY n.PublishedDate DESC LIMIT 3");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediConnect | Smart Healthcare System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* CSS SYNCED WITH MEDICAL CONTENT PAGE */
        :root { 
            --primary: #3498db; 
            --primary-glow: rgba(52, 152, 219, 0.4);
            --bg: #f4f7f6; 
            --dark: #2c3e50; 
            --white: #ffffff; 
            --danger: #e74c3c;
        }

        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); margin: 0; color: var(--dark); line-height: 1.6; }
        .container { max-width: 1200px; margin: auto; padding: 0 20px; }
        
        /* Navbar */
        nav { background: var(--white); padding: 15px 0; box-shadow: 0 4px 12px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 1000; }
        .nav-flex { display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 24px; font-weight: 800; text-decoration: none; color: var(--dark); }
        .logo span { color: var(--primary); }
        
        /* Glowing Login / Dashboard Button */
        .btn-login { 
            background: var(--primary); 
            color: white !important; 
            padding: 10px 25px; 
            border-radius: 50px; 
            text-decoration: none; 
            font-weight: 600;
            box-shadow: 0 4px 15px var(--primary-glow);
            transition: 0.3s;
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 8px 20px var(--primary-glow); }

        /* Hero */
        .hero { background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); color: white; padding: 80px 0; text-align: center; border-radius: 0 0 50px 50px; }
        .hero h1 { font-size: 40px; margin-bottom: 15px; font-weight: 800; }
        .search-box { max-width: 700px; margin: 25px auto 0; background: white; padding: 6px; border-radius: 50px; display: flex; box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .search-box input { flex: 1; border: none; padding: 12px 25px; outline: none; border-radius: 50px; font-size: 15px; }
        .search-box button { background: var(--primary); color: white; border: none; padding: 12px 30px; border-radius: 50px; cursor: pointer; font-weight: 600; }

        /* Section Commons */
        .section { padding: 50px 0; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .section-header h2 { font-size: 24px; position: relative; padding-left: 15px; margin: 0; }
        .section-header h2::before { content: ''; position: absolute; left: 0; top: 5px; bottom: 5px; width: 5px; background: var(--primary); border-radius: 2px; }
        .view-all { text-decoration: none; color: var(--primary); font-weight: 700; font-size: 14px; }

        /* Grid & Card - Glowing Hover Effect */
        .grid-4 { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; }
        
        /* Shared Card Style */
        .card { 
            background: white; border-radius: 18px; padding: 20px; 
            transition: 0.3s ease; border: 1px solid transparent;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
        }
        .card:hover { 
            transform: translateY(-8px); 
            box-shadow: 0 12px 25px var(--primary-glow); 
            border-color: var(--primary); 
        }

        .card-img-circle { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin: 0 auto 15px; display: block; border: 3px solid #f0f4f8; }
        
        .btn-book { 
            display: block; text-align: center; background: #eef5ff; 
            color: var(--primary); padding: 10px; border-radius: 10px; 
            text-decoration: none; font-weight: 700; margin-top: 15px; 
        }
        .btn-book:hover { background: var(--primary); color: white; }

        /* News Cards */
        .news-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 25px; }
        .news-card { 
            background: white; border-radius: 20px; padding: 25px; 
            border-top: 5px solid var(--primary); box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: 0.3s;
        }
        .news-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .category-tag { background: #e1f5fe; color: #03a9f4; padding: 4px 12px; border-radius: 50px; font-size: 11px; font-weight: 800; }
        
        footer { background: var(--dark); color: #94a3b8; padding: 50px 0; text-align: center; margin-top: 40px; }
    </style>
</head>
<body>

<nav>
    <div class="container nav-flex">
        <a href="home.php" class="logo">Medi<span>Connect</span></a>
        <div style="display: flex; gap: 25px; align-items: center;">
            <a href="#specialization" style="text-decoration:none; color:inherit; font-weight:600;">Specializations</a>
            <a href="#hospitals" style="text-decoration:none; color:inherit; font-weight:600;">Hospitals</a>     
                        <a href="about_us.php" style="text-decoration:none; color:inherit; font-weight:600;">About Us</a>      
            <?php if(isset($_SESSION['user_id'])): ?>
                <?php $link = ($_SESSION['user_type'] == 'Doctor') ? 'doctors/doctor_dashboard.php' : 'patient/patient_dashboard.php'; ?>
                <a href="<?= $link ?>" class="btn-login"><i class="fas fa-th-large"></i> Dashboard</a>
            <?php else: ?>
                <a href="authentication.php" class="btn-login">Login / Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<header class="hero">
    <div class="container">
        <h1>Book an Appointment, No Waiting</h1>
        <p>Find over 1000+ top-rated doctors and hospitals</p>
        <form action="patient/search_doctors.php" method="GET" class="search-box">
            <input type="text" name="keyword" placeholder="Doctor, symptoms, or hospital name...">
            <button type="submit">Search Now</button>
        </form>
    </div>
</header>

<main class="container">

    <section id="specialization" class="section">
        <div class="section-header">
            <h2>Popular Specializations</h2>
            <a href="#" class="view-all">View all Specializations</a>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 20px;">
            <?php while($s = $specs->fetch_assoc()): ?>
                <a href="authentication.php" style="text-decoration:none; color:inherit;">
                    <div class="card" style="text-align: center;">
                        <i class="fas fa-notes-medical" style="font-size: 30px; color: var(--primary); margin-bottom: 10px;"></i>
                        <div style="font-weight: 700; font-size: 14px;"><?= htmlspecialchars($s['Name']) ?></div>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>
    </section>

    <section id="hospitals" class="section">
        <div class="section-header">
            <h2>Featured Hospitals</h2>
            <a href="#" class="view-all">View Hospital Map</a>
        </div>
        <div class="grid-4">
            <?php while($h = $hospitals->fetch_assoc()): ?>
                <div class="card">
                    <i class="fas fa-hospital" style="font-size: 40px; color: #e74c3c; margin-bottom: 15px;"></i>
                    <h4 style="margin: 10px 0;"><?= htmlspecialchars($h['Name']) ?></h4>
                    <p style="font-size: 13px; color: #64748b; margin-bottom: 15px;">
                        <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($h['Address']) ?>
                    </p>
                    <a href="authentication.php" class="btn-book" style="background:#fff5f5; color:#e74c3c;">View Details</a>
                </div>
            <?php endwhile; ?>
        </div>
    </section>

    <section class="section">
        <div class="section-header">
            <h2>Featured Doctors</h2>
            <a href="search_doctors.php" class="view-all">Find More Doctors ></a>
        </div>
        <div class="grid-4">
            <?php while($d = $docs->fetch_assoc()): ?>
            <div class="card">
                <img src="<?= !empty($d['ProfilePicture']) ? '../public/uploads/avatars/'.$d['ProfilePicture'] : 'https://ui-avatars.com/api/?name='.urlencode($d['FullName']) ?>" class="card-img-circle">
                <h4 style="margin: 5px 0;">Dr. <?= htmlspecialchars($d['FullName']) ?></h4>
                <p style="font-size: 13px; color: var(--text-muted);">Internal Medicine</p>
                <a href="patient/booking.php?id=<?= $d['Doctor_ID'] ?>" class="btn-book">Book Appointment</a>
            </div>
            <?php endwhile; ?>
        </div>
    </section>

    <section class="section">
        <div class="section-header">
            <h2>Medical Handbook</h2>
            <a href="#" class="view-all">Read More Articles</a>
        </div>
        <div class="news-grid">
            <?php while($n = $news->fetch_assoc()): ?>
                <div class="news-card">
                    <span class="category-tag"><?= htmlspecialchars($n['Category']) ?></span>
                    <h3 style="margin: 15px 0; font-size: 19px;"><?= htmlspecialchars($n['Title']) ?></h3>
                    <p style="font-size: 14px; color: #64748b; margin-bottom: 20px;">
                        <?= mb_strimwidth(strip_tags($n['Body']), 0, 130, "...") ?>
                    </p>
                    <div style="display: flex; align-items: center; gap: 10px; font-size: 13px; border-top: 1px solid #eee; padding-top: 15px;">
                        <i class="fas fa-user-md" style="color:var(--primary)"></i> <b><?= htmlspecialchars($n['Author']) ?></b>
                        <span style="margin-left: auto; color: #94a3b8;"><?= date('M d, Y', strtotime($n['PublishedDate'])) ?></span>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </section>

</main>

<footer>
    <div class="container">
        <p style="font-weight: 800; font-size: 20px; color: white; margin-bottom: 10px;">Medi<span>Connect</span></p>
        <p>&copy; 2026 Smart Healthcare Connection Platform. All rights reserved.</p>
        <div style="margin-top: 20px; display: flex; justify-content: center; gap: 20px; font-size: 20px;">
            <i class="fab fa-facebook"></i> <i class="fab fa-youtube"></i> <i class="fab fa-twitter"></i>
        </div>
    </div>
</footer>

</body>
</html>