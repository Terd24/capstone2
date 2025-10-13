<?php
session_start();
require_once 'StudentLogin/db_conn.php';

$error_msg = '';
$success_msg = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error_msg = "Please enter both username and password.";
    } else {
        $login_success = false;
        
        // Try SuperAdmin login first - check super_admins table
        $conn->query("CREATE TABLE IF NOT EXISTS super_admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            first_name VARCHAR(100) NULL,
            last_name VARCHAR(100) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $superadmin_stmt = $conn->prepare("SELECT * FROM super_admins WHERE username = ?");
        $superadmin_stmt->bind_param("s", $username);
        $superadmin_stmt->execute();
        $superadmin_result = $superadmin_stmt->get_result();
        
        if ($superadmin_result->num_rows === 1) {
            $superadmin = $superadmin_result->fetch_assoc();
            
            if (password_verify($password, $superadmin['password'])) {
                $full_name = trim(($superadmin['first_name'] ?? '') . ' ' . ($superadmin['last_name'] ?? ''));
                // Set SuperAdmin session
                $_SESSION['superadmin_id'] = $superadmin['id'];
                $_SESSION['superadmin_name'] = $full_name ?: 'Principal/Owner';
                $_SESSION['username'] = $superadmin['username'];
                $_SESSION['first_name'] = $superadmin['first_name'] ?? 'Principal';
                $_SESSION['last_name'] = $superadmin['last_name'] ?? 'Owner';
                $_SESSION['role'] = 'superadmin';
                
                $login_success = true;
                $redirect_url = "AdminF/SuperAdminDashboard.php";
            }
        }
        
        // If super_admins table login failed, try employee_accounts table with superadmin role
        if (!$login_success) {
            $superadmin_stmt = $conn->prepare("SELECT ea.*, e.first_name, e.last_name FROM employee_accounts ea 
                                             JOIN employees e ON ea.employee_id = e.id_number 
                                             WHERE ea.username = ? AND ea.role = 'superadmin'");
            $superadmin_stmt->bind_param("s", $username);
            $superadmin_stmt->execute();
            $superadmin_result = $superadmin_stmt->get_result();
            
            if ($superadmin_result->num_rows === 1) {
                $superadmin = $superadmin_result->fetch_assoc();
                
                if (password_verify($password, $superadmin['password'])) {
                    // Set SuperAdmin session
                    $_SESSION['superadmin_id'] = $superadmin['id'];
                    $_SESSION['superadmin_name'] = $superadmin['first_name'] . ' ' . $superadmin['last_name'];
                    $_SESSION['username'] = $superadmin['username'];
                    $_SESSION['first_name'] = $superadmin['first_name'];
                    $_SESSION['last_name'] = $superadmin['last_name'];
                    $_SESSION['role'] = 'superadmin';
                    $_SESSION['id_number'] = $superadmin['employee_id'];
                    
                    $login_success = true;
                    $redirect_url = "AdminF/SuperAdminDashboard.php";
                }
            }
        }
        
        // If SuperAdmin login failed, try Owner login
        if (!$login_success) {
            // Check if owner_accounts table exists, if not create it
            $conn->query("CREATE TABLE IF NOT EXISTS owner_accounts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                last_login TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            
            // Insert default owner if not exists (password: 'password')
            $default_password = password_hash('password', PASSWORD_DEFAULT);
            $conn->query("INSERT IGNORE INTO owner_accounts (username, password, full_name, email) 
                         VALUES ('owner', '$default_password', 'School Owner', 'owner@cornerstonecollegeinc.com')");
            
            $owner_stmt = $conn->prepare("SELECT * FROM owner_accounts WHERE username = ?");
            $owner_stmt->bind_param("s", $username);
            $owner_stmt->execute();
            $owner_result = $owner_stmt->get_result();
            
            if ($owner_result->num_rows === 1) {
                $owner = $owner_result->fetch_assoc();
                
                if (password_verify($password, $owner['password'])) {
                    // Set Owner session
                    $_SESSION['owner_id'] = $owner['id'];
                    $_SESSION['owner_name'] = $owner['full_name'];
                    $_SESSION['username'] = $owner['username'];
                    $_SESSION['first_name'] = explode(' ', $owner['full_name'])[0] ?? 'Owner';
                    $_SESSION['last_name'] = explode(' ', $owner['full_name'])[1] ?? '';
                    $_SESSION['role'] = 'owner';
                    
                    $login_success = true;
                    $redirect_url = "OwnerF/Dashboard.php"; // Go to Owner dashboard
                }
            }
        }
        
        if ($login_success) {
            // Update last login time for owner if logged in as owner
            if (isset($_SESSION['owner_id']) && isset($owner)) {
                $conn->query("UPDATE owner_accounts SET last_login = NOW() WHERE id = " . $_SESSION['owner_id']);
            }
            
            // Disable maintenance mode when admin/owner logs in
            $conn->query("DELETE FROM system_config WHERE config_key = 'maintenance_mode'");
            $conn->query("UPDATE system_config SET config_value = '0' WHERE config_key = 'maintenance_mode'");
            
            header("Location: $redirect_url");
            exit;
        } else {
            $error_msg = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin/Owner Login - Cornerstone College Inc.</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center">

<div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md mx-4">
    <!-- Logo and Header -->
    <div class="text-center mb-8">
        <img src="images/LogoCCI.png" alt="Cornerstone College Inc." class="h-20 w-20 mx-auto mb-4 rounded-full">
        <h1 class="text-2xl font-bold text-gray-800">Cornerstone College Inc.</h1>
        <p class="text-gray-600 mt-2">🔧 Admin/Owner Portal</p>
        <p class="text-sm text-blue-600 mt-1">SuperAdmin & Owner Access</p>
    </div>

    <!-- Error Message -->
    <?php if (!empty($error_msg)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
            <?= htmlspecialchars($error_msg) ?>
        </div>
    <?php endif; ?>

    <!-- Login Form -->
    <form method="POST" class="space-y-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
            <input type="text" name="username" required 
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                   placeholder="Enter your username"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
            <input type="password" name="password" required 
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                   placeholder="Enter your password">
        </div>

        <button type="submit" 
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200">
            Admin/Owner Sign In
        </button>
    </form>

    <!-- Footer -->
    <div class="mt-8 text-center">
        <a href="StudentLogin/login.php" class="block text-blue-600 hover:text-blue-800 text-sm">
            ← Back to Main Login
        </a>
    </div>
</div>


</body>
</html>
