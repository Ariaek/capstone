<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login(['trainee']);

$pageTitle = 'Attendance History';
$userId = (int)$_SESSION['user_id'];

$history = $conn->prepare("
    SELECT
        t.title,
        a.log_date,
        a.time_in,
        a.time_out,
        a.status,
        a.approval_status,
        a.approval_remarks
    FROM attendance_logs a
    INNER JOIN trainings t ON t.id = a.training_id
    WHERE a.user_id = ?
    ORDER BY a.log_date DESC, a.id DESC
");
$history->bind_param('i', $userId);
$history->execute();
$historyResults = $history->get_result();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_trainee.php';
?>

<div class="topbar">
    <div class="page-title">
        <h1>Attendance History</h1>
        <p>Review your past attendance records and approval results.</p>
    </div>
</div>

<div class="table-card">
    <h2>Attendance Record History</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Training</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Status</th>
                    <th>Approval</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($historyResults && $historyResults->num_rows > 0): ?>
                    <?php while ($row = $historyResults->fetch_assoc()): ?>
                        <tr>
                            <td><?= e($row['log_date']); ?></td>
                            <td><?= e($row['title']); ?></td>
                            <td><?= e($row['time_in'] ?? '—'); ?></td>
                            <td><?= e($row['time_out'] ?? '—'); ?></td>
                            <td><?= e($row['status'] ?? '—'); ?></td>
                            <td><span class="badge"><?= e($row['approval_status'] ?? 'Pending'); ?></span></td>
                            <td><?= e($row['approval_remarks'] ?? '—'); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="empty">No attendance history found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>