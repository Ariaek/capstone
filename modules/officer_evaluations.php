<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login(['training_officer']);

$pageTitle = 'Evaluation Monitoring';
$userId = (int)($_SESSION['user_id'] ?? 0);
$message = '';
$error = '';

function clamp_score($value): float {
    $score = (float)$value;
    if ($score < 0) return 0;
    if ($score > 100) return 100;
    return $score;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_evaluation') {
    $trainingId = (int)($_POST['training_id'] ?? 0);
    $participantId = (int)($_POST['participant_id'] ?? 0);
    $evaluationDate = trim($_POST['evaluation_date'] ?? date('Y-m-d'));
    $attendanceScore = clamp_score($_POST['attendance_score'] ?? 0);
    $knowledgeScore = clamp_score($_POST['knowledge_score'] ?? 0);
    $skillsScore = clamp_score($_POST['skills_score'] ?? 0);
    $behaviorScore = clamp_score($_POST['behavior_score'] ?? 0);
    $remarks = trim($_POST['remarks'] ?? '');

    if ($trainingId <= 0 || $participantId <= 0) {
        $error = 'Invalid evaluation selection.';
    } else {
        $verify = $conn->prepare("\n            SELECT tp.user_id\n            FROM training_participants tp\n            INNER JOIN trainings t ON t.id = tp.training_id\n            WHERE tp.training_id = ? AND tp.user_id = ? AND t.trainer_user_id = ?\n            LIMIT 1\n        ");
        $verify->bind_param('iii', $trainingId, $participantId, $userId);
        $verify->execute();
        $isAllowed = $verify->get_result()->fetch_assoc();
        $verify->close();

        if (!$isAllowed) {
            $error = 'You can only evaluate participants under your assigned trainings.';
        } else {
            $overall = round(($attendanceScore + $knowledgeScore + $skillsScore + $behaviorScore) / 4, 2);
            $resultStatus = $overall >= 75 ? 'Passed' : ($overall >= 60 ? 'Needs Improvement' : 'Failed');

            $stmt = $conn->prepare("\n                INSERT INTO evaluation_records\n                (training_id, user_id, evaluated_by, evaluation_date, attendance_score, knowledge_score, skills_score, behavior_score, overall_score, result_status, remarks)\n                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)\n                ON DUPLICATE KEY UPDATE\n                    evaluated_by = VALUES(evaluated_by),\n                    evaluation_date = VALUES(evaluation_date),\n                    attendance_score = VALUES(attendance_score),\n                    knowledge_score = VALUES(knowledge_score),\n                    skills_score = VALUES(skills_score),\n                    behavior_score = VALUES(behavior_score),\n                    overall_score = VALUES(overall_score),\n                    result_status = VALUES(result_status),\n                    remarks = VALUES(remarks)\n            ");

            if ($stmt) {
                $stmt->bind_param(
                    'iiisddddsss',
                    $trainingId,
                    $participantId,
                    $userId,
                    $evaluationDate,
                    $attendanceScore,
                    $knowledgeScore,
                    $skillsScore,
                    $behaviorScore,
                    $overall,
                    $resultStatus,
                    $remarks
                );

                if ($stmt->execute()) {
                    $message = 'Evaluation saved successfully.';
                } else {
                    $error = 'Failed to save evaluation.';
                }
                $stmt->close();
            } else {
                $error = 'Database error while saving evaluation.';
            }
        }
    }
}

$records = $conn->query("\n    SELECT\n        t.id AS training_id,\n        t.title,\n        t.schedule_date,\n        u.id AS participant_id,\n        u.employee_no,\n        u.full_name,\n        er.evaluation_date,\n        er.attendance_score,\n        er.knowledge_score,\n        er.skills_score,\n        er.behavior_score,\n        er.overall_score,\n        er.result_status,\n        er.remarks\n    FROM training_participants tp\n    INNER JOIN trainings t ON t.id = tp.training_id\n    INNER JOIN users u ON u.id = tp.user_id\n    LEFT JOIN evaluation_records er ON er.training_id = tp.training_id AND er.user_id = tp.user_id\n    WHERE t.trainer_user_id = {$userId}\n    ORDER BY t.schedule_date DESC, t.title ASC, u.full_name ASC\n");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_officer.php';
?>
<div class="topbar">
    <div class="page-title">
        <h1>Evaluation Monitoring</h1>
        <p>Record post-training evaluation results for participants under your assigned trainings.</p>
    </div>
</div>

<?php if ($message): ?><div class="alert alert-success"><?= e($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error); ?></div><?php endif; ?>

<div class="table-card">
    <h2>Participant Evaluation Records</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Training</th>
                    <th>Participant</th>
                    <th>Scores</th>
                    <th>Result</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($records && $records->num_rows > 0): ?>
                <?php while ($row = $records->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong><?= e($row['title']); ?></strong>
                            <div class="small"><?= e($row['schedule_date']); ?></div>
                        </td>
                        <td>
                            <?= e($row['full_name']); ?>
                            <div class="small"><?= e($row['employee_no']); ?></div>
                        </td>
                        <td>
                            <div class="small">Attendance: <?= e($row['attendance_score'] ?? '—'); ?></div>
                            <div class="small">Knowledge: <?= e($row['knowledge_score'] ?? '—'); ?></div>
                            <div class="small">Skills: <?= e($row['skills_score'] ?? '—'); ?></div>
                            <div class="small">Behavior: <?= e($row['behavior_score'] ?? '—'); ?></div>
                        </td>
                        <td>
                            <span class="badge"><?= e($row['result_status'] ?? 'Not Yet Evaluated'); ?></span>
                            <div class="small">Overall: <?= e($row['overall_score'] ?? '—'); ?></div>
                        </td>
                        <td>
                            <button class="btn btn-blue" type="button" data-modal-target="#evalModal<?= (int)$row['training_id']; ?>_<?= (int)$row['participant_id']; ?>">Evaluate</button>
                        </td>
                    </tr>

                    <div class="modal" id="evalModal<?= (int)$row['training_id']; ?>_<?= (int)$row['participant_id']; ?>">
                        <div class="modal-box">
                            <div class="modal-header">
                                <h3>Save Evaluation</h3>
                                <button class="modal-close" data-close-modal>&times;</button>
                            </div>
                            <form method="POST">
                                <div class="modal-body">
                                    <input type="hidden" name="action" value="save_evaluation">
                                    <input type="hidden" name="training_id" value="<?= (int)$row['training_id']; ?>">
                                    <input type="hidden" name="participant_id" value="<?= (int)$row['participant_id']; ?>">
                                    <div class="form-grid">
                                        <div class="form-group full">
                                            <label>Evaluation Date</label>
                                            <input type="date" name="evaluation_date" value="<?= e($row['evaluation_date'] ?: date('Y-m-d')); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Attendance Score</label>
                                            <input type="number" name="attendance_score" min="0" max="100" step="0.01" value="<?= e($row['attendance_score'] ?? 0); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Knowledge Score</label>
                                            <input type="number" name="knowledge_score" min="0" max="100" step="0.01" value="<?= e($row['knowledge_score'] ?? 0); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Skills Score</label>
                                            <input type="number" name="skills_score" min="0" max="100" step="0.01" value="<?= e($row['skills_score'] ?? 0); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Behavior Score</label>
                                            <input type="number" name="behavior_score" min="0" max="100" step="0.01" value="<?= e($row['behavior_score'] ?? 0); ?>" required>
                                        </div>
                                        <div class="form-group full">
                                            <label>Remarks</label>
                                            <textarea name="remarks" placeholder="Enter observations or recommendations"><?= e($row['remarks'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button class="btn btn-light" type="button" data-close-modal>Cancel</button>
                                    <button class="btn btn-blue" type="submit">Save Evaluation</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" class="empty">No participants available for evaluation yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
