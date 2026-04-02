<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login(['training_officer']);

$pageTitle = 'Attendance Monitoring';
$userId = (int)$_SESSION['user_id'];

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $attendanceId = (int)($_POST['attendance_id'] ?? 0);
    $remarks = trim($_POST['approval_remarks'] ?? '');

    if (($action === 'approve_attendance' || $action === 'reject_attendance') && $attendanceId > 0) {
        $approvalStatus = $action === 'approve_attendance' ? 'Approved' : 'Rejected';

        $check = $conn->query("
            SELECT a.id
            FROM attendance_logs a
            INNER JOIN trainings t ON t.id = a.training_id
            WHERE a.id = {$attendanceId} AND t.trainer_user_id = {$userId}
            LIMIT 1
        ");

        if ($check && $check->num_rows === 1) {
            $stmt = $conn->prepare("
                UPDATE attendance_logs
                SET approval_status = ?, approved_by = ?, approved_at = NOW(), approval_remarks = ?
                WHERE id = ?
            ");
            if ($stmt) {
                $stmt->bind_param("sisi", $approvalStatus, $userId, $remarks, $attendanceId);
                if ($stmt->execute()) {
                    $message = "Attendance {$approvalStatus} successfully.";
                } else {
                    $error = 'Failed to update attendance approval.';
                }
                $stmt->close();
            } else {
                $error = 'Database error while updating attendance.';
            }
        } else {
            $error = 'You can only review attendance under your own trainings.';
        }
    }
}

$attendance = $conn->query("
    SELECT 
        a.id,
        a.log_date,
        a.time_in,
        a.time_out,
        a.status,
        a.approval_status,
        a.approval_remarks,
        u.employee_no,
        u.full_name,
        t.title
    FROM attendance_logs a
    INNER JOIN users u ON u.id = a.user_id
    INNER JOIN trainings t ON t.id = a.training_id
    WHERE t.trainer_user_id = {$userId}
    ORDER BY a.id DESC
    LIMIT 100
");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar_officer.php';
?>

<div class="topbar">
    <div class="page-title">
        <h1>Attendance Monitoring</h1>
        <p>Review, approve, or reject trainee attendance records under your trainings.</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= e($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= e($error); ?></div>
<?php endif; ?>

<div class="hero-panel">
    <div>
        <h2>Attendance Approval</h2>
        <p>Validate submitted attendance entries and record officer approval remarks.</p>
    </div>
    <div class="actions" style="display:flex; gap:10px; flex-wrap:wrap;">
        <button class="btn btn-primary" data-modal-target="#filterModal" type="button">Filter</button>
    </div>
</div>

<div class="table-card">
    <h2>Attendance List</h2>
    <div class="table-wrap">
        <table id="officerAttendanceTable">
            <thead>
                <tr>
                    <th>Employee No</th>
                    <th>Name</th>
                    <th>Training</th>
                    <th>Date</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Status</th>
                    <th>Approval</th>
                    <th style="width:240px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($attendance && $attendance->num_rows > 0): ?>
                    <?php while ($row = $attendance->fetch_assoc()): ?>
                        <tr class="attendance-row">
                            <td><?= e($row['employee_no'] ?? '—'); ?></td>
                            <td><?= e($row['full_name'] ?? '—'); ?></td>
                            <td><?= e($row['title'] ?? '—'); ?></td>
                            <td><?= e($row['log_date'] ?? '—'); ?></td>
                            <td><?= e($row['time_in'] ?: '—'); ?></td>
                            <td><?= e($row['time_out'] ?: '—'); ?></td>
                            <td><?= e($row['status'] ?? '—'); ?></td>
                            <td>
                                <span class="badge"><?= e($row['approval_status'] ?? 'Pending'); ?></span>
                                <?php if (!empty($row['approval_remarks'])): ?>
                                    <div class="small"><?= e($row['approval_remarks']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" style="display:grid; gap:8px;">
                                    <input type="hidden" name="attendance_id" value="<?= (int)$row['id']; ?>">
                                    <input type="text" name="approval_remarks" placeholder="Remarks (optional)">
                                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                        <button class="btn btn-light" type="submit" name="action" value="approve_attendance">Approve</button>
                                        <button class="btn btn-blue" type="submit" name="action" value="reject_attendance">Reject</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr id="emptyAttendanceRow">
                        <td colspan="9" class="empty">No attendance records found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal" id="filterModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Filter Attendance</h3>
            <button class="modal-close" data-close-modal type="button">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group full">
                <label for="officerAttendanceSearch">Search</label>
                <input type="text" id="officerAttendanceSearch" placeholder="Search employee no, trainee, training, date, status, approval">
            </div>
        </div>
        <div class="modal-footer" style="display:flex; gap:10px; justify-content:flex-end;">
            <button class="btn btn-light" type="button" id="clearAttendanceSearch">Reset</button>
            <button class="btn btn-light" type="button" data-close-modal>Close</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('officerAttendanceSearch');
    const clearButton = document.getElementById('clearAttendanceSearch');
    const table = document.getElementById('officerAttendanceTable');

    function filterAttendanceTable() {
        if (!table || !searchInput) return;

        const keyword = searchInput.value.toLowerCase().trim();
        const rows = table.querySelectorAll('tbody tr.attendance-row');
        let visibleCount = 0;

        rows.forEach(function (row) {
            const cells = row.querySelectorAll('td');
            let rowText = '';

            cells.forEach(function (cell, index) {
                if (index < 8) {
                    rowText += ' ' + cell.textContent.toLowerCase();
                }
            });

            const isMatch = keyword === '' || rowText.includes(keyword);
            row.style.display = isMatch ? '' : 'none';

            if (isMatch) {
                visibleCount++;
            }
        });

        let noResultRow = document.getElementById('noFilterResultRow');

        if (visibleCount === 0 && rows.length > 0) {
            if (!noResultRow) {
                noResultRow = document.createElement('tr');
                noResultRow.id = 'noFilterResultRow';
                noResultRow.innerHTML = '<td colspan="9" class="empty">No matching attendance records found.</td>';
                table.querySelector('tbody').appendChild(noResultRow);
            }
        } else {
            if (noResultRow) {
                noResultRow.remove();
            }
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', filterAttendanceTable);
        searchInput.addEventListener('keyup', filterAttendanceTable);
    }

    if (clearButton) {
        clearButton.addEventListener('click', function () {
            searchInput.value = '';
            filterAttendanceTable();
            searchInput.focus();
        });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>