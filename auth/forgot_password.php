<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/send_mail.php';

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $identity = trim($_POST['identity'] ?? '');

    if ($identity === '') {
        $error = "Enter username or email.";
    } else {

        // Detect if input is email or username
        if (filter_var($identity, FILTER_VALIDATE_EMAIL)) {
            $stmt = $conn->prepare("
                SELECT id, full_name, email, is_active
                FROM users
                WHERE email = ?
                LIMIT 1
            ");
            $stmt->bind_param("s", $identity);
        } else {
            $stmt = $conn->prepare("
                SELECT id, full_name, email, is_active
                FROM users
                WHERE username = ?
                LIMIT 1
            ");
            $stmt->bind_param("s", $identity);
        }

        $stmt->execute();
        $res = $stmt->get_result();

        if ($user = $res->fetch_assoc()) {

            if ((int)$user['is_active'] !== 1) {
                $error = "Account inactive.";
            } else {

                $userId = (int)$user['id'];

                // Generate 6-digit code
                $code = (string)rand(100000, 999999);
                $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

                // Delete old reset codes safely
                $stmtDel = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
                $stmtDel->bind_param("i", $userId);
                $stmtDel->execute();
                $stmtDel->close();

                // Insert new reset code
                $stmt2 = $conn->prepare("
                    INSERT INTO password_resets (user_id, reset_code, expires_at, used)
                    VALUES (?, ?, ?, 0)
                ");
                $stmt2->bind_param("iss", $userId, $code, $expires);
                $stmt2->execute();
                $stmt2->close();

                // Send email to the matched user's email
                $subject = "PTMS Reset Code";
                $body = "
                    <h2>Password Reset Code</h2>
                    <p>Your code is:</p>
                    <h1>{$code}</h1>
                    <p>Expires in 10 minutes.</p>
                ";

                $mailResult = send_ptms_mail($user['email'], $user['full_name'], $subject, $body);

                if (!$mailResult['success']) {
                    $error = "Failed to send email: " . $mailResult['message'];
                } else {
                    $_SESSION['reset_user'] = $userId;
                    header("Location: verify_code.php");
                    exit();
                }
            }

        } else {
            $error = "User not found.";
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:'Inter',sans-serif;
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    background:linear-gradient(135deg,#eef4ff,#e8f0ff);
    padding:20px;
}

.card{
    width:100%;
    max-width:420px;
    background:#fff;
    border-radius:24px;
    padding:40px;
    box-shadow:0 25px 60px rgba(18,62,130,0.12);
    border:1px solid #dbe7ff;
    text-align:center;
}

.title{
    font-size:28px;
    font-weight:800;
    color:#123e82;
    margin-bottom:8px;
}

.subtitle{
    font-size:14px;
    color:#6b7280;
    margin-bottom:25px;
}

.input-group{
    text-align:left;
    margin-bottom:18px;
}

.input-group label{
    font-size:14px;
    font-weight:600;
    color:#374151;
    display:block;
    margin-bottom:6px;
}

.input-group input{
    width:100%;
    height:48px;
    border-radius:12px;
    border:1px solid #d1d5db;
    padding:0 12px;
    font-size:14px;
}

.input-group input:focus{
    border-color:#123e82;
    outline:none;
    box-shadow:0 0 0 3px rgba(18,62,130,0.1);
}

.btn{
    width:100%;
    height:48px;
    border:none;
    border-radius:12px;
    background:#123e82;
    color:#fff;
    font-size:15px;
    font-weight:700;
    cursor:pointer;
    margin-top:10px;
    transition:0.2s ease;
}

.btn:hover{
    background:#0f356f;
}

.error{
    background:#fef2f2;
    color:#b91c1c;
    padding:12px;
    border-radius:10px;
    margin-bottom:15px;
    border:1px solid #fecaca;
    text-align:left;
    font-size:14px;
}

.success{
    background:#ecfdf3;
    color:#166534;
    padding:12px;
    border-radius:10px;
    margin-bottom:15px;
    border:1px solid #bbf7d0;
    text-align:left;
    font-size:14px;
}

.links{
    margin-top:18px;
    font-size:14px;
}

.links a{
    color:#123e82;
    text-decoration:none;
    font-weight:600;
}

.links a:hover{
    text-decoration:underline;
}
</style>
</head>
<body>

<div class="card">
    <div class="title">Forgot Password</div>
    <div class="subtitle">Enter your account to receive a verification code</div>

    <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if (!empty($message)): ?>
        <div class="success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="input-group">
            <label for="identity">Username or Email</label>
            <input
                type="text"
                id="identity"
                name="identity"
                placeholder="Enter username or email"
                value="<?= isset($_POST['identity']) ? htmlspecialchars($_POST['identity'], ENT_QUOTES, 'UTF-8') : '' ?>"
                required
            >
        </div>

        <button class="btn" type="submit">Send Code</button>
    </form>

    <div class="links">
        <a href="/PTMS_CAPS/auth/login.php">← Back to Login</a>
    </div>
</div>

</body>
</html>