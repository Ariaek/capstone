<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../vendor/autoload.php';

require_login(['admin']);

$pageTitle = 'Manage Users';

$spreadsheetAvailable = class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory');

$message = '';
$error = '';
$openModal = '';

$formData = [
    'employee_no'    => '',
    'full_name'      => '',
    'username'       => '',
    'email'          => '',
    'role'           => 'trainee',
    'is_active'      => '1',
    'department'     => '',
    'position_title' => '',
    'contact_no'     => ''
];

function clean_import_value($value): string
{
    return trim((string)$value);
}

function normalize_header($value): string
{
    $value = trim((string)$value);
    $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/', '_', $value);
    return trim($value, '_');
}

function generate_username_from_name(string $fullName, string $employeeNo = ''): string
{
    $base = strtolower($fullName);
    $base = preg_replace('/[^a-z0-9]+/i', '.', $base);
    $base = trim($base, '.');

    if ($base === '') {
        $base = 'user';
    }

    if ($employeeNo !== '') {
        $suffix = strtolower(preg_replace('/[^a-z0-9]/i', '', $employeeNo));
        if ($suffix !== '') {
            $base .= '.' . $suffix;
        }
    }

    return substr($base, 0, 50);
}

function username_exists(mysqli $conn, string $username): bool
{
    $check = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    if (!$check) {
        return false;
    }

    $check->bind_param("s", $username);
    $check->execute();
    $exists = $check->get_result()->fetch_assoc() ? true : false;
    $check->close();

    return $exists;
}

function make_unique_username(mysqli $conn, string $baseUsername): string
{
    $username = $baseUsername;
    $counter = 1;

    while (username_exists($conn, $username)) {
        $username = substr($baseUsername, 0, 45) . $counter;
        $counter++;
    }

    return $username;
}

/* HANDLE ADD USER */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_user') {
    $employee_no    = trim($_POST['employee_no'] ?? '');
    $full_name      = trim($_POST['full_name'] ?? '');
    $username       = trim($_POST['username'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $role           = trim($_POST['role'] ?? '');
    $is_active      = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
    $department     = trim($_POST['department'] ?? '');
    $position_title = trim($_POST['position_title'] ?? '');
    $contact_no     = trim($_POST['contact_no'] ?? '');
    $password       = trim($_POST['password'] ?? '');

    $formData = [
        'employee_no'    => $employee_no,
        'full_name'      => $full_name,
        'username'       => $username,
        'email'          => $email,
        'role'           => $role,
        'is_active'      => (string)$is_active,
        'department'     => $department,
        'position_title' => $position_title,
        'contact_no'     => $contact_no
    ];

    $allowedRoles = ['admin', 'training_officer', 'employee', 'trainee'];

    if (
        $employee_no === '' ||
        $full_name === '' ||
        $username === '' ||
        $email === '' ||
        $role === '' ||
        $department === '' ||
        $position_title === '' ||
        $password === ''
    ) {
        $error = 'Please complete all required fields.';
        $openModal = 'addUserModal';
    } elseif (!in_array($role, $allowedRoles, true)) {
        $error = 'Invalid role selected.';
        $openModal = 'addUserModal';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
        $openModal = 'addUserModal';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
        $openModal = 'addUserModal';
    } else {
        $check = $conn->prepare("
            SELECT id
            FROM users
            WHERE employee_no = ? OR username = ? OR email = ?
            LIMIT 1
        ");

        if ($check) {
            $check->bind_param("sss", $employee_no, $username, $email);
            $check->execute();
            $duplicate = $check->get_result()->fetch_assoc();
            $check->close();

            if ($duplicate) {
                $error = 'Employee number, username, or email already exists.';
                $openModal = 'addUserModal';
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                $insert = $conn->prepare("
                    INSERT INTO users
                    (
                        employee_no,
                        full_name,
                        username,
                        email,
                        password_hash,
                        role,
                        contact_no,
                        department,
                        position_title,
                        is_active,
                        must_change_password,
                        created_at
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
                ");

                if ($insert) {
                    $insert->bind_param(
                        "sssssssssi",
                        $employee_no,
                        $full_name,
                        $username,
                        $email,
                        $passwordHash,
                        $role,
                        $contact_no,
                        $department,
                        $position_title,
                        $is_active
                    );

                    if ($insert->execute()) {
                        $message = 'User account created successfully.';
                        $formData = [
                            'employee_no'    => '',
                            'full_name'      => '',
                            'username'       => '',
                            'email'          => '',
                            'role'           => 'trainee',
                            'is_active'      => '1',
                            'department'     => '',
                            'position_title' => '',
                            'contact_no'     => ''
                        ];
                    } else {
                        $error = 'Failed to save the user.';
                        $openModal = 'addUserModal';
                    }

                    $insert->close();
                } else {
                    $error = 'Database error while preparing the insert query.';
                    $openModal = 'addUserModal';
                }
            }
        } else {
            $error = 'Database error while checking existing users.';
            $openModal = 'addUserModal';
        }
    }
}

/* HANDLE IMPORT USERS */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'import_users') {
    $defaultRole = trim($_POST['import_role'] ?? 'trainee');
    $defaultStatus = isset($_POST['import_is_active']) ? (int)$_POST['import_is_active'] : 1;

    $allowedRoles = ['admin', 'training_officer', 'employee', 'trainee'];

    if (!in_array($defaultRole, $allowedRoles, true)) {
        $error = 'Invalid default role for import.';
        $openModal = 'importUsersModal';
    } elseif (!isset($_FILES['import_file']) || ($_FILES['import_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $error = 'Please upload a valid file.';
        $openModal = 'importUsersModal';
    } else {
        $fileName = $_FILES['import_file']['name'] ?? '';
        $tmpName  = $_FILES['import_file']['tmp_name'] ?? '';
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
            $error = 'Only .xlsx, .xls, or .csv files are supported.';
            $openModal = 'importUsersModal';
        } elseif (!$spreadsheetAvailable && in_array($ext, ['xlsx', 'xls'], true)) {
            $error = 'Excel import library is not available.';
            $openModal = 'importUsersModal';
        } else {
            try {
                $rows = [];

                if ($ext === 'csv') {
                    $handle = fopen($tmpName, 'r');
                    if (!$handle) {
                        throw new Exception('Unable to read the uploaded CSV file.');
                    }

                    $header = fgetcsv($handle);
                    if (!$header) {
                        fclose($handle);
                        throw new Exception('The CSV file is empty.');
                    }

                    $header = array_map('normalize_header', $header);

                    while (($data = fgetcsv($handle)) !== false) {
                        $row = [];
                        foreach ($header as $index => $columnName) {
                            $row[$columnName] = clean_import_value($data[$index] ?? '');
                        }
                        $rows[] = $row;
                    }

                    fclose($handle);
                } else {
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmpName);
                    $sheet = $spreadsheet->getActiveSheet();
                    $sheetRows = $sheet->toArray(null, true, true, true);

                    if (empty($sheetRows)) {
                        throw new Exception('The uploaded Excel file is empty.');
                    }

                    $headerRow = array_shift($sheetRows);
                    $headers = [];

                    foreach ($headerRow as $column => $headerValue) {
                        $headers[$column] = normalize_header($headerValue);
                    }

                    foreach ($sheetRows as $excelRow) {
                        $row = [];
                        foreach ($headers as $column => $normalizedHeader) {
                            $row[$normalizedHeader] = clean_import_value($excelRow[$column] ?? '');
                        }
                        $rows[] = $row;
                    }
                }

                if (empty($rows)) {
                    throw new Exception('The uploaded file has no data rows.');
                }

                $requiredColumns = ['employee_no', 'full_name', 'email', 'department', 'position_title'];
                $firstRowColumns = array_keys($rows[0]);
                $missingColumns = [];

                foreach ($requiredColumns as $col) {
                    if (!in_array($col, $firstRowColumns, true)) {
                        $missingColumns[] = $col;
                    }
                }

                if (!empty($missingColumns)) {
                    throw new Exception('Missing required column(s): ' . implode(', ', $missingColumns));
                }

                $rowCount = 1;
                $importedCount = 0;
                $skippedCount = 0;
                $skippedDetails = [];

                $conn->begin_transaction();

                foreach ($rows as $row) {
                    $rowCount++;

                    $employee_no    = clean_import_value($row['employee_no'] ?? '');
                    $full_name      = clean_import_value($row['full_name'] ?? '');
                    $email          = clean_import_value($row['email'] ?? '');
                    $department     = clean_import_value($row['department'] ?? '');
                    $position_title = clean_import_value($row['position_title'] ?? '');
                    $contact_no     = clean_import_value($row['contact_no'] ?? '');
                    $role           = clean_import_value($row['role'] ?? $defaultRole);
                    $username       = clean_import_value($row['username'] ?? '');

                    if ($employee_no === '' || $full_name === '' || $email === '' || $department === '' || $position_title === '') {
                        $skippedCount++;
                        $skippedDetails[] = "Row {$rowCount}: missing required data.";
                        continue;
                    }

                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $skippedCount++;
                        $skippedDetails[] = "Row {$rowCount}: invalid email.";
                        continue;
                    }

                    if (!in_array($role, $allowedRoles, true)) {
                        $role = $defaultRole;
                    }

                    if ($username === '') {
                        $username = generate_username_from_name($full_name, $employee_no);
                    }
                    $username = make_unique_username($conn, $username);

                    $check = $conn->prepare("
                        SELECT id
                        FROM users
                        WHERE employee_no = ? OR email = ? OR username = ?
                        LIMIT 1
                    ");

                    if (!$check) {
                        throw new Exception('Failed to prepare duplicate-check query.');
                    }

                    $check->bind_param("sss", $employee_no, $email, $username);
                    $check->execute();
                    $duplicate = $check->get_result()->fetch_assoc();
                    $check->close();

                    if ($duplicate) {
                        $skippedCount++;
                        $skippedDetails[] = "Row {$rowCount}: duplicate employee number, email, or username.";
                        continue;
                    }

                    $defaultPassword = 'PTMS' . preg_replace('/[^0-9]/', '', $employee_no);
                    if (strlen($defaultPassword) < 8) {
                        $defaultPassword = 'PTMS1234';
                    }
                    $passwordHash = password_hash($defaultPassword, PASSWORD_DEFAULT);

                    $insert = $conn->prepare("
                        INSERT INTO users
                        (
                            employee_no,
                            full_name,
                            username,
                            email,
                            password_hash,
                            role,
                            contact_no,
                            department,
                            position_title,
                            is_active,
                            must_change_password,
                            created_at
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
                    ");

                    if (!$insert) {
                        throw new Exception('Failed to prepare import insert query.');
                    }

                    $insert->bind_param(
                        "sssssssssi",
                        $employee_no,
                        $full_name,
                        $username,
                        $email,
                        $passwordHash,
                        $role,
                        $contact_no,
                        $department,
                        $position_title,
                        $defaultStatus
                    );

                    if (!$insert->execute()) {
                        $insert->close();
                        throw new Exception('Failed to insert imported user.');
                    }

                    $insert->close();
                    $importedCount++;
                }

                $conn->commit();

                $message = "Import completed. {$importedCount} user(s) added, {$skippedCount} skipped.";
                if (!empty($skippedDetails)) {
                    $message .= ' Notes: ' . implode(' ', array_slice($skippedDetails, 0, 5));
                    if (count($skippedDetails) > 5) {
                        $message .= ' ...';
                    }
                }
            } catch (Throwable $e) {
                $conn->rollback();
                $error = 'Import failed. ' . $e->getMessage();
                $openModal = 'importUsersModal';
            }
        }
    }
}

/* HANDLE EXPORT USERS */
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="ptms_users_' . date('Ymd_His') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    fputcsv($output, [
        'employee_no',
        'full_name',
        'username',
        'email',
        'role',
        'contact_no',
        'department',
        'position_title',
        'is_active',
        'created_at'
    ]);

    $exportUsers = $conn->query("
        SELECT employee_no, full_name, username, email, role, contact_no, department, position_title, is_active, created_at
        FROM users
        ORDER BY id DESC
    ");

    if ($exportUsers) {
        while ($row = $exportUsers->fetch_assoc()) {
            fputcsv($output, [
                $row['employee_no'] ?? '',
                $row['full_name'] ?? '',
                $row['username'] ?? '',
                $row['email'] ?? '',
                $row['role'] ?? '',
                $row['contact_no'] ?? '',
                $row['department'] ?? '',
                $row['position_title'] ?? '',
                $row['is_active'] ?? '',
                $row['created_at'] ?? ''
            ]);
        }
    }

    fclose($output);
    exit;
}

/* HANDLE DOWNLOAD IMPORT TEMPLATE */
if (isset($_GET['download_template']) && $_GET['download_template'] === '1') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="ptms_user_import_template.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    fputcsv($output, [
        'employee_no',
        'full_name',
        'email',
        'department',
        'position_title',
        'contact_no',
        'role',
        'username'
    ]);

    fputcsv($output, [
        'EMP-0001',
        'Juan Dela Cruz',
        'juan.delacruz@example.com',
        'Forecasting Division',
        'Trainee',
        '09171234567',
        'trainee',
        ''
    ]);

    fclose($output);
    exit;
}

/* FETCH USERS */
$users = $conn->query("
    SELECT id, employee_no, full_name, username, email, role, department, position_title, is_active, created_at
    FROM users
    ORDER BY id DESC
");

$totalUsers = 0;
$activeUsers = 0;
$inactiveUsers = 0;

$countResult = $conn->query("
    SELECT 
        COUNT(*) AS total_users,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_users,
        SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) AS inactive_users
    FROM users
");

if ($countResult && $row = $countResult->fetch_assoc()) {
    $totalUsers = (int)($row['total_users'] ?? 0);
    $activeUsers = (int)($row['active_users'] ?? 0);
    $inactiveUsers = (int)($row['inactive_users'] ?? 0);
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="topbar">
    <div class="page-title">
        <h1>Manage Users</h1>
        <p>View, import, export, and monitor all registered users in the PAG-ASA Training Management System.</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="ptms-toast success" id="ptmsToast">
        <div class="ptms-toast-icon">✓</div>
        <div class="ptms-toast-text">
            <strong>Success</strong>
            <span><?= e($message); ?></span>
        </div>
        <button type="button" class="ptms-toast-close" onclick="closeToast()">×</button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="ptms-toast error" id="ptmsToast">
        <div class="ptms-toast-icon">!</div>
        <div class="ptms-toast-text">
            <strong>Action Failed</strong>
            <span><?= e($error); ?></span>
        </div>
        <button type="button" class="ptms-toast-close" onclick="closeToast()">×</button>
    </div>
<?php endif; ?>

<div class="hero-panel">
    <div>
        <h2>User Management</h2>
        <p>Training Manager/Admin can upload the HR file from Gmail, then the system will automatically register valid applicants.</p>
    </div>
    <div class="actions">
        <button class="btn btn-primary" type="button" data-modal-target="#addUserModal">Add User</button>
        <button class="btn btn-secondary" type="button" data-modal-target="#importUsersModal">Import Users</button>
        <button class="btn btn-outline" type="button" data-modal-target="#exportUsersModal">Export List</button>
    </div>
</div>

<div class="grid-3">
    <div class="stat-card">
        <h3>Total Users</h3>
        <strong><?= $totalUsers; ?></strong>
        <span>All accounts</span>
    </div>
    <div class="stat-card">
        <h3>Active Users</h3>
        <strong><?= $activeUsers; ?></strong>
        <span>Enabled accounts</span>
    </div>
    <div class="stat-card">
        <h3>Inactive Users</h3>
        <strong><?= $inactiveUsers; ?></strong>
        <span>Disabled accounts</span>
    </div>
</div>

<div class="table-card section-space">
    <div class="table-head-flex">
        <h2>User List</h2>
        <div class="table-head-actions">
            <a href="users.php?download_template=1" class="btn btn-light">Download CSV Template</a>
            <a href="users.php?export=csv" class="btn btn-blue">Download CSV</a>
        </div>
    </div>

    <div class="table-wrap">
        <table id="usersTable">
            <thead>
                <tr>
                    <th>Employee No</th>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Department</th>
                    <th>Position</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users && $users->num_rows > 0): ?>
                    <?php while ($row = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?= e($row['employee_no']); ?></td>
                            <td><?= e($row['full_name']); ?></td>
                            <td><?= e($row['username']); ?></td>
                            <td><?= e($row['email']); ?></td>
                            <td><?= e($row['role']); ?></td>
                            <td><?= e($row['department']); ?></td>
                            <td><?= e($row['position_title']); ?></td>
                            <td>
                                <span class="badge <?= ((int)$row['is_active'] === 1) ? 'badge-success' : 'badge-inactive'; ?>">
                                    <?= ((int)$row['is_active'] === 1) ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td><?= e($row['created_at']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="empty">No users found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal" id="addUserModal">
    <div class="modal-box modal-lg">
        <div class="modal-header">
            <div>
                <h3>Add User</h3>
                <p class="modal-subtitle">Create a new system account for PTMS.</p>
            </div>
            <button class="modal-close" type="button" data-close-modal>&times;</button>
        </div>

        <div class="modal-body">
            <div class="modal-note">
                Newly created users will be required to change their password on first login.
            </div>

            <form method="POST" id="addUserForm" autocomplete="off">
                <input type="hidden" name="action" value="add_user">

                <div class="form-grid">
                    <div class="form-group">
                        <label>Employee No</label>
                        <input type="text" name="employee_no" value="<?= e($formData['employee_no']); ?>" placeholder="e.g. EMP-0002" required>
                    </div>

                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" value="<?= e($formData['full_name']); ?>" placeholder="Enter full name" required>
                    </div>

                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" value="<?= e($formData['username']); ?>" placeholder="Enter username" required>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?= e($formData['email']); ?>" placeholder="Enter email" required>
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="Minimum 8 characters" required>
                    </div>

                    <div class="form-group">
                        <label>Contact No</label>
                        <input type="text" name="contact_no" value="<?= e($formData['contact_no']); ?>" placeholder="Enter contact number">
                    </div>

                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" required>
                            <option value="admin" <?= $formData['role'] === 'admin' ? 'selected' : ''; ?>>admin</option>
                            <option value="training_officer" <?= $formData['role'] === 'training_officer' ? 'selected' : ''; ?>>training_officer</option>
                            <option value="employee" <?= $formData['role'] === 'employee' ? 'selected' : ''; ?>>employee</option>
                            <option value="trainee" <?= $formData['role'] === 'trainee' ? 'selected' : ''; ?>>trainee</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="is_active" required>
                            <option value="1" <?= $formData['is_active'] === '1' ? 'selected' : ''; ?>>Active</option>
                            <option value="0" <?= $formData['is_active'] === '0' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Department</label>
                        <input type="text" name="department" value="<?= e($formData['department']); ?>" placeholder="Enter department" required>
                    </div>

                    <div class="form-group">
                        <label>Position Title</label>
                        <input type="text" name="position_title" value="<?= e($formData['position_title']); ?>" placeholder="Enter position title" required>
                    </div>
                </div>
            </form>
        </div>

        <div class="modal-footer">
            <button class="btn btn-light" type="button" data-close-modal>Cancel</button>
            <button class="btn btn-blue" type="submit" form="addUserForm">Save User</button>
        </div>
    </div>
</div>

<div class="modal" id="importUsersModal">
    <div class="modal-box modal-lg">
        <div class="modal-header">
            <div>
                <h3>Import Users</h3>
                <p class="modal-subtitle">Upload the file received from HR through Gmail.</p>
            </div>
            <button class="modal-close" type="button" data-close-modal>&times;</button>
        </div>

        <div class="modal-body">
            <div class="modal-note">
                Workflow: HR emails the applicant list → Training Manager/Admin downloads the file → uploads it here → system automatically records valid users.
            </div>

            <div class="import-guide">
                <h4>Supported files</h4>
                <p>CSV, XLSX, and XLS</p>

                <h4>Required columns</h4>
                <p><code>employee_no</code>, <code>full_name</code>, <code>email</code>, <code>department</code>, <code>position_title</code></p>
                <p>Optional columns: <code>contact_no</code>, <code>role</code>, <code>username</code></p>
            </div>

            <form method="POST" id="importUsersForm" enctype="multipart/form-data">
                <input type="hidden" name="action" value="import_users">

                <div class="form-grid">
                    <div class="form-group full">
                        <label>Upload File</label>
                        <input type="file" name="import_file" accept=".csv,.xlsx,.xls" required>
                    </div>

                    <div class="form-group">
                        <label>Default Role</label>
                        <select name="import_role" required>
                            <option value="trainee">trainee</option>
                            <option value="employee">employee</option>
                            <option value="training_officer">training_officer</option>
                            <option value="admin">admin</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Default Status</label>
                        <select name="import_is_active" required>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>

        <div class="modal-footer">
            <a href="users.php?download_template=1" class="btn btn-light">Download CSV Template</a>
            <button class="btn btn-light" type="button" data-close-modal>Cancel</button>
            <button class="btn btn-blue" type="submit" form="importUsersForm">Import Users</button>
        </div>
    </div>
</div>

<div class="modal" id="exportUsersModal">
    <div class="modal-box">
        <div class="modal-header">
            <div>
                <h3>Export Users</h3>
                <p class="modal-subtitle">Download the current user list as a CSV file.</p>
            </div>
            <button class="modal-close" type="button" data-close-modal>&times;</button>
        </div>
        <div class="modal-body">
            <p>You can export the current users table into CSV format for record keeping, validation, or reporting.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-light" type="button" data-close-modal>Close</button>
            <a class="btn btn-blue" href="users.php?export=csv">Download CSV</a>
        </div>
    </div>
</div>

<style>
.ptms-toast{
    position:fixed;
    top:24px;
    right:24px;
    min-width:320px;
    max-width:460px;
    z-index:6000;
    border-radius:18px;
    padding:16px 18px;
    display:flex;
    align-items:flex-start;
    gap:12px;
    box-shadow:0 20px 40px rgba(15,23,42,.16);
    border:1px solid #e5e7eb;
    animation:slideInToast .28s ease;
}
.ptms-toast.success{background:#f0fdf4;border-color:#bbf7d0;}
.ptms-toast.error{background:#fef2f2;border-color:#fecaca;}
.ptms-toast-icon{
    width:36px;
    height:36px;
    border-radius:12px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:800;
    flex-shrink:0;
}
.ptms-toast.success .ptms-toast-icon{background:#dcfce7;color:#166534;}
.ptms-toast.error .ptms-toast-icon{background:#fee2e2;color:#991b1b;}
.ptms-toast-text{display:flex;flex-direction:column;gap:4px;color:#111827;line-height:1.5;}
.ptms-toast-text strong{font-size:14px;}
.ptms-toast-text span{font-size:13px;color:#4b5563;}
.ptms-toast-close{
    margin-left:auto;
    background:transparent;
    border:none;
    font-size:20px;
    line-height:1;
    color:#6b7280;
    cursor:pointer;
}
.modal-lg{width:min(860px, 96%);}
.modal-subtitle{margin:4px 0 0;color:#6b7280;font-size:13px;}
.modal-note{
    margin-bottom:16px;
    padding:14px 16px;
    border-radius:14px;
    background:#eff6ff;
    border:1px solid #bfdbfe;
    color:#123e82;
    font-size:14px;
}
.import-guide{
    margin-bottom:16px;
    padding:14px 16px;
    border-radius:14px;
    background:#f8fafc;
    border:1px solid #e5e7eb;
}
.import-guide h4{margin:0 0 8px;font-size:14px;color:#111827;}
.import-guide p{margin:0 0 6px;color:#4b5563;font-size:13px;}
.badge-success{background:#dcfce7;color:#166534;}
.badge-inactive{background:#f3f4f6;color:#6b7280;}
.table-head-flex{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:14px;
    flex-wrap:wrap;
    margin-bottom:14px;
}
.table-head-flex h2{margin:0;}
.table-head-actions{display:flex;gap:10px;flex-wrap:wrap;}
.btn-secondary{background:#0f766e;color:#fff;}
.btn-blue{background:#1d4ed8;color:#fff;}
.btn-light{background:#e5e7eb;color:#111827;text-decoration:none;}
@keyframes slideInToast{
    from{opacity:0;transform:translateY(-10px) translateX(20px);}
    to{opacity:1;transform:translateY(0) translateX(0);}
}
@media (max-width: 640px){
    .ptms-toast{
        left:16px;
        right:16px;
        top:16px;
        min-width:auto;
        max-width:none;
    }
}
</style>

<script>
function closeToast() {
    const toast = document.getElementById('ptmsToast');
    if (toast) {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-8px)';
        setTimeout(() => toast.remove(), 180);
    }
}

setTimeout(() => {
    closeToast();
}, 5000);

document.addEventListener('DOMContentLoaded', function () {
    const modalTriggers = document.querySelectorAll('[data-modal-target]');
    const closeTriggers = document.querySelectorAll('[data-close-modal]');

    modalTriggers.forEach(btn => {
        btn.addEventListener('click', function () {
            const target = document.querySelector(this.getAttribute('data-modal-target'));
            if (target) target.classList.add('show');
        });
    });

    closeTriggers.forEach(btn => {
        btn.addEventListener('click', function () {
            const modal = this.closest('.modal');
            if (modal) modal.classList.remove('show');
        });
    });

    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                modal.classList.remove('show');
            }
        });
    });

    const openModal = <?= json_encode($openModal); ?>;
    if (openModal) {
        const modal = document.getElementById(openModal);
        if (modal) modal.classList.add('show');
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>