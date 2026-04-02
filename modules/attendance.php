<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login(['admin']);

date_default_timezone_set('Asia/Manila');

$pageTitle = 'Attendance';

/*
|--------------------------------------------------------------------------
| SETTINGS
|--------------------------------------------------------------------------
*/
$today = date('Y-m-d');
$lateThreshold = '08:15:00'; // change if needed

/*
|--------------------------------------------------------------------------
| FILTER VALUES
|--------------------------------------------------------------------------
*/
$search   = trim($_GET['search'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo   = trim($_GET['date_to'] ?? '');

/*
|--------------------------------------------------------------------------
| MAIN FILTERED ATTENDANCE QUERY
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT 
        al.id,
        al.user_id,
        al.log_date,
        al.time_in,
        al.time_out,
        u.employee_no,
        u.full_name
    FROM attendance_logs al
    LEFT JOIN users u ON u.id = al.user_id
    WHERE 1=1
";

$types = '';
$params = [];

if ($search !== '') {
    $sql .= " AND (
        u.employee_no LIKE ?
        OR u.full_name LIKE ?
        OR al.log_date LIKE ?
        OR al.time_in LIKE ?
        OR al.time_out LIKE ?
    )";
    $like = "%{$search}%";
    $types .= 'sssss';
    array_push($params, $like, $like, $like, $like, $like);
}

if ($dateFrom !== '') {
    $sql .= " AND al.log_date >= ?";
    $types .= 's';
    $params[] = $dateFrom;
}

if ($dateTo !== '') {
    $sql .= " AND al.log_date <= ?";
    $types .= 's';
    $params[] = $dateTo;
}

$sql .= " ORDER BY al.log_date DESC, al.id DESC LIMIT 300";

$attendanceLogs = [];
$stmt = $conn->prepare($sql);

if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $attendanceLogs[] = $row;
        }
    }

    $stmt->close();
}

/*
|--------------------------------------------------------------------------
| COUNTS
|--------------------------------------------------------------------------
*/
$totalLogs = 0;
$qTotalLogs = $conn->query("SELECT COUNT(*) AS total FROM attendance_logs");
if ($qTotalLogs && $r = $qTotalLogs->fetch_assoc()) {
    $totalLogs = (int)($r['total'] ?? 0);
}

$filteredLogs = count($attendanceLogs);

/*
|--------------------------------------------------------------------------
| LIVE STATUS COUNTS
|--------------------------------------------------------------------------
*/

/* today's attendance log count */
$todayLogs = 0;
$qTodayLogs = $conn->query("
    SELECT COUNT(*) AS total
    FROM attendance_logs
    WHERE log_date = CURDATE()
");
if ($qTodayLogs && $r = $qTodayLogs->fetch_assoc()) {
    $todayLogs = (int)($r['total'] ?? 0);
}

/* currently timed-in users */
$currentTimedIn = 0;
$qCurrentTimedIn = $conn->query("
    SELECT COUNT(*) AS total
    FROM attendance_logs
    WHERE log_date = CURDATE()
      AND time_in IS NOT NULL
      AND time_in <> ''
      AND (time_out IS NULL OR time_out = '' OR time_out = '00:00:00')
");
if ($qCurrentTimedIn && $r = $qCurrentTimedIn->fetch_assoc()) {
    $currentTimedIn = (int)($r['total'] ?? 0);
}

/* late users count */
$lateCount = 0;
$stmtLateCount = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM attendance_logs
    WHERE log_date = CURDATE()
      AND time_in IS NOT NULL
      AND time_in <> ''
      AND time_in > ?
");
if ($stmtLateCount) {
    $stmtLateCount->bind_param('s', $lateThreshold);
    $stmtLateCount->execute();
    $resLateCount = $stmtLateCount->get_result();
    if ($resLateCount && $r = $resLateCount->fetch_assoc()) {
        $lateCount = (int)($r['total'] ?? 0);
    }
    $stmtLateCount->close();
}

/*
|--------------------------------------------------------------------------
| ABSENT DETECTION
|--------------------------------------------------------------------------
| TEMPORARY VERSION:
| counts users with role trainee/employee who have no log today
|--------------------------------------------------------------------------
*/
$absentCount = 0;
$qAbsentCount = $conn->query("
    SELECT COUNT(*) AS total
    FROM users u
    WHERE u.role IN ('trainee', 'employee')
      AND NOT EXISTS (
          SELECT 1
          FROM attendance_logs al
          WHERE al.user_id = u.id
            AND al.log_date = CURDATE()
      )
");
if ($qAbsentCount && $r = $qAbsentCount->fetch_assoc()) {
    $absentCount = (int)($r['total'] ?? 0);
}

/* pending time-out is same as currently timed-in */
$pendingTimeoutCount = $currentTimedIn;

/* live status */
$liveStatusLabel = $todayLogs > 0 ? 'Active Today' : 'No Activity Today';
$liveStatusText  = $todayLogs > 0
    ? 'Attendance logs are being recorded today'
    : 'No attendance activity recorded yet today';

/*
|--------------------------------------------------------------------------
| DETAIL TABLES
|--------------------------------------------------------------------------
*/

/* users currently timed-in */
$timedInList = $conn->query("
    SELECT 
        u.employee_no,
        u.full_name,
        al.log_date,
        al.time_in,
        al.time_out
    FROM attendance_logs al
    LEFT JOIN users u ON u.id = al.user_id
    WHERE al.log_date = CURDATE()
      AND al.time_in IS NOT NULL
      AND al.time_in <> ''
      AND (al.time_out IS NULL OR al.time_out = '' OR al.time_out = '00:00:00')
    ORDER BY al.time_in ASC
");

/* late users list */
$stmtLateList = $conn->prepare("
    SELECT 
        u.employee_no,
        u.full_name,
        al.log_date,
        al.time_in
    FROM attendance_logs al
    LEFT JOIN users u ON u.id = al.user_id
    WHERE al.log_date = CURDATE()
      AND al.time_in IS NOT NULL
      AND al.time_in <> ''
      AND al.time_in > ?
    ORDER BY al.time_in ASC
");
$lateList = false;
if ($stmtLateList) {
    $stmtLateList->bind_param('s', $lateThreshold);
    $stmtLateList->execute();
    $lateList = $stmtLateList->get_result();
}

/* absent users list */
$absentList = $conn->query("
    SELECT 
        u.employee_no,
        u.full_name,
        u.role
    FROM users u
    WHERE u.role IN ('trainee', 'employee')
      AND NOT EXISTS (
          SELECT 1
          FROM attendance_logs al
          WHERE al.user_id = u.id
            AND al.log_date = CURDATE()
      )
    ORDER BY u.full_name ASC
");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.modal.active {
    display: flex;
}
.modal-box {
    width: 100%;
    max-width: 620px;
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 20px 50px rgba(0,0,0,0.18);
    overflow: hidden;
}
.modal-header,
.modal-footer {
    padding: 18px 22px;
}
.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #eef2f7;
}
.modal-footer {
    border-top: 1px solid #eef2f7;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    flex-wrap: wrap;
}
.modal-body {
    padding: 22px;
}
.modal-header h3 {
    margin: 0;
    font-size: 22px;
    color: #143d8d;
}
.modal-close {
    border: none;
    background: transparent;
    font-size: 28px;
    cursor: pointer;
    line-height: 1;
    color: #475569;
}
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.form-group.full {
    grid-column: 1 / -1;
}
.form-group label {
    font-weight: 600;
    color: #334155;
}
.form-group input {
    height: 46px;
    border: 1px solid #dbe3ef;
    border-radius: 10px;
    padding: 0 14px;
    font-size: 14px;
    outline: none;
}
.form-group input:focus {
    border-color: #1d4ed8;
    box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.12);
}
.btn {
    border: none;
    border-radius: 10px;
    padding: 12px 18px;
    font-weight: 700;
    cursor: pointer;
    transition: 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.btn:hover {
    transform: translateY(-1px);
}
.btn-primary,
.btn-blue {
    background: #1d4ed8;
    color: #fff;
}
.btn-outline {
    background: transparent;
    color: #fff;
    border: 2px solid rgba(255,255,255,0.75);
}
.btn-light {
    background: #e2e8f0;
    color: #0f172a;
}
.btn-print {
    background: #0f766e;
    color: #fff;
}
.empty {
    text-align: center;
    color: #64748b;
    padding: 20px !important;
}
.table-tools {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 14px;
    flex-wrap: wrap;
}
.filter-badges {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 12px;
}
.filter-badge {
    background: #eff6ff;
    color: #1d4ed8;
    padding: 8px 12px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 600;
}
.grid-3 {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
}
.report-meta {
    display: none;
}
.status-chip {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
}
.status-live {
    background: #dcfce7;
    color: #166534;
}
.status-late {
    background: #fee2e2;
    color: #991b1b;
}
.status-pending {
    background: #fef3c7;
    color: #92400e;
}
.status-absent {
    background: #f3f4f6;
    color: #374151;
}
@media print {
    body * {
        visibility: hidden !important;
    }
    #attendancePrintArea,
    #attendancePrintArea * {
        visibility: visible !important;
    }
    #attendancePrintArea {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        background: #fff;
        padding: 20px;
    }
    .no-print,
    .sidebar,
    .topbar,
    .hero-panel,
    .modal {
        display: none !important;
    }
    .report-meta {
        display: block;
        margin-bottom: 18px;
    }
}
@media (max-width: 1100px) {
    .grid-3 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (max-width: 768px) {
    .form-grid,
    .grid-3 {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="topbar">
    <div class="page-title">
        <h1>Attendance</h1>
        <p>View recorded time-in and time-out logs of users participating in trainings and activities.</p>
    </div>
</div>

<div class="hero-panel">
    <div>
        <h2>Attendance Monitoring</h2>
        <p>Review attendance records, live monitoring, current timed-in users, late logs, absences, and pending time-out alerts.</p>

        <div class="filter-badges">
            <?php if ($search !== ''): ?>
                <span class="filter-badge">Search: <?= htmlspecialchars($search); ?></span>
            <?php endif; ?>

            <?php if ($dateFrom !== ''): ?>
                <span class="filter-badge">From: <?= htmlspecialchars($dateFrom); ?></span>
            <?php endif; ?>

            <?php if ($dateTo !== ''): ?>
                <span class="filter-badge">To: <?= htmlspecialchars($dateTo); ?></span>
            <?php endif; ?>

            <?php if ($search === '' && $dateFrom === '' && $dateTo === ''): ?>
                <span class="filter-badge">Showing latest logs</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="actions">
        <button class="btn btn-primary" type="button" data-modal-target="#exportAttendanceModal">Export Attendance</button>
        <button class="btn btn-outline" type="button" data-modal-target="#filterAttendanceModal">Filter Logs</button>
    </div>
</div>

<div class="grid-3">
    <div class="stat-card">
        <h3>Total Logs</h3>
        <strong><?= $totalLogs; ?></strong>
        <span>Recorded attendance entries</span>
    </div>

    <div class="stat-card">
        <h3>Live Status</h3>
        <strong><?= htmlspecialchars($liveStatusLabel); ?></strong>
        <span><?= htmlspecialchars($liveStatusText); ?></span>
    </div>

    <div class="stat-card">
        <h3>Users Timed-In</h3>
        <strong><?= $currentTimedIn; ?></strong>
        <span>Currently timed-in users</span>
    </div>

    <div class="stat-card">
        <h3>Late Detection</h3>
        <strong><?= $lateCount; ?></strong>
        <span>Users logged in after <?= htmlspecialchars($lateThreshold); ?></span>
    </div>

    <div class="stat-card">
        <h3>Absent Detection</h3>
        <strong><?= $absentCount; ?></strong>
        <span>Users with no attendance today</span>
    </div>

    <div class="stat-card">
        <h3>Pending Time-Out</h3>
        <strong><?= $pendingTimeoutCount; ?></strong>
        <span>Users with time-in but no time-out yet</span>
    </div>
</div>

<div class="table-card section-space" id="attendancePrintArea">
    <div class="report-meta">
        <h2 style="margin-bottom:6px;">Attendance Report</h2>
        <p style="margin:0 0 4px 0;">Generated on: <?= date('Y-m-d h:i A'); ?></p>
        <p style="margin:0;">
            Search: <?= $search !== '' ? htmlspecialchars($search) : 'All'; ?> |
            Date From: <?= $dateFrom !== '' ? htmlspecialchars($dateFrom) : 'Any'; ?> |
            Date To: <?= $dateTo !== '' ? htmlspecialchars($dateTo) : 'Any'; ?>
        </p>
    </div>

    <div class="table-tools no-print">
        <h2 style="margin:0;">Attendance Logs</h2>

        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <button type="button" class="btn btn-print" id="printAttendanceBtn">Print Attendance</button>

            <?php if ($search !== '' || $dateFrom !== '' || $dateTo !== ''): ?>
                <a href="attendance.php" class="btn btn-light">Clear Filters</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-wrap">
        <table id="attendanceTable">
            <thead>
                <tr>
                    <th>Employee No</th>
                    <th>Name</th>
                    <th>Date</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($attendanceLogs)): ?>
                    <?php foreach ($attendanceLogs as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['employee_no'] ?? '—'); ?></td>
                            <td><?= htmlspecialchars($row['full_name'] ?? '—'); ?></td>
                            <td><?= htmlspecialchars($row['log_date'] ?? '—'); ?></td>
                            <td><?= htmlspecialchars($row['time_in'] ?? '—'); ?></td>
                            <td><?= htmlspecialchars($row['time_out'] ?? '—'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="empty">No attendance logs found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="table-card section-space">
    <h2>Users Currently Timed-In</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Employee No</th>
                    <th>Name</th>
                    <th>Date</th>
                    <th>Time In</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($timedInList && $timedInList->num_rows > 0): ?>
                    <?php while ($row = $timedInList->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['employee_no'] ?? '—'); ?></td>
                            <td><?= htmlspecialchars($row['full_name'] ?? '—'); ?></td>
                            <td><?= htmlspecialchars($row['log_date'] ?? '—'); ?></td>
                            <td><?= htmlspecialchars($row['time_in'] ?? '—'); ?></td>
                            <td><span class="status-chip status-pending">Pending Time-Out</span></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="empty">No users are currently timed-in.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="table-card section-space">
    <h2>Late Users Today</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Employee No</th>
                    <th>Name</th>
                    <th>Date</th>
                    <th>Time In</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($lateList && $lateList->num_rows > 0): ?>
                    <?php while ($row = $lateList->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['employee_no'] ?? '—'); ?></td>
                            <td><?= htmlspecialchars($row['full_name'] ?? '—'); ?></td>
                            <td><?= htmlspecialchars($row['log_date'] ?? '—'); ?></td>
                            <td><?= htmlspecialchars($row['time_in'] ?? '—'); ?></td>
                            <td><span class="status-chip status-late">Late</span></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="empty">No late users detected today.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="table-card section-space">
    <h2>Absent Users Today</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Employee No</th>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($absentList && $absentList->num_rows > 0): ?>
                    <?php while ($row = $absentList->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['employee_no'] ?? '—'); ?></td>
                            <td><?= htmlspecialchars($row['full_name'] ?? '—'); ?></td>
                            <td><?= htmlspecialchars($row['role'] ?? '—'); ?></td>
                            <td><span class="status-chip status-absent">Absent Today</span></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="empty">No absent users detected today.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal" id="exportAttendanceModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Export Attendance</h3>
            <button class="modal-close" type="button" data-close-modal>&times;</button>
        </div>
        <div class="modal-body">
            <p style="margin-top:0; color:#475569;">
                Download a real CSV attendance report using the current filters.
            </p>
            <p style="margin-bottom:0; color:#64748b;">
                The search text and date range will be included in the exported report.
            </p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-light" type="button" data-close-modal>Close</button>
            <a
                class="btn btn-blue"
                href="export_attendance.php?search=<?= urlencode($search); ?>&date_from=<?= urlencode($dateFrom); ?>&date_to=<?= urlencode($dateTo); ?>"
            >
                Download CSV
            </a>
        </div>
    </div>
</div>

<div class="modal" id="filterAttendanceModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Filter Attendance Logs</h3>
            <button class="modal-close" type="button" data-close-modal>&times;</button>
        </div>

        <form method="GET" action="attendance.php">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group full">
                        <label for="search">Search by any text</label>
                        <input
                            type="text"
                            id="search"
                            name="search"
                            value="<?= htmlspecialchars($search); ?>"
                            placeholder="Type employee no, name, date, or time..."
                        >
                    </div>

                    <div class="form-group">
                        <label for="date_from">Date From</label>
                        <input
                            type="date"
                            id="date_from"
                            name="date_from"
                            value="<?= htmlspecialchars($dateFrom); ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="date_to">Date To</label>
                        <input
                            type="date"
                            id="date_to"
                            name="date_to"
                            value="<?= htmlspecialchars($dateTo); ?>"
                        >
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <a href="attendance.php" class="btn btn-light">Reset</a>
                <button class="btn btn-blue" type="submit">Generate Report</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-modal-target]').forEach(function (button) {
        button.addEventListener('click', function () {
            const target = document.querySelector(button.getAttribute('data-modal-target'));
            if (target) target.classList.add('active');
        });
    });

    document.querySelectorAll('[data-close-modal]').forEach(function (button) {
        button.addEventListener('click', function () {
            const modal = button.closest('.modal');
            if (modal) modal.classList.remove('active');
        });
    });

    document.querySelectorAll('.modal').forEach(function (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                modal.classList.remove('active');
            }
        });
    });

    const printBtn = document.getElementById('printAttendanceBtn');
    if (printBtn) {
        printBtn.addEventListener('click', function () {
            window.print();
        });
    }
});
</script>

<?php
if (isset($stmtLateList) && $stmtLateList) {
    $stmtLateList->close();
}
include __DIR__ . '/../includes/footer.php';
?>