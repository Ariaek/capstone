<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login(['training_officer']);

$pageTitle = 'Officer Reports';
$userId = (int)$_SESSION['user_id'];

$totalTrainings = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM trainings
    WHERE trainer_user_id = $userId
"))['total'] ?? 0;

$totalParticipants = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(DISTINCT tp.user_id) AS total
    FROM training_participants tp
    INNER JOIN trainings t ON t.id = tp.training_id
    WHERE t.trainer_user_id = $userId
"))['total'] ?? 0;

$totalAttendance = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM attendance_logs a
    INNER JOIN trainings t ON t.id = a.training_id
    WHERE t.trainer_user_id = $userId
"))['total'] ?? 0;

$totalApproved = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM attendance_logs a
    INNER JOIN trainings t ON t.id = a.training_id
    WHERE t.trainer_user_id = $userId AND a.approval_status = 'Approved'
"))['total'] ?? 0;

$totalRejected = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM attendance_logs a
    INNER JOIN trainings t ON t.id = a.training_id
    WHERE t.trainer_user_id = $userId AND a.approval_status = 'Rejected'
"))['total'] ?? 0;

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_officer.php';
?>

<div class="topbar">
    <div class="page-title">
        <h1>Reports</h1>
        <p>Summary analytics for your handled trainings, participants, and attendance reviews.</p>
    </div>
</div>

<div class="grid-4">
    <div class="stat-card">
        <h3>Total Trainings</h3>
        <strong><?= (int)$totalTrainings; ?></strong>
        <span>Handled sessions</span>
    </div>
    <div class="stat-card">
        <h3>Total Participants</h3>
        <strong><?= (int)$totalParticipants; ?></strong>
        <span>Assigned trainees</span>
    </div>
    <div class="stat-card">
        <h3>Approved Attendance</h3>
        <strong><?= (int)$totalApproved; ?></strong>
        <span>Validated entries</span>
    </div>
    <div class="stat-card">
        <h3>Rejected Attendance</h3>
        <strong><?= (int)$totalRejected; ?></strong>
        <span>Rejected entries</span>
    </div>
</div>

<div class="content-card section-space">
    <h2>Officer Report Summary</h2>
    <p style="line-height:1.7; color:#4b5563;">
        This page summarizes the training sessions, trainee participation, and attendance review outcomes
        under the currently logged-in training officer.
    </p>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>