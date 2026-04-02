<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login(['training_officer']);

$pageTitle = 'My Trainees';
$userId = (int)$_SESSION['user_id'];

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remove_participant') {
    $trainingId = (int)($_POST['training_id'] ?? 0);
    $participantId = (int)($_POST['participant_id'] ?? 0);

    if ($trainingId <= 0 || $participantId <= 0) {
        $error = 'Invalid participant selection.';
    } else {
        $check = $conn->query("
            SELECT tp.id
            FROM training_participants tp
            INNER JOIN trainings t ON t.id = tp.training_id
            WHERE tp.training_id = {$trainingId}
              AND tp.user_id = {$participantId}
              AND t.trainer_user_id = {$userId}
            LIMIT 1
        ");

        if ($check && $check->num_rows === 1) {
            $stmt = $conn->prepare("DELETE FROM training_participants WHERE training_id = ? AND user_id = ?");
            if ($stmt) {
                $stmt->bind_param("ii", $trainingId, $participantId);
                if ($stmt->execute()) {
                    $message = 'Participant removed successfully.';
                } else {
                    $error = 'Failed to remove participant.';
                }
                $stmt->close();
            }
        } else {
            $error = 'You can only manage participants under your own trainings.';
        }
    }
}

$trainees = $conn->query("
    SELECT tp.training_id, u.id AS user_id, u.employee_no, u.full_name, u.department, t.title
    FROM training_participants tp
    INNER JOIN users u ON u.id = tp.user_id
    INNER JOIN trainings t ON t.id = tp.training_id
    WHERE t.trainer_user_id = {$userId}
    ORDER BY t.schedule_date DESC, u.full_name ASC
");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_officer.php';
?>

<div class="topbar">
    <div class="page-title">
        <h1>My Trainees</h1>
        <p>Monitor and manage participants assigned to your handled trainings.</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= e($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= e($error); ?></div>
<?php endif; ?>

<div class="table-card">
    <h2>Trainee List</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Employee No</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Training</th>
                    <th style="width:140px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($trainees && $trainees->num_rows > 0): ?>
                    <?php while ($row = $trainees->fetch_assoc()): ?>
                        <tr>
                            <td><?= e($row['employee_no'] ?? '—'); ?></td>
                            <td><?= e($row['full_name'] ?? '—'); ?></td>
                            <td><?= e($row['department'] ?? '—'); ?></td>
                            <td><?= e($row['title'] ?? '—'); ?></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Remove this trainee from the training?');">
                                    <input type="hidden" name="action" value="remove_participant">
                                    <input type="hidden" name="training_id" value="<?= (int)$row['training_id']; ?>">
                                    <input type="hidden" name="participant_id" value="<?= (int)$row['user_id']; ?>">
                                    <button class="btn btn-blue" type="submit">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="empty">No trainees found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>