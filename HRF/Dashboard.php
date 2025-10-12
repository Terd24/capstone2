<?php
session_start();
include("../StudentLogin/db_conn.php");

// Require HR login or Super Admin access
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['hr', 'superadmin'])) {
    header("Location: ../StudentLogin/login.php");
    exit;
}

// Prevent caching (so back button after logout doesn't show dashboard)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Function to generate next Employee ID
function generateNextEmployeeId($conn) {
    $currentYear = date('Y');
    $prefix = 'CCI' . $currentYear . '-';
    
    // Get the highest existing employee ID for current year
    $query = "SELECT id_number FROM employees WHERE id_number LIKE ? ORDER BY id_number DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $searchPattern = $prefix . '%';
    $stmt->bind_param("s", $searchPattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastId = $row['id_number'];
        // Extract the numeric part after the dash
        $parts = explode('-', $lastId);
        if (count($parts) == 2) {
            $numericPart = intval($parts[1]);
            $nextNumber = $numericPart + 1;
        } else {
            $nextNumber = 1;
        }
    } else {
        // First employee for this year
        $nextNumber = 1;
    }
    
    // Format as CCI2025-001, CCI2025-002, etc.
    return $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
}

// Handle form submission (like registrar)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include("add_employee.php");
}

// Generate next Employee ID for display
$next_employee_id = generateNextEmployeeId($conn);

// Handle error message
$error_msg = $_SESSION['error_msg'] ?? '';
$show_modal = $_SESSION['show_modal'] ?? false;
$form_data = $_SESSION['form_data'] ?? [];

if ($error_msg) {
    unset($_SESSION['error_msg']);
}
if (isset($_SESSION['show_modal'])) {
    unset($_SESSION['show_modal']);
}
if (isset($_SESSION['form_data'])) {
    unset($_SESSION['form_data']);
}

// Ensure soft delete columns exist
$conn->query("ALTER TABLE employees ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL DEFAULT NULL");
$conn->query("ALTER TABLE employees ADD COLUMN IF NOT EXISTS deleted_by VARCHAR(255) NULL");
$conn->query("ALTER TABLE employees ADD COLUMN IF NOT EXISTS deleted_reason TEXT NULL");

// Fetch only active employees (not soft-deleted)
$result = $conn->query("SELECT id_number, CONCAT(first_name, ' ', last_name) as full_name, position, department, 
                       (SELECT username FROM employee_accounts WHERE employee_accounts.employee_id = employees.id_number) as username 
                       FROM employees WHERE deleted_at IS NULL ORDER BY id_number ASC");

// Get total active employee count
$count_result = $conn->query("SELECT COUNT(*) as total_employees FROM employees WHERE deleted_at IS NULL");
$total_employees = $count_result->fetch_assoc()['total_employees'];

$columns = ['ID Number', 'Full Name', 'Position', 'Department', 'Account Status'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Employee Management - CCI</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
input[type=number]::-webkit-inner-spin-button,
input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
input[type=number] { -moz-appearance: textfield; }

/* Hide scrollbars */
.no-scrollbar {
    -ms-overflow-style: none;  /* Internet Explorer 10+ */
    scrollbar-width: none;  /* Firefox */
}
.no-scrollbar::-webkit-scrollbar { 
    display: none;  /* Safari and Chrome */
}
</style>
</head>
<body class="bg-gradient-to-br from-[#f3f6fb] to-[#e6ecf7] font-sans min-h-screen text-gray-900">

<header class="bg-[#0B2C62] text-white shadow-lg">
  <div class="container mx-auto px-6 py-4">
    <div class="flex justify-between items-center">
      <div class="flex items-center space-x-4">
        <div class="text-left">
          <p class="text-sm text-blue-200">Welcome,</p>
          <p class="font-semibold"><?= htmlspecialchars($_SESSION['hr_name'] ?? 'HR User') ?></p>
        </div>

<!-- Delete Employee (entire record) Confirmation Modal -->
<div id="deleteEmployeeModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center" style="z-index:11000;">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl">
        <div class="flex flex-col items-center text-center">
            <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center mb-3">
                <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86A2 2 0 0021 17.13L13.93 4.87a2 2 0 00-3.86 0L3 17.13A2 2 0 005.07 19z" />
                </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Delete Employee</h3>
            <p class="text-gray-600 mb-6">Are you sure you want to delete this employee record? This will also remove their system accounts and cannot be undone.</p>
            <div class="flex w-full gap-3">
                <button onclick="closeDeleteEmployeeConfirmation()" class="flex-1 py-2.5 rounded-lg bg-gray-200 text-gray-800 hover:bg-gray-300 font-medium">Cancel</button>
                <button id="confirmDeleteEmployeeBtn" class="flex-1 py-2.5 rounded-lg bg-red-600 text-white hover:bg-red-700 font-medium">Delete</button>
            </div>
        </div>
    </div>
    
</div>
      </div>
      
      <div class="flex items-center space-x-4">
        <img src="../images/LogoCCI.png" alt="Cornerstone College Inc." class="h-12 w-12 rounded-full bg-white p-1">
        <div class="text-right">
          <h1 class="text-xl font-bold">Cornerstone College Inc.</h1>
          <p class="text-blue-200 text-sm">HR Portal</p>
        </div>
        <div class="relative">
          <button id="menuBtn" class="bg-white bg-opacity-20 hover:bg-opacity-30 p-2 rounded-lg transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
          </button>
          <div id="dropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg z-50 text-gray-800">
            <a href="logout.php" class="block px-4 py-3 hover:bg-gray-100 rounded-lg">
              <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
              </svg>
              Logout
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</header>

<div class="max-w-7xl mx-auto mt-8 p-6">

    <!-- Header Section with Title and Employee Count -->
    <div class="mb-6">
        <div class="flex items-center gap-4">
            <h2 class="text-2xl font-bold text-[#0B2C62]">Employee Management</h2>
            <div class="flex items-center gap-2 bg-[#0B2C62] text-white px-4 py-2 rounded-lg shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <span class="text-sm font-semibold">Total: <?= $total_employees ?></span>
            </div>
        </div>
    </div>

    <!-- Controls Section -->
    <div class="flex flex-col sm:flex-row gap-4 sm:items-center justify-between mb-6 bg-white p-4 rounded-xl shadow-sm border border-[#0B2C62]/10">
        <div class="flex items-center gap-3">
            <!-- Pagination will be shown here after table -->
        </div>
        
        <div class="flex items-center gap-3">
            <input type="text" id="searchInput" placeholder="Search by name or ID..." class="w-full sm:w-64 border border-[#0B2C62]/30 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62] placeholder-gray-400"/>
            <button onclick="openModal()" class="px-4 py-2 bg-green-500 text-white rounded-lg shadow hover:bg-green-600 transition flex items-center gap-2 font-medium whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add Employee
            </button>
            <button onclick="window.location.href='../HRF/ManageEmployeeSchedule.php'" class="px-4 py-2 bg-[#0B2C62] text-white rounded-lg shadow hover:bg-blue-900 transition font-medium whitespace-nowrap">
                Manage Teacher Schedule
            </button>
            <button onclick="window.location.href='../HRF/EmployeeAttendance.php'" class="px-4 py-2 bg-[#0B2C62] text-white rounded-lg shadow hover:bg-blue-900 transition font-medium whitespace-nowrap">
                Teacher Attendance
            </button>
        </div>
    </div>

    <!-- Employees Table -->
    <div class="overflow-x-auto bg-white shadow-lg rounded-2xl p-4 border border-blue-200">
        <table class="min-w-full text-sm border-collapse">
            <thead class="bg-[#0B2C62] text-white">
                <tr>
                    <?php foreach($columns as $col): ?>
                        <th class="px-4 py-3 border text-left font-semibold"><?= $col ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody id="employeeTable" class="divide-y divide-gray-200">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr class="hover:bg-blue-50 transition cursor-pointer" onclick="viewEmployee('<?= $row['id_number'] ?>')">
                            <td class="px-4 py-3"><?= htmlspecialchars($row['id_number']) ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($row['full_name']) ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($row['position']) ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($row['department']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $row['username'] ? '<span class="px-2 py-1 bg-green-500 text-white rounded text-xs">Has Account</span>' : '<span class="px-2 py-1 bg-gray-500 text-white rounded text-xs">No Account</span>' ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?= count($columns) ?>" class="px-4 py-6 text-center text-gray-500 italic">No employees found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination Controls -->
    <div id="paginationBar" class="mt-4 flex items-center justify-center gap-2 text-sm">
        <button id="prevPage" class="px-3 py-1 border rounded-lg text-[#0B2C62] hover:bg-[#0B2C62] hover:text-white disabled:opacity-40 transition">Prev</button>
        <span id="pageInfo" class="px-2 text-gray-600">Page 1 of 1</span>
        <button id="nextPage" class="px-3 py-1 border rounded-lg text-[#0B2C62] hover:bg-[#0B2C62] hover:text-white disabled:opacity-40 transition">Next</button>
    </div>
</div>

<!-- View Employee Modal -->
<div id="viewEmployeeModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white w-full max-w-5xl rounded-2xl shadow-2xl overflow-hidden border-2 border-[#0B2C62] transform transition-all scale-100">
        <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4 bg-[#0B2C62] rounded-t-2xl">
            <h2 class="text-lg font-semibold text-white">Employee Information</h2>
            <div class="flex flex-col items-end gap-1">
                <div class="flex gap-3">
                    <!-- Edit mode buttons (hidden by default) -->
                    <button id="saveChangesBtn" onclick="saveEmployeeChanges()" class="hidden px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition">Save Changes</button>
                    <button id="cancelEditBtn" onclick="cancelEdit()" class="hidden px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">Cancel</button>
                    
                    <!-- View mode buttons -->
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

<!-- Edit Employee Modal -->
<div id="editEmployeeModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="bg-[#0B2C62] text-white p-6 rounded-t-2xl">
            <div class="flex justify-between items-center">
                <h3 class="text-xl font-bold">Edit Employee</h3>
                <button onclick="closeEditModal()" class="text-white hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        <form id="editEmployeeForm" class="p-6">
            <div id="editEmployeeContent">
                <!-- Content will be populated by JavaScript -->
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-[#0B2C62] text-white rounded-lg hover:bg-[#0B2C62]/90 transition">Update Employee</button>
            </div>
        </form>
    </div>
</div>

<!-- Removed duplicate static Create Account Modal to avoid ID conflicts. The dynamic modal below is the single source of truth. -->

<!-- Add Employee Modal -->
<div id="addEmployeeModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white w-full max-w-5xl rounded-2xl shadow-2xl overflow-hidden border-2 border-[#0B2C62] transform transition-all scale-100">
        
        <!-- Header -->
        <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4 bg-[#0B2C62] rounded-t-2xl">
            <h2 class="text-lg font-semibold text-white">Add New Employee</h2>
            <button onclick="closeModal()" class="text-2xl font-bold text-white hover:text-gray-300">&times;</button>
        </div>

        <!-- Error Messages -->
        <?php if (!empty($error_msg)): ?>
            <div class="mx-6 mt-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
                <?= $error_msg ?>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" action="" autocomplete="off" novalidate class="px-6 py-6 grid grid-cols-1 md:grid-cols-3 gap-6 overflow-y-auto max-h-[80vh] no-scrollbar">
            
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
                        
                        <div>
                            <label class="block text-sm font-semibold mb-1">First Name *</label>
                            <input type="text" name="first_name" autocomplete="off" pattern="[A-Za-z\s]+" maxlength="20" title="Letters only, maximum 20 characters" required value="<?= htmlspecialchars($form_data['first_name'] ?? '') ?>" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62] name-input">
                        </div>
                                                 <div>
                            <label class="block text-sm font-semibold mb-1">Last Name *</label>
                            <input type="text" name="last_name" autocomplete="off" pattern="[A-Za-z\s]+" maxlength="20" title="Letters only, maximum 20 characters" required value="<?= htmlspecialchars($form_data['last_name'] ?? '') ?>" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62] name-input">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-1">Middle Name <span class="text-gray-500 text-xs">(Optional)</span></label>
                            <input type="text" name="middle_name" autocomplete="off" pattern="[A-Za-z\s]*" maxlength="20" title="Letters only, maximum 20 characters" value="<?= htmlspecialchars($form_data['middle_name'] ?? '') ?>" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62] name-input">
                        </div>

                                                <div>
                            <label class="block text-sm font-semibold mb-1">Employee ID <span class="text-gray-500 text-xs">(Auto-generated)</span></label>
                            <input type="text" name="id_number" autocomplete="off" value="<?= htmlspecialchars($form_data['id_number'] ?? $next_employee_id) ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62] bg-gray-100 cursor-not-allowed" style="background-color:#f3f4f6;">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-1">Position *</label>
                            <input type="text" name="position" autocomplete="off" pattern="[A-Za-z\s]+" maxlength="20" title="Letters only, maximum 20 characters" required value="<?= htmlspecialchars($form_data['position'] ?? '') ?>" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62] name-input">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Department *</label>
                            <select name="department" required class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
                                <option value="">-- Select Department --</option>
                                <option value="Academic Affairs" <?= ($form_data['department'] ?? '') === 'Academic Affairs' ? 'selected' : '' ?>>Academic Affairs</option>
                                <option value="Student Affairs" <?= ($form_data['department'] ?? '') === 'Student Affairs' ? 'selected' : '' ?>>Student Affairs</option>
                                <option value="Finance" <?= ($form_data['department'] ?? '') === 'Finance' ? 'selected' : '' ?>>Finance</option>
                                <option value="Human Resources" <?= ($form_data['department'] ?? '') === 'Human Resources' ? 'selected' : '' ?>>Human Resources</option>
                                <option value="IT Department" <?= ($form_data['department'] ?? '') === 'IT Department' ? 'selected' : '' ?>>IT Department</option>
                                <option value="Maintenance" <?= ($form_data['department'] ?? '') === 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
                                <option value="Security" <?= ($form_data['department'] ?? '') === 'Security' ? 'selected' : '' ?>>Security</option>
                            </select>
                        </div>
                        
                        <!-- Row 3: Hire Date, Email, Phone -->
                        <div>
                            <label class="block text-sm font-semibold mb-1">Hire Date *</label>
                            <input type="date" name="hire_date" required value="<?= htmlspecialchars($form_data['hire_date'] ?? '') ?>" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-1">Email *</label>
                            <input type="email" name="email" autocomplete="off" required value="<?= htmlspecialchars($form_data['email'] ?? '') ?>" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
                            <p class="field-error-message text-red-600 text-sm mt-1 font-medium hidden"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Phone *</label>
                            <input type="text" name="phone" required value="<?= htmlspecialchars($form_data['phone'] ?? '') ?>" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62] phone-input" inputmode="numeric" pattern="[0-9]{11}" minlength="11" maxlength="11" title="Please enter exactly 11 digits (e.g., 09123456789)" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)">
                            <p class="field-error-message text-red-600 text-sm mt-1 font-medium hidden"></p>
                        </div>
                        

                        <!-- Complete Address -->
                        <div class="col-span-3">
                            <label class="block text-sm font-semibold mb-1">Complete Address *</label>
                            <textarea name="address" rows="3" autocomplete="off" required minlength="20" maxlength="500" placeholder="Enter complete address (e.g., Block 8, Lot 15, Subdivision Name, Barangay, City, Province)" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]" title="Please enter a complete address with at least 20 characters including street, barangay, city/municipality, and province."><?= htmlspecialchars($form_data['address'] ?? '') ?></textarea>
                            <p class="text-xs text-gray-500 mt-1">Minimum 20 characters. Include street, barangay, city/municipality, and province.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Account Section -->
            <div class="col-span-3 mb-6">
                <div class="bg-gray-50 border-2 border-gray-400 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        SYSTEM ACCOUNT  
                    </h3>
                    
                    <div class="mb-4">
                        <div class="flex items-center gap-2 select-none">
                            <input type="checkbox" id="createAccount" name="create_account" class="rounded border-gray-300 text-[#0B2C62] focus:ring-[#0B2C62] cursor-pointer" aria-controls="accountFields">
                            <span class="text-sm font-medium text-gray-700 pointer-events-none">Create system account for this employee</span>
                        </div>
                    </div>
                    
                    <div id="accountFields" class="hidden">
                        <div class="grid grid-cols-3 gap-6 mb-4">
                            <div>
                                <label class="block text-sm font-semibold mb-1">Username <span class="text-gray-500 text-xs">(Auto-generated)</span></label>
                                <input type="text" id="usernameField" name="username" autocomplete="off" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62] bg-gray-100 cursor-not-allowed" style="background-color:#f3f4f6;">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-1">Password <span class="text-gray-500 text-xs">(Auto-generated)</span></label>
                                <input type="text" id="passwordField" name="password" autocomplete="new-password" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62] bg-gray-100 cursor-not-allowed" style="background-color:#f3f4f6;">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-1">Role</label>
                                <select id="employeeRole" name="role" onchange="toggleRFIDField()" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
                                    <option value="">-- Select Role --</option>
                                    <option value="registrar">Registrar</option>
                                    <option value="cashier">Cashier</option>
                                    <option value="guidance">Guidance</option>
                                    <option value="attendance">Attendance</option>
                                    <option value="teacher">Teacher</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- RFID Field - Only shown for Teacher role -->
                        <div id="rfidFieldContainer" class="hidden">
                            <div class="grid grid-cols-1 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold mb-1">RFID Number *</label>
                                    <input type="text" id="rfid_uid" name="rfid_uid" autocomplete="off" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62] rfid-input" inputmode="numeric" pattern="[0-9]{10}" minlength="10" maxlength="10" title="Please enter exactly 10 digits (numbers only)" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="col-span-3 flex justify-end gap-4 pt-6 border-t border-gray-200">
                <button type="button" onclick="closeModal()" class="px-5 py-2 border border-blue-600 text-blue-900 rounded-xl hover:bg-[#0B2C62] hover:text-white transition">Cancel</button>
                <button type="button" onclick="confirmAddEmployee()" class="px-5 py-2 bg-green-600 text-white rounded-xl shadow hover:bg-green-700 transition">
                    Add Employee
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Notifications removed - using toast notifications instead -->

<!-- Edit Account Modal -->
<div id="editAccountModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Employee Details</h3>
            <div class="flex items-center gap-2">
                <button id="editEmployeeBtn" onclick="toggleEditMode()" class="px-3 py-1 bg-blue-500 text-white rounded text-sm hover:bg-blue-600">
                    Edit
                </button>
                <button onclick="closeViewModal()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        <form id="editAccountForm" onsubmit="updateAccount(event)">
            <div id="editAccountContent">
                <!-- Content will be populated by JavaScript -->
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeEditAccountModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                    Update Account
                </button>
            </div>
        </form>
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

<style>
/* Hide scrollbar for employee details content */
#employeeDetailsContent::-webkit-scrollbar {
    display: none;
}
</style>

<script>
// Show modal if there's an error
<?php if ($show_modal): ?>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('addEmployeeModal').classList.remove('hidden');
});
<?php endif; ?>

// Modal functions
function openModal() {
    document.getElementById('addEmployeeModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('addEmployeeModal').classList.add('hidden');
}

// Validation helper functions
function highlightFieldError(element, message) {
    if (!element) return;
    
    // Add red border and background
    element.classList.remove('border-gray-300', 'focus:ring-[#0B2C62]');
    element.classList.add('border-red-500', 'focus:ring-red-500', 'bg-red-50');
    
    // Create or update error message
    let errorMsg = element.parentElement.querySelector('.field-error-message');
    if (!errorMsg) {
        errorMsg = document.createElement('p');
        errorMsg.className = 'field-error-message text-red-600 text-sm mt-1 font-medium';
        element.parentElement.appendChild(errorMsg);
    }
    errorMsg.textContent = message;
    errorMsg.classList.remove('hidden');
    
    // Focus on first error field
    if (!document.querySelector('.border-red-500')) {
        element.focus();
    }
}

function clearFieldErrors(form) {
    if (!form) return;
    
    // Remove red borders and backgrounds
    const errorFields = form.querySelectorAll('.border-red-500');
    errorFields.forEach(field => {
        field.classList.remove('border-red-500', 'focus:ring-red-500', 'bg-red-50');
        field.classList.add('border-gray-300', 'focus:ring-[#0B2C62]');
    });
    
    // Hide error messages
    const errorMessages = form.querySelectorAll('.field-error-message');
    errorMessages.forEach(msg => {
        msg.classList.add('hidden');
        msg.textContent = '';
    });
}

function clearSingleFieldError(element) {
    if (!element) return;
    
    // Remove red border and background
    element.classList.remove('border-red-500', 'focus:ring-red-500', 'bg-red-50');
    element.classList.add('border-gray-300', 'focus:ring-[#0B2C62]');
    
    // Hide error message
    const errorMsg = element.parentElement.querySelector('.field-error-message');
    if (errorMsg) {
        errorMsg.classList.add('hidden');
        errorMsg.textContent = '';
    }
}

// Add input event listeners to clear errors when user types
function setupFieldValidationListeners() {
    const form = document.querySelector('#addEmployeeModal form');
    if (!form) return;
    
    // Get all input, select, and textarea elements
    const fields = form.querySelectorAll('input, select, textarea');
    
    fields.forEach(field => {
        // Clear error on input/change
        field.addEventListener('input', function() {
            if (this.classList.contains('border-red-500')) {
                clearSingleFieldError(this);
            }
        });
        
        field.addEventListener('change', function() {
            if (this.classList.contains('border-red-500')) {
                clearSingleFieldError(this);
            }
        });
    });
}

// Auto-generate username and password based on last name and employee ID
function generateUsernameAndPassword() {
    const form = document.querySelector('#addEmployeeModal form');
    if (!form) return;
    
    const lastNameField = form.querySelector('input[name="last_name"]');
    const employeeIdField = form.querySelector('input[name="id_number"]');
    const usernameField = document.getElementById('usernameField');
    const passwordField = document.getElementById('passwordField');
    
    if (!lastNameField || !employeeIdField || !usernameField || !passwordField) return;
    
    const lastName = lastNameField.value.trim().toLowerCase();
    const employeeId = employeeIdField.value.trim();
    
    if (lastName && employeeId) {
        // Extract the 3-digit number from employee ID (e.g., CCI2025-001 -> 001)
        const parts = employeeId.split('-');
        const idNumber = parts.length === 2 ? parts[1] : '000';
        
        // Get current year
        const currentYear = new Date().getFullYear();
        
        // Format username: lastname001muzon@employee.cci.edu.ph
        const username = lastName + idNumber + 'muzon@employee.cci.edu.ph';
        usernameField.value = username;
        
        // Format password: lastname0012025
        const password = lastName + idNumber + currentYear;
        passwordField.value = password;
    } else {
        usernameField.value = '';
        passwordField.value = '';
    }
}

// Setup username and password auto-generation
function setupUsernameAndPasswordGeneration() {
    const form = document.querySelector('#addEmployeeModal form');
    if (!form) return;
    
    const lastNameField = form.querySelector('input[name="last_name"]');
    
    if (lastNameField) {
        // Generate username and password when last name changes
        lastNameField.addEventListener('input', generateUsernameAndPassword);
        lastNameField.addEventListener('blur', generateUsernameAndPassword);
    }
    
    // Generate on page load if last name already has value
    generateUsernameAndPassword();
}

// Initialize field validation listeners when page loads
document.addEventListener('DOMContentLoaded', function() {
    setupFieldValidationListeners();
    setupUsernameAndPasswordGeneration();
});

// Show/hide account fields and RFID when needed
const createAccountChk = document.getElementById('createAccount');
const accountFields = document.getElementById('accountFields');
const roleSelect = document.getElementById('employeeRole');
const rfidField = document.getElementById('rfidField');
const rfidInput = document.getElementById('rfid_uid');

function updateAccountFieldsVisibility() {
    if (!createAccountChk || !accountFields) return;
    const show = createAccountChk.checked;
    accountFields.classList.toggle('hidden', !show);
    updateRFIDRequirement();
}

function toggleRFIDField() {
    const roleSelect = document.getElementById('employeeRole');
    const rfidContainer = document.getElementById('rfidFieldContainer');
    const rfidInput = document.getElementById('rfid_uid');
    
    if (!roleSelect || !rfidContainer || !rfidInput) return;
    
    const selectedRole = roleSelect.value;
    
    // Show RFID field only for Teacher role
    if (selectedRole === 'teacher') {
        rfidContainer.classList.remove('hidden');
        rfidInput.setAttribute('required', 'required');
    } else {
        rfidContainer.classList.add('hidden');
        rfidInput.removeAttribute('required');
        rfidInput.value = ''; // Clear the value when hidden
    }
}

function updateRFIDRequirement() {
    // Call the new toggle function
    toggleRFIDField();
}

// Toggle RFID field in Create Account modal
function toggleCreateAccountRFID() {
    const roleSelect = document.getElementById('ca_role');
    const rfidContainer = document.getElementById('ca_rfid_container');
    const rfidInput = document.getElementById('ca_rfid_uid');
    
    if (!roleSelect || !rfidContainer || !rfidInput) return;
    
    const selectedRole = roleSelect.value;
    
    // Show RFID field only for Teacher role
    if (selectedRole === 'teacher') {
        rfidContainer.classList.remove('hidden');
        rfidInput.setAttribute('required', 'required');
    } else {
        rfidContainer.classList.add('hidden');
        rfidInput.removeAttribute('required');
        rfidInput.value = ''; // Clear the value when hidden
    }
}

if (createAccountChk) createAccountFieldsBound = (createAccountChk.addEventListener('change', updateAccountFieldsVisibility), true);
if (roleSelect) roleSelect.addEventListener('change', updateRFIDRequirement);
try { updateAccountFieldsVisibility(); } catch(e) { /* no-op */ }

// Show notification
document.addEventListener('DOMContentLoaded', function() {
    const notif = document.getElementById('notif');
    if (notif) {
        setTimeout(() => {
            notif.classList.remove('translate-x-full', 'opacity-0');
        }, 100);
        
        setTimeout(() => {
            notif.classList.add('translate-x-full', 'opacity-0');
        }, 3000);
    }
    
    const errorNotif = document.getElementById('error-notif');
    if (errorNotif) {
        setTimeout(() => {
            errorNotif.classList.remove('translate-x-full', 'opacity-0');
        }, 100);
        
        setTimeout(() => {
            errorNotif.classList.add('translate-x-full', 'opacity-0');
        }, 5000);
    }
});

// Employee view function
function viewEmployee(employeeId) {
    // Fetch employee details and show in modal
    fetch(`view_employee.php?id=${employeeId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showEmployeeDetailsModal(data.employee);
            } else {
                showToast('Error loading employee details: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error loading employee details', 'error');
        });
}

// Show employee details modal
let currentEmployeeId = null;
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
                        <!-- Row: ID Number and Full Name -->
                        <div>
                            <label class="block text-sm font-semibold mb-1">First Name</label>
                            <input type="text" id="first_name_${employee.id_number}" value="${employee.first_name}" readonly pattern="[A-Za-z\s]+" maxlength="20" title="Letters only, maximum 20 characters" class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 employee-field name-input">
                            <p id="first_name_error_${employee.id_number}" class="hidden text-sm text-red-600 mt-1">First name is required</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Last Name</label>
                            <input type="text" id="last_name_${employee.id_number}" value="${employee.last_name}" readonly pattern="[A-Za-z\s]+" maxlength="20" title="Letters only, maximum 20 characters" class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 employee-field name-input">
                            <p id="last_name_error_${employee.id_number}" class="hidden text-sm text-red-600 mt-1">Last name is required</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Middle Name <span class="text-gray-500 text-xs">(Optional)</span></label>
                            <input type="text" id="middle_name_${employee.id_number}" value="${employee.middle_name || ''}" readonly pattern="[A-Za-z\s]*" maxlength="20" title="Letters only, maximum 20 characters" class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 employee-field name-input">
                        </div>
                                                <div>
                            <label class="block text-sm font-semibold mb-1">Employee ID<span class="text-gray-500 text-xs">(Cannot be changed)</span></label>
                            <input type="text" value="${employee.id_number}" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-100 cursor-not-allowed employee-field-readonly" style="background-color:#f3f4f6;">
                        </div>
                        
                        <!-- Row: Position, Department, Email -->
                        <div>
                            <label class="block text-sm font-semibold mb-1">Position</label>
                            <input type="text" id="position_${employee.id_number}" value="${employee.position}" readonly pattern="[A-Za-z\s]+" maxlength="20" title="Letters only, maximum 20 characters" class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 employee-field name-input">
                            <p id="position_error_${employee.id_number}" class="hidden text-sm text-red-600 mt-1">Position is required</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Department</label>
                            <input type="text" id="department_${employee.id_number}" value="${employee.department}" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 employee-field">
                            <p id="department_error_${employee.id_number}" class="hidden text-sm text-red-600 mt-1">Department is required</p>
                        </div>

                         <div>
                            <label class="block text-sm font-semibold mb-1">Hire Date <span class="text-gray-500 text-xs">(Cannot be changed)</span></label>
                            <input type="date" id="hire_date_${employee.id_number}" value="${employee.hire_date}" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-100 cursor-not-allowed employee-field-readonly" style="background-color:#f3f4f6;">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Email</label>
                            <input type="email" id="email_${employee.id_number}" value="${employee.email || ''}" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 employee-field">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Phone</label>
                            <input type="tel" id="phone_${employee.id_number}" value="${employee.phone || ''}" readonly pattern="[0-9]{11}" minlength="11" maxlength="11" title="Please enter exactly 11 digits" inputmode="numeric" class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 employee-field phone-input">
                        </div>
                        
                        <!-- Complete Address (full width) -->
                        <div class="col-span-3">
                            <label class="block text-sm font-semibold mb-1">Complete Address</label>
                            <textarea id="address_${employee.id_number}" rows="3" readonly minlength="20" maxlength="500" class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 employee-field">${employee.address || ''}</textarea>
                            <p id="address_error_${employee.id_number}" class="hidden text-sm text-red-600 mt-1"></p>
                            <p class="text-xs text-gray-500 mt-1">Minimum 20 characters. Include street, barangay, city/municipality, and province.</p>
                        </div>
                        
                        ${employee.account_role === 'teacher' ? `
                        <div>
                            <label class="block text-sm font-semibold mb-1">RFID</label>
                            <input type="text" id="rfid_uid" name="rfid_uid" autocomplete="off" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62] digits-only" inputmode="numeric" pattern="[0-9]{10}" minlength="10" maxlength="10" data-maxlen="10" title="Please enter exactly 10 digits">
                        </div>
                        ` : ''}
                    </div>
                </div>
            </div>
            ${employee.username ? `
            <!-- Personal Account Section -->
            <div class="col-span-3 mb-6">
                <div class="bg-gray-50 border-2 border-gray-400 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-1 flex items-center justify-between">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <span>PERSONAL ACCOUNT</span>
                        <button type="button" onclick="showDeleteConfirmation('${employee.id_number}')" class="ml-auto px-3 py-1.5 text-sm bg-amber-600 text-white rounded hover:bg-amber-700">
                            Remove Login Access
                        </button>
                    </h3>
                    <p class="text-xs text-gray-500 mb-4 text-right">Removes login access only. The employee record will remain.</p>
                    <div class="grid grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-semibold mb-1">Username <span class="text-gray-500 text-xs">(Auto-updates with last name)</span></label>
                            <input type="text" id="username_${employee.id_number}" value="${employee.username}" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-100 cursor-not-allowed employee-field-readonly" style="background-color:#f3f4f6;">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Role</label>
                            <select id="role_${employee.id_number}" disabled class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 employee-field">
                                <option value="registrar" ${employee.account_role === 'registrar' ? 'selected' : ''}>Registrar</option>
                                <option value="cashier" ${employee.account_role === 'cashier' ? 'selected' : ''}>Cashier</option>
                                <option value="guidance" ${employee.account_role === 'guidance' ? 'selected' : ''}>Guidance</option>
                                <option value="attendance" ${employee.account_role === 'attendance' ? 'selected' : ''}>Attendance</option>
                                <option value="teacher" ${employee.account_role === 'teacher' ? 'selected' : ''}>Teacher</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Password</label>
                            <input type="password" id="password_${employee.id_number}" placeholder="Enter new password" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62] employee-field">
                            <small class="text-gray-500">Leave blank to keep current password</small>
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
        </div>
    `;
    document.getElementById('viewEmployeeModal').classList.remove('hidden');
    
    // Setup input restrictions for dynamically created fields
    setTimeout(() => {
        setupInputRestrictions();
        setupUsernameAutoUpdate(employee.id_number);
    }, 100);
}

// Auto-update username when last name changes in edit mode
function setupUsernameAutoUpdate(employeeId) {
    const lastNameField = document.getElementById(`last_name_${employeeId}`);
    const usernameField = document.getElementById(`username_${employeeId}`);
    
    if (!lastNameField || !usernameField) return;
    
    // Store original username to extract the ID part
    const originalUsername = usernameField.value;
    
    // Extract the 3-digit ID from username (e.g., smith001muzon@employee.cci.edu.ph -> 001)
    const match = originalUsername.match(/(\d{3})muzon@employee\.cci\.edu\.ph$/);
    const idNumber = match ? match[1] : '000';
    
    // Update username when last name changes
    lastNameField.addEventListener('input', function() {
        const lastName = this.value.trim().toLowerCase();
        if (lastName) {
            const newUsername = lastName + idNumber + 'muzon@employee.cci.edu.ph';
            usernameField.value = newUsername;
        }
    });
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
    // Disable all fields
    document.querySelectorAll('.employee-field').forEach(field => {
        if (field.type === 'text' || field.type === 'email' || field.type === 'date' || field.tagName === 'SELECT') {
            field.readOnly = true;
            field.disabled = true;
            field.classList.add('bg-gray-50');
            field.classList.remove('bg-white');
        }
    });
}

// Toggle edit mode
let isEditMode = false;
function toggleEditMode() {
    const editBtn = document.getElementById('editEmployeeBtn');
    const deleteBtn = document.getElementById('deleteEmployeeBtn');
    const saveBtn = document.getElementById('saveChangesBtn');
    const cancelBtn = document.getElementById('cancelEditBtn');
    const fields = document.querySelectorAll('.employee-field');
    
    isEditMode = true;
    
    // Hide Edit and Delete buttons
    if (editBtn) editBtn.classList.add('hidden');
    if (deleteBtn) deleteBtn.classList.add('hidden');
    
    // Show Save and Cancel buttons
    if (saveBtn) saveBtn.classList.remove('hidden');
    if (cancelBtn) cancelBtn.classList.remove('hidden');
    
    // Enable fields for editing (except readonly fields like ID Number)
    fields.forEach(field => {
        // Skip fields that should never be editable
        if (field.classList.contains('employee-field-readonly')) {
            return;
        }
        
        if (['TEXTAREA'].includes(field.tagName) || ['text', 'email', 'date', 'tel'].includes(field.type)) {
            field.readOnly = false;
            field.classList.remove('bg-gray-50');
            field.classList.add('bg-white');
            field.classList.add('focus:ring-2', 'focus:ring-[#0B2C62]', 'focus:border-[#0B2C62]');
        } else if (['radio', 'checkbox'].includes(field.type) || field.tagName === 'SELECT') {
            field.disabled = false;
            field.classList.remove('bg-gray-50');
            field.classList.add('bg-white');
        }
    });
    
    // Setup input restrictions after enabling fields
    setTimeout(() => {
        setupInputRestrictions();
    }, 100);
}

function cancelEdit() {
    const editBtn = document.getElementById('editEmployeeBtn');
    const deleteBtn = document.getElementById('deleteEmployeeBtn');
    const saveBtn = document.getElementById('saveChangesBtn');
    const cancelBtn = document.getElementById('cancelEditBtn');
    
    isEditMode = false;
    
    // Show Edit and Delete buttons
    if (editBtn) editBtn.classList.remove('hidden');
    if (deleteBtn) deleteBtn.classList.remove('hidden');
    
    // Hide Save and Cancel buttons
    if (saveBtn) saveBtn.classList.add('hidden');
    if (cancelBtn) cancelBtn.classList.add('hidden');
    
    // Reload the modal to restore original values
    if (currentEmployeeId) {
        // Fetch fresh employee data from server
        fetch(`view_employee.php?id=${currentEmployeeId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showEmployeeDetailsModal(data.employee);
                }
            })
            .catch(error => {
                console.error('Error reloading employee data:', error);
            });
    }
}

function saveEmployeeChanges() {
    if (!currentEmployeeId) {
        showToast('No employee selected', 'error');
        return;
    }
    
    // Collect employee data from the form fields
    const firstName = document.getElementById(`first_name_${currentEmployeeId}`)?.value;
    const middleName = document.getElementById(`middle_name_${currentEmployeeId}`)?.value;
    const lastName = document.getElementById(`last_name_${currentEmployeeId}`)?.value;
    const position = document.getElementById(`position_${currentEmployeeId}`)?.value;
    const department = document.getElementById(`department_${currentEmployeeId}`)?.value;
    const email = document.getElementById(`email_${currentEmployeeId}`)?.value;
    const phone = document.getElementById(`phone_${currentEmployeeId}`)?.value;
    const address = document.getElementById(`address_${currentEmployeeId}`)?.value;
    const hireDate = document.getElementById(`hire_date_${currentEmployeeId}`)?.value;
    
    // Clear all previous errors
    const fields = [
        { id: `first_name_${currentEmployeeId}`, errorId: `first_name_error_${currentEmployeeId}`, value: firstName },
        { id: `last_name_${currentEmployeeId}`, errorId: `last_name_error_${currentEmployeeId}`, value: lastName },
        { id: `position_${currentEmployeeId}`, errorId: `position_error_${currentEmployeeId}`, value: position },
        { id: `department_${currentEmployeeId}`, errorId: `department_error_${currentEmployeeId}`, value: department }
    ];
    
    let hasErrors = false;
    
    // Clear all red borders and error messages
    fields.forEach(field => {
        const element = document.getElementById(field.id);
        const errorElement = document.getElementById(field.errorId);
        if (element) {
            element.classList.remove('border-red-500');
            element.classList.add('border-gray-300');
        }
        if (errorElement) errorElement.classList.add('hidden');
    });
    
    // Clear address error
    const addressField = document.getElementById(`address_${currentEmployeeId}`);
    const addressError = document.getElementById(`address_error_${currentEmployeeId}`);
    if (addressError) addressError.classList.add('hidden');
    if (addressField) {
        addressField.classList.remove('border-red-500');
        addressField.classList.add('border-gray-300');
    }
    
    // Validate required fields
    fields.forEach(field => {
        if (!field.value || field.value.trim() === '') {
            const element = document.getElementById(field.id);
            const errorElement = document.getElementById(field.errorId);
            if (element) {
                element.classList.remove('border-gray-300');
                element.classList.add('border-red-500');
            }
            if (errorElement) errorElement.classList.remove('hidden');
            hasErrors = true;
        }
    });
    
    // Validate address
    let addressErrorMsg = '';
    if (!address || address.trim().length < 20) {
        addressErrorMsg = 'Complete address must include multiple components (street, barangay, city, etc.) separated by commas or spaces.';
        hasErrors = true;
    } else if (address.trim().length > 500) {
        addressErrorMsg = 'Complete address must not exceed 500 characters.';
        hasErrors = true;
    } else if (!/[,\s]/.test(address)) {
        addressErrorMsg = 'Complete address must include multiple components (street, barangay, city, etc.) separated by commas or spaces.';
        hasErrors = true;
    }
    
    if (addressErrorMsg) {
        if (addressError) {
            addressError.textContent = addressErrorMsg;
            addressError.classList.remove('hidden');
        }
        if (addressField) {
            addressField.classList.remove('border-gray-300');
            addressField.classList.add('border-red-500');
        }
    }
    
    if (hasErrors) {
        return;
    }
    
    // Prepare form data
    // Show confirmation dialog before saving
    showEmployeeSaveConfirmation(firstName, middleName, lastName, position, department, email, phone, address, hireDate);
}

function showEmployeeSaveConfirmation(firstName, middleName, lastName, position, department, email, phone, address, hireDate) {
    const c = document.createElement('div');
    c.className = 'fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-[2147483647]';
    c.innerHTML = `
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 p-6">
            <h3 class="text-lg font-semibold mb-2">Confirm Changes</h3>
            <p class="text-gray-600 mb-6">Are you sure you want to save these changes to the employee information?</p>
            <div class="flex justify-end gap-2">
                <button id="cCancel" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded">Cancel</button>
                <button id="cSave" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Confirm & Save</button>
            </div>
        </div>
    `;
    document.body.appendChild(c);
    c.querySelector('#cCancel').onclick = () => c.remove();
    c.querySelector('#cSave').onclick = () => {
        c.remove();
        performEmployeeSave(firstName, middleName, lastName, position, department, email, phone, address, hireDate);
    };
}

function performEmployeeSave(firstName, middleName, lastName, position, department, email, phone, address, hireDate) {
    const formData = new FormData();
    formData.append('employee_id', currentEmployeeId);
    formData.append('first_name', firstName);
    formData.append('middle_name', middleName || '');
    formData.append('last_name', lastName);
    formData.append('position', position);
    formData.append('department', department);
    formData.append('email', email || '');
    formData.append('phone', phone || '');
    formData.append('address', address);
    formData.append('hire_date', hireDate);
    
    fetch('edit_employee.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Show success notification
            showToast('Employee updated successfully!', 'success');
            
            // Exit edit mode and refresh the modal data without closing
            isEditMode = false;
            
            // Show Edit and Delete buttons
            const editBtn = document.getElementById('editEmployeeBtn');
            const deleteBtn = document.getElementById('deleteEmployeeBtn');
            if (editBtn) editBtn.classList.remove('hidden');
            if (deleteBtn) deleteBtn.classList.remove('hidden');
            
            // Hide Save and Cancel buttons
            const saveBtn = document.getElementById('saveChangesBtn');
            const cancelBtn = document.getElementById('cancelEditBtn');
            if (saveBtn) saveBtn.classList.add('hidden');
            if (cancelBtn) cancelBtn.classList.add('hidden');
            
            // Reload employee data in the modal without closing it
            fetch(`view_employee.php?id=${currentEmployeeId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showEmployeeDetailsModal(data.employee);
                    }
                });
        } else {
            showToast('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error updating employee: ' + error.message, 'error');
    });
}

// Get current employee ID
function getCurrentEmployeeId() {
    return currentEmployeeId;
}

// Show delete confirmation modal
function showDeleteConfirmation(employeeId) {
    const modal = document.getElementById('deleteConfirmationModal');
    modal.classList.remove('hidden');
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    confirmBtn.onclick = function() {
        removeAccount(employeeId);
    };
}

function closeDeleteConfirmation() {
    document.getElementById('deleteConfirmationModal').classList.add('hidden');
}

// Delete Employee (entire record) handlers
function showDeleteEmployeeConfirmation(employeeId) {
    const modal = document.getElementById('deleteEmployeeModal');
    modal.classList.remove('hidden');
    const btn = document.getElementById('confirmDeleteEmployeeBtn');
    btn.onclick = function(){ removeEmployee(employeeId); };
}

function closeDeleteEmployeeConfirmation() {
    document.getElementById('deleteEmployeeModal').classList.add('hidden');
}

function removeEmployee(employeeId) {
    console.log('Deleting employee:', employeeId);
    fetch('delete_employee.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `employee_id=${encodeURIComponent(employeeId)}`
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data=>{
        console.log('Response data:', data);
        if(data.success){
            closeDeleteEmployeeConfirmation();
            closeViewModal();
            showToast('Employee deleted successfully', 'success');
            setTimeout(()=> location.reload(), 800);
        } else {
            closeDeleteEmployeeConfirmation();
            showToast(data.message || 'Error deleting employee', 'error');
        }
    })
    .catch(error=>{
        console.error('Delete error:', error);
        closeDeleteEmployeeConfirmation();
        showToast('Network error while deleting employee: ' + error.message, 'error');
    });
}

// Account management functions
function editAccountDetails(employeeId) {
    fetch(`view_employee.php?id=${employeeId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.employee.username) {
                showEditAccountModal(data.employee);
            } else {
                showToast('Error loading account details', 'error');
            }
        });
}

function resetPassword(employeeId) {
    if (confirm('Are you sure you want to reset this employee\'s password?')) {
        const newPassword = prompt('Enter new password:');
        if (newPassword && newPassword.length >= 6) {
            fetch('manage_account.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=reset_password&employee_id=${employeeId}&new_password=${encodeURIComponent(newPassword)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Password reset successfully', 'success');
                    setTimeout(() => closeViewModal(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            });
        } else {
            showToast('Password must be at least 6 characters long', 'error');
        }
    }
}

function removeAccount(employeeId) {
    // Perform deletion and use custom toast, no browser confirm() or alert()
    fetch('manage_account.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=remove_account&employee_id=${employeeId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeDeleteConfirmation();
            closeViewModal();
            showToast('Account removed successfully', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            closeDeleteConfirmation();
            showToast(data.message || 'Error removing account', 'error');
        }
    })
    .catch(() => {
        closeDeleteConfirmation();
        showToast('Network error while removing account', 'error');
    });
}

function createAccountForEmployee(employeeId) {
    fetch(`view_employee.php?id=${employeeId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showCreateAccountModal(data.employee);
            }
        });
}

// Modal functions for account management
function showEditAccountModal(employee) {
    const content = document.getElementById('editAccountContent');
    content.innerHTML = `
        <input type="hidden" name="employee_id" value="${employee.id_number}">
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
            <input type="text" name="username" value="${employee.username}" required 
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
            <select name="role" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="registrar" ${employee.account_role === 'registrar' ? 'selected' : ''}>Registrar</option>
                <option value="cashier" ${employee.account_role === 'cashier' ? 'selected' : ''}>Cashier</option>
                <option value="guidance" ${employee.account_role === 'guidance' ? 'selected' : ''}>Guidance</option>
                <option value="attendance" ${employee.account_role === 'attendance' ? 'selected' : ''}>Attendance</option>
                <option value="teacher" ${employee.account_role === 'teacher' ? 'selected' : ''}>Teacher</option>
            </select>
        </div>
    `;
    document.getElementById('editAccountModal').classList.remove('hidden');
}

function showCreateAccountModal(employee) {
    const content = document.getElementById('createAccountContent');
    content.innerHTML = `
        <input type="hidden" name="employee_id" value="${employee.id_number}">
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
            <input type="text" id="ca_username" name="username" required autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false" tabindex="0"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500">
            <span id="ca_username_error" class="hidden text-red-500 text-sm mt-1"></span>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
            <input type="password" name="password" required minlength="6" autocomplete="new-password" tabindex="0"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
            <select name="role" id="ca_role" required onchange="toggleCreateAccountRFID()" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500" tabindex="0">
                <option value="">Select Role</option>
                <option value="registrar">Registrar</option>
                <option value="cashier">Cashier</option>
                <option value="guidance">Guidance</option>
                <option value="attendance">Attendance</option>
                <option value="teacher">Teacher</option>
            </select>
        </div>
        <div id="ca_rfid_container" class="mb-4 hidden">
            <label class="block text-sm font-medium text-gray-700 mb-2">RFID Number *</label>
            <input type="text" id="ca_rfid_uid" name="rfid_uid" autocomplete="off" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500 digits-only" inputmode="numeric" pattern="[0-9]{10}" minlength="10" maxlength="10" title="Please enter exactly 10 digits (numbers only)" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)">
        </div>
    `;
    document.getElementById('createAccountModal').classList.remove('hidden');
    // Autofocus the username field to ensure typing works immediately
    setTimeout(() => {
        const usernameInput = document.querySelector('#createAccountModal input[name="username"]');
        if (usernameInput) {
            usernameInput.readOnly = false;
            usernameInput.disabled = false;
            usernameInput.focus();
        }
        const passwordInput = document.querySelector('#createAccountModal input[name="password"]');
        if (passwordInput) {
            passwordInput.readOnly = false;
            passwordInput.disabled = false;
        }
    }, 0);
    // Prevent global key handlers from hijacking keystrokes while modal is open
    const modalEl = document.getElementById('createAccountModal');
    if (modalEl && !modalEl._keydownBound) {
        modalEl.addEventListener('keydown', (e) => { e.stopPropagation(); }, true);
        modalEl._keydownBound = true;
    }
}

function closeEditAccountModal() {
    document.getElementById('editAccountModal').classList.add('hidden');
}

function closeCreateAccountModal() {
    document.getElementById('createAccountModal').classList.add('hidden');
}

function updateAccount(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'update_account');
    
    fetch('manage_account.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Account updated successfully', 'success');
            closeEditAccountModal();
            closeViewModal();
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast('Error: ' + data.message, 'error');
        }
    });
}

function createNewAccount(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('create_account', '1');
    // Clear previous username error styles/messages
    const u = document.getElementById('ca_username');
    const uErr = document.getElementById('ca_username_error');
    if (u) {
        u.classList.remove('border-red-500','ring-2','ring-red-500');
    }
    if (uErr) {
        uErr.textContent = '';
        uErr.classList.add('hidden');
    }
    
    fetch('create_account.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Account created successfully', 'success');
            closeCreateAccountModal();
            closeViewModal();
            setTimeout(() => location.reload(), 1500);
        } else {
            // Inline handle for duplicate username
            if (data.message && data.message.toLowerCase().includes('username already taken')) {
                if (u) {
                    u.classList.add('border-red-500','ring-2','ring-red-500');
                    u.focus();
                }
                if (uErr) {
                    uErr.textContent = 'Username already in use';
                    uErr.classList.remove('hidden');
                }
                return; // keep modal open, no toast
            }
            showToast('Error: ' + data.message, 'error');
        }
    });
}

// Edit employee function
function editEmployee(employeeId) {
    fetch(`view_employee.php?id=${employeeId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showEditEmployeeModal(data.employee);
            } else {
                showToast('Error loading employee details: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error loading employee details', 'error');
        });
}

// Show edit employee modal
function showEditEmployeeModal(employee) {
    const content = document.getElementById('editEmployeeContent');
    content.innerHTML = `
        <input type="hidden" name="employee_id" value="${employee.id_number}">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ID Number</label>
                <input type="text" name="id_number" value="${employee.id_number}" readonly class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-100 cursor-not-allowed" style="background-color:#f3f4f6;">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                <input type="text" name="full_name" value="${employee.full_name}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Position</label>
                <input type="text" name="position" value="${employee.position}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                <select name="department" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
                    <option value="Academic Affairs" ${employee.department === 'Academic Affairs' ? 'selected' : ''}>Academic Affairs</option>
                    <option value="Student Affairs" ${employee.department === 'Student Affairs' ? 'selected' : ''}>Student Affairs</option>
                    <option value="Finance" ${employee.department === 'Finance' ? 'selected' : ''}>Finance</option>
                    <option value="Human Resources" ${employee.department === 'Human Resources' ? 'selected' : ''}>Human Resources</option>
                    <option value="IT Department" ${employee.department === 'IT Department' ? 'selected' : ''}>IT Department</option>
                    <option value="Maintenance" ${employee.department === 'Maintenance' ? 'selected' : ''}>Maintenance</option>
                    <option value="Security" ${employee.department === 'Security' ? 'selected' : ''}>Security</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                <input type="email" name="email" value="${employee.email || ''}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                <input type="text" name="phone" value="${employee.phone || ''}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Hire Date <span class="text-gray-500 text-xs">(Cannot be changed)</span></label>
                <input type="date" name="hire_date" value="${employee.hire_date}" readonly class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-100 cursor-not-allowed" style="background-color:#f3f4f6;">
            </div>
        </div>
    `;
    document.getElementById('editEmployeeModal').classList.remove('hidden');
}

// Close edit modal
function closeEditModal() {
    document.getElementById('editEmployeeModal').classList.add('hidden');
}

// Handle edit employee form submission
document.getElementById('editEmployeeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('edit_employee.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success notification
            showToast('Employee updated successfully!', 'success');
            
            closeEditModal();
            // Reload employee data in the view modal without closing it
            fetch(`view_employee.php?id=${currentEmployeeId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showEmployeeDetailsModal(data.employee);
                    }
                });
        } else {
            showToast('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error updating employee', 'error');
    });
});

// Removed duplicate create account form submission handler to avoid duplicate alerts.

// Show success notification with animation
const notificationElement = document.getElementById("notif");
if (notificationElement) {
    setTimeout(() => {
        notificationElement.style.transform = 'translateX(0)';
        notificationElement.style.opacity = '1';
    }, 100);
    
    setTimeout(() => {
        notificationElement.style.opacity = '0';
        notificationElement.style.transform = 'translateX(100px)';
        setTimeout(() => notificationElement.remove(), 300);
    }, 4000);
}

// Search and pagination functionality (10 per page)
const searchInput = document.getElementById('searchInput');
const tableBody = document.getElementById('employeeTable');
const prevBtn = document.getElementById('prevPage');
const nextBtn = document.getElementById('nextPage');
const pageInfo = document.getElementById('pageInfo');
const pageSize = 10;
let currentPage = 1;
let allRows = Array.from(tableBody.querySelectorAll('tr'));

function renderPage() {
    // Filter rows based on search
    const searchTerm = searchInput.value.toLowerCase();
    const filteredRows = allRows.filter(row => {
        const text = row.textContent.toLowerCase();
        return text.includes(searchTerm);
    });
    
    // Calculate pagination
    const total = filteredRows.length;
    const totalPages = Math.max(1, Math.ceil(total / pageSize));
    currentPage = Math.min(Math.max(1, currentPage), totalPages);
    const start = (currentPage - 1) * pageSize;
    const end = start + pageSize;
    const pageRows = filteredRows.slice(start, end);
    
    // Hide all rows first
    allRows.forEach(row => row.style.display = 'none');
    
    // Show only current page rows
    pageRows.forEach(row => row.style.display = '');
    
    // Update pagination controls
    if (pageInfo) pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
    if (prevBtn) prevBtn.disabled = (currentPage <= 1);
    if (nextBtn) nextBtn.disabled = (currentPage >= totalPages);
}

// Event listeners
if (searchInput) {
    searchInput.addEventListener('input', () => {
        currentPage = 1;
        renderPage();
    });
}
if (prevBtn) prevBtn.addEventListener('click', () => { currentPage--; renderPage(); });
if (nextBtn) nextBtn.addEventListener('click', () => { currentPage++; renderPage(); });

// Initial render
renderPage();

// Menu dropdown functionality
document.getElementById('menuBtn').addEventListener('click', function() {
    const dropdown = document.getElementById('dropdownMenu');
    dropdown.classList.toggle('hidden');
});

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('dropdownMenu');
    const menuButton = document.getElementById('menuBtn');
    
    if (!dropdown.contains(event.target) && !menuButton.contains(event.target)) {
        dropdown.classList.add('hidden');
    }
});

// =========================
// Add Employee Confirmation (mirrors Super Admin)
// =========================
function confirmAddEmployee() {
    const modal = document.getElementById('addEmployeeModal');
    if (!modal) return;

    const form = modal.querySelector('form');
    if (!form) return;

    // Clear previous error states and messages
    clearFieldErrors(form);

    // Gather values
    const idNumber = (form.querySelector('input[name="id_number"]')?.value || '').trim();
    const firstName = (form.querySelector('input[name="first_name"]')?.value || '').trim();
    const middleName = (form.querySelector('input[name="middle_name"]')?.value || '').trim();
    const lastName = (form.querySelector('input[name="last_name"]')?.value || '').trim();
    const position = (form.querySelector('input[name="position"]')?.value || '').trim();
    const department = (form.querySelector('select[name="department"]')?.value || '').trim();
    const hireDate = (form.querySelector('input[name="hire_date"]')?.value || '').trim();
    const email = (form.querySelector('input[name="email"]')?.value || '').trim();
    const phone = (form.querySelector('input[name="phone"]')?.value || '').trim();
    const address = (form.querySelector('textarea[name="address"]')?.value || '').trim();
    const createAccount = !!form.querySelector('#createAccount')?.checked;
    const username = (form.querySelector('input[name="username"]')?.value || '').trim();
    const password = (form.querySelector('input[name="password"]')?.value || '');
    const role = (form.querySelector('#employeeRole')?.value || '').trim();
    const rfid = (form.querySelector('#rfid_uid')?.value || '').trim();

    // Validate with inline messages
    let hasErrors = false;
    const need = (selector, msg) => { const el = form.querySelector(selector); if (!el || !el.value.trim()) { highlightFieldError(el, msg); hasErrors = true; return false; } return true; };
    const invalid = (selector, testFn, msg) => { const el = form.querySelector(selector); const val = el?.value?.trim() || ''; if (val && !testFn(val)) { highlightFieldError(el, msg); hasErrors = true; } };

    // Employee ID is auto-generated, no need to validate
    need('input[name="first_name"]', 'First name is required');
    need('input[name="last_name"]', 'Last name is required');
    need('input[name="position"]', 'Position is required');
    need('select[name="department"]', 'Department is required');
    need('input[name="hire_date"]', 'Hire date is required');
    if (need('input[name="email"]', 'Email is required')) {
        invalid('input[name="email"]', v => /^([^\s@]+)@([^\s@]+)\.[^\s@]+$/.test(v), 'Please enter a valid email address');
    }
    if (need('input[name="phone"]', 'Phone is required')) {
        invalid('input[name="phone"]', v => /^\d{11}$/.test(v), 'Phone must be exactly 11 digits');
    }
    need('textarea[name="address"]', 'Address is required');
    invalid('textarea[name="address"]', v => v.length >= 20, 'Address must be at least 20 characters long');
    invalid('textarea[name="address"]', v => v.length <= 500, 'Address must not exceed 500 characters');
    invalid('textarea[name="address"]', v => /[,\s]/.test(v), 'Complete address must include multiple components (street, barangay, city, etc.) separated by commas or spaces.');

    if (createAccount) {
        // Username and password are auto-generated, no need to validate
        if (!role) { highlightFieldError(form.querySelector('#employeeRole'), 'Role is required'); hasErrors = true; }
        if (role === 'teacher') {
            const r = form.querySelector('#rfid_uid');
            if (!r || !/^\d{10}$/.test((r.value || '').trim())) { highlightFieldError(r, 'RFID must be exactly 10 digits'); hasErrors = true; }
        }
    }
    if (hasErrors) return;

    const fullName = `${firstName}${middleName ? ' ' + middleName : ''} ${lastName}`;
    const formattedDate = hireDate ? new Date(hireDate + 'T00:00:00').toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A';

    showHRConfirmationModal({ idNumber, fullName, position, department, email, phone, address, hireDate: formattedDate, createAccount, username, password, role, rfid, form });
}

function showHRConfirmationModal(data) {
    const existing = document.getElementById('hr-confirm-modal');
    if (existing) existing.remove();

    const wrapper = document.createElement('div');
    wrapper.id = 'hr-confirm-modal';
    wrapper.className = 'fixed inset-0 bg-black bg-opacity-70 z-[6000] flex items-center justify-center p-4';
    wrapper.innerHTML = `
    <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden border-2 border-gray-200">
      <div class="bg-gradient-to-r from-[#0B2C62] to-[#153e86] text-white px-6 py-5 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <h3 class="text-xl font-bold">Confirm Employee Creation</h3>
        </div>
        <button onclick="closeHRConfirmationModal()" class="text-white hover:text-gray-200 p-2 rounded-lg hover:bg-white/10 transition-colors">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="p-6 max-h-[calc(90vh-160px)] overflow-y-auto bg-gray-50">
        <div class="mb-6">
          <div class="flex items-center gap-3 mb-4 p-3 bg-[#0B2C62]/5 rounded-lg">
            <div class="w-8 h-8 bg-[#0B2C62] rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </div>
            <h4 class="text-lg font-bold text-[#0B2C62]">Personal Information</h4>
          </div>
          <div class="bg-white rounded-lg p-5 shadow-sm border border-gray-200 space-y-3">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="p-3 bg-gray-50 rounded-lg"><span class="font-semibold text-gray-800 block">Employee ID:</span><span class="text-[#0B2C62] font-medium text-lg">${escapeHtml(data.idNumber)}</span></div>
              <div class="p-3 bg-gray-50 rounded-lg"><span class="font-semibold text-gray-800 block">Full Name:</span><span class="text-[#0B2C62] font-medium text-lg">${escapeHtml(data.fullName)}</span></div>
              <div class="p-3 bg-gray-50 rounded-lg"><span class="font-semibold text-gray-800 block">Position:</span><span class="text-gray-900 font-medium">${escapeHtml(data.position)}</span></div>
              <div class="p-3 bg-gray-50 rounded-lg"><span class="font-semibold text-gray-800 block">Department:</span><span class="text-gray-900 font-medium">${escapeHtml(data.department)}</span></div>
              <div class="p-3 bg-gray-50 rounded-lg"><span class="font-semibold text-gray-800 block">Email:</span><span class="text-gray-900 font-medium">${escapeHtml(data.email)}</span></div>
              <div class="p-3 bg-gray-50 rounded-lg"><span class="font-semibold text-gray-800 block">Phone:</span><span class="text-gray-900 font-medium">${escapeHtml(data.phone)}</span></div>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg"><span class="font-semibold text-gray-800 block">Address:</span><span class="text-gray-900 font-medium">${escapeHtml(data.address)}</span></div>
            <div class="p-3 bg-gray-50 rounded-lg"><span class="font-semibold text-gray-800 block">Hire Date:</span><span class="text-gray-900 font-medium">${escapeHtml(data.hireDate)}</span></div>
          </div>
        </div>
        <div class="mb-6">
          <div class="flex items-center gap-3 mb-4 p-3 bg-[#0B2C62]/5 rounded-lg">
            <div class="w-8 h-8 bg-[#0B2C62] rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </div>
            <h4 class="text-lg font-bold text-[#0B2C62]">System Account</h4>
          </div>
          <div class="bg-white rounded-lg p-5 shadow-sm border-2 border-gray-300 space-y-4">
            ${data.createAccount ? `
              <div class="flex items-center gap-3 mb-4"><svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg><span class="font-semibold text-gray-900">System account will be created</span></div>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div><span class="font-semibold text-gray-700 block">Username:</span><span class="text-gray-900 font-medium">${escapeHtml(data.username)}</span></div>
                <div><span class="font-semibold text-gray-700 block">Password:</span><span class="text-gray-900 font-medium">${'•'.repeat(data.password.length)} (${data.password.length} chars)</span></div>
                <div><span class="font-semibold text-gray-700 block">Role:</span><span class="text-gray-900 font-medium">${escapeHtml(data.role || 'N/A')}</span></div>
              </div>
              ${data.role === 'teacher' ? `<div class=\"grid grid-cols-1 md:grid-cols-3 gap-4\"><div><span class=\"font-semibold text-gray-700 block\">RFID:</span><span class=\"text-gray-900 font-medium\">${escapeHtml(data.rfid)}</span></div></div>` : ''}
              <p class="text-gray-700 text-sm pt-2 border-t border-gray-200">This employee will have login access to the selected role.</p>
            ` : `
              <div class="flex items-center gap-3 mb-2"><svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg><span class="font-semibold text-gray-900">Employee record only</span></div>
              <p class="text-gray-700 text-sm">No system account will be created. Employee will NOT have login access.</p>
            `}
          </div>
        </div>
      </div>
      <div class="bg-white border-t-2 border-gray-200 px-6 py-5 pb-8 flex justify-end gap-4">
        <button onclick="closeHRConfirmationModal()" class="px-8 py-3 border-2 border-gray-400 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors font-medium text-lg">Cancel</button>
        <button onclick="proceedWithCreation()" class="px-8 py-3 bg-gradient-to-r from-[#0B2C62] to-[#153e86] hover:from-[#153e86] hover:to-[#1e40af] text-white rounded-lg transition-all duration-200 flex items-center gap-3 font-bold text-lg shadow-lg hover:shadow-xl">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          <span>Confirm & Create Employee</span>
        </button>
      </div>
    </div>`;

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    document.body.appendChild(wrapper);
    window.__hrAddEmpForm = data.form; // store ref
}

function closeHRConfirmationModal() {
    const modal = document.getElementById('hr-confirm-modal');
    if (modal) modal.remove();
    window.__hrAddEmpForm = null;
}

function proceedWithCreation() {
    if (window.__hrAddEmpForm) {
        const f = window.__hrAddEmpForm;
        closeHRConfirmationModal();
        f.submit();
    }
}

</script>
<!-- Toast Notification -->
<div id="toast" class="fixed top-5 right-5 z-50 hidden">
  <div id="toastInner" class="px-4 py-3 rounded shadow-lg text-white"></div>
  <style>
    .toast-success { background: #16a34a; }
    .toast-error { background: #dc2626; }
  </style>
  <script>
    function showToast(message, type='success'){
      const t = document.getElementById('toast');
      const ti = document.getElementById('toastInner');
      ti.className = 'px-4 py-3 rounded shadow-lg text-white ' + (type==='success'?'toast-success':'toast-error');
      ti.textContent = message;
      t.classList.remove('hidden');
      clearTimeout(window.__toastTimer);
      window.__toastTimer = setTimeout(()=>{ t.classList.add('hidden'); }, 2000);
    }

    // Additional input validation
    // Function to setup input restrictions
    function setupInputRestrictions() {
      // Restrict name inputs to letters only and max 20 characters
      const nameInputs = document.querySelectorAll('.name-input');
      nameInputs.forEach(input => {
        // Remove old listener if exists
        input.removeEventListener('input', restrictToLetters);
        input.addEventListener('input', restrictToLetters);
      });

      // Restrict phone inputs to digits only and max 11 digits
      const phoneInputs = document.querySelectorAll('.phone-input');
      phoneInputs.forEach(input => {
        input.removeEventListener('input', restrictToDigits);
        input.addEventListener('input', restrictToDigits);
      });

      // Employee ID validation - numbers only, max 11 digits
      const empIdInput = document.querySelector('.employee-id-input');
      if (empIdInput) {
        empIdInput.removeEventListener('input', restrictToDigits11);
        empIdInput.addEventListener('input', restrictToDigits11);
      }
    }

    function restrictToLetters(e) {
      this.value = this.value.replace(/[^A-Za-z\s]/g, '').slice(0, 20);
    }

    function restrictToDigits(e) {
      this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);
    }

    function restrictToDigits11(e) {
      this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);
    }

    document.addEventListener('DOMContentLoaded', function() {
      setupInputRestrictions();
    });
    
    // ===== PREVENT BACK BUTTON AFTER LOGOUT =====
    window.addEventListener("pageshow", function(event) {
      if (event.persisted || (performance.navigation.type === 2)) window.location.reload();
    });
  </script>
</div>
</body>
</html>


