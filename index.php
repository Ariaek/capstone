<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: /PTMS_CAPS/dashboard/router.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PTMS</title>
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
                        <a href="/PTMS_CAPS/index.php" class="active">Home</a>
                        <a href="/PTMS_CAPS/about.php">About</a>
                        <a href="/PTMS_CAPS/features.php">Features</a>
                        <a href="/PTMS_CAPS/contact.php">Contact Us</a>
                    </nav>
                    <a href="/PTMS_CAPS/auth/login.php" class="login-btn">Login</a>
                </div>
            </header>

            <section class="hero">
                <div class="hero-inner">
                    <div class="eyebrow">Training administration made accessible</div>
                    <h2 class="hero-title">Manage training activities with a <span>clear, organized, and responsive system</span>.</h2>
                    <p class="hero-text">
                        PTMS is a web-based platform built to support structured training management for administrators,
                        training officers, and trainees. It brings together user administration, training scheduling,
                        participant assignment, attendance monitoring, and reporting in one environment that can be
                        accessed on desktop and mobile browsers.
                    </p>
                    <p class="hero-text">
                        The system helps streamline daily operations by giving each role the right tools: administrators
                        manage accounts and oversee records, training officers organize sessions and monitor participation,
                        and trainees record their own attendance through time in and time out actions during assigned
                        training schedules.
                    </p>

                    <div class="action-row">
                        <a href="/PTMS_CAPS/auth/login.php" class="btn btn-primary">Access System</a>
                        <a href="/PTMS_CAPS/features.php" class="btn btn-secondary">Explore Features</a>
                        <a href="/PTMS_CAPS/contact.php" class="btn btn-secondary">Contact Us</a>
                    </div>
                </div>
            </section>

            <section class="content-section">
                <div class="cards-grid-3">
                    <div class="info-card">
                        <h3>Training Administration</h3>
                        <p>
                            Organize training activities from one place, including session creation, trainer assignment,
                            schedule management, venue details, participant enrollment, and monitoring of attendance records.
                        </p>
                    </div>
                    <div class="info-card">
                        <h3>Role-Based Access</h3>
                        <p>
                            The platform supports distinct user roles for manager-administrator, training officer, and trainee,
                            allowing each user to access functions that match their responsibilities within the training workflow.
                        </p>
                    </div>
                    <div class="info-card">
                        <h3>Attendance Recording</h3>
                        <p>
                            Trainees can record attendance directly in the system through time in and time out actions, while
                            training officers and administrators can review attendance history and summaries when needed.
                        </p>
                    </div>
                </div>

                <div class="cards-grid-2">
                    <div class="info-card">
                        <h3>Responsive Web Access</h3>
                        <p>
                            PTMS is designed to adapt to different screen sizes, making it accessible on desktop computers,
                            tablets, and mobile phone browsers for convenient use in office and field settings.
                        </p>
                    </div>
                    <div class="info-card">
                        <h3>Centralized Information</h3>
                        <p>
                            User accounts, assigned trainings, attendance logs, and reports are organized in a single system
                            to support easier retrieval of records and more consistent training administration.
                        </p>
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