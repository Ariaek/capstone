<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login(['trainee']);

$pageTitle = 'My Trainings';
$userId = (int)$_SESSION['user_id'];

$trainings = $conn->prepare("
    SELECT
        t.title,
        t.schedule_date,
        t.start_time,
        t.end_time,
        t.venue,
        COALESCE(u.full_name, 'Unassigned') AS trainer_name,
        tp.status
    FROM training_participants tp
    INNER JOIN trainings t ON t.id = tp.training_id
    LEFT JOIN users u ON u.id = t.trainer_user_id
    WHERE tp.user_id = ?
    ORDER BY t.schedule_date DESC, t.start_time DESC
");
$trainings->bind_param('i', $userId);
$trainings->execute();
$results = $trainings->get_result();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_trainee.php';
?>

<div class="topbar">
    <div class="page-title">
        <h1>My Trainings</h1>
        <p>View all training sessions assigned to you.</p>
    </div>
</div>

<div class="table-card">
    <h2>Assigned Training List</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Training</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Venue</th>
                    <th>Officer</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($results && $results->num_rows > 0): ?>
                    <?php while ($row = $results->fetch_assoc()): ?>
                        <tr>
                            <td><?= e($row['title']); ?></td>
                            <td><?= e($row['schedule_date']); ?></td>
                            <td><?= e(substr((string)$row['start_time'],0,5)); ?> - <?= e(substr((string)$row['end_time'],0,5)); ?></td>
                            <td><?= e($row['venue'] ?? '—'); ?></td>
                            <td><?= e($row['trainer_name']); ?></td>
                            <td><span class="badge"><?= e($row['status'] ?? 'Enrolled'); ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="empty">No trainings assigned yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>