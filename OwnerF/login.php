<?php
session_start();
include("../StudentLogin/db_conn.php");

// Redirect if already logged in
if (isset($_SESSION['owner_id']) && $_SESSION['role'] === 'owner') {
    header("Location: Dashboard.php");
    exit;
}

$error_msg = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error_msg = "Please enter both username and password.";
    } else {
        // Check owner credentials first
        $stmt = $conn->prepare("SELECT id, username, password, full_name, email FROM owner_accounts WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $login_success = false;
        $user_data = null;
        
        if ($result->num_rows === 1) {
            $owner = $result->fetch_assoc();
            
            if (password_verify($password, $owner['password'])) {
                $login_success = true;
                $user_data = $owner;
            }
        }
        
        // If owner login failed, try superadmin credentials as backup
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
                    $login_success = true;
                    // Convert superadmin data to owner format
                    $user_data = [
                        'id' => 999, // Special ID for superadmin
                        'username' => $superadmin['username'],
                        'full_name' => $superadmin['first_name'] . ' ' . $superadmin['last_name'] . ' (SuperAdmin)',
                        'email' => 'superadmin@cornerstonecollegeinc.com'
                    ];
                }
            }
        }
        
        if ($login_success && $user_data) {
                // Set session variables
                $_SESSION['owner_id'] = $user_data['id'];
                $_SESSION['owner_username'] = $user_data['username'];
                $_SESSION['owner_name'] = $user_data['full_name'];
                $_SESSION['owner_email'] = $user_data['email'];
                $_SESSION['role'] = 'owner';
                
                // Update last login (only for real owner accounts)
                if ($user_data['id'] != 999) {
                    $update_stmt = $conn->prepare("UPDATE owner_accounts SET last_login = NOW() WHERE id = ?");
                    $update_stmt->bind_param("i", $user_data['id']);
                    $update_stmt->execute();
                }
                
                // Log the login
                $log_stmt = $conn->prepare("INSERT INTO system_logs (action_type, performed_by, user_role, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
                $action = "owner_login";
                $description = "Owner logged in successfully";
                $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
                $log_stmt->bind_param("ssssss", $action, $user_data['username'], $_SESSION['role'], $description, $ip, $user_agent);
                $log_stmt->execute();
                
                header("Location: Dashboard.php");
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
    <title>Owner Login - Cornerstone College Inc.</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .school-gradient { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #1e40af 100%); }
    </style>
</head>
<body class="school-gradient min-h-screen flex items-center justify-center">

<div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md mx-4">
    <!-- Logo and Header -->
    <div class="text-center mb-8">
        <img src="../images/LogoCCI.png" alt="Cornerstone College Inc." class="h-20 w-20 mx-auto mb-4 rounded-full">
        <h1 class="text-2xl font-bold text-gray-800">Cornerstone College Inc.</h1>
        <p class="text-gray-600 mt-2">Owner Portal Access</p>
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
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200 transform hover:scale-105">
            Sign In as Owner
        </button>
    </form>

    <!-- Footer -->
    <div class="mt-8 text-center">
        <p class="text-sm text-gray-500">
            Authorized personnel only. All access is logged and monitored.
        </p>
        <div class="mt-4">
            <a href="../StudentLogin/login.php" class="text-blue-600 hover:text-blue-800 text-sm">
                ← Back to Main Login
            </a>
        </div>
    </div>
</div>

</body>
</html>
