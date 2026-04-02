<?php
session_start();
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = 'Your message has been submitted successfully.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact | PTMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/PTMS_CAPS/assets/css/public-ui.css">
    <style>
        .contact-grid{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:28px;
            align-items:start;
        }

        .contact-info-card,
        .contact-form-card{
            background:#ffffff;
            border:1px solid #e5eefc;
            border-radius:22px;
            padding:28px;
            box-shadow:0 20px 40px rgba(18,62,130,0.08);
        }

        .contact-info-card h3,
        .contact-form-card h3{
            margin:0 0 14px;
            font-size:24px;
            color:#123e82;
        }

        .contact-info-card p{
            margin:0 0 14px;
            color:#5b6472;
            line-height:1.7;
        }

        .contact-list{
            display:grid;
            gap:14px;
            margin-top:22px;
        }

        .contact-item{
            background:#f8fbff;
            border:1px solid #dbe7ff;
            border-radius:16px;
            padding:16px 18px;
        }

        .contact-item strong{
            display:block;
            color:#123e82;
            margin-bottom:4px;
            font-size:14px;
        }

        .contact-item span{
            color:#4b5563;
            font-size:14px;
            line-height:1.6;
        }

        .contact-form-card .form-group{
            margin-bottom:18px;
        }

        .contact-form-card label{
            display:block;
            margin-bottom:8px;
            font-weight:600;
            color:#1f2937;
        }

        .contact-form-card input,
        .contact-form-card textarea{
            width:100%;
            border:1px solid #d1d5db;
            border-radius:14px;
            padding:14px 15px;
            font-size:14px;
            outline:none;
            transition:0.2s ease;
            font-family:inherit;
            background:#fff;
        }

        .contact-form-card input:focus,
        .contact-form-card textarea:focus{
            border-color:#123e82;
            box-shadow:0 0 0 4px rgba(18,62,130,0.10);
        }

        .contact-form-card textarea{
            min-height:140px;
            resize:vertical;
        }

        .contact-submit{
            width:100%;
        }

        @media (max-width: 900px){
            .contact-grid{
                grid-template-columns:1fr;
            }
        }
    </style>
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
                        <nav class="nav-links">
    <a href="/PTMS_CAPS/index.php">Home</a>
    <a href="/PTMS_CAPS/about.php">About</a>
    <a href="/PTMS_CAPS/features.php">Features</a>
    <a href="/PTMS_CAPS/contact.php" class="active">Contact Us</a>
</nav>
                    
                    <a href="/PTMS_CAPS/auth/login.php" class="login-btn">Login</a>
                </div>
            </header>

            <section class="hero">
                <div class="hero-inner">
                    <div class="eyebrow">Get in touch</div>
                    <h2 class="hero-title">Contact the <span>PTMS support team</span>.</h2>
                    <p class="hero-text">
                        Send your inquiries, coordination requests, or support concerns regarding the
                        PAG-ASA Training Management System through this contact page.
                    </p>
                </div>
            </section>

            <section class="content-section">
                <div class="contact-grid">
                    <div class="contact-info-card">
                        <h3>Contact Information</h3>
                        <p>
                            You may use this page to communicate with the PTMS support team for general inquiries,
                            training coordination, technical concerns, or assistance with account-related issues.
                        </p>

                        <div class="contact-list">
                            <div class="contact-item">
                                <strong>Email Address</strong>
                                <span>ptms.support@gmail.com</span>
                            </div>

                            <div class="contact-item">
                                <strong>Phone Number</strong>
                                <span>+63 912 345 6789</span>
                            </div>

                            <div class="contact-item">
                                <strong>Office</strong>
                                <span>PAG-ASA Training Office</span>
                            </div>

                            <div class="contact-item">
                                <strong>Support Hours</strong>
                                <span>Monday to Friday, 8:00 AM to 5:00 PM</span>
                            </div>
                        </div>
                    </div>

                    <div class="contact-form-card">
                        <h3>Send a Message</h3>

                        <?php if ($success !== ''): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" required>
                            </div>

                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" required>
                            </div>

                            <div class="form-group">
                                <label for="subject">Subject</label>
                                <input type="text" id="subject" name="subject" required>
                            </div>

                            <div class="form-group">
                                <label for="message">Message</label>
                                <textarea id="message" name="message" required></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary contact-submit">Send Message</button>
                        </form>
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