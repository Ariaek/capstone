<?php
session_start();

if (!isset($_SESSION['role'])) {
    header('Location: /PTMS_CAPS/auth/login.php');
    exit;
}

if ($_SESSION['role'] === 'admin') {
    header('Location: /PTMS_CAPS/dashboard/admin_dashboard.php');
    exit;
}

if ($_SESSION['role'] === 'training_officer') {
    header('Location: /PTMS_CAPS/dashboard/trainer_dashboard.php');
    exit;
}

if ($_SESSION['role'] === 'trainee') {
    header('Location: /PTMS_CAPS/modules/my_attendance.php');
    exit;
}

header('Location: /PTMS_CAPS/auth/login.php');
exit;