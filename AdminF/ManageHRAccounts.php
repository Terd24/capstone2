<?php
session_start();
include("../StudentLogin/db_conn.php");

// Require Super Admin login
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    header('Location: ../StudentLogin/login.php');
    exit;
}

// Handle HR account creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'create_hr') {
        // Get all form data
        $employee_id = $_POST['employee_id'] ?? '';
        $first_name = $_POST['first_name'] ?? '';
        $middle_name = $_POST['middle_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $position = $_POST['position'] ?? '';
        $department = $_POST['department'] ?? '';
        $hire_date = $_POST['hire_date'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        $create_account = isset($_POST['create_account']);
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        // Initialize error tracking
        $field_errors = [];
        
        // Validate required fields
        if (empty($employee_id)) $field_errors['employee_id'] = 'Employee ID is required';
        if (empty($first_name)) $field_errors['first_name'] = 'First name is required';
        if (empty($last_name)) $field_errors['last_name'] = 'Last name is required';
        if (empty($position)) $field_errors['position'] = 'Position is required';
        if (empty($department)) $field_errors['department'] = 'Department is required';
        if (empty($hire_date)) $field_errors['hire_date'] = 'Hire date is required';
        if (empty($email)) $field_errors['email'] = 'Email is required';
        if (empty($phone)) $field_errors['phone'] = 'Phone is required';
        if (empty($address)) $field_errors['address'] = 'Address is required';
        
        if ($create_account) {
            if (empty($username)) $field_errors['username'] = 'Username is required';
            if (empty($password)) $field_errors['password'] = 'Password is required';
        }
        
        if (!empty($field_errors)) {
            $error = 'Please fill in all required fields';
        } else {
            // Check if employee ID already exists
            $stmt = $conn->prepare("SELECT id_number FROM employees WHERE id_number = ?");
            $stmt->bind_param("s", $employee_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'Employee ID already exists';
                $field_errors['employee_id'] = 'This Employee ID is already taken';
            } else if ($create_account) {
                // Check if username is taken
                $stmt = $conn->prepare("SELECT username FROM employee_accounts WHERE username = ?");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $error = 'Username already taken';
                    $field_errors['username'] = 'This username is already taken';
                } else {
                    // Create employee and account
                    $conn->begin_transaction();
                    try {
                        // Insert employee
                        $stmt = $conn->prepare("INSERT INTO employees (id_number, first_name, middle_name, last_name, position, department, hire_date, email, phone, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("ssssssssss", $employee_id, $first_name, $middle_name, $last_name, $position, $department, $hire_date, $email, $phone, $address);
                        $stmt->execute();
                        
                        // Create HR account
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("INSERT INTO employee_accounts (employee_id, username, password, role) VALUES (?, ?, ?, 'hr')");
                        $stmt->bind_param("sss", $employee_id, $username, $hashed_password);
                        $stmt->execute();
                        
                        $conn->commit();
                        // Redirect to prevent form resubmission
                        header('Location: ManageHRAccounts.php?success=' . urlencode('Employee and HR account created successfully'));
                        exit;
                    } catch (Exception $e) {
                        $conn->rollback();
                        $error = 'Failed to create employee and account: ' . $e->getMessage();
                    }
                }
            } else {
                // Create employee only
                try {
                    $stmt = $conn->prepare("INSERT INTO employees (id_number, first_name, middle_name, last_name, position, department, hire_date, email, phone, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssssssss", $employee_id, $first_name, $middle_name, $last_name, $position, $department, $hire_date, $email, $phone, $address);
                    
                    if ($stmt->execute()) {
                        // Redirect to prevent form resubmission
                        header('Location: ManageHRAccounts.php?success=' . urlencode('Employee created successfully (no system account)'));
                        exit;
                    } else {
                        $error = 'Failed to create employee: ' . $conn->error;
                    }
                } catch (Exception $e) {
                    $error = 'Failed to create employee: ' . $e->getMessage();
                }
            }
        }
    }
}

// Handle success message from redirect
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

// Helper function to get field CSS class
function getFieldClass($field_name, $field_errors = []) {
    $base_class = "w-full border px-3 py-2 rounded-lg focus:ring-2";
    if (isset($field_errors[$field_name])) {
        return $base_class . " border-red-500 focus:ring-red-500 focus:border-red-500 bg-red-50";
    }
    return $base_class . " border-gray-300 focus:ring-[#0B2C62] focus:border-[#0B2C62]";
}

// Helper function to get field error message
function getFieldError($field_name, $field_errors = []) {
    return isset($field_errors[$field_name]) ? $field_errors[$field_name] : '';
}

// Fetch all employees
$employees = $conn->query("SELECT e.*, ea.username, ea.role FROM employees e LEFT JOIN employee_accounts ea ON e.id_number = ea.employee_id ORDER BY e.first_name, e.last_name");


// Fetch only HR employees (with and without accounts)
$hr_accounts = $conn->query("
    SELECT DISTINCT e.id_number, e.first_name, e.last_name, e.position, e.hire_date, ea.username, ea.created_at 
    FROM employees e 
    LEFT JOIN employee_accounts ea ON e.id_number = ea.employee_id 
    WHERE e.department = 'HR' OR ea.role = 'hr'
    ORDER BY e.first_name, e.last_name
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage HR Accounts - Principal/Owner</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Reset Password Button styles */
        button[id^="resetPasswordBtn_"]:not([disabled]) {
            background-color: #eab308 !important;
            cursor: pointer !important;
        }
        button[id^="resetPasswordBtn_"]:not([disabled]):hover {
            background-color: #ca8a04 !important;
        }
        button[id^="resetPasswordBtn_"][disabled] {
            background-color: #9ca3af !important;
            cursor: not-allowed !important;
        }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">

<header class="bg-gradient-to-r from-[#0B2C62] to-[#153e86] text-white shadow">
  <div class="container mx-auto px-6 py-6 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <a href="SuperAdminDashboard.php" class="inline-flex items-center justify-center w-8 h-8 bg-white/10 hover:bg-white/20 rounded transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      </a>
      <div class="text-lg font-semibold">HR Account Management</div>
    </div>
    <div class="flex items-center gap-3">
      <img src="../images/LogoCCI.png" class="h-12 w-12 rounded-full bg-white p-1 shadow-sm" alt="Logo">
      <div class="leading-tight">
        <div class="text-lg font-bold">Cornerstone College Inc.</div>
        <div class="text-base text-blue-200">HR Account Management Portal</div>
      </div>
    </div>
  </div>
</header>

<main class="container mx-auto px-6 py-6">
    <?php if (isset($success)): ?>
        <div class="mb-4 bg-green-50 border border-green-200 text-green-900 px-4 py-3 rounded-lg"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="mb-4 bg-red-50 border border-red-200 text-red-900 px-4 py-3 rounded-lg"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>


    <!-- Employee Registration Form -->
    <div class="bg-white rounded-2xl shadow p-6 mb-6">
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.314 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <span class="text-amber-800 font-semibold">School Owner Approval Required</span>
            </div>
            <p class="text-amber-700 text-sm mt-2">As IT Personnel, HR management operations require School Owner approval. All actions will be logged and may require additional authorization.</p>
        </div>

        <form method="POST" class="space-y-8">
            <input type="hidden" name="action" value="create_hr">
            
            <!-- Personal Information Section -->
            <div class="border-2 border-gray-400 rounded-lg p-4 bg-gray-50">
                <div class="flex items-center gap-3 mb-4">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-800">PERSONAL INFORMATION</h3>
                </div>
                
                <div class="grid grid-cols-3 gap-6">
                    <!-- Row 1: Employee ID, First Name, Middle Name -->
                    <div>
                        <label class="block text-sm font-semibold mb-1">Employee ID *</label>
                        <input type="text" name="employee_id" autocomplete="off" required maxlength="11" pattern="[0-9]{1,11}" title="Numbers only, maximum 11 digits" class="<?= getFieldClass('employee_id', $field_errors ?? []) ?>" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)" value="<?= htmlspecialchars($_POST['employee_id'] ?? '') ?>">
                        <?php if ($error_msg = getFieldError('employee_id', $field_errors ?? [])): ?>
                            <p class="text-red-500 text-xs mt-1"><?= htmlspecialchars($error_msg) ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">First Name *</label>
                        <input type="text" name="first_name" autocomplete="off" pattern="[A-Za-z\s]+" maxlength="20" title="Letters only, maximum 20 characters" required class="<?= getFieldClass('first_name', $field_errors ?? []) ?>" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '').slice(0, 20)" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
                        <?php if ($error_msg = getFieldError('first_name', $field_errors ?? [])): ?>
                            <p class="text-red-500 text-xs mt-1"><?= htmlspecialchars($error_msg) ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Middle Name <span class="text-gray-500 text-xs">(Optional)</span></label>
                        <input type="text" name="middle_name" autocomplete="off" pattern="[A-Za-z\s]*" maxlength="20" title="Letters only, maximum 20 characters" placeholder="Optional" class="<?= getFieldClass('middle_name', $field_errors ?? []) ?>" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '').slice(0, 20)" value="<?= htmlspecialchars($_POST['middle_name'] ?? '') ?>">
                        <?php if ($error_msg = getFieldError('middle_name', $field_errors ?? [])): ?>
                            <p class="text-red-500 text-xs mt-1"><?= htmlspecialchars($error_msg) ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Row 2: Last Name, Position, Department -->
                    <div>
                        <label class="block text-sm font-semibold mb-1">Last Name *</label>
                        <input type="text" name="last_name" autocomplete="off" pattern="[A-Za-z\s]+" maxlength="20" title="Letters only, maximum 20 characters" required class="<?= getFieldClass('last_name', $field_errors ?? []) ?>" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '').slice(0, 20)" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
                        <?php if ($error_msg = getFieldError('last_name', $field_errors ?? [])): ?>
                            <p class="text-red-500 text-xs mt-1"><?= htmlspecialchars($error_msg) ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Position *</label>
                        <input type="text" name="position" autocomplete="off" pattern="[A-Za-z\s]+" maxlength="20" title="Letters only, maximum 20 characters" required class="<?= getFieldClass('position', $field_errors ?? []) ?>" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '').slice(0, 20)" value="<?= htmlspecialchars($_POST['position'] ?? '') ?>">
                        <?php if ($error_msg = getFieldError('position', $field_errors ?? [])): ?>
                            <p class="text-red-500 text-xs mt-1"><?= htmlspecialchars($error_msg) ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Department *</label>
                        <select name="department" required class="<?= getFieldClass('department', $field_errors ?? []) ?>">
                            <option value="">-- Select Department --</option>
                            <option value="HR" <?= (($_POST['department'] ?? '') === 'HR') ? 'selected' : '' ?>>HR</option>
                        </select>
                        <?php if ($error_msg = getFieldError('department', $field_errors ?? [])): ?>
                            <p class="text-red-500 text-xs mt-1"><?= htmlspecialchars($error_msg) ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Row 3: Hire Date, Email, Phone -->
                    <div>
                        <label class="block text-sm font-semibold mb-1">Hire Date *</label>
                        <input type="date" name="hire_date" required class="<?= getFieldClass('hire_date', $field_errors ?? []) ?>" value="<?= htmlspecialchars($_POST['hire_date'] ?? '') ?>">
                        <?php if ($error_msg = getFieldError('hire_date', $field_errors ?? [])): ?>
                            <p class="text-red-500 text-xs mt-1"><?= htmlspecialchars($error_msg) ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Email *</label>
                        <input type="email" name="email" autocomplete="off" required class="<?= getFieldClass('email', $field_errors ?? []) ?>" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        <?php if ($error_msg = getFieldError('email', $field_errors ?? [])): ?>
                            <p class="text-red-500 text-xs mt-1"><?= htmlspecialchars($error_msg) ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Phone *</label>
                        <input type="text" name="phone" required class="<?= getFieldClass('phone', $field_errors ?? []) ?>" inputmode="numeric" pattern="[0-9]{11}" minlength="11" maxlength="11" title="Please enter exactly 11 digits (e.g., 09123456789)" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                        <?php if ($error_msg = getFieldError('phone', $field_errors ?? [])): ?>
                            <p class="text-red-500 text-xs mt-1"><?= htmlspecialchars($error_msg) ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Complete Address -->
                    <div class="col-span-3">
                        <label class="block text-sm font-semibold mb-1">Complete Address *</label>
                        <textarea name="address" rows="3" autocomplete="off" required class="<?= getFieldClass('address', $field_errors ?? []) ?>"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                        <?php if ($error_msg = getFieldError('address', $field_errors ?? [])): ?>
                            <p class="text-red-500 text-xs mt-1"><?= htmlspecialchars($error_msg) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- System Account Section -->
            <div class="border-2 border-gray-400 rounded-lg p-4 bg-gray-50">
                <div class="flex items-center gap-3 mb-4">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-800">SYSTEM ACCOUNT</h3>
                </div>
                
                <div class="mb-4">
                    <div class="flex items-center gap-2 select-none">
                        <input type="checkbox" name="create_account" id="create_account" class="rounded border-gray-300 text-[#0B2C62] focus:ring-[#0B2C62] cursor-pointer">
                        <span class="text-sm font-medium text-gray-700 pointer-events-none">Create system account for this employee</span>
                    </div>
                </div>
                
                <div id="account_fields" class="hidden grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold mb-1">Username</label>
                        <input type="text" name="username" autocomplete="off" pattern="^[A-Za-z0-9_]+$" title="Letters, numbers, underscores only" class="<?= getFieldClass('username', $field_errors ?? []) ?>" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                        <?php if ($error_msg = getFieldError('username', $field_errors ?? [])): ?>
                            <p class="text-red-500 text-xs mt-1"><?= htmlspecialchars($error_msg) ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Password</label>
                        <input type="password" name="password" autocomplete="new-password" minlength="6" class="<?= getFieldClass('password', $field_errors ?? []) ?>">
                        <?php if ($error_msg = getFieldError('password', $field_errors ?? [])): ?>
                            <p class="text-red-500 text-xs mt-1"><?= htmlspecialchars($error_msg) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end pt-4">
                <button type="submit" class="bg-[#0B2C62] text-white px-10 py-4 rounded-xl hover:bg-blue-800 transition font-semibold text-lg shadow-lg">
                    Save Employee
                </button>
            </div>
        </form>
    </div>

    <!-- Existing HR Accounts -->
    <div class="bg-white rounded-2xl shadow p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Existing HR Accounts</h2>
        
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-[#0B2C62] text-white">
                    <tr>
                        <th class="px-4 py-2 text-left">Employee ID</th>
                        <th class="px-4 py-2 text-left">Name</th>
                        <th class="px-4 py-2 text-left">Position</th>
                        <th class="px-4 py-2 text-left">Username</th>
                        <th class="px-4 py-2 text-left">Hire Date</th>
                        <th class="px-4 py-2 text-left">Account Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($hr_accounts->num_rows > 0): ?>
                        <?php while ($hr = $hr_accounts->fetch_assoc()): ?>
                            <tr class="border-b hover:bg-blue-50 cursor-pointer transition-colors" onclick="viewEmployeeDetails('<?= htmlspecialchars($hr['id_number']) ?>')">
                                <td class="px-4 py-3"><?= htmlspecialchars($hr['id_number']) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($hr['first_name'] . ' ' . $hr['last_name']) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($hr['position']) ?></td>
                                <td class="px-4 py-3">
                                    <?php if ($hr['username']): ?>
                                        <?= htmlspecialchars($hr['username']) ?>
                                    <?php else: ?>
                                        <span class="text-gray-400 italic">No account</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3"><?= date('M j, Y', strtotime($hr['hire_date'])) ?></td>
                                <td class="px-4 py-3">
                                    <?php if ($hr['username']): ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Has Account
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            No Account
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500">No HR employees found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<!-- Notification Popup Modal -->
<div id="notification" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div id="notificationContent" class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md mx-4 transform scale-95 opacity-0 transition-all duration-300">
        <div class="flex flex-col items-center text-center">
            <div id="notificationIconContainer" class="w-16 h-16 rounded-full flex items-center justify-center mb-4">
                <svg id="notificationIcon" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h3 id="notificationTitle" class="text-xl font-semibold text-gray-900 mb-2">Success</h3>
            <p id="notificationMessage" class="text-gray-600 mb-6"></p>
            <button onclick="closeNotification()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                OK
            </button>
        </div>
    </div>
</div>

<!-- View Employee Modal -->
<div id="viewEmployeeModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white w-full max-w-5xl rounded-2xl shadow-2xl overflow-hidden border-2 border-[#0B2C62] transform transition-all scale-100">
        <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4 bg-[#0B2C62] rounded-t-2xl">
            <h2 class="text-lg font-semibold text-white">Employee Information</h2>
            <div class="flex flex-col items-end gap-1">
                <div class="flex gap-3">
                    <button id="editEmployeeBtn" onclick="toggleEditMode()" class="px-4 py-2 bg-[#2F8D46] text-white rounded-lg hover:bg-[#256f37] transition">Edit</button>
                    <button id="deleteEmployeeBtn" onclick="showDeleteEmployeeConfirmation(getCurrentEmployeeId())" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">Delete Employee</button>
                    <button onclick="closeViewModal()" class="text-2xl font-bold text-white hover:text-gray-300">&times;</button>
                </div>
            </div>
        </div>
        <div class="px-6 py-6 grid grid-cols-1 md:grid-cols-3 gap-6 overflow-y-auto max-h-[80vh] no-scrollbar" id="employeeDetailsContent">
            <!-- Content will be populated by JavaScript -->
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteConfirmationModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center" style="z-index:10090;">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl">
        <div class="flex flex-col items-center text-center">
            <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center mb-3">
                <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86A2 2 0 0021 17.13L13.93 4.87a2 2 0 00-3.86 0L3 17.13A2 2 0 005.07 19z" />
                </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Remove Login Access</h3>
            <p class="text-gray-600 mb-6">Are you sure you want to remove this employee's system account? The employee record will remain, but they will lose access to the system.</p>
            <div class="flex w-full gap-3">
                <button onclick="closeDeleteConfirmation()" class="flex-1 py-2.5 rounded-lg bg-gray-200 text-gray-800 hover:bg-gray-300 font-medium">Cancel</button>
                <button id="confirmDeleteBtn" class="flex-1 py-2.5 rounded-lg bg-red-600 text-white hover:bg-red-700 font-medium">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Create Account Modal -->
<div id="createAccountModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Create System Account</h3>
            <button onclick="closeCreateAccountModal()" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form id="createAccountForm" onsubmit="createNewAccount(event)" autocomplete="off">
            <div id="createAccountContent">
                <!-- Content will be populated by JavaScript -->
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeCreateAccountModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
                    Create Account
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Password Reset Modal -->
<div id="passwordResetModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center" style="z-index:10100;">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl">
        <div class="flex flex-col items-center text-center">
            <div class="w-16 h-16 rounded-full bg-green-50 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Temporary Password Generated</h3>
            <p class="text-gray-600 mb-4">The new temporary password has been generated:</p>
            <div class="bg-gray-100 border border-gray-300 rounded-lg px-4 py-3 mb-4 w-full">
                <p class="text-lg font-mono font-semibold text-gray-900 break-all" id="generatedPassword"></p>
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                <p class="text-sm text-blue-800"><strong>⚠️ Important:</strong> Please save this password and click <strong>"Save Changes"</strong> button to apply the password reset.</p>
            </div>
            <p class="text-xs text-amber-600 mb-6">The employee will be required to change this password on first login.</p>
            <button onclick="closePasswordResetModal()" class="w-full py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 font-medium">
                OK, I've Saved It
            </button>
        </div>
    </div>
</div>

<style>
/* Hide scrollbar for employee details content */
#employeeDetailsContent::-webkit-scrollbar {
    display: none;
}
</style>

<script>
// Show notification popup function
function showNotification(message, type = 'success') {
    const notification = document.getElementById('notification');
    const content = document.getElementById('notificationContent');
    const iconContainer = document.getElementById('notificationIconContainer');
    const icon = document.getElementById('notificationIcon');
    const title = document.getElementById('notificationTitle');
    const messageEl = document.getElementById('notificationMessage');
    
    messageEl.textContent = message;
    
    // Set colors, title, and icon based on type
    if (type === 'success') {
        iconContainer.className = 'w-16 h-16 rounded-full flex items-center justify-center mb-4 bg-green-100';
        icon.className = 'w-8 h-8 text-green-600';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>';
        title.textContent = 'Success';
        title.className = 'text-xl font-semibold text-gray-900 mb-2';
    } else if (type === 'error') {
        iconContainer.className = 'w-16 h-16 rounded-full flex items-center justify-center mb-4 bg-red-100';
        icon.className = 'w-8 h-8 text-red-600';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>';
        title.textContent = 'Error';
        title.className = 'text-xl font-semibold text-gray-900 mb-2';
    }
    
    // Show notification popup
    notification.classList.remove('hidden');
    
    // Animate in
    setTimeout(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);
}

// Close notification popup
function closeNotification() {
    const notification = document.getElementById('notification');
    const content = document.getElementById('notificationContent');
    
    // Animate out
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    
    // Hide after animation
    setTimeout(() => {
        notification.classList.add('hidden');
    }, 300);
}

// Show account fields if there are username/password errors
<?php if (isset($field_errors) && (isset($field_errors['username']) || isset($field_errors['password']))): ?>
document.addEventListener('DOMContentLoaded', function() {
    const checkbox = document.getElementById('create_account');
    const accountFields = document.getElementById('account_fields');
    const usernameField = document.querySelector('input[name="username"]');
    const passwordField = document.querySelector('input[name="password"]');
    
    // Check the checkbox and show fields
    checkbox.checked = true;
    accountFields.classList.remove('hidden');
    usernameField.required = true;
    passwordField.required = true;
});
<?php endif; ?>

// Handle checkbox for showing/hiding account fields
document.getElementById('create_account').addEventListener('change', function() {
    const accountFields = document.getElementById('account_fields');
    const usernameField = document.querySelector('input[name="username"]');
    const passwordField = document.querySelector('input[name="password"]');
    
    if (this.checked) {
        accountFields.classList.remove('hidden');
        usernameField.required = true;
        passwordField.required = true;
    } else {
        accountFields.classList.add('hidden');
        usernameField.required = false;
        passwordField.required = false;
        usernameField.value = '';
        passwordField.value = '';
    }
});

// Employee view function
function viewEmployeeDetails(employeeId) {
    // Fetch employee details and show in modal
    fetch(`get_employee_details.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'employee_id=' + encodeURIComponent(employeeId)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showEmployeeDetailsModal(data.employee);
        } else {
            showNotification('Error loading employee details: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error loading employee details', 'error');
    });
}

// Show employee details modal
let currentEmployeeId = null;
let isEditMode = false;

function showEmployeeDetailsModal(employee) {
    currentEmployeeId = employee.id_number;
    const content = document.getElementById('employeeDetailsContent');
    
    // Ensure top Delete (entire employee) button is visible
    const deleteBtn = document.getElementById('deleteEmployeeBtn');
    if (deleteBtn) deleteBtn.classList.remove('hidden');
    
    // Update edit button text based on mode
    const editBtn = document.getElementById('editEmployeeBtn');
    if (editBtn) {
        editBtn.textContent = 'Edit';
        editBtn.className = 'px-4 py-2 bg-[#2F8D46] text-white rounded-lg hover:bg-[#256f37] transition';
    }
    
    content.innerHTML = `
        <!-- Personal Information Section -->
        <div class="col-span-3 mb-6">
            <div class="bg-gray-50 border-2 border-gray-400 rounded-lg p-4">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    PERSONAL INFORMATION
                </h3>
                <div class="grid grid-cols-3 gap-6">
                    <!-- Row: ID Number, First Name, Middle Name -->
                    <div>
                        <label class="block text-sm font-semibold mb-1">ID Number</label>
                        <input type="text" value="${employee.id_number}" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 employee-field">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">First Name</label>
                        <input type="text" id="first_name_${employee.id_number}" value="${employee.first_name}" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 employee-field">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Middle Name</label>
                        <input type="text" id="middle_name_${employee.id_number}" value="${employee.middle_name || ''}" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 employee-field">
                    </div>
                    
                    <!-- Row: Last Name, Position, Department -->
                    <div>
                        <label class="block text-sm font-semibold mb-1">Last Name</label>
                        <input type="text" id="last_name_${employee.id_number}" value="${employee.last_name}" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 employee-field">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Position</label>
                        <input type="text" id="position_${employee.id_number}" value="${employee.position}" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 employee-field">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Department</label>
                        <input type="text" id="department_${employee.id_number}" value="${employee.department}" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 employee-field">
                    </div>
                    
                    <!-- Row: Email, Phone, Hire Date -->
                    <div>
                        <label class="block text-sm font-semibold mb-1">Email</label>
                        <input type="email" id="email_${employee.id_number}" value="${employee.email || ''}" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 employee-field">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Phone</label>
                        <input type="text" id="phone_${employee.id_number}" value="${employee.phone || ''}" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 employee-field">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Hire Date</label>
                        <input type="date" id="hire_date_${employee.id_number}" value="${employee.hire_date}" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 employee-field">
                    </div>
                </div>
            </div>
        </div>
        ${employee.username ? `
        <!-- Personal Account Section -->
        <div class="col-span-3 mb-6">
            <div class="bg-gray-50 border-2 border-gray-400 rounded-lg p-4">
                <h3 class="text-lg font-semibold text-gray-800 mb-1 flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <span>PERSONAL ACCOUNT</span>
                    </div>
                    <button type="button" onclick="showDeleteConfirmation('${employee.id_number}')" class="ml-auto px-3 py-1.5 text-sm bg-amber-600 text-white rounded hover:bg-amber-700">
                        Remove Login Access
                    </button>
                </h3>
                <p class="text-xs text-gray-500 mb-4 text-right">Removes login access only. The employee record will remain.</p>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold mb-1">Username</label>
                        <input type="text" id="username_${employee.id_number}" value="${employee.username}" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 employee-field">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Password</label>
                        <div class="flex gap-2">
                            <input type="text" id="password_${employee.id_number}" placeholder="Click Reset Password button to generate" readonly disabled class="flex-1 border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 employee-field cursor-not-allowed" style="pointer-events: none;">
                            <button type="button" id="resetPasswordBtn_${employee.id_number}" onclick="resetEmployeePassword('${employee.id_number}')" disabled class="px-4 py-2 bg-gray-400 text-white rounded-lg font-medium transition-colors cursor-not-allowed whitespace-nowrap">
                                Reset Password
                            </button>
                        </div>
                        <small class="text-gray-500">Leave blank to keep current password. Click "Reset Password" to generate a new temporary password.</small>
                    </div>
                </div>
            </div>
        </div>
        ` : `
        <!-- No Account Section -->
        <div class="col-span-3">
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 text-center">
                <p class="text-gray-600 mb-3">This employee doesn't have a system account.</p>
                <button onclick="createAccountForEmployee('${employee.id_number}')" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
                    + Create Account
                </button>
            </div>
        </div>
        `}
        
        <!-- Submit Buttons -->
        <div class="col-span-3 flex justify-end gap-4 pt-6 border-t border-gray-200">
            <button type="button" onclick="closeViewModal()" class="px-5 py-2 border border-blue-600 text-blue-900 rounded-xl hover:bg-[#0B2C62] hover:text-white transition">Back to List</button>
            <button type="button" id="saveChangesBtn_${employee.id_number}" onclick="saveEmployeeChanges()" class="px-5 py-2 bg-green-600 text-white rounded-xl shadow hover:bg-green-700 transition hidden">
                Save Changes
            </button>
        </div>
    `;
    document.getElementById('viewEmployeeModal').classList.remove('hidden');
}

// Close view modal
function closeViewModal() {
    document.getElementById('viewEmployeeModal').classList.add('hidden');
    // Reset edit mode when closing
    isEditMode = false;
    currentEmployeeId = null;
    const editBtn = document.getElementById('editEmployeeBtn');
    if (editBtn) {
        editBtn.textContent = 'Edit';
        editBtn.className = 'px-4 py-2 bg-[#2F8D46] text-white rounded-lg hover:bg-[#256f37] transition';
    }
    // Hide delete and save buttons
    const deleteBtn = document.getElementById('deleteEmployeeBtn');
    if (deleteBtn) deleteBtn.classList.add('hidden');
    const saveBtn = document.querySelector('[id^="saveChangesBtn_"]');
    if (saveBtn) saveBtn.classList.add('hidden');
}

// Toggle edit mode
function toggleEditMode() {
    isEditMode = !isEditMode;
    const editBtn = document.getElementById('editEmployeeBtn');
    const saveBtn = document.querySelector('[id^="saveChangesBtn_"]');
    const fields = document.querySelectorAll('.employee-field');
    const resetPasswordBtn = document.querySelector('[id^="resetPasswordBtn_"]');
    const passwordField = document.querySelector('[id^="password_"]');
    
    if (isEditMode) {
        // Switch to edit mode
        editBtn.textContent = 'Cancel';
        editBtn.className = 'px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition';
        if (saveBtn) saveBtn.classList.remove('hidden');
        
        // Enable reset password button
        if (resetPasswordBtn) {
            resetPasswordBtn.disabled = false;
            resetPasswordBtn.removeAttribute('disabled');
            resetPasswordBtn.className = 'px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg font-medium transition-colors cursor-pointer';
            resetPasswordBtn.style.backgroundColor = '#eab308';
            resetPasswordBtn.style.cursor = 'pointer';
        }
        
        // Keep password field ALWAYS disabled and readonly (only Reset button should fill it)
        if (passwordField) {
            passwordField.readOnly = true;
            passwordField.disabled = true;
            passwordField.setAttribute('readonly', 'readonly');
            passwordField.setAttribute('disabled', 'disabled');
            passwordField.style.pointerEvents = 'none';
            passwordField.classList.add('bg-gray-50');
            passwordField.classList.remove('bg-white');
        }
        
        // Enable fields
        fields.forEach(field => {
            if (field.id && !field.id.includes('id_number') && !field.id.includes('password_') && !field.id.includes('username_')) { // Don't edit ID number, password, or username
                field.readOnly = false;
                field.disabled = false;
                field.classList.remove('bg-gray-50');
                field.classList.add('bg-white');
            }
        });
    } else {
        // Switch back to view mode
        editBtn.textContent = 'Edit';
        editBtn.className = 'px-4 py-2 bg-[#2F8D46] text-white rounded-lg hover:bg-[#256f37] transition';
        if (saveBtn) saveBtn.classList.add('hidden');
        
        // Disable reset password button and clear password field
        if (resetPasswordBtn) {
            resetPasswordBtn.disabled = true;
            resetPasswordBtn.setAttribute('disabled', 'disabled');
            resetPasswordBtn.className = 'px-4 py-2 bg-gray-400 text-white rounded-lg font-medium transition-colors cursor-not-allowed';
        }
        
        // Clear password field when canceling
        if (passwordField) {
            passwordField.value = '';
            passwordField.disabled = true;
            passwordField.setAttribute('disabled', 'disabled');
        }
        
        // Disable fields
        fields.forEach(field => {
            field.readOnly = true;
            field.disabled = true;
            field.classList.remove('bg-white');
            field.classList.add('bg-gray-50');
        });
    }
}

// Get current employee ID
function getCurrentEmployeeId() {
    return currentEmployeeId;
}

// Reset employee password function
function resetEmployeePassword(employeeId) {
    const passwordField = document.getElementById(`password_${employeeId}`);
    const lastNameField = document.getElementById(`last_name_${employeeId}`);
    
    if (!passwordField || !lastNameField || !employeeId) {
        alert('Error: Required fields not found. Please ensure last name and employee ID are available.');
        return;
    }
    
    // Get values
    const lastName = (lastNameField.value || '').toLowerCase().replace(/[^a-z]/g, '');
    
    // Validate all fields are filled
    if (!lastName || !employeeId) {
        alert('Error: Last name and employee ID are required to generate password.');
        return;
    }
    
    // Extract the 3-digit number from employee ID (e.g., CCI2025-006 -> 006)
    const parts = employeeId.split('-');
    const idNumber = parts.length === 2 ? parts[1] : '000';
    
    // Get current year
    const currentYear = new Date().getFullYear();
    
    // Format: lastname + idNumber + currentYear (e.g., smith0062025)
    const newPassword = lastName + idNumber + currentYear;
    
    // Enable field and set value (keep it enabled so it submits with the form)
    passwordField.disabled = false;
    passwordField.removeAttribute('disabled');
    passwordField.value = newPassword;
    // Keep readonly and pointer-events:none for visual purposes, but NOT disabled
    passwordField.readOnly = true;
    passwordField.style.pointerEvents = 'none';
    
    // Show custom modal instead of alert
    document.getElementById('generatedPassword').textContent = newPassword;
    document.getElementById('passwordResetModal').classList.remove('hidden');
}

// Close password reset modal
function closePasswordResetModal() {
    document.getElementById('passwordResetModal').classList.add('hidden');
}

// Save employee changes
function saveEmployeeChanges() {
    if (!currentEmployeeId) return;
    
    const formData = new FormData();
    formData.append('employee_id', currentEmployeeId);
    formData.append('action', 'update_employee');
    formData.append('first_name', document.getElementById(`first_name_${currentEmployeeId}`).value);
    formData.append('middle_name', document.getElementById(`middle_name_${currentEmployeeId}`).value);
    formData.append('last_name', document.getElementById(`last_name_${currentEmployeeId}`).value);
    formData.append('position', document.getElementById(`position_${currentEmployeeId}`).value);
    formData.append('department', document.getElementById(`department_${currentEmployeeId}`).value);
    formData.append('email', document.getElementById(`email_${currentEmployeeId}`).value);
    formData.append('phone', document.getElementById(`phone_${currentEmployeeId}`).value);
    formData.append('hire_date', document.getElementById(`hire_date_${currentEmployeeId}`).value);
    
    // Add account fields if they exist
    const usernameField = document.getElementById(`username_${currentEmployeeId}`);
    const passwordField = document.getElementById(`password_${currentEmployeeId}`);
    
    if (usernameField) formData.append('username', usernameField.value);
    if (passwordField) formData.append('password', passwordField.value);
    
    fetch('update_employee.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Employee updated successfully');
            closeViewModal();
            location.reload();
        } else {
            showNotification('Error updating employee: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error updating employee', 'error');
    });
}

// Show delete confirmation
function showDeleteConfirmation(employeeId) {
    currentEmployeeId = employeeId;
    document.getElementById('deleteConfirmationModal').classList.remove('hidden');
    
    // Set up the confirm button
    document.getElementById('confirmDeleteBtn').onclick = function() {
        removeEmployeeAccess(employeeId);
    };
}

// Close delete confirmation
function closeDeleteConfirmation() {
    document.getElementById('deleteConfirmationModal').classList.add('hidden');
}

// Remove employee access
function removeEmployeeAccess(employeeId) {
    fetch('remove_employee_access.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'employee_id=' + encodeURIComponent(employeeId)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Login access removed successfully');
            closeDeleteConfirmation();
            closeViewModal();
            location.reload();
        } else {
            showNotification('Error removing access: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error removing access', 'error');
    });
}

// Create account for employee
function createAccountForEmployee(employeeId) {
    document.getElementById('createAccountModal').classList.remove('hidden');
    
    // Populate the create account form
    document.getElementById('createAccountContent').innerHTML = `
        <input type="hidden" name="employee_id" value="${employeeId}">
        <input type="hidden" name="role" value="hr">
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
            <input type="text" name="username" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
            <input type="password" name="password" required minlength="6" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>
    `;
}

// Close create account modal
function closeCreateAccountModal() {
    document.getElementById('createAccountModal').classList.add('hidden');
}

// Create new account
function createNewAccount(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    formData.append('action', 'create_account');
    
    fetch('create_employee_account.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Account created successfully');
            closeCreateAccountModal();
            closeViewModal();
            location.reload();
        } else {
            showNotification('Error creating account: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error creating account', 'error');
    });
}

// Show delete employee confirmation
function showDeleteEmployeeConfirmation(employeeId) {
    if (confirm('Are you sure you want to delete this employee completely? This action cannot be undone and will remove all employee data and associated accounts.')) {
        deleteEmployee(employeeId);
    }
}

// Delete employee completely
function deleteEmployee(employeeId) {
    fetch('delete_employee.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'employee_id=' + encodeURIComponent(employeeId)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Employee deleted successfully');
            closeViewModal();
            location.reload();
        } else {
            showNotification('Error deleting employee: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error deleting employee', 'error');
    });
}
</script>

</body>
</html>
