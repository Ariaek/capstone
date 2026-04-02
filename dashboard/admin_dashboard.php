<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login(['admin']);

$pageTitle = 'Admin Dashboard';

function getCount(mysqli $conn, string $sql): int {
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        return (int)($row['total'] ?? 0);
    }
    return 0;
}

$stats = [
    'users'      => getCount($conn, "SELECT COUNT(*) AS total FROM users WHERE is_active = 1"),
    'trainings'  => getCount($conn, "SELECT COUNT(*) AS total FROM trainings"),
    'attendance' => getCount($conn, "SELECT COUNT(*) AS total FROM attendance_logs"),
    'officers'   => getCount($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'training_officer' AND is_active = 1"),
];

$recentUsers = $conn->query("
    SELECT employee_no, full_name, role, department
    FROM users
    ORDER BY id DESC
    LIMIT 8
");

$recentTrainings = $conn->query("
    SELECT 
        t.title,
        t.schedule_date,
        t.venue,
        COALESCE(u.full_name, 'Unassigned') AS trainer_name
    FROM trainings t
    LEFT JOIN users u ON u.id = t.trainer_user_id
    ORDER BY t.schedule_date DESC
    LIMIT 8
");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="topbar">
    <div class="page-title">
        <h1>Manager / Admin Dashboard</h1>
        <p>Oversee users, training structure, attendance records, and system-wide information.</p>
    </div>
</div>

<div class="hero-panel">
    <div>
        <h1>Welcome, <span class="highlight"><?= e($_SESSION['full_name'] ?? 'Administrator'); ?></span></h1>
        <p>This dashboard focuses on management oversight, user administration, training records, and monitoring across the whole platform.</p>
    </div>
    <div class="actions">
        <a class="btn btn-primary" href="/PTMS_CAPS/modules/users.php">Manage Users</a>
        <a class="btn btn-outline" href="/PTMS_CAPS/modules/reports.php">Open Reports</a>
    </div>
</div>

<div class="grid-4">
    <div class="stat-card">
        <h3>Active Users</h3>
        <strong><?= $stats['users']; ?></strong>
        <span>Accounts in the system</span>
    </div>
    <div class="stat-card">
        <h3>Training Officers</h3>
        <strong><?= $stats['officers']; ?></strong>
        <span>Operational training staff</span>
    </div>
    <div class="stat-card">
        <h3>Trainings</h3>
        <strong><?= $stats['trainings']; ?></strong>
        <span>Stored schedules</span>
    </div>
    <div class="stat-card">
        <h3>Attendance Logs</h3>
        <strong><?= $stats['attendance']; ?></strong>
        <span>Recorded attendance</span>
    </div>
</div>

<div class="grid-2 section-space">
    <section class="table-card">
        <h2>Recent Users</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Employee No</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Department</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recentUsers && $recentUsers->num_rows > 0): ?>
                        <?php while ($row = $recentUsers->fetch_assoc()): ?>
                            <tr>
                                <td><?= e($row['employee_no'] ?? '—'); ?></td>
                                <td><?= e($row['full_name'] ?? '—'); ?></td>
                                <td><?= e($row['role'] ?? '—'); ?></td>
                                <td><?= e($row['department'] ?? '—'); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="empty">No recent users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="table-card">
        <h2>Recent Trainings</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Date</th>
                        <th>Venue</th>
                        <th>Trainer</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recentTrainings && $recentTrainings->num_rows > 0): ?>
                        <?php while ($row = $recentTrainings->fetch_assoc()): ?>
                            <tr>
                                <td><?= e($row['title'] ?? '—'); ?></td>
                                <td><?= e($row['schedule_date'] ?? '—'); ?></td>
                                <td><?= e($row['venue'] ?? '—'); ?></td>
                                <td><?= e($row['trainer_name'] ?? 'Unassigned'); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="empty">No recent trainings found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>