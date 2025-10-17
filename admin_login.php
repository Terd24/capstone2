<?php
session_start();
require_once 'StudentLogin/db_conn.php';

$error_msg = '';
$success_msg = '';

// Helper: log successful logins for reporting (Super Admin dashboard)
function log_login($conn, $userType, $idNumber, $username, $role) {
    // Create table if not exists (idempotent)
    $conn->query("CREATE TABLE IF NOT EXISTS login_activity (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_type VARCHAR(20) NOT NULL,
        id_number VARCHAR(50) NOT NULL,
        username VARCHAR(100) NOT NULL,
        role VARCHAR(50) NOT NULL,
        login_time DATETIME NOT NULL,
        logout_time DATETIME NULL,
        session_duration INT NULL,
        session_id VARCHAR(128) NULL,
        INDEX idx_login_date (login_time),
        INDEX idx_id_number (id_number)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    if ($stmt = $conn->prepare("INSERT INTO login_activity (user_type, id_number, username, role, login_time, session_id) VALUES (?,?,?,?,NOW(),?)")) {
        $sid = session_id();
        $stmt->bind_param('sssss', $userType, $idNumber, $username, $role, $sid);
        $stmt->execute();
        $stmt->close();
    }
}

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
                $_SESSION['id_number'] = 'SA-' . $superadmin['id']; // Create ID for tracking
                $_SESSION['first_name'] = $superadmin['first_name'] ?? 'Principal';
                $_SESSION['last_name'] = $superadmin['last_name'] ?? 'Owner';
                $_SESSION['role'] = 'superadmin';
                
                // Log SuperAdmin login
                log_login($conn, 'employee', 'SA-' . $superadmin['id'], $superadmin['username'], 'superadmin');
                
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
                    
                    // Log SuperAdmin login
                    log_login($conn, 'employee', $superadmin['employee_id'], $superadmin['username'], 'superadmin');
                    
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
                    $_SESSION['id_number'] = 'OWN-' . $owner['id']; // Create ID for tracking
                    $_SESSION['first_name'] = explode(' ', $owner['full_name'])[0] ?? 'Owner';
                    $_SESSION['last_name'] = explode(' ', $owner['full_name'])[1] ?? '';
                    $_SESSION['role'] = 'owner';
                    
                    // Log Owner login
                    log_login($conn, 'employee', 'OWN-' . $owner['id'], $owner['username'], 'owner');
                    
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
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center p-4" onload="clearFormOnLoad()">

<div class="w-full max-w-md">
    <!-- Header (Logo Outside) -->
    <div class="text-center mb-8">
        <img src="images/LogoCCI.png" alt="Cornerstone College Inc." class="w-20 h-20 mx-auto mb-4">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Cornerstone College Inc.</h1>
        <p class="text-gray-600 text-sm">Admin/Owner Portal</p>
        <p class="text-sm text-gray-600 mt-1">SuperAdmin & Owner Access</p>
    </div>

    <!-- Login Card -->
    <div class="bg-white rounded-2xl shadow-lg p-8">
        <div class="text-center mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-2">Welcome Back</h2>
            <p class="text-gray-600 text-sm">Sign in to access your account</p>
        </div>

        <!-- Login Form -->
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <input type="text" name="username" id="usernameInput" required autocomplete="off"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                       placeholder="Enter your username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <div class="relative">
                    <input type="password" name="password" id="passwordInput" required autocomplete="off"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg pr-12 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                           placeholder="Enter your password">
                    <button type="button" onclick="togglePassword()" 
                            class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors focus:outline-none">
                        <svg id="eyeIcon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Error Message -->
            <?php if (!empty($error_msg)): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                    <?= htmlspecialchars($error_msg) ?>
                </div>
            <?php endif; ?>

            <button type="submit" 
                    class="w-full bg-[#0B2C62] text-white py-3 rounded-xl font-semibold hover:opacity-90 transition-all transform hover:scale-[1.02]">
                Sign In
            </button>
        </form>
    </div>
</div>

<script>
    // Toggle password visibility
    function togglePassword() {
        const passwordInput = document.getElementById("passwordInput");
        const eyeIcon = document.getElementById("eyeIcon");
        
        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            // Add diagonal line through eye
            eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path><line x1="4" y1="4" x2="20" y2="20" stroke-linecap="round"></line>';
        } else {
            passwordInput.type = "password";
            // Eye without line
            eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>';
        }
    }

    // Clear form when page loads (prevents back button from showing cached data)
    function clearFormOnLoad() {
        document.getElementById("usernameInput").value = "";
        document.getElementById("passwordInput").value = "";
    }

    // Prevent form caching
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            clearFormOnLoad();
        }
    });
</script>

</body>
</html>
