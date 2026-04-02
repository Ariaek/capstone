<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login(['trainee', 'employee']);

$pageTitle = 'My Notifications';
$userId = (int)($_SESSION['user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark_read') {
    $notificationId = (int)($_POST['notification_id'] ?? 0);
    if ($notificationId > 0) {
        $stmt = $conn->prepare("UPDATE notification_logs SET is_read = 1, read_at = NOW() WHERE id = ? AND recipient_user_id = ?");
        $stmt->bind_param('ii', $notificationId, $userId);
        $stmt->execute();
        $stmt->close();
    }
}

$notifications = $conn->prepare("\n    SELECT nl.id, nl.subject, nl.message, nl.delivery_channel, nl.sent_at, nl.is_read, nl.read_at, t.title\n    FROM notification_logs nl\n    LEFT JOIN trainings t ON t.id = nl.training_id\n    WHERE nl.recipient_user_id = ?\n    ORDER BY nl.id DESC\n");
$notifications->bind_param('i', $userId);
$notifications->execute();
$results = $notifications->get_result();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_trainee.php';
?>
<div class="topbar">
    <div class="page-title">
        <h1>My Notifications</h1>
        <p>View schedule reminders, training updates, and officer instructions.</p>
    </div>
</div>

<div class="table-card">
    <h2>Notification Inbox</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Training</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Sent At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($results && $results->num_rows > 0): ?>
                <?php while ($row = $results->fetch_assoc()): ?>
                    <tr>
                        <td><?= e($row['subject']); ?></td>
                        <td><?= e($row['title'] ?? 'General'); ?></td>
                        <td><?= nl2br(e($row['message'])); ?></td>
                        <td><span class="badge"><?= $row['is_read'] ? 'Read' : 'Unread'; ?></span></td>
                        <td><?= e($row['sent_at']); ?></td>
                        <td>
                            <?php if (!(int)$row['is_read']): ?>
                                <form method="POST">
                                    <input type="hidden" name="action" value="mark_read">
                                    <input type="hidden" name="notification_id" value="<?= (int)$row['id']; ?>">
                                    <button class="btn btn-light" type="submit">Mark Read</button>
                                </form>
                            <?php else: ?>
                                <span class="small">Read at <?= e($row['read_at']); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" class="empty">No notifications available.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
