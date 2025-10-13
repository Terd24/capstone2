<?php
session_start();
require_once 'db_conn.php';

// Check if system is in maintenance mode
function is_maintenance_mode($conn) {
    // Ensure system_config table exists
    $conn->query("CREATE TABLE IF NOT EXISTS system_config (
        config_key VARCHAR(50) PRIMARY KEY,
        config_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    $result = $conn->query("SELECT config_value FROM system_config WHERE config_key = 'maintenance_mode'");
    if ($result && $row = $result->fetch_assoc()) {
        return ($row['config_value'] == '1' || $row['config_value'] === 'enabled');
    }
    return false;
}

// Check maintenance mode for non-superadmin users
if (is_maintenance_mode($conn)) {
    // Allow superadmin to bypass maintenance mode
    if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
        // Show maintenance page for all other users
        ?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Maintenance - Cornerstone College Inc.</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 text-center">
        <div class="mb-6">
            <img src="../images/LogoCCI.png" alt="Cornerstone College Inc." class="h-20 w-20 mx-auto rounded-full bg-blue-100 p-2">
        </div>
        <div class="mb-6">
            <svg class="w-16 h-16 mx-auto text-amber-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">System Under Maintenance</h1>
            <p class="text-gray-600 mb-4">We're currently performing system maintenance to improve your experience.</p>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                <p class="text-blue-800 text-sm">
                    <strong>Cornerstone College Inc.</strong><br>
                    The system will be back online shortly. Please try again later.
                </p>
            </div>
        </div>
        <div class="text-sm text-gray-500 mb-6">
            <p>If you need immediate assistance, please contact the IT department.</p>
        </div>
        
        <!-- Admin/Owner Login Button -->
        <div class="border-t border-gray-200 pt-4">
            <p class="text-gray-600 text-sm mb-2">System Administrator Access</p>
            <button onclick="location.href='../admin_login.php'" 
                    class="text-purple-600 hover:text-purple-700 font-medium text-sm hover:underline">
                🔧 Admin/Owner Login
            </button>
        </div>
    </div>
</body>
</html><?php
        exit;
    }
}

// 🚫 If already logged in, redirect directly to dashboard
if (isset($_SESSION['role'])) {
    switch (strtolower($_SESSION['role'])) {
        case 'student':
            header("Location: studentDashboard.php");
            exit;
        case 'guidance':
            header("Location: ../GuidanceF/GuidanceDashboard.php");
            exit;
        case 'cashier':
            header("Location: ../CashierF/Dashboard.php");
            exit;
        case 'registrar':
            header("Location: ../RegistrarF/RegistrarDashboard.php");
            exit;
        case 'attendance':
            header("Location: ../AttendanceF/Dashboard.php");
            exit;
        case 'parent':
            header("Location: ../ParentLogin/ParentDashboard.php");
            exit;
        case 'hr':
            header("Location: ../HRF/Dashboard.php");
            exit;
        case 'teacher':
            header("Location: ../EmployeePortal/AttendanceRecords.php");
            exit;
        case 'owner':
        case 'superadmin':
            // SuperAdmin and Owner should only login through admin_login.php
            session_destroy();
            header("Location: ../admin_login.php");
            exit;
    }
}

// ❌ Stop caching (important for Back button issue)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

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

// 🔑 Handle login submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['status'=>'error','message'=>'Please enter both username and password.']);
        exit;
    }

    $usernameFound = false;

    // 1️⃣ Try student
    $stmt = $conn->prepare("SELECT * FROM student_account WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $usernameFound = true;
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['student_id'] = $row['id'];
            $_SESSION['id_number'] = $row['id_number'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['student_name'] = $row['first_name'] . ' ' . $row['last_name'];
            $_SESSION['full_name'] = $row['first_name'] . ' ' . $row['last_name'];
            $_SESSION['program'] = $row['academic_track'] ?? 'N/A';
            $_SESSION['year_section'] = $row['grade_level'] ?? 'N/A';
            $_SESSION['first_name'] = $row['first_name'];
            $_SESSION['last_name'] = $row['last_name'];
            $_SESSION['grade_level'] = $row['grade_level'];
            $_SESSION['role'] = 'student';
            
            // Check if student must change password (first-time login)
            $must_change = $row['must_change_password'] ?? 1; // Default to 1 if column doesn't exist
            
            // Log student login
            log_login($conn, 'student', $row['id_number'], $row['username'], 'student');
            
            // Redirect to password change if first-time login
            if ($must_change == 1) {
                $_SESSION['must_change_password'] = true;
                echo json_encode(['status'=>'success','redirect'=>'change_password.php']);
            } else {
                echo json_encode(['status'=>'success','redirect'=>'studentDashboard.php']);
            }
            exit;
        }
    }

    // 2️⃣ SuperAdmin and Owner accounts are now handled in admin_login.php only

    // 3️⃣ Try employee_accounts (registrar, cashier, guidance, attendance, hr, teacher)
    $stmt = $conn->prepare("SELECT ea.*, e.first_name, e.last_name, e.id_number FROM employee_accounts ea 
                           JOIN employees e ON ea.employee_id = e.id_number 
                           WHERE ea.username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $usernameFound = true;
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $role = strtolower(trim((string)$row['role']));
            $full_name = $row['first_name'] . ' ' . $row['last_name'];

            // Set common session variables
            $_SESSION['employee_id'] = $row['id'];
            $_SESSION['id_number'] = $row['id_number'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['first_name'] = $row['first_name'];
            $_SESSION['last_name'] = $row['last_name'];
            $_SESSION['role'] = $role;
            
            // Check if employee must change password (first-time login)
            $must_change = $row['must_change_password'] ?? 0;

            // Log employee/superadmin login
            log_login($conn, 'employee', $row['id_number'], $row['username'], $role);
            
            // If must change password, redirect to employee password change page
            if ($must_change == 1) {
                $_SESSION['must_change_password'] = true;
                echo json_encode(['status'=>'success','redirect'=>'../EmployeePortal/change_password.php']);
                exit;
            }

            // Role routing
            switch($role) {
                case 'registrar':
                    $_SESSION['registrar_id'] = $row['id'];
                    $_SESSION['registrar_name'] = $full_name;
                    echo json_encode(['status'=>'success','redirect'=>'../RegistrarF/RegistrarDashboard.php']);
                    break;
                case 'cashier':
                    $_SESSION['cashier_id'] = $row['id'];
                    $_SESSION['cashier_name'] = $full_name;
                    echo json_encode(['status'=>'success','redirect'=>'../CashierF/Dashboard.php']);
                    break;
                case 'guidance':
                    $_SESSION['guidance_id'] = $row['id'];
                    $_SESSION['guidance_name'] = $full_name;
                    echo json_encode(['status' => 'success','redirect' => '../GuidanceF/GuidanceDashboard.php']);
                    break;
                case 'attendance':
                    $_SESSION['attendance_id'] = $row['id'];
                    $_SESSION['attendance_name'] = $full_name;
                    echo json_encode(['status' => 'success','redirect' => '../AttendanceF/Dashboard.php']);
                    break;
                case 'hr':
                    $_SESSION['hr_id'] = $row['id'];
                    $_SESSION['hr_name'] = $full_name;
                    echo json_encode(['status' => 'success','redirect' => '../HRF/Dashboard.php']);
                    break;
                case 'teacher':
                    echo json_encode(['status' => 'success','redirect' => '../EmployeePortal/AttendanceRecords.php']);
                    break;
                default:
                    // Fallback: treat any unexpected role as a generic teacher portal access
                    // Log for later clean-up
                    error_log('Unknown employee role: ' . print_r($row['role'], true) . ' for username ' . $row['username']);
                    $_SESSION['role'] = 'teacher';
                    echo json_encode(['status' => 'success','redirect' => '../EmployeePortal/AttendanceRecords.php']);
            }
            exit;
        }
    }

    // 3️⃣ Try parent
    $stmt = $conn->prepare("SELECT * FROM parent_account WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $usernameFound = true;
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['parent_id'] = $row['parent_id'];
            $_SESSION['id_number'] = $row['id_number'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['parent_name'] = $row['first_name'] . ' ' . $row['last_name'];
            $_SESSION['first_name'] = $row['first_name'];
            $_SESSION['last_name'] = $row['last_name'];
            $_SESSION['child_id'] = $row['child_id'];
            $_SESSION['child_name'] = $row['child_name'];
            $_SESSION['role'] = 'parent';
            echo json_encode(['status' => 'success','redirect' => '../ParentLogin/ParentDashboard.php']);
            exit;
        }
    }


    // Final decision
    if ($usernameFound) {
        echo json_encode(['status'=>'error','message'=>'Incorrect password.']);
    } else {
        echo json_encode(['status'=>'error','message'=>'Username not found.']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login - Cornerstone College Inc.</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" type="image/png" href="../images/LogoCCI.png">
  <link rel="manifest" href="/onecci/manifest.webmanifest">
  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('/onecci/sw.js').catch(console.error);
      });
    }
  </script>
  <style>
    .school-gradient { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #1e40af 100%); }
    .card-shadow { box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
    .logo-glow { filter: drop-shadow(0 0 20px rgba(59, 130, 246, 0.3)); }
  </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center p-4">

  <div class="w-full max-w-md">
    <!-- Header -->
    <div class="text-center mb-8">
      <img src="../images/LogoCCI.png" alt="Cornerstone College Inc." class="w-20 h-20 mx-auto mb-4">
      <h1 class="text-2xl font-bold text-gray-800">Cornerstone College Inc.</h1>
      <p class="text-gray-600 text-sm">Student & Staff Portal</p>
    </div>

    <!-- Login Card -->
    <div class="bg-white rounded-2xl card-shadow p-8">
      <div class="text-center mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-2">Welcome Back</h2>
        <p class="text-gray-600 text-sm">Sign in to access your account</p>
      </div>
      <form id="loginForm" class="space-y-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
          <input id="usernameInput" type="text" placeholder="Enter your username" 
                 class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" required>
        </div>
        <div class="relative">
          <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
          <input id="passwordInput" type="password" placeholder="Enter your password" 
                 class="w-full px-4 py-3 border border-gray-300 rounded-xl pr-12 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" required>
          <button type="button" onclick="togglePassword()" class="absolute right-4 top-10 text-gray-500 hover:text-gray-700">
            <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
            </svg>
          </button>
        </div>
        
        <div id="errorMessage" class="hidden bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm"></div>
        
        <button type="submit" class="w-full bg-[#0B2C62] text-white py-3 rounded-xl font-semibold hover:opacity-90 transition-all transform hover:scale-[1.02]">
          Sign In
        </button>
      </form>
      
      <div class="text-center mt-6 pt-6 border-t border-gray-200">
        <p class="text-gray-600 text-sm mb-2">Are you a parent or guardian?</p>
        <button onclick="location.href='../ParentLogin/ParentLogin.html'" 
                class="text-blue-600 hover:text-blue-600 font-medium text-sm hover:underline">
          Parent/Guardian Portal
        </button>
        
        <div class="mt-4 pt-4 border-t border-gray-200">
          <p class="text-gray-600 text-sm mb-2">Administrative Access</p>
          <button onclick="location.href='../admin_login.php'" 
                  class="text-blue-600 hover:text-blue-600 font-medium text-sm hover:underline">
            Admin/Owner Login
          </button>
        </div>
      </div>
    </div>
  </div>

  <script>
    function togglePassword() {
      const passwordInput = document.getElementById("passwordInput");
      const eyeIcon = document.getElementById("eyeIcon");
      
      if (passwordInput.type === "password") {
        passwordInput.type = "text";
        eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>';
      } else {
        passwordInput.type = "password";
        eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>';
      }
    }

    document.getElementById("loginForm").addEventListener("submit", function(e) {
      e.preventDefault();
      const username = document.getElementById("usernameInput").value;
      const password = document.getElementById("passwordInput").value;

      fetch('login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          window.location.href = data.redirect;
        } else {
          const errorDiv = document.getElementById('errorMessage');
          errorDiv.textContent = data.message;
          errorDiv.classList.remove('hidden');
        }
      });
    });
  </script>
</body>
</html>
