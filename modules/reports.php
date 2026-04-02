<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login(['admin']);

$pageTitle = 'Reports';

$totalUsers = 0;
$totalTrainings = 0;
$totalAttendance = 0;

$r1 = $conn->query("SELECT COUNT(*) AS total FROM users");
if ($r1 && $row = $r1->fetch_assoc()) {
    $totalUsers = (int)($row['total'] ?? 0);
}

$r2 = $conn->query("SELECT COUNT(*) AS total FROM trainings");
if ($r2 && $row = $r2->fetch_assoc()) {
    $totalTrainings = (int)($row['total'] ?? 0);
}

$r3 = $conn->query("SELECT COUNT(*) AS total FROM attendance_logs");
if ($r3 && $row = $r3->fetch_assoc()) {
    $totalAttendance = (int)($row['total'] ?? 0);
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="topbar">
    <div class="page-title">
        <h1>Reports</h1>
        <p>Review summary information and reporting insights for users, trainings, and attendance.</p>
    </div>
</div>

<div class="hero-panel">
    <div>
        <h2>System Reports</h2>
        <p>This section provides a quick reporting overview of the PTMS database and may later be extended into downloadable reports.</p>
    </div>
    <div class="actions">
        <button class="btn btn-primary" data-modal-target="#generateReportModal">Generate Report</button>
        <button class="btn btn-outline" data-modal-target="#printSummaryModal">Print Summary</button>
    </div>
</div>

<div class="grid-3" id="reportSummaryArea">
    <div class="stat-card">
        <h3>Total Users</h3>
        <strong><?= $totalUsers; ?></strong>
        <span>Registered people in system</span>
    </div>
    <div class="stat-card">
        <h3>Total Trainings</h3>
        <strong><?= $totalTrainings; ?></strong>
        <span>Available training records</span>
    </div>
    <div class="stat-card">
        <h3>Total Attendance Logs</h3>
        <strong><?= $totalAttendance; ?></strong>
        <span>System attendance entries</span>
    </div>
</div>

<div class="grid-2 section-space">
    <div class="content-card">
        <h2>Report Coverage</h2>
        <div class="info-list">
            <div class="info-item">
                <strong>User Summary</strong>
                Shows how many accounts are active and stored in the system.
            </div>
            <div class="info-item">
                <strong>Training Summary</strong>
                Displays the amount of scheduled and stored training sessions.
            </div>
            <div class="info-item">
                <strong>Attendance Summary</strong>
                Displays attendance activity based on recorded logs.
            </div>
        </div>
    </div>

    <div class="content-card">
        <h2>Planned Report Features</h2>
        <div class="info-list">
            <div class="info-item">
                <strong>Printable Reports</strong>
                Generate organized summaries for administration use.
            </div>
            <div class="info-item">
                <strong>Filtered Reporting</strong>
                Filter reports by department, role, or date.
            </div>
            <div class="info-item">
                <strong>Performance Insights</strong>
                Extend reporting into training completion and employee development metrics.
            </div>
        </div>
    </div>
</div>

<div class="modal" id="generateReportModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Generate Report</h3>
            <button class="modal-close" data-close-modal>&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group full">
                <label>Report Type</label>
                <select>
                    <option>User Summary</option>
                    <option>Training Summary</option>
                    <option>Attendance Summary</option>
                    <option>Full PTMS Overview</option>
                </select>
            </div>
            <div class="form-group full" style="margin-top:14px;">
                <label>Remarks</label>
                <textarea placeholder="Add remarks or notes"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-light" data-close-modal>Cancel</button>
            <button class="btn btn-blue" type="button" onclick="alert('Report generated. Add backend logic here for PDF/Excel generation.');">Generate</button>
        </div>
    </div>
</div>

<div class="modal" id="printSummaryModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Print Summary</h3>
            <button class="modal-close" data-close-modal>&times;</button>
        </div>
        <div class="modal-body">
            <p>This will print the report summary cards currently shown on the page.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-light" data-close-modal>Close</button>
            <button class="btn btn-blue" type="button" data-print-section="#reportSummaryArea">Print Now</button>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>