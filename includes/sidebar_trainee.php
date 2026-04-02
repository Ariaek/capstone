<?php
$currentPage = basename($_SERVER['PHP_SELF']);
function isTraineeActive($fileNames, $currentPage) {
    if (is_array($fileNames)) {
        return in_array($currentPage, $fileNames, true) ? 'active' : '';
    }
    return $fileNames === $currentPage ? 'active' : '';
}
?>
<aside class="sidebar">
    <div class="brand">PTMS Trainee</div>
    <a href="/PTMS_CAPS/dashboard/trainee_dashboard.php" class="<?= isTraineeActive('trainee_dashboard.php', $currentPage); ?>">Dashboard</a>
    <a href="/PTMS_CAPS/modules/trainee_trainings.php" class="<?= isTraineeActive('trainee_trainings.php', $currentPage); ?>">My Trainings</a>
    <a href="/PTMS_CAPS/modules/trainee_attendance.php" class="<?= isTraineeActive('trainee_attendance.php', $currentPage); ?>">My Attendance</a>
    <a href="/PTMS_CAPS/modules/trainee_history.php" class="<?= isTraineeActive('trainee_history.php', $currentPage); ?>">Attendance History</a>
    <a href="/PTMS_CAPS/modules/trainee_evaluations.php" class="<?= isTraineeActive('trainee_evaluations.php', $currentPage); ?>">My Evaluations</a>
    <a href="/PTMS_CAPS/modules/trainee_notifications.php" class="<?= isTraineeActive('trainee_notifications.php', $currentPage); ?>">My Notifications</a>
    <a href="/PTMS_CAPS/modules/trainee_profile.php" class="<?= isTraineeActive('trainee_profile.php', $currentPage); ?>">My Profile</a>
    <a href="/PTMS_CAPS/auth/logout.php">Logout</a>
</aside>
<main class="main">
