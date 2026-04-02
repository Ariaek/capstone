<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login(['admin', 'training_officer', 'trainee', 'employee']);

$sessionUserId = (int)($_SESSION['user_id'] ?? 0);
$sessionRole = $_SESSION['role'] ?? '';
$trainingId = (int)($_GET['training_id'] ?? 0);
$userId = (int)($_GET['user_id'] ?? 0);

$stmt = $conn->prepare("\n    SELECT\n        c.certificate_no, c.issued_at, c.completion_date, c.is_revoked,\n        t.id AS training_id, t.title, t.schedule_date, t.venue,\n        u.full_name AS participant_name, u.employee_no,\n        issuer.full_name AS issued_by_name,\n        owner.trainer_user_id\n    FROM certificates c\n    INNER JOIN trainings t ON t.id = c.training_id\n    INNER JOIN trainings owner ON owner.id = c.training_id\n    INNER JOIN users u ON u.id = c.user_id\n    INNER JOIN users issuer ON issuer.id = c.issued_by\n    WHERE c.training_id = ? AND c.user_id = ?\n    LIMIT 1\n");
$stmt->bind_param('ii', $trainingId, $userId);
$stmt->execute();
$certificate = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$certificate) {
    exit('Certificate not found.');
}

$allowed = false;
if ($sessionRole === 'admin') {
    $allowed = true;
} elseif ($sessionRole === 'training_officer' && (int)$certificate['trainer_user_id'] === $sessionUserId) {
    $allowed = true;
} elseif (in_array($sessionRole, ['trainee', 'employee'], true) && $sessionUserId === $userId) {
    $allowed = true;
}

if (!$allowed) {
    exit('Unauthorized access.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate of Completion</title>
    <style>
        body{font-family:Georgia,serif;background:#f3f6fb;margin:0;padding:30px;color:#1f2937}
        .wrap{max-width:980px;margin:0 auto;background:#fff;border:10px solid #123e82;padding:48px;box-shadow:0 20px 50px rgba(0,0,0,.08)}
        .top{text-align:center;border-bottom:2px solid #dbeafe;padding-bottom:18px;margin-bottom:30px}
        .top h1{margin:0;color:#123e82;font-size:42px}
        .top p{margin:8px 0 0;font-family:Arial,sans-serif;color:#4b5563}
        .title{text-align:center;font-size:18px;letter-spacing:.22em;margin-top:10px;color:#6b7280;font-family:Arial,sans-serif}
        .name{text-align:center;font-size:40px;margin:34px 0 8px;color:#0f172a;font-weight:bold}
        .content{text-align:center;font-size:20px;line-height:1.8}
        .meta{margin-top:34px;display:grid;grid-template-columns:1fr 1fr;gap:18px;font-family:Arial,sans-serif}
        .meta-box{border-top:1px solid #cbd5e1;padding-top:10px;text-align:center}
        .toolbar{text-align:center;margin:18px 0 28px}
        .btn{display:inline-block;padding:12px 18px;background:#123e82;color:#fff;text-decoration:none;border-radius:10px;font-family:Arial,sans-serif}
        .status{text-align:center;margin-top:12px;font-family:Arial,sans-serif;color:#991b1b;font-weight:bold}
        @media print {.toolbar{display:none} body{background:#fff;padding:0} .wrap{box-shadow:none;margin:0;border-width:8px}}
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn" onclick="window.print()">Print Certificate</button>
    </div>
    <div class="wrap">
        <div class="top">
            <h1>PAG-ASA PTMS</h1>
            <p>Web-Based Training Management System</p>
            <div class="title">CERTIFICATE OF COMPLETION</div>
        </div>
        <div class="content">This certificate is proudly presented to</div>
        <div class="name"><?= htmlspecialchars($certificate['participant_name'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="content">
            for successfully completing the training program<br>
            <strong><?= htmlspecialchars($certificate['title'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
            conducted on <?= htmlspecialchars($certificate['completion_date'] ?: $certificate['schedule_date'], ENT_QUOTES, 'UTF-8'); ?>
            at <?= htmlspecialchars($certificate['venue'] ?: 'PAG-ASA Training Venue', ENT_QUOTES, 'UTF-8'); ?>.
        </div>
        <div class="meta">
            <div class="meta-box">
                <strong><?= htmlspecialchars($certificate['issued_by_name'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                Issued By
            </div>
            <div class="meta-box">
                <strong><?= htmlspecialchars($certificate['certificate_no'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                Certificate No.
            </div>
        </div>
        <?php if ((int)$certificate['is_revoked'] === 1): ?>
            <div class="status">This certificate has been revoked.</div>
        <?php endif; ?>
    </div>
</body>
</html>
