    <?php
session_start();
include("../StudentLogin/db_conn.php");

// Require HR login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'hr') {
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

// Fetch employees
$result = $conn->query("SELECT id_number, CONCAT(first_name, ' ', last_name) as full_name, position, department, 
                       (SELECT username FROM employee_accounts WHERE employee_accounts.employee_id = employees.id_number) as username 
                       FROM employees ORDER BY last_name ASC");
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

    <!-- Top Controls -->
    <div class="flex flex-col md:flex-row gap-4 md:items-center justify-between mb-8 bg-white p-6 rounded-2xl shadow-md border border-[#0B2C62]/20">
        <div class="flex items-center gap-3">
            <label class="font-medium text-[#0B2C62]">Employee Management</label>
        </div>

        <div class="flex items-center gap-3">
            <label class="font-medium text-[#0B2C62]">Show entries:</label>
            <input type="number" id="showEntries" min="1" value="10" class="w-20 border border-[#0B2C62]/40 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]"/>
        </div>

        <div class="flex items-center gap-3">
            <input type="text" id="searchInput" placeholder="Search by name or ID..." class="w-64 border border-[#0B2C62]/40 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]"/>
            <button onclick="openModal()" class="px-4 py-2 bg-[#2F8D46] text-white rounded-lg shadow hover:bg-[#256f37] transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add Employee
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
                        <tr class="hover:bg-blue-50 transition cursor-pointer" onclick="viewEmployee('<?= htmlspecialchars($row['id_number']) ?>')">
                            <td class="px-4 py-3"><?= htmlspecialchars($row['id_number']) ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($row['full_name']) ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($row['position']) ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($row['department']) ?></td>
                            <td class="px-4 py-3">
                                <?php if ($row['username']): ?>
                                    <span class="px-3 py-1 bg-green-500 text-white rounded-full text-xs font-medium">Has Account</span>
                                <?php else: ?>
                                    <span class="px-3 py-1 bg-gray-500 text-white rounded-full text-xs font-medium">No Account</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex space-x-1">
                                    <button onclick="viewEmployee('<?= htmlspecialchars($row['id_number']) ?>')" class="p-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                    <button onclick="editEmployee('<?= htmlspecialchars($row['id_number']) ?>')" class="p-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <?php if (!$row['username']): ?>
                                    <button onclick="createAccount('<?= htmlspecialchars($row['id_number']) ?>')" class="p-2 bg-green-500 text-white rounded hover:bg-green-600 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                    </button>
                                    <?php endif; ?>
                                </div>
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

<!-- View Employee Details Modal -->
<div id="viewEmployeeModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="bg-[#0B2C62] text-white p-6 rounded-t-2xl">
            <div class="flex justify-between items-center">
                <h3 class="text-xl font-bold">Employee Details</h3>
                <button onclick="closeViewModal()" class="text-white hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        <div class="p-6" id="employeeDetailsContent">
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

<!-- Create Account Modal -->
<div id="createAccountModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full mx-4">
        <div class="bg-[#0B2C62] text-white p-6 rounded-t-2xl">
            <div class="flex justify-between items-center">
                <h3 class="text-xl font-bold">Create System Account</h3>
                <button onclick="closeCreateAccountModal()" class="text-white hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        <form id="createAccountForm" class="p-6">
            <input type="hidden" id="accountEmployeeId" name="employee_id">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                <input type="text" name="username" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                <input type="password" name="password" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                <select name="role" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
                    <option value="">Select Role</option>
                    <option value="registrar">Registrar</option>
                    <option value="cashier">Cashier</option>
                    <option value="guidance">Guidance</option>
                    <option value="hr">HR</option>
                </select>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeCreateAccountModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-[#0B2C62] text-white rounded-lg hover:bg-[#0B2C62]/90 transition">Create Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Employee Modal -->
<div id="addEmployeeModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="bg-[#0B2C62] text-white p-6 rounded-t-2xl">
            <div class="flex justify-between items-center">
                <h3 class="text-xl font-bold">Add New Employee</h3>
                <button onclick="closeModal()" class="text-white hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        <form method="POST" class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ID Number</label>
                    <input type="text" name="id_number" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                    <input type="text" name="first_name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                    <input type="text" name="last_name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Position</label>
                    <input type="text" name="position" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                    <select name="department" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
                        <option value="">Select Department</option>
                        <option value="Academic Affairs">Academic Affairs</option>
                        <option value="Student Affairs">Student Affairs</option>
                        <option value="Finance">Finance</option>
                        <option value="Human Resources">Human Resources</option>
                        <option value="IT Department">IT Department</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Security">Security</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" name="email" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                    <input type="text" name="phone" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                    <textarea name="address" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]"></textarea>
                </div>
            </div>
            
            <hr class="my-6">
            <h4 class="text-lg font-semibold text-gray-800 mb-4">Optional System Account</h4>
            <div class="mb-4">
                <label class="flex items-center">
                    <input type="checkbox" id="createAccount" name="create_account" class="rounded border-gray-300 text-[#0B2C62] focus:ring-[#0B2C62]">
                    <span class="ml-2 text-sm text-gray-700">Create system account for this employee</span>
                </label>
            </div>
            
            <div id="accountFields" class="hidden grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                    <input type="text" name="username" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <input type="password" name="password" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                    <select name="role" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
                        <option value="">Select Role</option>
                        <option value="registrar">Registrar</option>
                        <option value="cashier">Cashier</option>
                        <option value="guidance">Guidance</option>
                        <option value="hr">HR</option>
                    </select>
                </div>
            </div>
            
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-[#0B2C62] text-white rounded-lg hover:bg-[#0B2C62]/90 transition">Add Employee</button>
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

<script>
// Modal functions
function openModal() {
    document.getElementById('addEmployeeModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('addEmployeeModal').classList.add('hidden');
}

// Show/hide account fields
document.getElementById('createAccount').addEventListener('change', function() {
    const accountFields = document.getElementById('accountFields');
    if (this.checked) {
        accountFields.classList.remove('hidden');
    } else {
        accountFields.classList.add('hidden');
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
function showEmployeeDetailsModal(employee) {
    const content = document.getElementById('employeeDetailsContent');
    content.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ID Number</label>
                <p class="text-gray-900 bg-gray-50 p-2 rounded">${employee.id_number}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                <p class="text-gray-900 bg-gray-50 p-2 rounded">${employee.full_name}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Position</label>
                <p class="text-gray-900 bg-gray-50 p-2 rounded">${employee.position}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                <p class="text-gray-900 bg-gray-50 p-2 rounded">${employee.department}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <p class="text-gray-900 bg-gray-50 p-2 rounded">${employee.email || 'Not provided'}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                <p class="text-gray-900 bg-gray-50 p-2 rounded">${employee.phone || 'Not provided'}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Hire Date</label>
                <p class="text-gray-900 bg-gray-50 p-2 rounded">${employee.hire_date}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Account Status</label>
                <p class="text-gray-900 bg-gray-50 p-2 rounded">
                    ${employee.username ? 
                        `<span class="px-2 py-1 bg-green-500 text-white rounded text-sm">Has Account (${employee.account_role})</span>` : 
                        '<span class="px-2 py-1 bg-gray-500 text-white rounded text-sm">No Account</span>'
                    }
                </p>
            </div>
        </div>
    `;
    document.getElementById('viewEmployeeModal').classList.remove('hidden');
}

// Close view modal
function closeViewModal() {
    document.getElementById('viewEmployeeModal').classList.add('hidden');
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

// Create account function
function createAccount(employeeId) {
    document.getElementById('accountEmployeeId').value = employeeId;
    document.getElementById('createAccountModal').classList.remove('hidden');
}

// Close create account modal
function closeCreateAccountModal() {
    document.getElementById('createAccountModal').classList.add('hidden');
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

// Handle create account form submission
document.getElementById('createAccountForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('create_account.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Account created successfully!');
            closeCreateAccountModal();
            location.reload(); // Refresh to show updated data
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error creating account');
    });
});

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
</body>
</html>
