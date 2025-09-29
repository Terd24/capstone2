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
        $employee_id = $_POST['employee_id'] ?? '';
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($employee_id) || empty($username) || empty($password)) {
            $error = 'All fields are required';
        } else {
            // Check if employee exists
            $stmt = $conn->prepare("SELECT id_number, first_name, last_name FROM employees WHERE id_number = ?");
            $stmt->bind_param("s", $employee_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $error = 'Employee not found';
            } else {
                // Check if username is taken
                $stmt = $conn->prepare("SELECT username FROM employee_accounts WHERE username = ?");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $error = 'Username already taken';
                } else {
                    // Create HR account
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO employee_accounts (employee_id, username, password, role) VALUES (?, ?, ?, 'hr')");
                    $stmt->bind_param("sss", $employee_id, $username, $hashed_password);
                    
                    if ($stmt->execute()) {
                        $success = 'HR account created successfully';
                    } else {
                        $error = 'Failed to create HR account: ' . $conn->error;
                    }
                }
            }
        }
    }
}

// Fetch all employees
$employees = $conn->query("SELECT e.*, ea.username, ea.role FROM employees e LEFT JOIN employee_accounts ea ON e.id_number = ea.employee_id ORDER BY e.first_name, e.last_name");

// Fetch HR accounts
$hr_accounts = $conn->query("SELECT e.id_number, e.first_name, e.last_name, e.position, ea.username, ea.created_at FROM employees e JOIN employee_accounts ea ON e.id_number = ea.employee_id WHERE ea.role = 'hr' ORDER BY e.first_name, e.last_name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage HR Accounts - Principal/Owner</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">

<header class="bg-gradient-to-r from-[#0B2C62] to-[#153e86] text-white shadow">
    <div class="container mx-auto px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <img src="../images/LogoCCI.png" class="h-11 w-11 rounded-full bg-white p-1 shadow-sm" alt="Logo">
            <div class="leading-tight">
                <div class="text-lg font-bold">Cornerstone College Inc.</div>
                <div class="text-[11px] text-blue-200">HR Account Management - IT Personnel (Requires School Owner Approval)</div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="SuperAdminDashboard.php" class="text-sm bg-white/10 hover:bg-white/20 px-3 py-2 rounded-lg transition">← Back to Dashboard</a>
            <a href="../StudentLogin/logout.php" class="inline-flex items-center gap-2 bg-white/10 hover:bg-white/20 px-3 py-2 rounded-lg text-sm transition">Logout</a>
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

    <!-- Create HR Account Section -->
    <div class="bg-white rounded-2xl shadow p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Create HR Account</h2>
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-4">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.314 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <span class="text-amber-800 font-semibold">School Owner Approval Required</span>
            </div>
            <p class="text-amber-700 text-sm mt-2">As IT Personnel, creating HR accounts requires School Owner approval. This action will be logged and may require additional authorization.</p>
        </div>
        
        <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="hidden" name="action" value="create_hr">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Employee ID</label>
                <input type="text" name="employee_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                <input type="text" name="username" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                <input type="password" name="password" required minlength="6" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="w-full bg-[#0B2C62] text-white px-4 py-2 rounded-lg hover:bg-blue-800 transition">Create HR Account</button>
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
                        <th class="px-4 py-2 text-left">Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($hr_accounts->num_rows > 0): ?>
                        <?php while ($hr = $hr_accounts->fetch_assoc()): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3"><?= htmlspecialchars($hr['id_number']) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($hr['first_name'] . ' ' . $hr['last_name']) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($hr['position']) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($hr['username']) ?></td>
                                <td class="px-4 py-3"><?= date('M j, Y', strtotime($hr['created_at'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500">No HR accounts found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- All Employees -->
    <div class="bg-white rounded-2xl shadow p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">All Employees</h2>
        
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left">Employee ID</th>
                        <th class="px-4 py-2 text-left">Name</th>
                        <th class="px-4 py-2 text-left">Position</th>
                        <th class="px-4 py-2 text-left">Department</th>
                        <th class="px-4 py-2 text-left">Account Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($employees->num_rows > 0): ?>
                        <?php while ($emp = $employees->fetch_assoc()): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3"><?= htmlspecialchars($emp['id_number']) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($emp['position']) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($emp['department']) ?></td>
                                <td class="px-4 py-3">
                                    <?php if ($emp['username']): ?>
                                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">
                                            <?= strtoupper($emp['role']) ?> Account
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs">No Account</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500">No employees found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

</body>
</html>
