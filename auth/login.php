<?php
session_start();
require_once __DIR__ . '/../config/db.php';

/*
|--------------------------------------------------------------------------
| If already logged in, send user to the correct dashboard
|--------------------------------------------------------------------------
*/
if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header("Location: /PTMS_CAPS/dashboard/admin_dashboard.php");
            exit();

        case 'training_officer':
            header("Location: /PTMS_CAPS/dashboard/trainer_dashboard.php");
            exit();

        case 'trainee':
            header("Location: /PTMS_CAPS/dashboard/trainee_dashboard.php");
            exit();

        case 'employee':
            header("Location: /PTMS_CAPS/dashboard/employee_dashboard.php");
            exit();

        default:
            session_unset();
            session_destroy();
            break;
    }
}

$error = '';

if (isset($_GET['error']) && $_GET['error'] === 'unauthorized') {
    $error = 'You are not authorized to access that page.';
}

/*
|--------------------------------------------------------------------------
| Handle login
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember_me']);

    if ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } else {
        if (!isset($conn) || !($conn instanceof mysqli)) {
            $error = 'Database connection error.';
        } else {
            $stmt = $conn->prepare("
                SELECT id, employee_no, full_name, username, email, password_hash, role, is_active
                FROM users
                WHERE username = ?
                LIMIT 1
            ");

            if ($stmt) {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result && $result->num_rows === 1) {
                    $user = $result->fetch_assoc();

                    if ((int)$user['is_active'] !== 1) {
                        $error = 'Your account is inactive.';
                    } elseif (!password_verify($password, $user['password_hash'])) {
                        $error = 'Invalid username or password.';
                    } else {
                        session_regenerate_id(true);

                        $_SESSION['user_id'] = (int)$user['id'];
                        $_SESSION['employee_no'] = $user['employee_no'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['role'] = $user['role'];

                        if ($remember) {
                            setcookie('ptms_username', $user['username'], time() + (86400 * 30), "/");
                        } else {
                            if (isset($_COOKIE['ptms_username'])) {
                                setcookie('ptms_username', '', time() - 3600, "/");
                            }
                        }

                        switch ($user['role']) {
                            case 'admin':
                                header("Location: /PTMS_CAPS/dashboard/admin_dashboard.php");
                                exit();

                            case 'training_officer':
                                header("Location: /PTMS_CAPS/dashboard/trainer_dashboard.php");
                                exit();

                            case 'trainee':
                                header("Location: /PTMS_CAPS/dashboard/trainee_dashboard.php");
                                exit();

                            case 'employee':
                                header("Location: /PTMS_CAPS/dashboard/employee_dashboard.php");
                                exit();

                            default:
                                session_unset();
                                session_destroy();
                                $error = 'Unknown user role.';
                                break;
                        }
                    }
                } else {
                    $error = 'Invalid username or password.';
                }

                $stmt->close();
            } else {
                $error = 'Database error. Please try again.';
            }
        }
    }
}

$savedUsername = $_POST['username'] ?? ($_COOKIE['ptms_username'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PTMS Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        body{
            font-family:'Inter', Arial, sans-serif;
            min-height:100vh;
            background:linear-gradient(135deg, #eef4ff 0%, #f8fbff 45%, #e8f0ff 100%);
            color:#1f2937;
        }

        .page{
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:40px 20px;
        }

        .login-shell{
            width:100%;
            max-width:1100px;
            display:grid;
            grid-template-columns:1.1fr 0.9fr;
            background:#ffffff;
            border-radius:28px;
            overflow:hidden;
            box-shadow:0 25px 70px rgba(18, 62, 130, 0.12);
            border:1px solid #dbe7ff;
        }

        .login-left{
            background:linear-gradient(135deg, #123e82 0%, #1f5fbf 100%);
            color:#fff;
            padding:56px 48px;
            display:flex;
            flex-direction:column;
            justify-content:center;
            position:relative;
        }

        .login-left::before,
        .login-left::after{
            content:"";
            position:absolute;
            border-radius:50%;
            background:rgba(255,255,255,0.08);
        }

        .login-left::before{
            width:220px;
            height:220px;
            top:-70px;
            right:-40px;
        }

        .login-left::after{
            width:180px;
            height:180px;
            bottom:-50px;
            left:-40px;
        }

        .brand{
            position:relative;
            z-index:1;
            display:flex;
            align-items:center;
            gap:14px;
            margin-bottom:28px;
        }

        .brand-badge{
            width:58px;
            height:58px;
            border-radius:18px;
            background:rgba(255,255,255,0.18);
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:20px;
            font-weight:800;
            letter-spacing:1px;
        }

        .brand-text h1{
            font-size:28px;
            font-weight:800;
            margin-bottom:4px;
        }

        .brand-text p{
            font-size:14px;
            color:rgba(255,255,255,0.85);
            line-height:1.5;
        }

        .hero-copy{
            position:relative;
            z-index:1;
        }

        .hero-copy .eyebrow{
            display:inline-block;
            padding:8px 14px;
            border-radius:999px;
            background:rgba(255,255,255,0.14);
            font-size:12px;
            font-weight:700;
            margin-bottom:18px;
        }

        .hero-copy h2{
            font-size:38px;
            line-height:1.18;
            font-weight:800;
            margin-bottom:18px;
        }

        .hero-copy h2 span{
            color:#dbeafe;
        }

        .hero-copy p{
            font-size:15px;
            line-height:1.8;
            color:rgba(255,255,255,0.90);
            max-width:480px;
            margin-bottom:24px;
        }

        .feature-list{
            position:relative;
            z-index:1;
            display:grid;
            gap:12px;
            margin-top:10px;
        }

        .feature-item{
            background:rgba(255,255,255,0.10);
            border:1px solid rgba(255,255,255,0.12);
            padding:14px 16px;
            border-radius:14px;
            font-size:14px;
            line-height:1.6;
        }

        .login-right{
            padding:56px 42px;
            display:flex;
            align-items:center;
            justify-content:center;
            background:#ffffff;
        }

        .form-wrap{
            width:100%;
            max-width:390px;
        }

        .back-link{
            display:inline-flex;
            align-items:center;
            gap:8px;
            text-decoration:none;
            color:#123e82;
            font-size:14px;
            font-weight:600;
            margin-bottom:20px;
        }

        .form-head{
            margin-bottom:24px;
        }

        .form-head h3{
            font-size:30px;
            color:#123e82;
            margin-bottom:8px;
            font-weight:800;
        }

        .form-head p{
            font-size:14px;
            color:#6b7280;
            line-height:1.7;
        }

        .error-box{
            margin-bottom:16px;
            padding:14px 16px;
            border-radius:14px;
            background:#fef2f2;
            color:#b91c1c;
            border:1px solid #fecaca;
            font-size:14px;
        }

        .form-group{
            margin-bottom:16px;
        }

        .form-group label{
            display:block;
            margin-bottom:8px;
            font-size:14px;
            font-weight:700;
            color:#374151;
        }

        .form-group input{
            width:100%;
            height:48px;
            border:1px solid #d1d5db;
            border-radius:14px;
            padding:0 14px;
            font-size:14px;
            outline:none;
            transition:0.2s ease;
            background:#fff;
        }

        .form-group input:focus{
            border-color:#123e82;
            box-shadow:0 0 0 3px rgba(18,62,130,0.10);
        }

        .options{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:12px;
            margin:8px 0 22px;
            flex-wrap:wrap;
        }

        .remember{
            display:flex;
            align-items:center;
            gap:8px;
            font-size:14px;
            color:#4b5563;
        }

        .remember input{
            width:16px;
            height:16px;
            accent-color:#123e82;
        }

        .forgot{
            text-decoration:none;
            font-size:14px;
            font-weight:600;
            color:#123e82;
        }

        .forgot:hover{
            text-decoration:underline;
        }

        .login-btn{
            width:100%;
            height:50px;
            border:none;
            border-radius:14px;
            background:#123e82;
            color:#fff;
            font-size:15px;
            font-weight:700;
            cursor:pointer;
            transition:0.2s ease;
            box-shadow:0 12px 24px rgba(18,62,130,0.18);
        }

        .login-btn:hover{
            background:#0f356f;
            transform:translateY(-1px);
        }

        .bottom-note{
            margin-top:18px;
            text-align:center;
            font-size:13px;
            color:#6b7280;
            line-height:1.6;
        }

        .bottom-note a{
            color:#123e82;
            font-weight:700;
            text-decoration:none;
        }

        .bottom-note a:hover{
            text-decoration:underline;
        }

        @media (max-width: 920px){
            .login-shell{
                grid-template-columns:1fr;
            }

            .login-left{
                padding:42px 28px;
            }

            .login-right{
                padding:38px 24px 42px;
            }

            .hero-copy h2{
                font-size:30px;
            }
        }

        @media (max-width: 520px){
            .page{
                padding:18px;
            }

            .login-left,
            .login-right{
                padding-left:20px;
                padding-right:20px;
            }

            .form-head h3{
                font-size:26px;
            }

            .hero-copy h2{
                font-size:26px;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="login-shell">
            <div class="login-left">
                <div class="brand">
                    <div class="brand-badge">PT</div>
                    <div class="brand-text">
                        <h1>PTMS</h1>
                        <p>PAG-ASA sTraining Management System</p>
                    </div>
                </div>

                <div class="hero-copy">
                    <div class="eyebrow">Secure role-based access</div>
                    <h2>Welcome back to the <span>PTMS portal</span>.</h2>
                    <p>
                        Sign in to manage trainings, monitor attendance, review reports,
                        and access your role-based dashboard in one organized system.
                    </p>
                </div>

                <div class="feature-list">
                    <div class="feature-item">Training administration and scheduling</div>
                    <div class="feature-item">Attendance monitoring with time in and time out</div>
                    <div class="feature-item">Centralized access for admin, officers, employees, and trainees</div>
                </div>
            </div>

            <div class="login-right">
                <div class="form-wrap">
                    <a href="/PTMS_CAPS/index.php" class="back-link">← Back to Home</a>

                    <div class="form-head">
                        <h3>Login</h3>
                        <p>Enter your username and password to continue to your PTMS dashboard.</p>
                    </div>

                    <?php if ($error !== ''): ?>
                        <div class="error-box"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input
                                type="text"
                                id="username"
                                name="username"
                                placeholder="Enter your username"
                                value="<?= htmlspecialchars($savedUsername, ENT_QUOTES, 'UTF-8'); ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Enter your password"
                                required
                            >
                        </div>

                        <div class="options">
                            <label class="remember">
                                <input type="checkbox" name="remember_me" <?= isset($_COOKIE['ptms_username']) ? 'checked' : ''; ?>>
                                <span>Remember me</span>
                            </label>

                            <a href="/PTMS_CAPS/auth/forgot_password.php" class="forgot">
                                Forgot password?
                            </a>
                        </div>

                        <button type="submit" class="login-btn">Sign In</button>
                    </form>

                    <div class="bottom-note">
                        PAG ASA Training Management System<br>
                        <a href="/PTMS_CAPS/index.php">Return to homepage</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>