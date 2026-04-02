<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/send_mail.php';
require_login(['training_officer']);

$pageTitle = 'Training Notifications';
$userId = (int)($_SESSION['user_id'] ?? 0);
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_notification') {
    $trainingId = (int)($_POST['training_id'] ?? 0);
    $recipientMode = trim($_POST['recipient_mode'] ?? 'all');
    $participantId = (int)($_POST['participant_id'] ?? 0);
    $subject = trim($_POST['subject'] ?? 'Training Reminder');
    $body = trim($_POST['message'] ?? '');
    $channel = trim($_POST['delivery_channel'] ?? 'In-App');

    if ($trainingId <= 0 || $body === '') {
        $error = 'Please choose a training and enter your message.';
    } else {
        $checkTraining = $conn->prepare("SELECT id, title, schedule_date FROM trainings WHERE id = ? AND trainer_user_id = ? LIMIT 1");
        $checkTraining->bind_param('ii', $trainingId, $userId);
        $checkTraining->execute();
        $training = $checkTraining->get_result()->fetch_assoc();
        $checkTraining->close();

        if (!$training) {
            $error = 'Invalid training selection.';
        } else {
            if ($recipientMode === 'single' && $participantId > 0) {
                $participantsSql = $conn->prepare("\n                    SELECT u.id, u.full_name, u.email\n                    FROM training_participants tp\n                    INNER JOIN users u ON u.id = tp.user_id\n                    WHERE tp.training_id = ? AND tp.user_id = ?\n                ");
                $participantsSql->bind_param('ii', $trainingId, $participantId);
            } else {
                $participantsSql = $conn->prepare("\n                    SELECT u.id, u.full_name, u.email\n                    FROM training_participants tp\n                    INNER JOIN users u ON u.id = tp.user_id\n                    WHERE tp.training_id = ?\n                ");
                $participantsSql->bind_param('i', $trainingId);
            }

            $participantsSql->execute();
            $participants = $participantsSql->get_result();
            $participantsSql->close();

            if (!$participants || $participants->num_rows === 0) {
                $error = 'No recipients found for the selected training.';
            } else {
                $insert = $conn->prepare("\n                    INSERT INTO notification_logs\n                    (training_id, sender_user_id, recipient_user_id, subject, message, delivery_channel)\n                    VALUES (?, ?, ?, ?, ?, ?)\n                ");

                if (!$insert) {
                    $error = 'Failed to prepare notification log.';
                } else {
                    $sentCount = 0;
                    while ($recipient = $participants->fetch_assoc()) {
                        $recipientId = (int)$recipient['id'];
                        $insert->bind_param('iiisss', $trainingId, $userId, $recipientId, $subject, $body, $channel);
                        $insert->execute();
                        $sentCount++;

                        if ($channel === 'Email + In-App' && !empty($recipient['email'])) {
                            $htmlBody = "<h2>PTMS Training Notification</h2>"
                                . "<p><strong>Training:</strong> " . htmlspecialchars($training['title'], ENT_QUOTES, 'UTF-8') . "</p>"
                                . "<p><strong>Schedule Date:</strong> " . htmlspecialchars($training['schedule_date'], ENT_QUOTES, 'UTF-8') . "</p>"
                                . "<p>" . nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8')) . "</p>";
                            send_ptms_mail($recipient['email'], $recipient['full_name'], $subject, $htmlBody);
                        }
                    }
                    $insert->close();
                    $message = 'Notification sent successfully to ' . $sentCount . ' recipient(s).';
                }
            }
        }
    }
}

$trainings = $conn->query("\n    SELECT id, title, schedule_date\n    FROM trainings\n    WHERE trainer_user_id = {$userId}\n    ORDER BY schedule_date DESC, title ASC\n");

$participants = $conn->query("\n    SELECT tp.training_id, u.id, u.full_name, t.title\n    FROM training_participants tp\n    INNER JOIN users u ON u.id = tp.user_id\n    INNER JOIN trainings t ON t.id = tp.training_id\n    WHERE t.trainer_user_id = {$userId}\n    ORDER BY t.schedule_date DESC, u.full_name ASC\n");

$recentNotifications = $conn->query("\n    SELECT nl.subject, nl.message, nl.delivery_channel, nl.sent_at, u.full_name AS recipient_name, t.title\n    FROM notification_logs nl\n    INNER JOIN users u ON u.id = nl.recipient_user_id\n    LEFT JOIN trainings t ON t.id = nl.training_id\n    WHERE nl.sender_user_id = {$userId}\n    ORDER BY nl.id DESC\n    LIMIT 20\n");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_officer.php';
?>
<div class="topbar">
    <div class="page-title">
        <h1>Training Notifications</h1>
        <p>Send reminders and schedule notices to participants under your assigned trainings.</p>
    </div>
</div>

<?php if ($message): ?><div class="alert alert-success"><?= e($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error); ?></div><?php endif; ?>

<div class="grid-2">
    <section class="content-card">
        <h2>Send Notification</h2>
        <form method="POST">
            <input type="hidden" name="action" value="send_notification">
            <div class="form-grid">
                <div class="form-group full">
                    <label>Training</label>
                    <select name="training_id" required>
                        <option value="">Select training</option>
                        <?php if ($trainings): while ($training = $trainings->fetch_assoc()): ?>
                            <option value="<?= (int)$training['id']; ?>"><?= e($training['title']); ?> - <?= e($training['schedule_date']); ?></option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Recipient Mode</label>
                    <select name="recipient_mode">
                        <option value="all">All Participants</option>
                        <option value="single">Single Participant</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Participant (optional)</label>
                    <select name="participant_id">
                        <option value="0">Choose participant</option>
                        <?php if ($participants): while ($participant = $participants->fetch_assoc()): ?>
                            <option value="<?= (int)$participant['id']; ?>"><?= e($participant['full_name']); ?> - <?= e($participant['title']); ?></option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div class="form-group full">
                    <label>Subject</label>
                    <input type="text" name="subject" value="Training Reminder" required>
                </div>
                <div class="form-group full">
                    <label>Message</label>
                    <textarea name="message" placeholder="Enter the reminder, requirement, or schedule update" required></textarea>
                </div>
                <div class="form-group full">
                    <label>Delivery Channel</label>
                    <select name="delivery_channel">
                        <option value="In-App">In-App</option>
                        <option value="Email + In-App">Email + In-App</option>
                    </select>
                </div>
            </div>
            <div style="margin-top:14px;">
                <button class="btn btn-blue" type="submit">Send Notification</button>
            </div>
        </form>
    </section>

    <section class="table-card">
        <h2>Recent Sent Notifications</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Recipient</th>
                        <th>Training</th>
                        <th>Subject</th>
                        <th>Channel</th>
                        <th>Sent</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($recentNotifications && $recentNotifications->num_rows > 0): ?>
                    <?php while ($row = $recentNotifications->fetch_assoc()): ?>
                        <tr>
                            <td><?= e($row['recipient_name']); ?></td>
                            <td><?= e($row['title'] ?? 'General'); ?></td>
                            <td><?= e($row['subject']); ?><div class="small"><?= e($row['message']); ?></div></td>
                            <td><?= e($row['delivery_channel']); ?></td>
                            <td><?= e($row['sent_at']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="empty">No sent notifications yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
