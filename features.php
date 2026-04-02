<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Features | PTMS</title>
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
                        <a href="/PTMS_CAPS/about.php">About</a>
                        <a href="/PTMS_CAPS/features.php" class="active">Features</a>
                        <a href="/PTMS_CAPS/contact.php">Contact Us</a>
                    </nav>
                    <a href="/PTMS_CAPS/auth/login.php" class="login-btn">Login</a>
                </div>
            </header>

            <section class="hero">
                <div class="hero-inner">
                    <div class="eyebrow">System modules</div>
                    <h2 class="hero-title">Core features that support <span>training management</span>.</h2>
                    <p class="hero-text">
                        The system is built to handle user administration, training schedules, attendance recording,
                        records management, and reporting in a responsive web environment.
                    </p>
                </div>
            </section>

            <section class="content-section">
                <div class="cards-grid-3">
                    <div class="info-card">
                        <h3>User Management</h3>
                        <p>Manager-admin can create and manage user accounts with controlled system access.</p>
                    </div>
                    <div class="info-card">
                        <h3>Training Scheduling</h3>
                        <p>Create sessions for orientation, compliance, refresher, and skills development.</p>
                    </div>
                    <div class="info-card">
                        <h3>Time In / Time Out</h3>
                        <p>Trainees can record actual attendance timestamps for their assigned training sessions.</p>
                    </div>
                    <div class="info-card">
                        <h3>Centralized Records</h3>
                        <p>Training history, attendance logs, and participant records are stored in MySQL.</p>
                    </div>
                    <div class="info-card">
                        <h3>Reports</h3>
                        <p>Management can view attendance and training summaries without manual consolidation.</p>
                    </div>
                    <div class="info-card">
                        <h3>Responsive Interface</h3>
                        <p>The interface adjusts to different screen sizes for desktop and mobile web access.</p>
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