<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login(['admin']);

$pageTitle = 'Trainings';

$message = '';
$error = '';

/* HANDLE ADD TRAINING */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_training') {
    $title = trim($_POST['title'] ?? '');
    $schedule_date = $_POST['schedule_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $venue = trim($_POST['venue'] ?? '');
    $trainer_user_id = (int)($_POST['trainer_user_id'] ?? 0);

    if ($title === '' || $schedule_date === '' || $start_time === '' || $end_time === '') {
        $error = 'Please fill in all required training fields.';
    } else {
        $stmt = $conn->prepare("
            INSERT INTO trainings (title, schedule_date, start_time, end_time, venue, trainer_user_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        if ($stmt) {
            $trainerVal = $trainer_user_id > 0 ? $trainer_user_id : null;
            $stmt->bind_param("sssssi", $title, $schedule_date, $start_time, $end_time, $venue, $trainerVal);
            if ($stmt->execute()) {
                $message = 'Training created successfully.';
            } else {
                $error = 'Failed to create training.';
            }
            $stmt->close();
        } else {
            $error = 'Database error while creating training.';
        }
    }
}

/* HANDLE UPDATE TRAINING */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_training') {
    $training_id = (int)($_POST['training_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $schedule_date = $_POST['schedule_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $venue = trim($_POST['venue'] ?? '');
    $trainer_user_id = (int)($_POST['trainer_user_id'] ?? 0);

    if ($training_id <= 0 || $title === '' || $schedule_date === '' || $start_time === '' || $end_time === '') {
        $error = 'Please complete the update form.';
    } else {
        $stmt = $conn->prepare("
            UPDATE trainings
            SET title = ?, schedule_date = ?, start_time = ?, end_time = ?, venue = ?, trainer_user_id = ?
            WHERE id = ?
        ");
        if ($stmt) {
            $trainerVal = $trainer_user_id > 0 ? $trainer_user_id : null;
            $stmt->bind_param("sssssii", $title, $schedule_date, $start_time, $end_time, $venue, $trainerVal, $training_id);
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

/* HANDLE DELETE TRAINING */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_training') {
    $training_id = (int)($_POST['training_id'] ?? 0);

    if ($training_id <= 0) {
        $error = 'Invalid training selected.';
    } else {
        $stmt = $conn->prepare("DELETE FROM trainings WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $training_id);
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

/* DATA FOR TABLE */
$trainings = $conn->query("
    SELECT 
        t.*,
        COALESCE(u.full_name, 'Unassigned') AS trainer_name
    FROM trainings t
    LEFT JOIN users u ON u.id = t.trainer_user_id
    ORDER BY t.schedule_date DESC, t.start_time DESC
");

/* TRAINING OFFICERS FOR DROPDOWN */
$officers = $conn->query("
    SELECT id, full_name
    FROM users
    WHERE role = 'training_officer' AND is_active = 1
    ORDER BY full_name ASC
");

$officerOptions = [];
if ($officers && $officers->num_rows > 0) {
    while ($officer = $officers->fetch_assoc()) {
        $officerOptions[] = $officer;
    }
}

$totalTrainings = 0;
$countTrainings = $conn->query("SELECT COUNT(*) AS total FROM trainings");
if ($countTrainings && $row = $countTrainings->fetch_assoc()) {
    $totalTrainings = (int)($row['total'] ?? 0);
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="topbar">
    <div class="page-title">
        <h1>Trainings</h1>
        <p>Create trainings, assign training officers, and manage schedules.</p>
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
        <h2>Training Management</h2>
        <p>Centralized view of all training programs, schedules, venues, and officer assignments.</p>
    </div>
    <div class="actions">
        <button class="btn btn-primary" data-modal-target="#addTrainingModal">Add Training</button>
    </div>
</div>

<div class="grid-3">
    <div class="stat-card">
        <h3>Total Trainings</h3>
        <strong><?= $totalTrainings; ?></strong>
        <span>Stored training records</span>
    </div>
    <div class="stat-card">
        <h3>Assignment</h3>
        <strong>Connected</strong>
        <span>Officer linkage ready</span>
    </div>
    <div class="stat-card">
        <h3>Enrollment</h3>
        <strong>Ready</strong>
        <span>Participant workflow enabled</span>
    </div>
</div>

<div class="table-card section-space">
    <h2>Training List</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Venue</th>
                    <th>Assigned Officer</th>
                    <th style="width: 220px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($trainings && $trainings->num_rows > 0): ?>
                    <?php while ($row = $trainings->fetch_assoc()): ?>
                        <tr>
                            <td><?= e($row['title'] ?? '—'); ?></td>
                            <td><?= e($row['schedule_date'] ?? '—'); ?></td>
                            <td><?= e(substr((string)$row['start_time'], 0, 5)); ?> - <?= e(substr((string)$row['end_time'], 0, 5)); ?></td>
                            <td><?= e($row['venue'] ?? '—'); ?></td>
                            <td><?= e($row['trainer_name'] ?? 'Unassigned'); ?></td>
                            <td>
                                <button
                                    type="button"
                                    class="btn btn-light edit-training-btn"
                                    data-modal-target="#editTrainingModal"
                                    data-id="<?= (int)$row['id']; ?>"
                                    data-title="<?= e($row['title']); ?>"
                                    data-date="<?= e($row['schedule_date']); ?>"
                                    data-start="<?= e($row['start_time']); ?>"
                                    data-end="<?= e($row['end_time']); ?>"
                                    data-venue="<?= e($row['venue']); ?>"
                                    data-trainer="<?= (int)($row['trainer_user_id'] ?? 0); ?>"
                                >
                                    Edit
                                </button>

                                <a class="btn btn-primary" href="/PTMS_CAPS/modules/training_participants.php?training_id=<?= (int)$row['id']; ?>">
                                    Participants
                                </a>

                                <form method="POST" style="display:inline-block;" onsubmit="return confirm('Delete this training?');">
                                    <input type="hidden" name="action" value="delete_training">
                                    <input type="hidden" name="training_id" value="<?= (int)$row['id']; ?>">
                                    <button type="submit" class="btn btn-blue">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="empty">No trainings found.</td>
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

                    <div class="form-group full">
                        <label>Assign Training Officer</label>
                        <select name="trainer_user_id">
                            <option value="0">Unassigned</option>
                            <?php foreach ($officerOptions as $officer): ?>
                                <option value="<?= (int)$officer['id']; ?>"><?= e($officer['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-blue">Save Training</button>
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

                    <div class="form-group full">
                        <label>Assign Training Officer</label>
                        <select name="trainer_user_id" id="edit_trainer">
                            <option value="0">Unassigned</option>
                            <?php foreach ($officerOptions as $officer): ?>
                                <option value="<?= (int)$officer['id']; ?>"><?= e($officer['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-blue">Update Training</button>
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
        document.getElementById('edit_trainer').value = this.dataset.trainer;
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>