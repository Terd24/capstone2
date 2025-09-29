<?php
session_start();
include("../StudentLogin/db_conn.php");

// Require HR login or Super Admin (Principal/Owner) access
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'hr' && $_SESSION['role'] !== 'superadmin')) {
    header("Location: ../StudentLogin/login.php");
    exit;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include("add_employee.php");
}

// Handle success message
$success_msg = $_SESSION['success_msg'] ?? '';
if ($success_msg) {
    unset($_SESSION['success_msg']);
}

// Handle error message
$error_msg = $_SESSION['error_msg'] ?? '';
if ($error_msg) {
    unset($_SESSION['error_msg']);
}

// Fetch employees
$result = $conn->query("SELECT id_number, CONCAT(first_name, ' ', last_name) as full_name, position, department, 
                       (SELECT username FROM employee_accounts WHERE employee_accounts.employee_id = employees.id_number) as username 
                       FROM employees ORDER BY last_name ASC");

// Get total employee count
$count_result = $conn->query("SELECT COUNT(*) as total_employees FROM employees");
$total_employees = $count_result->fetch_assoc()['total_employees'];

$columns = ['ID Number', 'Full Name', 'Position', 'Department', 'Account Status', 'Actions'];
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
            <label class="font-medium text-[#0B2C62] text-sm">Show entries:</label>
            <input type="number" id="showEntries" min="1" value="10" class="w-20 border border-[#0B2C62]/30 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]"/>
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
                Manage Employee Schedule
            </button>
            <button onclick="window.location.href='../HRF/EmployeeAttendance.php'" class="px-4 py-2 bg-[#0B2C62] text-white rounded-lg shadow hover:bg-blue-900 transition font-medium whitespace-nowrap">
                Employee Attendance
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
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" onclick="event.stopPropagation()">
                                <button onclick="viewEmployee('<?= $row['id_number'] ?>')" class="text-blue-600 hover:text-blue-900 mr-2 p-2 rounded" title="View Employee">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </button>
                                <?php if (!$row['username']): ?>
                                <button onclick="createAccountForEmployee('<?= $row['id_number'] ?>')" class="text-green-600 hover:text-green-900 p-2 rounded" title="Create Account">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                </button>
                                <?php endif; ?>
                            </td>
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

        <!-- Form -->
        <form method="POST" autocomplete="off" class="px-6 py-6 grid grid-cols-1 md:grid-cols-3 gap-6 overflow-y-auto max-h-[80vh] no-scrollbar">
            
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
                        <!-- Row 1: ID Number, First Name, Middle Name -->
                        <div>
                            <label class="block text-sm font-semibold mb-1">Employee ID *</label>
                            <input type="text" name="id_number" autocomplete="off" required maxlength="11" pattern="[0-9]{1,11}" title="Numbers only, maximum 11 digits" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62] employee-id-input">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">First Name *</label>
                            <input type="text" name="first_name" autocomplete="off" pattern="[A-Za-z\s]+" maxlength="20" title="Letters only, maximum 20 characters" required class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62] name-input">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Middle Name <span class="text-gray-500 text-xs">(Optional)</span></label>
                            <input type="text" name="middle_name" autocomplete="off" pattern="[A-Za-z\s]*" maxlength="20" title="Letters only, maximum 20 characters" placeholder="Optional" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62] name-input">
                        </div>
                        
                        <!-- Row 2: Last Name, Position, Department -->
                        <div>
                            <label class="block text-sm font-semibold mb-1">Last Name *</label>
                            <input type="text" name="last_name" autocomplete="off" pattern="[A-Za-z\s]+" maxlength="20" title="Letters only, maximum 20 characters" required class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62] name-input">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-1">Position *</label>
                            <input type="text" name="position" autocomplete="off" pattern="[A-Za-z\s]+" maxlength="20" title="Letters only, maximum 20 characters" required class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62] name-input">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Department *</label>
                            <select name="department" required class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
                                <option value="">-- Select Department --</option>
                                <option value="Academic Affairs">Academic Affairs</option>
                                <option value="Student Affairs">Student Affairs</option>
                                <option value="Finance">Finance</option>
                                <option value="Human Resources">Human Resources</option>
                                <option value="IT Department">IT Department</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Security">Security</option>
                            </select>
                        </div>
                        
                        <!-- Row 3: Hire Date, Email, Phone -->
                        <div>
                            <label class="block text-sm font-semibold mb-1">Hire Date *</label>
                            <input type="date" name="hire_date" required class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-1">Email *</label>
                            <input type="email" name="email" autocomplete="off" required class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Phone *</label>
                            <input type="text" name="phone" required class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62] phone-input" inputmode="numeric" pattern="[0-9]{11}" minlength="11" maxlength="11" title="Please enter exactly 11 digits (e.g., 09123456789)" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)">
                        </div>
                        
                        <!-- Row 4: RFID -->
                        <div>
                            <label class="block text-sm font-semibold mb-1">RFID *</label>
                            <input type="text" id="rfid_uid" name="rfid_uid" autocomplete="off" required class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62] rfid-input" inputmode="numeric" pattern="[0-9]{10}" minlength="10" maxlength="10" title="Please enter exactly 10 digits (numbers only)" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)">
                        </div>

                        <!-- Complete Address -->
                        <div class="col-span-3">
                            <label class="block text-sm font-semibold mb-1">Complete Address *</label>
                            <textarea name="address" rows="3" autocomplete="off" required class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]"></textarea>
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
                    
                    <div id="accountFields" class="hidden grid grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-semibold mb-1">Username</label>
                            <input type="text" name="username" autocomplete="off" pattern="^[A-Za-z0-9_]+$" title="Letters, numbers, underscores only" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Password</label>
                            <input type="password" name="password" autocomplete="new-password" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Role</label>
                            <select id="employeeRole" name="role" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
                                <option value="">-- Select Role --</option>
                                <option value="registrar">Registrar</option>
                                <option value="cashier">Cashier</option>
                                <option value="guidance">Guidance</option>
                                <option value="attendance">Attendance</option>
                                <option value="employee">Employee</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="col-span-3 flex justify-end gap-4 pt-6 border-t border-gray-200">
                <button type="button" onclick="closeModal()" class="px-5 py-2 border border-blue-600 text-blue-900 rounded-xl hover:bg-[#0B2C62] hover:text-white transition">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-green-600 text-white rounded-xl shadow hover:bg-green-700 transition">
                    Add Employee
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Success Notification -->
<?php if (!empty($success_msg)): ?>
<div id="notif" class="fixed top-4 right-4 bg-green-400 text-white px-4 py-2 rounded shadow-lg z-50 transform translate-x-full opacity-0 transition-all duration-300">
    <?= htmlspecialchars($success_msg) ?>
</div>
<?php endif; ?>

<!-- Error Notification -->
<?php if (!empty($error_msg)): ?>
<div id="error-notif" class="fixed top-4 right-4 bg-red-500 text-white px-4 py-2 rounded shadow-lg z-50 transform translate-x-full opacity-0 transition-all duration-300">
    <?= htmlspecialchars($error_msg) ?>
</div>
<?php endif; ?>

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
// Modal functions
function openModal() {
    document.getElementById('addEmployeeModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('addEmployeeModal').classList.add('hidden');
}

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

function updateRFIDRequirement() {
    // After moving RFID into Personal Information, the old rfidField may not exist
    if (!rfidField || !rfidInput) return;
    // Always hide the old field if present and never require
    rfidField.classList.add('hidden');
    rfidInput.required = false;
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
                alert('Error loading employee details: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading employee details');
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
                            <label class="block text-sm font-semibold mb-1">ID Number</label>
                            <input type="text" value="${employee.id_number}" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 employee-field">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">First Name</label>
                            <input type="text" id="first_name_${employee.id_number}" value="${employee.first_name}" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 employee-field">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Last Name</label>
                            <input type="text" id="last_name_${employee.id_number}" value="${employee.last_name}" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 employee-field">
                        </div>
                        
                        <!-- Row: Position, Department, Email -->
                        <div>
                            <label class="block text-sm font-semibold mb-1">Position</label>
                            <input type="text" id="position_${employee.id_number}" value="${employee.position}" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 employee-field">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Department</label>
                            <input type="text" id="department_${employee.id_number}" value="${employee.department}" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 employee-field">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Email</label>
                            <input type="email" id="email_${employee.id_number}" value="${employee.email || ''}" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 employee-field">
                        </div>
                        
                        <!-- Row: Phone, Hire Date -->
                        <div>
                            <label class="block text-sm font-semibold mb-1">Phone</label>
                            <input type="text" id="phone_${employee.id_number}" value="${employee.phone || ''}" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 employee-field">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Hire Date</label>
                            <input type="date" id="hire_date_${employee.id_number}" value="${employee.hire_date}" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 employee-field">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">RFID</label>
                            <input type="text" id="rfid_uid" name="rfid_uid" autocomplete="off" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62] digits-only" inputmode="numeric" pattern="[0-9]{10}" minlength="10" maxlength="10" data-maxlen="10" title="Please enter exactly 10 digits">
                        </div>
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
                            <label class="block text-sm font-semibold mb-1">Username</label>
                            <input type="text" id="username_${employee.id_number}" value="${employee.username}" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 employee-field">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Role</label>
                            <select id="role_${employee.id_number}" disabled class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 employee-field">
                                <option value="registrar" ${employee.account_role === 'registrar' ? 'selected' : ''}>Registrar</option>
                                <option value="cashier" ${employee.account_role === 'cashier' ? 'selected' : ''}>Cashier</option>
                                <option value="guidance" ${employee.account_role === 'guidance' ? 'selected' : ''}>Guidance</option>
                                <option value="attendance" ${employee.account_role === 'attendance' ? 'selected' : ''}>Attendance</option>
                                <option value="employee" ${employee.account_role === 'employee' ? 'selected' : ''}>Employee</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Password</label>
                            <input type="password" id="password_${employee.id_number}" placeholder="Enter new password" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 employee-field">
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
    const fields = document.querySelectorAll('.employee-field');
    
    isEditMode = !isEditMode;
    
    if (isEditMode) {
        editBtn.textContent = 'Cancel';
        editBtn.className = 'px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition';
        
        // Show save changes button
        const saveBtn = document.querySelector('[id^="saveChangesBtn_"]');
        if (saveBtn) saveBtn.classList.remove('hidden');
        
        // Enable fields
        fields.forEach(field => {
            if (field.type === 'text' || field.type === 'email' || field.type === 'date' || field.tagName === 'SELECT') {
                field.readOnly = false;
                field.disabled = false;
                field.classList.remove('bg-gray-50');
                field.classList.add('bg-white');
            }
        });
    } else {
        // Cancel editing - just reset without saving
        editBtn.textContent = 'Edit';
        editBtn.className = 'px-4 py-2 bg-[#2F8D46] text-white rounded-lg hover:bg-[#256f37] transition';
        
        // Hide save changes button
        const saveBtn = document.querySelector('[id^="saveChangesBtn_"]');
        if (saveBtn) saveBtn.classList.add('hidden');
        
        // Disable fields
        fields.forEach(field => {
            if (field.type === 'text' || field.type === 'email' || field.type === 'date' || field.tagName === 'SELECT') {
                field.readOnly = true;
                field.disabled = true;
                field.classList.add('bg-gray-50');
                field.classList.remove('bg-white');
            }
        });
    }
}

function saveEmployeeChanges() {
    // Get current employee ID from the modal
    const employeeId = document.querySelector('[id^="username_"]').id.split('_')[1];
    const username = document.getElementById(`username_${employeeId}`).value;
    const password = document.getElementById(`password_${employeeId}`).value;
    const role = document.getElementById(`role_${employeeId}`).value;
    
    // Prepare form data
    const formData = new FormData();
    formData.append('action', 'update_account');
    formData.append('employee_id', employeeId);
    formData.append('username', username);
    formData.append('role', role);
    
    if (password.trim()) {
        formData.append('new_password', password);
    }
    
    fetch('manage_account.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Account updated successfully');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating account');
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
                alert('Error loading account details');
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
                    alert('Password reset successfully');
                    closeViewModal();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        } else {
            alert('Password must be at least 6 characters long');
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
                <option value="employee" ${employee.account_role === 'employee' ? 'selected' : ''}>Employee</option>
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
            <select name="role" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500" tabindex="0">
                <option value="">Select Role</option>
                <option value="registrar">Registrar</option>
                <option value="cashier">Cashier</option>
                <option value="guidance">Guidance</option>
                <option value="attendance">Attendance</option>
                <option value="employee">Employee</option>
            </select>
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
            alert('Account updated successfully');
            closeEditAccountModal();
            closeViewModal();
            location.reload();
        } else {
            alert('Error: ' + data.message);
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
            alert('Account created successfully');
            closeCreateAccountModal();
            closeViewModal();
            location.reload();
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
                return; // keep modal open, no alert
            }
            alert('Error: ' + data.message);
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
                alert('Error loading employee details: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading employee details');
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
                <input type="text" name="id_number" value="${employee.id_number}" readonly class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-100">
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
                <label class="block text-sm font-medium text-gray-700 mb-2">Hire Date</label>
                <input type="date" name="hire_date" value="${employee.hire_date}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
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
            alert('Employee updated successfully!');
            closeEditModal();
            location.reload(); // Refresh to show updated data
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating employee');
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

// Search and pagination functionality
const searchInput = document.getElementById('searchInput');
const showEntriesInput = document.getElementById('showEntries');
let tableRows = Array.from(document.querySelectorAll('#employeeTable tr'));

function updateEntries() {
    const value = parseInt(showEntriesInput.value) || tableRows.length;
    let shown = 0;
    tableRows.forEach(row => row.style.display = '');
    const query = searchInput.value.toLowerCase().trim();
    tableRows.forEach(row => { if (!row.textContent.toLowerCase().includes(query)) row.style.display='none'; });
    shown = 0;
    tableRows.forEach(row => {
        if(row.style.display !== 'none'){ if(shown<value) row.style.display=''; else row.style.display='none'; shown++; }
    });
}
showEntriesInput.addEventListener('input', updateEntries);
searchInput.addEventListener('input', updateEntries);
updateEntries();

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
    document.addEventListener('DOMContentLoaded', function() {
      // Restrict name inputs to letters only and max 20 characters
      const nameInputs = document.querySelectorAll('.name-input');
      nameInputs.forEach(input => {
        input.addEventListener('input', function() {
          this.value = this.value.replace(/[^A-Za-z\s]/g, '').slice(0, 20);
        });
      });

      // Employee ID validation - numbers only, max 11 digits
      const empIdInput = document.querySelector('.employee-id-input');
      if (empIdInput) {
        empIdInput.addEventListener('input', function() {
          this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);
        });
      }
    });
  </script>
</div>
</body>
</html>


