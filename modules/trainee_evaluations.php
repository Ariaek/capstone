<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login(['trainee', 'employee']);

$pageTitle = 'My Evaluations';
$userId = (int)($_SESSION['user_id'] ?? 0);

$evaluations = $conn->prepare("\n    SELECT\n        t.title,\n        t.schedule_date,\n        er.evaluation_date,\n        er.attendance_score,\n        er.knowledge_score,\n        er.skills_score,\n        er.behavior_score,\n        er.overall_score,\n        er.result_status,\n        er.remarks,\n        COALESCE(u.full_name, 'Training Officer') AS evaluator_name\n    FROM evaluation_records er\n    INNER JOIN trainings t ON t.id = er.training_id\n    LEFT JOIN users u ON u.id = er.evaluated_by\n    WHERE er.user_id = ?\n    ORDER BY er.evaluation_date DESC, er.id DESC\n");
$evaluations->bind_param('i', $userId);
$evaluations->execute();
$results = $evaluations->get_result();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_trainee.php';
?>
<div class="topbar">
    <div class="page-title">
        <h1>My Evaluations</h1>
        <p>Review your training evaluation scores and officer remarks.</p>
    </div>
</div>

<div class="table-card">
    <h2>Evaluation Results</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Training</th>
                    <th>Evaluation Date</th>
                    <th>Scores</th>
                    <th>Result</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($results && $results->num_rows > 0): ?>
                <?php while ($row = $results->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong><?= e($row['title']); ?></strong>
                            <div class="small">Schedule: <?= e($row['schedule_date']); ?></div>
                            <div class="small">Evaluator: <?= e($row['evaluator_name']); ?></div>
                        </td>
                        <td><?= e($row['evaluation_date']); ?></td>
                        <td>
                            <div class="small">Attendance: <?= e($row['attendance_score']); ?></div>
                            <div class="small">Knowledge: <?= e($row['knowledge_score']); ?></div>
                            <div class="small">Skills: <?= e($row['skills_score']); ?></div>
                            <div class="small">Behavior: <?= e($row['behavior_score']); ?></div>
                            <div class="small">Overall: <?= e($row['overall_score']); ?></div>
                        </td>
                        <td><span class="badge"><?= e($row['result_status']); ?></span></td>
                        <td><?= nl2br(e($row['remarks'] ?: '—')); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" class="empty">No evaluation records available yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
