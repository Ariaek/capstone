<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login(['trainee']);

$pageTitle = 'My Profile';
$userId = (int)$_SESSION['user_id'];
$message = '';
$error = '';

/* GET USER FIRST */
$stmt = $conn->prepare("
    SELECT employee_no, full_name, username, email, contact_no, department, position_title, role
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    $error = 'User record not found.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile' && $user) {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contact_no = trim($_POST['contact_no'] ?? '');

    $currentFullName = trim((string)($user['full_name'] ?? ''));
    $currentEmail = trim((string)($user['email'] ?? ''));
    $currentContactNo = trim((string)($user['contact_no'] ?? ''));

    if ($full_name === '' || $email === '') {
        $error = 'Full name and email are required.';
    } elseif (
        $full_name === $currentFullName &&
        $email === $currentEmail &&
        $contact_no === $currentContactNo
    ) {
        $error = 'No changes were made to your profile.';
    } else {
        $stmt = $conn->prepare("
            UPDATE users
            SET full_name = ?, email = ?, contact_no = ?
            WHERE id = ? AND role = 'trainee'
        ");

        if ($stmt) {
            $stmt->bind_param("sssi", $full_name, $email, $contact_no, $userId);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $logType = 'Profile Update';
                    $logMessage = 'Updated profile information (Name/Email/Contact).';

                    $log = $conn->prepare("
                        INSERT INTO trainee_activity_logs (trainee_user_id, activity_type, activity_message)
                        VALUES (?, ?, ?)
                    ");
                    if ($log) {
                        $log->bind_param("iss", $userId, $logType, $logMessage);
                        $log->execute();
                        $log->close();
                    }

                    $_SESSION['full_name'] = $full_name;
                    $_SESSION['email'] = $email;

                    $message = 'Profile updated successfully.';

                    /* refresh displayed user data */
                    $user['full_name'] = $full_name;
                    $user['email'] = $email;
                    $user['contact_no'] = $contact_no;
                } else {
                    $error = 'No changes were made to your profile.';
                }
            } else {
                $error = 'Failed to update profile.';
            }

            $stmt->close();
        } else {
            $error = 'Database error while updating profile.';
        }
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_trainee.php';
?>

<div class="topbar">
    <div class="page-title">
        <h1>My Profile</h1>
        <p>View and update your trainee account information.</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= e($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= e($error); ?></div>
<?php endif; ?>

<div class="grid-2">
    <section class="content-card">
        <h2>Profile Information</h2>
        <div class="info-list">
            <div class="info-item"><strong>Employee No:</strong> <?= e($user['employee_no'] ?? '—'); ?></div>
            <div class="info-item"><strong>Username:</strong> <?= e($user['username'] ?? '—'); ?></div>
            <div class="info-item"><strong>Department:</strong> <?= e($user['department'] ?? '—'); ?></div>
            <div class="info-item"><strong>Position:</strong> <?= e($user['position_title'] ?? '—'); ?></div>
            <div class="info-item"><strong>Role:</strong> <?= e($user['role'] ?? 'trainee'); ?></div>
        </div>
    </section>

    <section class="content-card">
        <h2>Update Profile</h2>
        <form method="POST">
            <input type="hidden" name="action" value="update_profile">

            <div class="form-grid">
                <div class="form-group full">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?= e($user['full_name'] ?? ''); ?>" required>
                </div>

                <div class="form-group full">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= e($user['email'] ?? ''); ?>" required>
                </div>

                <div class="form-group full">
                    <label>Contact No</label>
                    <input type="text" name="contact_no" value="<?= e($user['contact_no'] ?? ''); ?>">
                </div>
            </div>

            <div style="margin-top:14px;">
                <button type="submit" class="btn btn-blue">Update Profile</button>
            </div>
        </form>
    </section>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>