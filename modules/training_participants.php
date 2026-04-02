<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login(['admin']);

$pageTitle = 'Training Participants';

$trainingId = (int)($_GET['training_id'] ?? $_POST['training_id'] ?? 0);
if ($trainingId <= 0) {
    die('Invalid training selected.');
}

$message = '';
$error = '';

$trainingStmt = $conn->prepare("
    SELECT t.id, t.title, t.schedule_date, COALESCE(u.full_name, 'Unassigned') AS trainer_name
    FROM trainings t
    LEFT JOIN users u ON u.id = t.trainer_user_id
    WHERE t.id = ?
    LIMIT 1
");
$trainingStmt->bind_param("i", $trainingId);
$trainingStmt->execute();
$trainingRes = $trainingStmt->get_result();
$training = $trainingRes ? $trainingRes->fetch_assoc() : null;
$trainingStmt->close();

if (!$training) {
    die('Training not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_participant') {
        $userId = (int)($_POST['user_id'] ?? 0);

        if ($userId <= 0) {
            $error = 'Please select a trainee/employee.';
        } else {
            $stmt = $conn->prepare("
                INSERT IGNORE INTO training_participants (training_id, user_id, status)
                VALUES (?, ?, 'Enrolled')
            ");
            if ($stmt) {
                $stmt->bind_param("ii", $trainingId, $userId);
                if ($stmt->execute()) {
                    $message = 'Participant assigned successfully.';
                } else {
                    $error = 'Failed to assign participant.';
                }
                $stmt->close();
            } else {
                $error = 'Database error while assigning participant.';
            }
        }
    }

    if ($action === 'remove_participant') {
        $userId = (int)($_POST['user_id'] ?? 0);

        if ($userId <= 0) {
            $error = 'Invalid participant selected.';
        } else {
            $stmt = $conn->prepare("
                DELETE FROM training_participants
                WHERE training_id = ? AND user_id = ?
            ");
            if ($stmt) {
                $stmt->bind_param("ii", $trainingId, $userId);
                if ($stmt->execute()) {
                    $message = 'Participant removed successfully.';
                } else {
                    $error = 'Failed to remove participant.';
                }
                $stmt->close();
            }
        }
    }
}

$availableUsers = $conn->query("
    SELECT id, employee_no, full_name, role, department
    FROM users
    WHERE role IN ('employee', 'trainee') AND is_active = 1
    ORDER BY full_name ASC
");

$participants = $conn->query("
    SELECT u.id, u.employee_no, u.full_name, u.role, u.department, tp.status
    FROM training_participants tp
    INNER JOIN users u ON u.id = tp.user_id
    WHERE tp.training_id = {$trainingId}
    ORDER BY u.full_name ASC
");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="topbar">
    <div class="page-title">
        <h1>Training Participants</h1>
        <p>Assign trainees and employees to this training.</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= e($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= e($error); ?></div>
<?php endif; ?>

<div class="hero-panel">
    <div>
        <h2><?= e($training['title']); ?></h2>
        <p>
            Date: <strong><?= e($training['schedule_date']); ?></strong><br>
            Assigned Officer: <strong><?= e($training['trainer_name']); ?></strong>
        </p>
    </div>
    <div class="actions">
        <a class="btn btn-primary" href="/PTMS_CAPS/modules/trainings.php">Back to Trainings</a>
    </div>
</div>

<div class="content-card">
    <h2>Assign Participant</h2>
    <form method="POST">
        <input type="hidden" name="action" value="add_participant">
        <input type="hidden" name="training_id" value="<?= (int)$trainingId; ?>">

        <div class="form-grid">
            <div class="form-group full">
                <label>Select Trainee / Employee</label>
                <select name="user_id" required>
                    <option value="">Choose participant</option>
                    <?php if ($availableUsers && $availableUsers->num_rows > 0): ?>
                        <?php while ($user = $availableUsers->fetch_assoc()): ?>
                            <option value="<?= (int)$user['id']; ?>">
                                <?= e($user['full_name']); ?> (<?= e($user['employee_no']); ?> - <?= e($user['role']); ?>)
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <div style="margin-top:14px;">
            <button type="submit" class="btn btn-blue">Assign Participant</button>
        </div>
    </form>
</div>

<div class="table-card section-space">
    <h2>Assigned Participants</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Employee No</th>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th style="width:140px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($participants && $participants->num_rows > 0): ?>
                    <?php while ($row = $participants->fetch_assoc()): ?>
                        <tr>
                            <td><?= e($row['employee_no'] ?? '—'); ?></td>
                            <td><?= e($row['full_name'] ?? '—'); ?></td>
                            <td><?= e($row['role'] ?? '—'); ?></td>
                            <td><?= e($row['department'] ?? '—'); ?></td>
                            <td><span class="badge"><?= e($row['status'] ?? 'Enrolled'); ?></span></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Remove this participant?');">
                                    <input type="hidden" name="action" value="remove_participant">
                                    <input type="hidden" name="training_id" value="<?= (int)$trainingId; ?>">
                                    <input type="hidden" name="user_id" value="<?= (int)$row['id']; ?>">
                                    <button type="submit" class="btn btn-blue">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="empty">No participants assigned yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>