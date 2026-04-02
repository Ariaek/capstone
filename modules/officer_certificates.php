<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login(['training_officer']);

$pageTitle = 'Certificate Generation';
$userId = (int)($_SESSION['user_id'] ?? 0);
$message = '';
$error = '';

function make_certificate_no(int $trainingId, int $userId): string {
    return 'PTMS-' . date('Y') . '-' . str_pad((string)$trainingId, 4, '0', STR_PAD_LEFT) . '-' . str_pad((string)$userId, 4, '0', STR_PAD_LEFT);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $trainingId = (int)($_POST['training_id'] ?? 0);
    $participantId = (int)($_POST['participant_id'] ?? 0);

    $verify = $conn->prepare("\n        SELECT t.id, t.title, t.schedule_date, u.full_name\n        FROM trainings t\n        INNER JOIN training_participants tp ON tp.training_id = t.id\n        INNER JOIN users u ON u.id = tp.user_id\n        WHERE t.id = ? AND tp.user_id = ? AND t.trainer_user_id = ?\n        LIMIT 1\n    ");
    $verify->bind_param('iii', $trainingId, $participantId, $userId);
    $verify->execute();
    $row = $verify->get_result()->fetch_assoc();
    $verify->close();

    if (!$row) {
        $error = 'Invalid certificate selection.';
    } else {
        if ($action === 'generate_certificate') {
            $checkAttendance = $conn->prepare("\n                SELECT COUNT(*) AS total\n                FROM attendance_logs\n                WHERE training_id = ? AND user_id = ? AND approval_status = 'Approved'\n                  AND time_in IS NOT NULL AND time_in <> ''\n            ");
            $checkAttendance->bind_param('ii', $trainingId, $participantId);
            $checkAttendance->execute();
            $approvedCount = (int)($checkAttendance->get_result()->fetch_assoc()['total'] ?? 0);
            $checkAttendance->close();

            if ($approvedCount <= 0) {
                $error = 'Certificate can only be generated for participants with approved attendance.';
            } else {
                $certificateNo = make_certificate_no($trainingId, $participantId);
                $stmt = $conn->prepare("\n                    INSERT INTO certificates (training_id, user_id, issued_by, certificate_no, completion_date, remarks, is_revoked, revoked_at)\n                    VALUES (?, ?, ?, ?, ?, 'System-generated certificate', 0, NULL)\n                    ON DUPLICATE KEY UPDATE\n                        issued_by = VALUES(issued_by),\n                        certificate_no = VALUES(certificate_no),\n                        completion_date = VALUES(completion_date),\n                        remarks = VALUES(remarks),\n                        is_revoked = 0,\n                        revoked_at = NULL,\n                        issued_at = NOW()\n                ");
                $completionDate = $row['schedule_date'];
                $stmt->bind_param('iiiss', $trainingId, $participantId, $userId, $certificateNo, $completionDate);
                if ($stmt->execute()) {
                    $message = 'Certificate generated successfully.';
                } else {
                    $error = 'Failed to generate certificate.';
                }
                $stmt->close();
            }
        }

        if ($action === 'revoke_certificate') {
            $stmt = $conn->prepare("UPDATE certificates SET is_revoked = 1, revoked_at = NOW() WHERE training_id = ? AND user_id = ?");
            $stmt->bind_param('ii', $trainingId, $participantId);
            if ($stmt->execute()) {
                $message = 'Certificate revoked successfully.';
            } else {
                $error = 'Failed to revoke certificate.';
            }
            $stmt->close();
        }
    }
}

$records = $conn->query("\n    SELECT\n        t.id AS training_id,\n        t.title,\n        t.schedule_date,\n        u.id AS participant_id,\n        u.employee_no,\n        u.full_name,\n        (\n            SELECT COUNT(*)\n            FROM attendance_logs a\n            WHERE a.training_id = t.id AND a.user_id = u.id AND a.approval_status = 'Approved'\n              AND a.time_in IS NOT NULL AND a.time_in <> ''\n        ) AS approved_attendance,\n        c.certificate_no,\n        c.issued_at,\n        c.is_revoked\n    FROM trainings t\n    INNER JOIN training_participants tp ON tp.training_id = t.id\n    INNER JOIN users u ON u.id = tp.user_id\n    LEFT JOIN certificates c ON c.training_id = t.id AND c.user_id = u.id\n    WHERE t.trainer_user_id = {$userId}\n    ORDER BY t.schedule_date DESC, u.full_name ASC\n");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_officer.php';
?>
<div class="topbar">
    <div class="page-title">
        <h1>Certificate Generation</h1>
        <p>Generate and manage certificates for participants who completed their training requirements.</p>
    </div>
</div>

<?php if ($message): ?><div class="alert alert-success"><?= e($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error); ?></div><?php endif; ?>

<div class="table-card">
    <h2>Certificate Records</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Training</th>
                    <th>Participant</th>
                    <th>Approved Attendance</th>
                    <th>Certificate</th>
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
                        <td><?= (int)$row['approved_attendance']; ?></td>
                        <td>
                            <?php if (!empty($row['certificate_no']) && !(int)$row['is_revoked']): ?>
                                <span class="badge">Issued</span>
                                <div class="small"><?= e($row['certificate_no']); ?></div>
                            <?php elseif (!empty($row['certificate_no']) && (int)$row['is_revoked']): ?>
                                <span class="badge">Revoked</span>
                                <div class="small"><?= e($row['certificate_no']); ?></div>
                            <?php else: ?>
                                <span class="small">Not yet generated</span>
                            <?php endif; ?>
                        </td>
                        <td style="display:flex; gap:8px; flex-wrap:wrap;">
                            <form method="POST">
                                <input type="hidden" name="action" value="generate_certificate">
                                <input type="hidden" name="training_id" value="<?= (int)$row['training_id']; ?>">
                                <input type="hidden" name="participant_id" value="<?= (int)$row['participant_id']; ?>">
                                <button class="btn btn-blue" type="submit">Generate</button>
                            </form>
                            <?php if (!empty($row['certificate_no'])): ?>
                                <a class="btn btn-light" target="_blank" href="/PTMS_CAPS/modules/certificate_view.php?training_id=<?= (int)$row['training_id']; ?>&user_id=<?= (int)$row['participant_id']; ?>">View</a>
                                <form method="POST">
                                    <input type="hidden" name="action" value="revoke_certificate">
                                    <input type="hidden" name="training_id" value="<?= (int)$row['training_id']; ?>">
                                    <input type="hidden" name="participant_id" value="<?= (int)$row['participant_id']; ?>">
                                    <button class="btn btn-light" type="submit">Revoke</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" class="empty">No certificate candidates available yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
