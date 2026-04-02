<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login(['training_officer']);

$pageTitle = 'Training Officer Dashboard';
$userId = (int)$_SESSION['user_id'];

$message = '';
$error = '';

/*
|--------------------------------------------------------------------------
| PASSWORD SECURITY CHECK
|--------------------------------------------------------------------------
| Show modal when:
| 1) must_change_password = 1
| 2) password_updated_at is NULL
| 3) last password update was 60+ days ago
|--------------------------------------------------------------------------
*/
$mustChangePassword = 0;
$securityStmt = $conn->prepare("
    SELECT must_change_password, password_updated_at
    FROM users
    WHERE id = ?
    LIMIT 1
");
$securityStmt->bind_param("i", $userId);
$securityStmt->execute();
$securityRow = $securityStmt->get_result()->fetch_assoc();
$securityStmt->close();

if ($securityRow) {
    $mustChangePassword = (int)($securityRow['must_change_password'] ?? 0);

    $passwordUpdatedAt = $securityRow['password_updated_at'] ?? null;

    if ($mustChangePassword !== 1) {
        if (empty($passwordUpdatedAt)) {
            $mustChangePassword = 1;
        } else {
            $lastPasswordUpdate = new DateTime($passwordUpdatedAt);
            $todayDate = new DateTime();
            $daysSinceChange = (int)$lastPasswordUpdate->diff($todayDate)->days;

            if ($daysSinceChange >= 60) {
                $mustChangePassword = 1;
            }
        }
    }
}

/* HANDLE PASSWORD CHANGE */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password_now') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $error = 'Please complete all password fields.';
        $mustChangePassword = 1;
    } elseif (strlen($newPassword) < 8) {
        $error = 'New password must be at least 8 characters.';
        $mustChangePassword = 1;
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New password and confirm password do not match.';
        $mustChangePassword = 1;
    } else {
        $stmt = $conn->prepare("
            SELECT password_hash
            FROM users
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $userRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$userRow || !password_verify($currentPassword, $userRow['password_hash'])) {
            $error = 'Current password is incorrect.';
            $mustChangePassword = 1;
        } elseif (password_verify($newPassword, $userRow['password_hash'])) {
            $error = 'New password must be different from your current password.';
            $mustChangePassword = 1;
        } else {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

            $update = $conn->prepare("
                UPDATE users
                SET password_hash = ?, must_change_password = 0, password_updated_at = NOW()
                WHERE id = ?
            ");
            $update->bind_param("si", $newHash, $userId);

            if ($update->execute()) {
                $message = 'Password updated successfully.';
                $mustChangePassword = 0;
            } else {
                $error = 'Failed to update password.';
                $mustChangePassword = 1;
            }

            $update->close();
        }
    }
}

/* DASHBOARD STATS */
$trainingsHandled = 0;
$assignedTrainees = 0;
$attendanceLogs = 0;

$sql1 = "SELECT COUNT(*) AS total FROM trainings WHERE trainer_user_id = $userId";
$res1 = mysqli_query($conn, $sql1);
if ($res1) {
    $row = mysqli_fetch_assoc($res1);
    $trainingsHandled = (int)$row['total'];
}

$sql2 = "
    SELECT COUNT(DISTINCT tp.user_id) AS total
    FROM training_participants tp
    INNER JOIN trainings t ON t.id = tp.training_id
    WHERE t.trainer_user_id = $userId
";
$res2 = mysqli_query($conn, $sql2);
if ($res2) {
    $row = mysqli_fetch_assoc($res2);
    $assignedTrainees = (int)$row['total'];
}

$sql3 = "
    SELECT COUNT(*) AS total
    FROM attendance_logs a
    INNER JOIN trainings t ON t.id = a.training_id
    WHERE t.trainer_user_id = $userId
";
$res3 = mysqli_query($conn, $sql3);
if ($res3) {
    $row = mysqli_fetch_assoc($res3);
    $attendanceLogs = (int)$row['total'];
}

$today = date('Y-m-d');

$todayTrainings = mysqli_query($conn, "
    SELECT title, start_time, end_time, venue
    FROM trainings
    WHERE trainer_user_id = $userId AND schedule_date = '$today'
    ORDER BY start_time ASC
");

$recentTrainings = mysqli_query($conn, "
    SELECT title, schedule_date, start_time, end_time, venue
    FROM trainings
    WHERE trainer_user_id = $userId
    ORDER BY schedule_date DESC, start_time DESC
    LIMIT 5
");

$recentAttendance = mysqli_query($conn, "
    SELECT u.full_name, t.title, a.time_in, a.time_out
    FROM attendance_logs a
    INNER JOIN trainings t ON t.id = a.training_id
    INNER JOIN users u ON u.id = a.user_id
    WHERE t.trainer_user_id = $userId
    ORDER BY a.id DESC
    LIMIT 6
");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_officer.php';
?>

<div class="topbar">
    <div class="page-title">
        <h1>Training Officer Dashboard</h1>
        <p>Monitor trainings, manage participants, and track attendance.</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= e($message); ?></div>
<?php endif; ?>

<?php if ($error && !$mustChangePassword): ?>
    <div class="alert alert-error"><?= e($error); ?></div>
<?php endif; ?>

<div class="hero-panel">
    <div>
        <h1>Welcome, <span class="highlight"><?= e($_SESSION['full_name']); ?></span></h1>
        <p>Oversee your assigned training sessions and ensure smooth execution.</p>
    </div>

    <div class="actions">
        <a class="btn btn-primary" href="/PTMS_CAPS/modules/officer_trainings.php">My Trainings</a>
        <a class="btn btn-outline" href="/PTMS_CAPS/modules/officer_attendance.php">View Attendance</a>
    </div>
</div>

<div class="grid-3">
    <div class="stat-card">
        <h3>Trainings</h3>
        <strong><?= $trainingsHandled ?></strong>
        <span>Handled sessions</span>
    </div>

    <div class="stat-card">
        <h3>Trainees</h3>
        <strong><?= $assignedTrainees ?></strong>
        <span>Participants</span>
    </div>

    <div class="stat-card">
        <h3>Attendance</h3>
        <strong><?= $attendanceLogs ?></strong>
        <span>Recorded logs</span>
    </div>
</div>

<div class="table-card section-space">
    <h2>Today's Trainings</h2>
    <table>
        <thead>
            <tr>
                <th>Training</th>
                <th>Time</th>
                <th>Venue</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($todayTrainings && mysqli_num_rows($todayTrainings) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($todayTrainings)): ?>
            <tr>
                <td><?= e($row['title']) ?></td>
                <td><?= substr((string)$row['start_time'],0,5) ?> - <?= substr((string)$row['end_time'],0,5) ?></td>
                <td><?= e($row['venue'] ?: '—') ?></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="3" class="empty">No trainings today</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="grid-2 section-space">
    <section class="table-card">
        <h2>Recent Trainings</h2>
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($recentTrainings && mysqli_num_rows($recentTrainings) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($recentTrainings)): ?>
                <tr>
                    <td><?= e($row['title']) ?></td>
                    <td><?= e($row['schedule_date']) ?></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="2" class="empty">No recent trainings found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </section>

    <section class="table-card">
        <h2>Recent Attendance</h2>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Training</th>
                    <th>In</th>
                    <th>Out</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($recentAttendance && mysqli_num_rows($recentAttendance) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($recentAttendance)): ?>
                <tr>
                    <td><?= e($row['full_name']) ?></td>
                    <td><?= e($row['title']) ?></td>
                    <td><?= e($row['time_in'] ?: '—') ?></td>
                    <td><?= e($row['time_out'] ?: '—') ?></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4" class="empty">No attendance logs found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </section>
</div>

<?php if ($mustChangePassword === 1): ?>
<div class="security-overlay" id="securityOverlay">
    <div class="security-modal">
        <div class="security-graphic">
            <div class="security-icon-wrap">
                <div class="security-icon">!</div>
            </div>
        </div>

        <div class="security-content">
            <h2>Security Notice</h2>
            <p class="security-subtext">
                For your account protection, PTMS requires a password update every 60 days before continuing normal use of the dashboard.
            </p>

            <div class="security-alert-box">
                <strong>Password Update Required</strong>
                <span>Your password has reached the security renewal period. Please set a stronger password now.</span>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error" style="margin-top:16px;"><?= e($error); ?></div>
            <?php endif; ?>

            <form method="POST" class="security-form">
                <input type="hidden" name="action" value="change_password_now">

                <div class="form-group full">
                    <label>Current Password</label>
                    <input type="password" name="current_password" required>
                </div>

                <div class="form-group full">
                    <label>New Password</label>
                    <input type="password" name="new_password" required>
                </div>

                <div class="form-group full">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" required>
                </div>

                <div class="security-footer">
                    <div class="security-note">
                        Use at least 8 characters and avoid reusing old passwords.
                    </div>
                    <button type="submit" class="security-btn">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.security-overlay{
    position:fixed;
    inset:0;
    background:rgba(15,23,42,0.58);
    backdrop-filter:blur(4px);
    z-index:5000;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:24px;
}
.security-modal{
    width:min(720px, 100%);
    background:#fff;
    border-radius:28px;
    overflow:hidden;
    box-shadow:0 30px 80px rgba(0,0,0,0.25);
    border:1px solid #e5e7eb;
}
.security-graphic{
    min-height:140px;
    background:
        radial-gradient(circle at 20% 30%, rgba(18,62,130,.06), transparent 28%),
        radial-gradient(circle at 80% 40%, rgba(18,62,130,.05), transparent 24%),
        linear-gradient(180deg, #f8fbff 0%, #f1f5f9 100%);
    display:flex;
    align-items:center;
    justify-content:center;
    position:relative;
}
.security-graphic::before{
    content:"";
    position:absolute;
    width:160px;
    height:4px;
    background:#d1d5db;
    border-radius:999px;
    top:58px;
    left:calc(50% - 120px);
    transform:rotate(-12deg);
}
.security-graphic::after{
    content:"";
    position:absolute;
    width:140px;
    height:4px;
    background:#cbd5e1;
    border-radius:999px;
    top:64px;
    right:calc(50% - 120px);
    transform:rotate(14deg);
}
.security-icon-wrap{
    width:88px;
    height:88px;
    border-radius:22px;
    background:#ffffff;
    display:flex;
    align-items:center;
    justify-content:center;
    box-shadow:0 14px 28px rgba(0,0,0,.10);
    border:1px solid #e5e7eb;
    position:relative;
    z-index:2;
}
.security-icon{
    width:0;
    height:0;
    border-left:24px solid transparent;
    border-right:24px solid transparent;
    border-bottom:42px solid #ef4444;
    position:relative;
}
.security-icon::after{
    content:"!";
    position:absolute;
    left:-4px;
    top:13px;
    color:#fff;
    font-weight:800;
    font-size:20px;
}
.security-content{
    padding:28px 30px 30px;
}
.security-content h2{
    font-size:34px;
    color:#111827;
    margin-bottom:10px;
}
.security-subtext{
    color:#6b7280;
    font-size:16px;
    line-height:1.7;
    margin-bottom:18px;
}
.security-alert-box{
    display:flex;
    flex-direction:column;
    gap:6px;
    padding:16px 18px;
    border-radius:16px;
    background:#fff7ed;
    border:1px solid #fdba74;
    color:#9a3412;
}
.security-alert-box strong{
    font-size:15px;
}
.security-alert-box span{
    font-size:14px;
    line-height:1.6;
}
.security-form{
    margin-top:18px;
}
.security-footer{
    margin-top:12px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:16px;
    flex-wrap:wrap;
}
.security-note{
    color:#6b7280;
    font-size:13px;
    line-height:1.6;
}
.security-btn{
    border:none;
    background:#7c3aed;
    color:#fff;
    height:52px;
    padding:0 24px;
    border-radius:999px;
    font-size:15px;
    font-weight:700;
    cursor:pointer;
    box-shadow:0 12px 24px rgba(124,58,237,.22);
    transition:.2s ease;
}
.security-btn:hover{
    transform:translateY(-1px);
    background:#6d28d9;
}
@media (max-width: 640px){
    .security-content{
        padding:22px 18px 22px;
    }
    .security-content h2{
        font-size:28px;
    }
    .security-subtext{
        font-size:14px;
    }
    .security-btn{
        width:100%;
    }
}
</style>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>