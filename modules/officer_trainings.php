<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login(['training_officer']);

$pageTitle = 'My Trainings';
$userId = (int)$_SESSION['user_id'];

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_training') {
        $title = trim($_POST['title'] ?? '');
        $schedule_date = $_POST['schedule_date'] ?? '';
        $start_time = $_POST['start_time'] ?? '';
        $end_time = $_POST['end_time'] ?? '';
        $venue = trim($_POST['venue'] ?? '');

        if ($title === '' || $schedule_date === '' || $start_time === '' || $end_time === '') {
            $error = 'Please fill in all required training fields.';
        } else {
            $stmt = $conn->prepare("
                INSERT INTO trainings (title, schedule_date, start_time, end_time, venue, trainer_user_id)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            if ($stmt) {
                $stmt->bind_param("sssssi", $title, $schedule_date, $start_time, $end_time, $venue, $userId);
                if ($stmt->execute()) {
                    $message = 'Training added successfully.';
                } else {
                    $error = 'Failed to add training.';
                }
                $stmt->close();
            } else {
                $error = 'Database error while adding training.';
            }
        }
    }

    if ($action === 'update_training') {
        $trainingId = (int)($_POST['training_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $schedule_date = $_POST['schedule_date'] ?? '';
        $start_time = $_POST['start_time'] ?? '';
        $end_time = $_POST['end_time'] ?? '';
        $venue = trim($_POST['venue'] ?? '');

        if ($trainingId <= 0 || $title === '' || $schedule_date === '' || $start_time === '' || $end_time === '') {
            $error = 'Please complete the training update form.';
        } else {
            $stmt = $conn->prepare("
                UPDATE trainings
                SET title = ?, schedule_date = ?, start_time = ?, end_time = ?, venue = ?
                WHERE id = ? AND trainer_user_id = ?
            ");
            if ($stmt) {
                $stmt->bind_param("sssssii", $title, $schedule_date, $start_time, $end_time, $venue, $trainingId, $userId);
                if ($stmt->execute()) {
                    $message = 'Training updated successfully.';
                } else {
                    $error = 'Failed to update training.';
                }
                $stmt->close();
            } else {
                $error = 'Database error while updating training.';
            }
        }
    }

    if ($action === 'delete_training') {
        $trainingId = (int)($_POST['training_id'] ?? 0);

        if ($trainingId <= 0) {
            $error = 'Invalid training selected.';
        } else {
            $stmt = $conn->prepare("DELETE FROM trainings WHERE id = ? AND trainer_user_id = ?");
            if ($stmt) {
                $stmt->bind_param("ii", $trainingId, $userId);
                if ($stmt->execute()) {
                    $message = 'Training deleted successfully.';
                } else {
                    $error = 'Failed to delete training.';
                }
                $stmt->close();
            } else {
                $error = 'Database error while deleting training.';
            }
        }
    }
}

$trainings = $conn->query("
    SELECT id, title, schedule_date, start_time, end_time, venue
    FROM trainings
    WHERE trainer_user_id = {$userId}
    ORDER BY schedule_date DESC, start_time DESC
");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_officer.php';
?>

<div class="topbar">
    <div class="page-title">
        <h1>My Trainings</h1>
        <p>Create, update, and remove your handled training schedules.</p>
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
        <h2>Training CRUD</h2>
        <p>Manage your schedule, venue, and training session details directly from this page.</p>
    </div>
    <div class="actions">
        <button class="btn btn-primary" data-modal-target="#addTrainingModal">Add Training</button>
    </div>
</div>

<div class="table-card">
    <h2>Assigned Trainings</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Venue</th>
                    <th style="width:220px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($trainings && $trainings->num_rows > 0): ?>
                    <?php while ($row = $trainings->fetch_assoc()): ?>
                        <tr>
                            <td><?= e($row['title']); ?></td>
                            <td><?= e($row['schedule_date']); ?></td>
                            <td><?= e(substr((string)$row['start_time'], 0, 5)); ?> - <?= e(substr((string)$row['end_time'], 0, 5)); ?></td>
                            <td><?= e($row['venue'] ?: '—'); ?></td>
                            <td>
                                <button
                                    class="btn btn-light edit-training-btn"
                                    type="button"
                                    data-modal-target="#editTrainingModal"
                                    data-id="<?= (int)$row['id']; ?>"
                                    data-title="<?= e($row['title']); ?>"
                                    data-date="<?= e($row['schedule_date']); ?>"
                                    data-start="<?= e($row['start_time']); ?>"
                                    data-end="<?= e($row['end_time']); ?>"
                                    data-venue="<?= e($row['venue']); ?>"
                                >
                                    Edit
                                </button>

                                <form method="POST" style="display:inline-block;" onsubmit="return confirm('Delete this training?');">
                                    <input type="hidden" name="action" value="delete_training">
                                    <input type="hidden" name="training_id" value="<?= (int)$row['id']; ?>">
                                    <button class="btn btn-blue" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="empty">No trainings assigned yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal" id="addTrainingModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Add Training</h3>
            <button class="modal-close" data-close-modal>&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="add_training">
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Training Title</label>
                        <input type="text" name="title" required>
                    </div>
                    <div class="form-group">
                        <label>Schedule Date</label>
                        <input type="date" name="schedule_date" required>
                    </div>
                    <div class="form-group">
                        <label>Venue</label>
                        <input type="text" name="venue">
                    </div>
                    <div class="form-group">
                        <label>Start Time</label>
                        <input type="time" name="start_time" required>
                    </div>
                    <div class="form-group">
                        <label>End Time</label>
                        <input type="time" name="end_time" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" type="button" data-close-modal>Cancel</button>
                <button class="btn btn-blue" type="submit">Save Training</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="editTrainingModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Edit Training</h3>
            <button class="modal-close" data-close-modal>&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="update_training">
                <input type="hidden" name="training_id" id="edit_training_id">
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Training Title</label>
                        <input type="text" name="title" id="edit_title" required>
                    </div>
                    <div class="form-group">
                        <label>Schedule Date</label>
                        <input type="date" name="schedule_date" id="edit_date" required>
                    </div>
                    <div class="form-group">
                        <label>Venue</label>
                        <input type="text" name="venue" id="edit_venue">
                    </div>
                    <div class="form-group">
                        <label>Start Time</label>
                        <input type="time" name="start_time" id="edit_start" required>
                    </div>
                    <div class="form-group">
                        <label>End Time</label>
                        <input type="time" name="end_time" id="edit_end" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" type="button" data-close-modal>Cancel</button>
                <button class="btn btn-blue" type="submit">Update Training</button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.edit-training-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('edit_training_id').value = this.dataset.id;
        document.getElementById('edit_title').value = this.dataset.title;
        document.getElementById('edit_date').value = this.dataset.date;
        document.getElementById('edit_start').value = this.dataset.start;
        document.getElementById('edit_end').value = this.dataset.end;
        document.getElementById('edit_venue').value = this.dataset.venue;
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>