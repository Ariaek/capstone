<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login(array $allowed_roles = []): void {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header("Location: /PTMS_CAPS/auth/login.php");
        exit();
    }

    if (!empty($allowed_roles) && !in_array($_SESSION['role'], $allowed_roles, true)) {
        session_unset();
        session_destroy();
        header("Location: /PTMS_CAPS/auth/login.php?error=unauthorized");
        exit();
    }
}