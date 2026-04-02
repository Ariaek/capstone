<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login(['trainee']);

$pageTitle = 'My Attendance';
$userId = (int)$_SESSION['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attendance_action'], $_POST['training_id'])) {

    $trainingId = (int)$_POST['training_id'];
    $today = date('Y-m-d');
    $now = date('H:i:s');

    $stmt = $conn->prepare('
        SELECT id, time_in, time_out
        FROM attendance_logs
        WHERE user_id = ? AND training_id = ? AND log_date = ?
        LIMIT 1
    ');
    $stmt->bind_param('iis', $userId, $trainingId, $today);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();

    /* TIME IN */
    if ($_POST['attendance_action'] === 'time_in') {

        if ($existing && !empty($existing['time_in'])) {
            $error = 'You have already timed in for this training today.';
        } else {

            if ($existing) {
                $stmt = $conn->prepare('
                    UPDATE attendance_logs
                    SET time_in = ?, status = ?, approval_status = "Pending"
                    WHERE id = ?
                ');
                $status = 'Present';
                $stmt->bind_param('ssi', $now, $status, $existing['id']);
                $stmt->execute();
            } else {
                $stmt = $conn->prepare('
                    INSERT INTO attendance_logs (training_id, user_id, log_date, time_in, status, approval_status)
                    VALUES (?, ?, ?, ?, ?, "Pending")
                ');
                $status = 'Present';
                $stmt->bind_param('iisss', $trainingId, $userId, $today, $now, $status);
                $stmt->execute();
            }

            // ✅ LOG ACTIVITY
            $logType = 'Attendance';
            $logMessage = 'Timed IN for training ID #' . $trainingId;

            $log = $conn->prepare("
                INSERT INTO trainee_activity_logs (trainee_user_id, activity_type, activity_message)
                VALUES (?, ?, ?)
            ");
            $log->bind_param("iss", $userId, $logType, $logMessage);
            $log->execute();
            $log->close();

            $message = 'Time in recorded successfully.';
        }
    }

    /* TIME OUT */
    if ($_POST['attendance_action'] === 'time_out') {

        if (!$existing || empty($existing['time_in'])) {
            $error = 'You need to time in first before timing out.';
        } elseif (!empty($existing['time_out'])) {
            $error = 'You have already timed out for this training today.';
        } else {

            $stmt = $conn->prepare('
                UPDATE attendance_logs
                SET time_out = ?, approval_status = "Pending"
                WHERE id = ?
            ');
            $stmt->bind_param('si', $now, $existing['id']);
            $stmt->execute();

            // ✅ LOG ACTIVITY
            $logType = 'Attendance';
            $logMessage = 'Timed OUT for training ID #' . $trainingId;

            $log = $conn->prepare("
                INSERT INTO trainee_activity_logs (trainee_user_id, activity_type, activity_message)
                VALUES (?, ?, ?)
            ");
            $log->bind_param("iss", $userId, $logType, $logMessage);
            $log->execute();
            $log->close();

            $message = 'Time out recorded successfully.';
        }
    }
}

/* GET TRAININGS */
$assigned = $conn->prepare("
    SELECT
        t.id,
        t.title,
        t.schedule_date,
        t.start_time,
        t.end_time,
        t.venue,
        (
            SELECT al.time_in
            FROM attendance_logs al
            WHERE al.training_id = t.id AND al.user_id = ? AND al.log_date = CURDATE()
            LIMIT 1
        ) AS today_time_in,
        (
            SELECT al.time_out
            FROM attendance_logs al
            WHERE al.training_id = t.id AND al.user_id = ? AND al.log_date = CURDATE()
            LIMIT 1
        ) AS today_time_out
    FROM training_participants tp
    INNER JOIN trainings t ON t.id = tp.training_id
    WHERE tp.user_id = ? AND tp.status = 'Enrolled'
    ORDER BY t.schedule_date DESC, t.start_time DESC
");

$assigned->bind_param('iii', $userId, $userId, $userId);
$assigned->execute();
$assignedResults = $assigned->get_result();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_trainee.php';
?>

<div class="topbar">
    <div class="page-title">
        <h1>My Attendance</h1>
        <p>Record time in and time out for your assigned trainings.</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= e($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= e($error); ?></div>
<?php endif; ?>

<div class="table-card">
    <h2>Attendance Actions</h2>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Training</th>
                    <th>Schedule</th>
                    <th>Today</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($assignedResults && $assignedResults->num_rows > 0): ?>
                    <?php while ($row = $assignedResults->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?= e($row['title']); ?></strong>
                                <div class="small"><?= e($row['venue'] ?? 'No venue'); ?></div>
                            </td>

                            <td>
                                <?= e($row['schedule_date']); ?><br>
                                <span class="small">
                                    <?= e(substr((string)$row['start_time'],0,5)); ?> -
                                    <?= e(substr((string)$row['end_time'],0,5)); ?>
                                </span>
                            </td>

                            <td>
                                <span class="small">
                                    In: <?= e($row['today_time_in'] ?? '—'); ?><br>
                                    Out: <?= e($row['today_time_out'] ?? '—'); ?>
                                </span>
                            </td>

                            <td>
                                <form method="POST" style="display:flex;gap:8px;flex-wrap:wrap;">
                                    <input type="hidden" name="training_id" value="<?= (int)$row['id']; ?>">
                                    <button class="btn btn-blue" type="submit" name="attendance_action" value="time_in">Time In</button>
                                    <button class="btn btn-light" type="submit" name="attendance_action" value="time_out">Time Out</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="empty">No assigned trainings available.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>