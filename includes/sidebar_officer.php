<?php
$currentPage = basename($_SERVER['PHP_SELF']);
function isOfficerActive($fileNames, $currentPage) {
    if (is_array($fileNames)) {
        return in_array($currentPage, $fileNames, true) ? 'active' : '';
    }
    return $fileNames === $currentPage ? 'active' : '';
}
?>
<aside class="sidebar">
    <div class="brand">PTMS Officer</div>
    <a href="/PTMS_CAPS/dashboard/trainer_dashboard.php" class="<?= isOfficerActive('trainer_dashboard.php', $currentPage); ?>">Dashboard</a>
    <a href="/PTMS_CAPS/modules/officer_trainings.php" class="<?= isOfficerActive('officer_trainings.php', $currentPage); ?>">My Trainings</a>
    <a href="/PTMS_CAPS/modules/officer_trainees.php" class="<?= isOfficerActive('officer_trainees.php', $currentPage); ?>">My Trainees</a>
    <a href="/PTMS_CAPS/modules/officer_attendance.php" class="<?= isOfficerActive('officer_attendance.php', $currentPage); ?>">Attendance Monitoring</a>
    <a href="/PTMS_CAPS/modules/officer_evaluations.php" class="<?= isOfficerActive('officer_evaluations.php', $currentPage); ?>">Evaluations</a>
    <a href="/PTMS_CAPS/modules/officer_certificates.php" class="<?= isOfficerActive('officer_certificates.php', $currentPage); ?>">Certificates</a>
    <a href="/PTMS_CAPS/modules/officer_notifications.php" class="<?= isOfficerActive('officer_notifications.php', $currentPage); ?>">Notifications</a>
    <a href="/PTMS_CAPS/modules/officer_reports.php" class="<?= isOfficerActive('officer_reports.php', $currentPage); ?>">Reports</a>
    <a href="/PTMS_CAPS/auth/logout.php">Logout</a>
</aside>
<main class="main">
