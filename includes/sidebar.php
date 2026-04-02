<?php
$currentPage = basename($_SERVER['PHP_SELF']);

function isActive($fileName, $currentPage) {
    return $fileName === $currentPage ? 'active' : '';
}
?>

<aside class="sidebar">
    <div class="brand">PTMS Admin</div>

    <a href="/PTMS_CAPS/dashboard/admin_dashboard.php" class="<?= isActive('admin_dashboard.php', $currentPage); ?>">Dashboard</a>
    <a href="/PTMS_CAPS/modules/users.php" class="<?= isActive('users.php', $currentPage); ?>">Manage Users</a>
    <a href="/PTMS_CAPS/modules/trainings.php" class="<?= isActive('trainings.php', $currentPage); ?>">Trainings</a>
    <a href="/PTMS_CAPS/modules/attendance.php" class="<?= isActive('attendance.php', $currentPage); ?>">Attendance</a>
    <a href="/PTMS_CAPS/modules/reports.php" class="<?= isActive('reports.php', $currentPage); ?>">Reports</a>
    <a href="/PTMS_CAPS/auth/logout.php">Logout</a>
</aside>

<main class="main">