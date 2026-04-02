<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About | PTMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/PTMS_CAPS/assets/css/public-ui.css">
</head>
<body>
    <div class="page-shell">
        <div class="site-frame">
            <header class="top-nav">
                <div class="brand">
                    <div class="brand-badge">PT</div>
                    <div class="brand-text">
                        <h1>PTMS</h1>
                        <p>PAG-ASA Training Management System</p>
                    </div>
                </div>

                <div class="nav-right">
                    <nav class="nav-links">
                        <a href="/PTMS_CAPS/index.php">Home</a>
                        <a href="/PTMS_CAPS/about.php" class="active">About</a>
                        <a href="/PTMS_CAPS/features.php">Features</a>
                        <a href="/PTMS_CAPS/contact.php">Contact Us</a>
                    </nav>
                    <a href="/PTMS_CAPS/auth/login.php" class="login-btn">Login</a>
                </div>
            </header>

            <section class="hero">
                <div class="hero-inner">
                    <div class="eyebrow">About the system</div>
                    <h2 class="hero-title">A centralized platform for <span>training operations</span>.</h2>
                    <p class="hero-text">
                        PTMS is designed to organize and simplify training administration by handling users,
                        schedules, attendance monitoring, and reporting in one integrated web-based system.
                    </p>
                </div>
            </section>

            <section class="content-section">
                <h3 class="section-title">What the system does</h3>
                <p class="section-text">
                    The system supports administrators, training officers, and trainees through a role-based workflow.
                    Managers oversee records and reporting, training officers handle scheduling and participant assignments,
                    while trainees record attendance for their assigned training sessions.
                </p>

                <div class="cards-grid-2">
                    <div class="info-card">
                        <h3>Centralized Management</h3>
                        <p>All users, training schedules, participant lists, and attendance data are stored in one system for easier monitoring and retrieval.</p>
                    </div>
                    <div class="info-card">
                        <h3>Live Attendance Recording</h3>
                        <p>Trainees can record their actual attendance using time in and time out buttons linked directly to the database.</p>
                    </div>
                    <div class="info-card">
                        <h3>Structured User Roles</h3>
                        <p>The system separates access for manager-administrator, training officer, and trainee to maintain proper control and workflow.</p>
                    </div>
                    <div class="info-card">
                        <h3>Accessible on Different Devices</h3>
                        <p>The website adjusts across desktops, tablets, and mobile browsers to support flexible access.</p>
                    </div>
                </div>
            </section>

            <div class="page-footer">
                PTMS — PAG-ASA Training Management System
            </div>
        </div>
    </div>
</body>
</html>