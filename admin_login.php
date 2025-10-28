<?php
session_start();
require_once 'StudentLogin/db_conn.php';

$error_msg = '';
$success_msg = '';

// 🚫 If already logged in as employee, redirect to appropriate dashboard
if (isset($_SESSION['role'])) {
    $role = strtolower($_SESSION['role']);
    
    switch ($role) {
        case 'superadmin':
            header("Location: AdminF/SuperAdminDashboard.php");
            exit;
        case 'owner':
            header("Location: OwnerF/Dashboard.php");
            exit;
        case 'hr':
            header("Location: HRF/Dashboard.php");
            exit;
        case 'registrar':
            header("Location: RegistrarF/RegistrarDashboard.php");
            exit;
        case 'cashier':
            header("Location: CashierF/Dashboard.php");
            exit;
        case 'guidance':
            header("Location: GuidanceF/GuidanceDashboard.php");
            exit;
        case 'attendance':
            header("Location: AttendanceF/Dashboard.php");
            exit;
        case 'teacher':
            header("Location: EmployeePortal/Dashboard.php");
            exit;
        case 'department_head':
            header("Location: DepartmentHeadF/Dashboard.php");
            exit;
        case 'student':
        case 'parent':
            // Students and parents should use StudentLogin
            session_destroy();
            header("Location: StudentLogin/login.php");
            exit;
    }
}

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
                    // Set SuperAdmin session (employee_id is the actual ID number from employees table)
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
        
        // If SuperAdmin login failed, try HR login
        if (!$login_success) {
            $hr_stmt = $conn->prepare("SELECT ea.*, e.first_name, e.last_name FROM employee_accounts ea 
                                       JOIN employees e ON ea.employee_id = e.id_number 
                                       WHERE ea.username = ? AND ea.role = 'hr'");
            $hr_stmt->bind_param("s", $username);
            $hr_stmt->execute();
            $hr_result = $hr_stmt->get_result();
            
            if ($hr_result->num_rows === 1) {
                $hr = $hr_result->fetch_assoc();
                
                if (password_verify($password, $hr['password'])) {
                    // Set HR session (employee_id is the actual ID number from employees table)
                    $_SESSION['hr_id'] = $hr['id'];
                    $_SESSION['hr_name'] = $hr['first_name'] . ' ' . $hr['last_name'];
                    $_SESSION['username'] = $hr['username'];
                    $_SESSION['first_name'] = $hr['first_name'];
                    $_SESSION['last_name'] = $hr['last_name'];
                    $_SESSION['role'] = 'hr';
                    $_SESSION['id_number'] = $hr['employee_id'];
                    
                    // Log HR login
                    log_login($conn, 'employee', $hr['employee_id'], $hr['username'], 'hr');
                    
                    $login_success = true;
                    $redirect_url = "HRF/Dashboard.php";
                }
            }
        }
        
        // If SuperAdmin and HR login failed, try other employee roles (registrar, cashier, guidance, attendance, teacher)
        if (!$login_success) {
            $employee_stmt = $conn->prepare("SELECT ea.*, e.first_name, e.last_name FROM employee_accounts ea 
                                           JOIN employees e ON ea.employee_id = e.id_number 
                                           WHERE ea.username = ?");
            $employee_stmt->bind_param("s", $username);
            $employee_stmt->execute();
            $employee_result = $employee_stmt->get_result();
            
            if ($employee_result->num_rows === 1) {
                $employee = $employee_result->fetch_assoc();
                
                if (password_verify($password, $employee['password'])) {
                    $role = strtolower(trim((string)$employee['role']));
                    $full_name = $employee['first_name'] . ' ' . $employee['last_name'];

                    // Set common session variables (employee_id is the actual ID number from employees table)
                    $_SESSION['employee_id'] = $employee['id'];
                    $_SESSION['id_number'] = $employee['employee_id'];
                    $_SESSION['username'] = $employee['username'];
                    $_SESSION['first_name'] = $employee['first_name'];
                    $_SESSION['last_name'] = $employee['last_name'];
                    $_SESSION['role'] = $role;
                    
                    // Check if employee must change password (first-time login)
                    $must_change = $employee['must_change_password'] ?? 0;

                    // Log employee login
                    log_login($conn, 'employee', $employee['employee_id'], $employee['username'], $role);
                    
                    // If must change password, redirect to employee password change page
                    if ($must_change == 1) {
                        $_SESSION['must_change_password'] = true;
                        $login_success = true;
                        $redirect_url = "EmployeePortal/change_password.php";
                    } else {
                        // Role routing
                        switch($role) {
                            case 'registrar':
                                $_SESSION['registrar_id'] = $employee['id'];
                                $_SESSION['registrar_name'] = $full_name;
                                $redirect_url = "RegistrarF/RegistrarDashboard.php";
                                break;
                            case 'cashier':
                                $_SESSION['cashier_id'] = $employee['id'];
                                $_SESSION['cashier_name'] = $full_name;
                                $redirect_url = "CashierF/Dashboard.php";
                                break;
                            case 'guidance':
                                $_SESSION['guidance_id'] = $employee['id'];
                                $_SESSION['guidance_name'] = $full_name;
                                $redirect_url = "GuidanceF/GuidanceDashboard.php";
                                break;
                            case 'attendance':
                                $_SESSION['attendance_id'] = $employee['id'];
                                $_SESSION['attendance_name'] = $full_name;
                                $redirect_url = "AttendanceF/Dashboard.php";
                                break;
                            case 'teacher':
                                $redirect_url = "EmployeePortal/Dashboard.php";
                                break;
                            case 'department_head':
                                $_SESSION['dept_head_id'] = $employee['id'];
                                $_SESSION['dept_head_name'] = $full_name;
                                $redirect_url = "DepartmentHeadF/Dashboard.php";
                                break;
                            default:
                                // Fallback: treat any unexpected role as a generic teacher portal access
                                error_log('Unknown employee role: ' . $employee['role'] . ' for username ' . $employee['username']);
                                $_SESSION['role'] = 'teacher';
                                $redirect_url = "EmployeePortal/Dashboard.php";
                        }
                        $login_success = true;
                    }
                }
            }
        }
        
        // If all employee logins failed, try Owner login
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
    <link rel="icon" type="image/png" href="images/LogoCCI.png">
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#0B2C62">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="OneCCI">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(console.error);
            });
        }
    </script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-md">
    <!-- Back to Home Button -->
    <div class="mb-6">
        <a href="index.php" class="inline-flex items-center px-4 py-2 text-gray-600 hover:text-gray-800 hover:bg-white/50 rounded-lg transition-all group backdrop-blur-sm">
            <svg class="w-4 h-4 mr-2 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            <span class="text-sm font-medium">Back to Home</span>
        </a>
    </div>

    <!-- Header (Logo Outside) -->
    <div class="text-center mb-8">
        <img src="images/LogoCCI.png" alt="Cornerstone College Inc." class="w-20 h-20 mx-auto mb-4">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Cornerstone College Inc.</h1>
        <p class="text-gray-600 text-sm">Admin & Staff Portal</p>
        <p class="text-sm text-gray-600 mt-1">For School Administrators & Employees</p>
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

    // Clear only password when page loads (preserve username on error)
    function clearPasswordOnLoad() {
        document.getElementById("passwordInput").value = "";
    }

    // Clear password on page load
    window.addEventListener('load', clearPasswordOnLoad);

    // Prevent form caching - only clear password
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            clearPasswordOnLoad();
        }
    });
</script>

</body>
</html>