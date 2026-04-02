<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

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

    $checkSql = "SELECT id, time_in, time_out FROM attendance_logs WHERE user_id = $userId AND training_id = $trainingId AND log_date = '$today' LIMIT 1";
    $checkRes = mysqli_query($conn, $checkSql);
    $existing = $checkRes ? mysqli_fetch_assoc($checkRes) : null;

    if ($_POST['attendance_action'] === 'time_in') {
        if ($existing && !empty($existing['time_in'])) {
            $error = 'You have already timed in for this training today.';
        } else {
            if ($existing) {
                mysqli_query($conn, "UPDATE attendance_logs SET time_in = '$now', status = 'Present' WHERE id = ".$existing['id']);
            } else {
                mysqli_query($conn, "INSERT INTO attendance_logs (training_id, user_id, log_date, time_in, status) VALUES ($trainingId, $userId, '$today', '$now', 'Present')");
            }
            $message = 'Time in recorded successfully.';
        }
    }

    if ($_POST['attendance_action'] === 'time_out') {
        if (!$existing || empty($existing['time_in'])) {
            $error = 'You need to time in first before timing out.';
        } elseif (!empty($existing['time_out'])) {
            $error = 'You have already timed out for this training today.';
        } else {
            mysqli_query($conn, "UPDATE attendance_logs SET time_out = '$now' WHERE id = ".$existing['id']);
            $message = 'Time out recorded successfully.';
        }
    }
}

$assignedSql = "
    SELECT t.id, t.title, t.schedule_date, t.start_time, t.end_time, t.venue
    FROM training_participants tp
    INNER JOIN trainings t ON t.id = tp.training_id
    WHERE tp.user_id = $userId AND tp.status = 'Enrolled'
    ORDER BY t.schedule_date DESC, t.start_time DESC
";
$assignedResults = mysqli_query($conn, $assignedSql);

$historySql = "
    SELECT t.title, a.log_date, a.time_in, a.time_out, a.status
    FROM attendance_logs a
    INNER JOIN trainings t ON t.id = a.training_id
    WHERE a.user_id = $userId
    ORDER BY a.log_date DESC, a.id DESC
    LIMIT 20
";
$historyResults = mysqli_query($conn, $historySql);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="topbar">
    <div class="page-title">
        <h1>My Attendance</h1>
        <p>View your assigned trainings, record your time in and time out, and check your attendance history.</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo e($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo e($error); ?></div>
<?php endif; ?>

<div class="grid-2">
    <section class="table-card">
        <h2>Assigned Trainings</h2>
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
                    <?php if ($assignedResults && mysqli_num_rows($assignedResults) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($assignedResults)): ?>
                            <?php
                                $trainingId = (int)$row['id'];
                                $today = date('Y-m-d');
                                $todayRes = mysqli_query($conn, "SELECT time_in, time_out FROM attendance_logs WHERE user_id = $userId AND training_id = $trainingId AND log_date = '$today' LIMIT 1");
                                $todayLog = $todayRes ? mysqli_fetch_assoc($todayRes) : null;
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo e($row['title']); ?></strong>
                                    <div class="small"><?php echo e($row['venue'] ?: 'No venue'); ?></div>
                                </td>
                                <td>
                                    <?php echo e($row['schedule_date']); ?><br>
                                    <span class="small"><?php echo e(substr((string)$row['start_time'],0,5)); ?> - <?php echo e(substr((string)$row['end_time'],0,5)); ?></span>
                                </td>
                                <td>
                                    <span class="small">
                                        In: <?php echo e($todayLog['time_in'] ?? '—'); ?><br>
                                        Out: <?php echo e($todayLog['time_out'] ?? '—'); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display:flex; gap:8px; flex-wrap:wrap;">
                                        <input type="hidden" name="training_id" value="<?php echo $trainingId; ?>">
                                        <button type="submit" name="attendance_action" value="time_in">Time In</button>
                                        <button type="submit" name="attendance_action" value="time_out" class="btn-outline">Time Out</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">No assigned trainings yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="table-card">
        <h2>Attendance History</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Training</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($historyResults && mysqli_num_rows($historyResults) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($historyResults)): ?>
                            <tr>
                                <td><?php echo e($row['log_date']); ?></td>
                                <td><?php echo e($row['title']); ?></td>
                                <td><?php echo e($row['time_in'] ?: '—'); ?></td>
                                <td><?php echo e($row['time_out'] ?: '—'); ?></td>
                                <td><?php echo e($row['status']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">No attendance history yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>