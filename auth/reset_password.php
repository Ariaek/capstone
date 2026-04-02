<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['verified_reset']) || !isset($_SESSION['reset_user'])) {
    header("Location: /PTMS_CAPS/auth/forgot_password.php");
    exit();
}

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $userId = (int)$_SESSION['reset_user'];

    if ($pass === '' || $confirm === '') {
        $error = "Fill in all fields.";
    } elseif (strlen($pass) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($pass !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $hash, $userId);
            $stmt->execute();
            $stmt->close();

            $conn->query("UPDATE password_resets SET used = 1 WHERE user_id = $userId");

            unset($_SESSION['reset_user']);
            unset($_SESSION['verified_reset']);

            $message = "Password updated successfully.";
        } else {
            $error = "Unable to update password right now.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | PTMS</title>
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
            display:flex;
            align-items:center;
            justify-content:center;
            padding:24px;
            color:#1f2937;
        }

        .card{
            width:100%;
            max-width:460px;
            background:#fff;
            border-radius:24px;
            padding:38px;
            box-shadow:0 25px 70px rgba(18, 62, 130, 0.12);
            border:1px solid #dbe7ff;
        }

        .title{
            font-size:30px;
            font-weight:800;
            color:#123e82;
            margin-bottom:8px;
            text-align:center;
        }

        .subtitle{
            color:#6b7280;
            font-size:14px;
            line-height:1.7;
            margin-bottom:24px;
            text-align:center;
        }

        .alert{
            padding:14px 16px;
            border-radius:14px;
            margin-bottom:16px;
            font-size:14px;
        }

        .alert-error{
            background:#fef2f2;
            color:#b91c1c;
            border:1px solid #fecaca;
        }

        .alert-success{
            background:#ecfdf3;
            color:#166534;
            border:1px solid #bbf7d0;
        }

        .form-group{
            margin-bottom:18px;
        }

        .form-group label{
            display:block;
            margin-bottom:8px;
            font-size:14px;
            font-weight:700;
            color:#374151;
        }

        .password-wrap{
            position:relative;
        }

        .password-wrap input{
            width:100%;
            height:50px;
            border:1px solid #d1d5db;
            border-radius:14px;
            padding:0 48px 0 14px;
            font-size:14px;
            outline:none;
            background:#fff;
        }

        .password-wrap input:focus{
            border-color:#123e82;
            box-shadow:0 0 0 3px rgba(18,62,130,0.10);
        }

        .toggle-password{
            position:absolute;
            top:50%;
            right:14px;
            transform:translateY(-50%);
            border:none;
            background:none;
            cursor:pointer;
            color:#6b7280;
            font-size:13px;
            font-weight:700;
        }

        .helper{
            margin-top:6px;
            font-size:12px;
            color:#6b7280;
        }

        .btn{
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
            margin-top:6px;
            box-shadow:0 12px 24px rgba(18,62,130,0.18);
        }

        .btn:hover{
            background:#0f356f;
            transform:translateY(-1px);
        }

        .links{
            margin-top:18px;
            text-align:center;
            font-size:14px;
        }

        .links a{
            color:#123e82;
            text-decoration:none;
            font-weight:700;
        }

        .links a:hover{
            text-decoration:underline;
        }

        @media (max-width: 520px){
            .card{
                padding:26px 20px;
            }

            .title{
                font-size:26px;
            }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="title">Reset Password</div>
        <div class="subtitle">
            Create a new password for your PTMS account.
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($message !== ''): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="links">
                <a href="/PTMS_CAPS/auth/login.php">Go to Login</a>
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <div class="password-wrap">
                        <input type="password" id="password" name="password" placeholder="Enter new password" required>
                        <button type="button" class="toggle-password" data-target="password">Show</button>
                    </div>
                    <div class="helper">Use at least 8 characters.</div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="password-wrap">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
                        <button type="button" class="toggle-password" data-target="confirm_password">Show</button>
                    </div>
                </div>

                <button type="submit" class="btn">Update Password</button>
            </form>

            <div class="links">
                <a href="/PTMS_CAPS/auth/login.php">← Back to Login</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.querySelectorAll('.toggle-password').forEach(function(button) {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);

                if (input.type === 'password') {
                    input.type = 'text';
                    this.textContent = 'Hide';
                } else {
                    input.type = 'password';
                    this.textContent = 'Show';
                }
            });
        });
    </script>
</body>
</html>