<?php
session_start();
require_once '../config/connectDB.php';

// Get notification count if logged in
$count_noti = 0;
if (isset($_SESSION['user_id'])) {
    $conn = (new ConnectDB())->connection();
    $user_id = $_SESSION['user_id'];
    $noti_res = $conn->query("SELECT COUNT(*) as total FROM User_Notifications WHERE User_ID = $user_id AND IsRead = FALSE");
    $count_noti = ($noti_res) ? $noti_res->fetch_assoc()['total'] : 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | MediConnect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { 
            --primary: #3498db; 
            --dark: #2c3e50; 
            --bg: #f4f7f6; 
            --white: #ffffff; 
            --text: #546e7a;
        }

        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); margin: 0; color: var(--dark); line-height: 1.8; }
        .container { max-width: 1100px; margin: auto; padding: 0 20px; }

        /* Hero Section */
        .about-hero { 
            background: linear-gradient(rgba(44, 62, 80, 0.8), rgba(44, 62, 80, 0.8)), 
                        url('https://images.unsplash.com/photo-1576091160550-2173dba999ef?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            color: white; 
            padding: 100px 0; 
            text-align: center; 
        }
        .about-hero h1 { font-size: 3rem; margin-bottom: 10px; }
        .about-hero p { font-size: 1.2rem; opacity: 0.9; max-width: 700px; margin: auto; }

        /* Section Styling */
        .section { padding: 60px 0; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 50px; align-items: center; }
        
        .content-box h2 { color: var(--primary); font-size: 2rem; margin-bottom: 20px; position: relative; }
        .content-box h2::after { content: ''; display: block; width: 50px; height: 4px; background: var(--primary); margin-top: 10px; }

        /* Cards */
        .mission-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; margin-top: 40px; }
        .info-card { 
            background: var(--white); 
            padding: 30px; 
            border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: 0.3s;
            border-bottom: 4px solid transparent;
        }
        .info-card:hover { transform: translateY(-10px); border-color: var(--primary); }
        .info-card i { font-size: 2.5rem; color: var(--primary); margin-bottom: 20px; }
        .info-card h3 { margin-bottom: 15px; color: var(--dark); }

        /* Footer Style */
        footer { background: var(--dark); color: #94a3b8; padding: 50px 0; text-align: center; margin-top: 40px; }
        .highlight { color: var(--primary); font-weight: 700; }

        @media (max-width: 768px) {
            .grid { grid-template-columns: 1fr; text-align: center; }
            .content-box h2::after { margin: 10px auto; }
        }
    </style>
</head>
<body>

<header class="about-hero">
    <div class="container">
        <h1>About MediConnect</h1>
        <p>Modernizing healthcare accessibility through innovation and real-world technology solutions.</p>
    </div>
</header>

<main class="container">
    <section class="section grid">
        <div class="content-box">
            <h2>Our Vision</h2>
            <p>We all need to visit doctors at some point—whether in emergencies or for routine check-ups. However, patients are often forced to physically visit clinics or wait in long queues just for an appointment.</p>
            <p><span class="highlight">MediConnect Group</span>, with years of experience in medical services, addresses this challenge by creating a centralized digital bridge between specialist doctors and patients.</p>
        </div>
        <div>
            <img src="https://images.unsplash.com/photo-1516549655169-df83a0774514?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="Healthcare" style="width:100%; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
        </div>
    </section>

    <section class="section">
        <div style="text-align: center; margin-bottom: 50px;">
            <h2>The eProjects Initiative</h2>
            <p style="max-width: 800px; margin: auto;">This application is a building block of our hands-on learning program, designed to solve real-life problems using practical tools.</p>
        </div>

        <div class="mission-grid">
            <div class="info-card">
                <i class="fas fa-search-location"></i>
                <h3>Smart Search</h3>
                <p>Allowing individuals to find specialist doctors based on location and specialization instantly.</p>
            </div>
            <div class="info-card">
                <i class="fas fa-calendar-check"></i>
                <h3>Easy Booking</h3>
                <p>Providing a seamless ability to book, reschedule, or cancel appointments online without the wait.</p>
            </div>
            <div class="info-card">
                <i class="fas fa-database"></i>
                <h3>Centralized Records</h3>
                <p>Maintaining secure and accessible information for both doctors and patients for better care coordination.</p>
            </div>
            <div class="info-card">
                <i class="fas fa-book-medical"></i>
                <h3>Medical Resources</h3>
                <p>Hosting valuable medical knowledge resources and health handbooks for the entire community.</p>
            </div>
        </div>
    </section>

    <section class="section" style="background: #fff; border-radius: 20px; padding: 40px; margin-bottom: 60px;">
        <div class="grid">
            <div style="text-align: center;">
                <i class="fas fa-laptop-code" style="font-size: 5rem; color: #eee;"></i>
            </div>
            <div class="content-box">
                <h2>Our Approach</h2>
                <p>Rather than focusing on teaching specific software, our program aims to present <span class="highlight">real-world scenarios</span> that enable students to create practical applications using available tools.</p>
                <ul style="list-style: none; padding: 0;">
                    <li style="margin-bottom: 10px;"><i class="fas fa-check-circle" style="color: var(--primary);"></i> Hands-on experience on real-life projects.</li>
                    <li style="margin-bottom: 10px;"><i class="fas fa-check-circle" style="color: var(--primary);"></i> Strong foundations through pre-project revision.</li>
                    <li style="margin-bottom: 10px;"><i class="fas fa-check-circle" style="color: var(--primary);"></i> Faculty-assisted laboratory implementation.</li>
                </ul>
            </div>
        </div>
    </section>
</main>

<footer>
    <div class="container">
        <p style="font-weight: 800; font-size: 20px; color: white; margin-bottom: 10px;">MediConnect</p>
        <p>For any queries related to the project, reach out to the <span style="color:white;">eProjects Team</span>.</p>
        <p>&copy; 2026 Smart Healthcare Connection Platform. All rights reserved.</p>
    </div>
</footer>

</body>
</html>