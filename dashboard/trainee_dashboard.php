<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login(['trainee']);

$pageTitle = 'Trainee Dashboard';
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

/* ATTENDANCE ACTIONS */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attendance_action'], $_POST['training_id'])) {
    $trainingId = (int)$_POST['training_id'];
    $today = date('Y-m-d');
    $now = date('H:i:s');

    $stmt = $conn->prepare('
        SELECT id, time_in, time_out
        FROM attendance_logs
        WHERE user_id = ? AND training_id = ? AND log_date = ?
        LIMIT 1
    ');
    $stmt->bind_param('iis', $userId, $trainingId, $today);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();

    if ($_POST['attendance_action'] === 'time_in') {
        if ($existing && !empty($existing['time_in'])) {
            $error = 'You have already timed in for this training today.';
        } else {
            if ($existing) {
                $stmt = $conn->prepare('
                    UPDATE attendance_logs
                    SET time_in = ?, status = ?, approval_status = "Pending", approved_by = NULL, approved_at = NULL, approval_remarks = NULL
                    WHERE id = ?
                ');
                $status = 'Present';
                $stmt->bind_param('ssi', $now, $status, $existing['id']);
                $stmt->execute();
                $stmt->close();
            } else {
                $stmt = $conn->prepare('
                    INSERT INTO attendance_logs (training_id, user_id, log_date, time_in, status, approval_status)
                    VALUES (?, ?, ?, ?, ?, "Pending")
                ');
                $status = 'Present';
                $stmt->bind_param('iisss', $trainingId, $userId, $today, $now, $status);
                $stmt->execute();
                $stmt->close();
            }

            $logType = 'Attendance';
            $logMessage = 'Timed IN for training ID #' . $trainingId;
            $log = $conn->prepare("
                INSERT INTO trainee_activity_logs (trainee_user_id, activity_type, activity_message)
                VALUES (?, ?, ?)
            ");
            if ($log) {
                $log->bind_param("iss", $userId, $logType, $logMessage);
                $log->execute();
                $log->close();
            }

            $message = 'Time in recorded successfully.';
        }
    }

    if ($_POST['attendance_action'] === 'time_out') {
        if (!$existing || empty($existing['time_in'])) {
            $error = 'You need to time in first before timing out.';
        } elseif (!empty($existing['time_out'])) {
            $error = 'You have already timed out for this training today.';
        } else {
            $stmt = $conn->prepare('
                UPDATE attendance_logs
                SET time_out = ?, approval_status = "Pending", approved_by = NULL, approved_at = NULL, approval_remarks = NULL
                WHERE id = ?
            ');
            $stmt->bind_param('si', $now, $existing['id']);
            $stmt->execute();
            $stmt->close();

            $logType = 'Attendance';
            $logMessage = 'Timed OUT for training ID #' . $trainingId;
            $log = $conn->prepare("
                INSERT INTO trainee_activity_logs (trainee_user_id, activity_type, activity_message)
                VALUES (?, ?, ?)
            ");
            if ($log) {
                $log->bind_param("iss", $userId, $logType, $logMessage);
                $log->execute();
                $log->close();
            }

            $message = 'Time out recorded successfully.';
        }
    }
}

/* GET ASSIGNED TRAININGS */
$assigned = $conn->prepare("
    SELECT
        t.id,
        t.title,
        t.schedule_date,
        t.start_time,
        t.end_time,
        t.venue,
        (
            SELECT al.time_in
            FROM attendance_logs al
            WHERE al.training_id = t.id AND al.user_id = ? AND al.log_date = CURDATE()
            LIMIT 1
        ) AS today_time_in,
        (
            SELECT al.time_out
            FROM attendance_logs al
            WHERE al.training_id = t.id AND al.user_id = ? AND al.log_date = CURDATE()
            LIMIT 1
        ) AS today_time_out
    FROM training_participants tp
    INNER JOIN trainings t ON t.id = tp.training_id
    WHERE tp.user_id = ? AND tp.status = 'Enrolled'
    ORDER BY t.schedule_date DESC, t.start_time DESC
");
$assigned->bind_param('iii', $userId, $userId, $userId);
$assigned->execute();
$assignedResults = $assigned->get_result();

$history = $conn->prepare("
    SELECT
        t.title,
        a.log_date,
        a.time_in,
        a.time_out,
        a.status,
        a.approval_status
    FROM attendance_logs a
    INNER JOIN trainings t ON t.id = a.training_id
    WHERE a.user_id = ?
    ORDER BY a.log_date DESC, a.id DESC
    LIMIT 12
");
$history->bind_param('i', $userId);
$history->execute();
$historyResults = $history->get_result();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_trainee.php';
?>

<div class="topbar">
    <div class="page-title">
        <h1>Trainee Dashboard</h1>
        <p>View your assigned trainings and record your attendance in real time.</p>
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
        <p>Your assigned trainings and attendance records are shown here. Use the Time In and Time Out buttons only for your valid schedules.</p>
    </div>
    <div class="actions">
        <a class="btn btn-primary" href="/PTMS_CAPS/modules/trainee_trainings.php">My Trainings</a>
        <a class="btn btn-outline" href="/PTMS_CAPS/modules/trainee_attendance.php">My Attendance</a>
    </div>
</div>

<div class="grid-2">
    <section class="table-card">
        <h2>Assigned Trainings</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Training</th>
                        <th>Schedule</th>
                        <th>Today</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($assignedResults && $assignedResults->num_rows > 0): ?>
                        <?php while ($row = $assignedResults->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?= e($row['title']); ?></strong>
                                    <div class="small"><?= e($row['venue'] ?? 'No venue'); ?></div>
                                </td>
                                <td>
                                    <?= e($row['schedule_date']); ?><br>
                                    <span class="small">
                                        <?= e(substr((string)$row['start_time'], 0, 5)); ?> - <?= e(substr((string)$row['end_time'], 0, 5)); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="small">
                                        In: <?= e($row['today_time_in'] ?? '—'); ?><br>
                                        Out: <?= e($row['today_time_out'] ?? '—'); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display:flex;gap:8px;flex-wrap:wrap;">
                                        <input type="hidden" name="training_id" value="<?= (int)$row['id']; ?>">
                                        <button class="btn btn-blue" type="submit" name="attendance_action" value="time_in">Time In</button>
                                        <button class="btn btn-light" type="submit" name="attendance_action" value="time_out">Time Out</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="empty">No assigned trainings yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="table-card">
        <h2>Recent Attendance History</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Training</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Approval</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($historyResults && $historyResults->num_rows > 0): ?>
                        <?php while ($row = $historyResults->fetch_assoc()): ?>
                            <tr>
                                <td><?= e($row['log_date']); ?></td>
                                <td><?= e($row['title']); ?></td>
                                <td><?= e($row['time_in'] ?? '—'); ?></td>
                                <td><?= e($row['time_out'] ?? '—'); ?></td>
                                <td><span class="badge"><?= e($row['approval_status'] ?? 'Pending'); ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="empty">No attendance history yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php if ($mustChangePassword === 1): ?>
<div class="security-overlay" id="securityOverlay">
    <div class="security-modal" role="dialog" aria-modal="true" aria-labelledby="securityModalTitle">
        <div class="security-graphic">
            <div class="security-badge">PTMS Security System</div>
            <div class="security-icon-wrap">
                <div class="security-lock">
                    <span></span>
                </div>
            </div>
        </div>

        <div class="security-content">
            <div class="security-label">ACCOUNT PROTECTION</div>
            <h2 id="securityModalTitle">Account Security Update Required</h2>
            <p class="security-subtext">
                For your protection, PTMS requires a password update every 60 days before you can continue using the dashboard.
            </p>

            <div class="security-alert-box">
                <strong>Mandatory Password Update</strong>
                <span>Your password has reached the security renewal period. Please create a new secure password.</span>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error" style="margin-top:16px;"><?= e($error); ?></div>
            <?php endif; ?>

            <form method="POST" class="security-form" id="securityPasswordForm" novalidate>
                <input type="hidden" name="action" value="change_password_now">

                <div class="form-group full password-field">
                    <label for="current_password">Current Password</label>
                    <div class="password-input-wrap">
                        <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
                        <button type="button" class="toggle-password" data-target="current_password">Show</button>
                    </div>
                </div>

                <div class="form-group full password-field">
                    <label for="new_password">New Password</label>
                    <div class="password-input-wrap">
                        <input type="password" id="new_password" name="new_password" required autocomplete="new-password">
                        <button type="button" class="toggle-password" data-target="new_password">Show</button>
                    </div>
                    <div class="strength-wrap">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                        <span class="strength-text" id="strengthText">Password strength</span>
                    </div>
                </div>

                <div class="form-group full password-field">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="password-input-wrap">
                        <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
                        <button type="button" class="toggle-password" data-target="confirm_password">Show</button>
                    </div>
                    <div class="password-match-text" id="passwordMatchText"></div>
                </div>

                <div class="security-tips">
                    <strong>Password Guidelines</strong>
                    <ul>
                        <li>Use at least 8 characters</li>
                        <li>Include uppercase and lowercase letters</li>
                        <li>Include a number or special character</li>
                        <li>Avoid reusing your old password</li>
                    </ul>
                </div>

                <div class="security-footer">
                    <div class="security-note">
                        This update is required before you can continue using the dashboard.
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
    background:rgba(15,23,42,0.62);
    backdrop-filter:blur(5px);
    z-index:5000;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:24px;
    overflow-y:auto;
}
.security-modal{
    width:min(760px, 100%);
    max-height:90vh;
    background:#ffffff;
    border-radius:26px;
    overflow:hidden;
    box-shadow:0 32px 80px rgba(15,23,42,.28);
    border:1px solid #e5e7eb;
    display:flex;
    flex-direction:column;
}
.security-graphic{
    min-height:150px;
    background:
        radial-gradient(circle at 18% 24%, rgba(37,99,235,.08), transparent 26%),
        radial-gradient(circle at 82% 22%, rgba(14,165,233,.08), transparent 24%),
        linear-gradient(180deg, #f8fbff 0%, #eef4ff 100%);
    display:flex;
    align-items:center;
    justify-content:center;
    position:relative;
    padding:20px;
    flex-shrink:0;
}
.security-badge{
    position:absolute;
    top:20px;
    left:24px;
    font-size:12px;
    font-weight:700;
    letter-spacing:.08em;
    color:#475569;
    background:#ffffffcc;
    border:1px solid #dbeafe;
    padding:8px 12px;
    border-radius:999px;
}
.security-icon-wrap{
    width:110px;
    height:110px;
    border-radius:28px;
    background:#ffffff;
    display:flex;
    align-items:center;
    justify-content:center;
    box-shadow:0 18px 35px rgba(30,41,59,.12);
    border:1px solid #e5e7eb;
}
.security-lock{
    width:46px;
    height:36px;
    background:linear-gradient(180deg,#1d4ed8,#123e82);
    border-radius:10px;
    position:relative;
    box-shadow:0 10px 20px rgba(29,78,216,.20);
}
.security-lock::before{
    content:"";
    position:absolute;
    width:28px;
    height:24px;
    border:5px solid #1d4ed8;
    border-bottom:none;
    border-radius:18px 18px 0 0;
    left:50%;
    transform:translateX(-50%);
    top:-22px;
    background:transparent;
}
.security-lock span{
    position:absolute;
    width:8px;
    height:8px;
    background:#fff;
    border-radius:50%;
    left:50%;
    top:12px;
    transform:translateX(-50%);
}
.security-lock span::after{
    content:"";
    position:absolute;
    width:3px;
    height:10px;
    background:#fff;
    left:50%;
    top:7px;
    transform:translateX(-50%);
    border-radius:999px;
}
.security-content{
    padding:30px;
    overflow-y:auto;
}
.security-label{
    font-size:12px;
    font-weight:700;
    letter-spacing:.1em;
    color:#64748b;
    margin-bottom:8px;
}
.security-content h2{
    margin:0 0 10px;
    font-size:32px;
    line-height:1.2;
    color:#0f172a;
}
.security-subtext{
    color:#64748b;
    font-size:15px;
    line-height:1.7;
    margin-bottom:18px;
}
.security-alert-box{
    display:flex;
    flex-direction:column;
    gap:6px;
    padding:16px 18px;
    border-radius:16px;
    background:#eff6ff;
    border:1px solid #bfdbfe;
    color:#123e82;
    margin-bottom:16px;
}
.security-alert-box strong{
    font-size:15px;
}
.security-alert-box span{
    font-size:14px;
    line-height:1.6;
}
.security-form{
    margin-top:8px;
}
.security-form .form-group{
    margin-bottom:16px;
}
.security-form label{
    display:block;
    margin-bottom:8px;
    font-weight:600;
    color:#1f2937;
}
.password-input-wrap{
    position:relative;
}
.security-form input{
    width:100%;
    height:54px;
    border:1px solid #d1d5db;
    border-radius:14px;
    padding:0 90px 0 14px;
    font-size:14px;
    outline:none;
    transition:.2s ease;
    box-sizing:border-box;
    background:#fff;
}
.security-form input:focus{
    border-color:#2563eb;
    box-shadow:0 0 0 4px rgba(37,99,235,.12);
}
.toggle-password{
    position:absolute;
    right:10px;
    top:50%;
    transform:translateY(-50%);
    border:none;
    background:#eff6ff;
    color:#123e82;
    height:34px;
    min-width:58px;
    padding:0 12px;
    border-radius:10px;
    font-size:12px;
    font-weight:700;
    cursor:pointer;
}
.toggle-password:hover{
    background:#dbeafe;
}
.strength-wrap{
    display:flex;
    align-items:center;
    gap:10px;
    margin-top:10px;
}
.strength-bar{
    flex:1;
    height:8px;
    background:#e5e7eb;
    border-radius:999px;
    overflow:hidden;
}
.strength-fill{
    height:100%;
    width:0%;
    border-radius:999px;
    transition:.25s ease;
    background:#ef4444;
}
.strength-text{
    font-size:12px;
    font-weight:700;
    color:#64748b;
    min-width:110px;
    text-align:right;
}
.password-match-text{
    font-size:12px;
    margin-top:8px;
    font-weight:700;
    min-height:18px;
}
.password-match-text.match{
    color:#15803d;
}
.password-match-text.no-match{
    color:#b91c1c;
}
.security-tips{
    margin-top:8px;
    padding:16px 18px;
    background:#f8fafc;
    border:1px solid #e5e7eb;
    border-radius:16px;
    font-size:13px;
    color:#334155;
}
.security-tips strong{
    display:block;
    margin-bottom:8px;
}
.security-tips ul{
    margin:0;
    padding-left:18px;
}
.security-tips li{
    margin-bottom:4px;
}
.security-footer{
    margin-top:18px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:16px;
    flex-wrap:wrap;
    position:sticky;
    bottom:0;
    background:#fff;
    padding-top:14px;
    border-top:1px solid #eef2f7;
}
.security-note{
    color:#64748b;
    font-size:13px;
    line-height:1.6;
}
.security-btn{
    border:none;
    background:linear-gradient(135deg,#123e82,#1d4ed8);
    color:#fff;
    height:52px;
    padding:0 24px;
    border-radius:999px;
    font-size:15px;
    font-weight:700;
    cursor:pointer;
    box-shadow:0 14px 28px rgba(29,78,216,.22);
    transition:.2s ease;
}
.security-btn:hover{
    transform:translateY(-1px);
    opacity:.96;
}
@media (max-width: 640px){
    .security-overlay{
        padding:14px;
    }
    .security-content{
        padding:22px 18px 22px;
    }
    .security-content h2{
        font-size:26px;
    }
    .security-subtext{
        font-size:14px;
    }
    .security-btn{
        width:100%;
    }
    .security-footer{
        flex-direction:column;
        align-items:stretch;
    }
    .strength-wrap{
        flex-direction:column;
        align-items:stretch;
    }
    .strength-text{
        text-align:left;
        min-width:auto;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const currentPassword = document.getElementById('current_password');
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    const strengthFill = document.getElementById('strengthFill');
    const strengthText = document.getElementById('strengthText');
    const passwordMatchText = document.getElementById('passwordMatchText');
    const toggleButtons = document.querySelectorAll('.toggle-password');

    if (currentPassword) {
        setTimeout(() => currentPassword.focus(), 250);
    }

    function evaluateStrength(password) {
        let score = 0;
        if (password.length >= 8) score++;
        if (/[A-Z]/.test(password) && /[a-z]/.test(password)) score++;
        if (/\d/.test(password)) score++;
        if (/[^A-Za-z0-9]/.test(password)) score++;

        if (score <= 1) {
            strengthFill.style.width = '25%';
            strengthFill.style.background = '#ef4444';
            strengthText.textContent = 'Weak';
        } else if (score === 2) {
            strengthFill.style.width = '50%';
            strengthFill.style.background = '#f59e0b';
            strengthText.textContent = 'Fair';
        } else if (score === 3) {
            strengthFill.style.width = '75%';
            strengthFill.style.background = '#3b82f6';
            strengthText.textContent = 'Good';
        } else {
            strengthFill.style.width = '100%';
            strengthFill.style.background = '#10b981';
            strengthText.textContent = 'Strong';
        }

        if (password.length === 0) {
            strengthFill.style.width = '0%';
            strengthFill.style.background = '#ef4444';
            strengthText.textContent = 'Password strength';
        }
    }

    function checkPasswordMatch() {
        if (!confirmPassword.value) {
            passwordMatchText.textContent = '';
            passwordMatchText.className = 'password-match-text';
            return;
        }

        if (newPassword.value === confirmPassword.value) {
            passwordMatchText.textContent = 'Passwords match';
            passwordMatchText.className = 'password-match-text match';
        } else {
            passwordMatchText.textContent = 'Passwords do not match';
            passwordMatchText.className = 'password-match-text no-match';
        }
    }

    if (newPassword) {
        newPassword.addEventListener('input', function () {
            evaluateStrength(this.value);
            checkPasswordMatch();
        });
    }

    if (confirmPassword) {
        confirmPassword.addEventListener('input', checkPasswordMatch);
    }

    toggleButtons.forEach(button => {
        button.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (!input) return;

            if (input.type === 'password') {
                input.type = 'text';
                this.textContent = 'Hide';
            } else {
                input.type = 'password';
                this.textContent = 'Show';
            }
        });
    });
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>