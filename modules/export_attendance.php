<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login(['admin']);

date_default_timezone_set('Asia/Manila');

$search   = trim($_GET['search'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo   = trim($_GET['date_to'] ?? '');

/*
|--------------------------------------------------------------------------
| BUILD EXPORT QUERY
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT 
        u.employee_no,
        u.full_name,
        al.log_date,
        al.time_in,
        al.time_out
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

$sql .= " ORDER BY al.log_date DESC, al.id DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    exit('Failed to prepare export query.');
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

/*
|--------------------------------------------------------------------------
| CSV DOWNLOAD
|--------------------------------------------------------------------------
*/
$filename = 'attendance_report_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

fputcsv($output, ['PTMS Attendance Report']);
fputcsv($output, ['Generated On', date('Y-m-d h:i A')]);
fputcsv($output, ['Search', $search !== '' ? $search : 'All']);
fputcsv($output, ['Date From', $dateFrom !== '' ? $dateFrom : 'Any']);
fputcsv($output, ['Date To', $dateTo !== '' ? $dateTo : 'Any']);
fputcsv($output, []);
fputcsv($output, ['Employee No', 'Name', 'Date', 'Time In', 'Time Out']);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['employee_no'] ?? '',
            $row['full_name'] ?? '',
            $row['log_date'] ?? '',
            $row['time_in'] ?? '',
            $row['time_out'] ?? ''
        ]);
    }
}

fclose($output);
$stmt->close();
exit;