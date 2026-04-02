<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['reset_user'])) {
    header("Location: forgot_password.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $code = implode('', $_POST['code']);
    $userId = $_SESSION['reset_user'];

    $stmt = $conn->prepare("
        SELECT * FROM password_resets
        WHERE user_id=? AND reset_code=? AND used=0
        LIMIT 1
    ");
    $stmt->bind_param("is", $userId, $code);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {

        if (strtotime($row['expires_at']) < time()) {
            $error = "Code expired.";
        } else {
            $_SESSION['verified_reset'] = true;
            header("Location: reset_password.php");
            exit();
        }

    } else {
        $error = "Invalid code.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Verify Code</title>

<style>
body{
    font-family:Arial;
    background:linear-gradient(135deg,#eef4ff,#e8f0ff);
    display:flex;
    align-items:center;
    justify-content:center;
    height:100vh;
}

.card{
    background:#fff;
    padding:40px;
    border-radius:20px;
    width:350px;
    text-align:center;
    box-shadow:0 20px 40px rgba(0,0,0,0.1);
}

h2{
    color:#123e82;
}

.otp{
    display:flex;
    justify-content:space-between;
    margin:20px 0;
}

.otp input{
    width:45px;
    height:55px;
    font-size:22px;
    text-align:center;
    border:2px solid #ccc;
    border-radius:10px;
}

.otp input:focus{
    border-color:#123e82;
    outline:none;
}

button{
    width:100%;
    padding:12px;
    background:#123e82;
    color:#fff;
    border:none;
    border-radius:10px;
    cursor:pointer;
}

.error{
    color:red;
    margin-top:10px;
}
</style>
</head>

<body>

<div class="card">

<h2>Enter Verification Code</h2>
<p>Check your email</p>

<form method="POST">

<div class="otp">
    <input type="text" name="code[]" maxlength="1">
    <input type="text" name="code[]" maxlength="1">
    <input type="text" name="code[]" maxlength="1">
    <input type="text" name="code[]" maxlength="1">
    <input type="text" name="code[]" maxlength="1">
    <input type="text" name="code[]" maxlength="1">
</div>

<button type="submit">Verify</button>

</form>

<div class="error"><?php echo $error; ?></div>

<p style="margin-top:15px;">
    Didn't receive code? <a href="forgot_password.php">Resend</a>
</p>

</div>

<script>
const inputs = document.querySelectorAll('.otp input');

inputs.forEach((input, index) => {

    input.addEventListener('input', (e) => {
        if(e.target.value.length === 1 && index < inputs.length - 1){
            inputs[index + 1].focus();
        }
    });

    input.addEventListener('keydown', (e) => {
        if(e.key === "Backspace" && !input.value && index > 0){
            inputs[index - 1].focus();
        }
    });

});

// paste support
document.addEventListener('paste', (e) => {
    let paste = e.clipboardData.getData('text');
    if(paste.length === 6){
        inputs.forEach((input, i) => {
            input.value = paste[i];
        });
    }
});
</script>

</body>
</html>