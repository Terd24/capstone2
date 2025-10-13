<?php
session_start();
require_once '../StudentLogin/db_conn.php';

// Check if employee is logged in and needs to change password
if (!isset($_SESSION['employee_id']) || !isset($_SESSION['must_change_password'])) {
    header("Location: ../StudentLogin/login.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Common weak passwords list
    $weak_passwords = ['password', 'password123', '123456', '12345678', 'qwerty', 'abc123', 'password1'];
    
    // Validation
    if (empty($new_password) || empty($confirm_password)) {
        $error = 'Please fill in all fields.';
    } elseif (strlen($new_password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif (!preg_match('/[A-Z]/', $new_password)) {
        $error = 'Password must contain at least one uppercase letter (A-Z).';
    } elseif (!preg_match('/[a-z]/', $new_password)) {
        $error = 'Password must contain at least one lowercase letter (a-z).';
    } elseif (!preg_match('/[0-9]/', $new_password)) {
        $error = 'Password must contain at least one number (0-9).';
    } elseif (preg_match('/\s/', $new_password)) {
        $error = 'Password cannot contain spaces.';
    } elseif (in_array(strtolower($new_password), $weak_passwords)) {
        $error = 'This password is too common. Please choose a stronger password.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE employee_accounts SET password = ?, must_change_password = 0 WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $_SESSION['employee_id']);
        
        if ($stmt->execute()) {
            // Remove the flag from session
            unset($_SESSION['must_change_password']);
            $success = 'Password changed successfully! Redirecting...';
            
            // Set role-specific session variables
            $role = strtolower($_SESSION['role']);
            $full_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
            
            switch($role) {
                case 'registrar':
                    $_SESSION['registrar_id'] = $_SESSION['employee_id'];
                    $_SESSION['registrar_name'] = $full_name;
                    $redirect_url = '../RegistrarF/RegistrarDashboard.php';
                    break;
                case 'cashier':
                    $_SESSION['cashier_id'] = $_SESSION['employee_id'];
                    $_SESSION['cashier_name'] = $full_name;
                    $redirect_url = '../CashierF/Dashboard.php';
                    break;
                case 'guidance':
                    $_SESSION['guidance_id'] = $_SESSION['employee_id'];
                    $_SESSION['guidance_name'] = $full_name;
                    $redirect_url = '../GuidanceF/GuidanceDashboard.php';
                    break;
                case 'attendance':
                    $_SESSION['attendance_id'] = $_SESSION['employee_id'];
                    $_SESSION['attendance_name'] = $full_name;
                    $redirect_url = '../AttendanceF/Dashboard.php';
                    break;
                case 'hr':
                    $_SESSION['hr_id'] = $_SESSION['employee_id'];
                    $_SESSION['hr_name'] = $full_name;
                    $redirect_url = '../HRF/Dashboard.php';
                    break;
                case 'teacher':
                    $redirect_url = 'AttendanceRecords.php';
                    break;
                default:
                    $redirect_url = '../StudentLogin/login.php';
            }
            
            header("refresh:2;url=$redirect_url");
        } else {
            $error = 'Failed to update password. Please try again.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - First Time Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Prevent back button
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            history.go(1);
        };
    </script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <div class="text-center mb-6">
                <img src="../images/LogoCCI.png" alt="Cornerstone College Inc." class="w-16 h-16 mx-auto mb-4">
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Welcome, <?= htmlspecialchars($_SESSION['first_name']) ?>!</h1>
                <p class="text-gray-600 text-sm">This is your first time logging in. Please create a new password for your account.</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-4 text-sm">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-4 text-sm">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                    <input type="password" name="new_password" id="new_password" required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Enter new password"
                           value="<?= htmlspecialchars($_POST['new_password'] ?? '') ?>">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Re-enter your new password"
                           value="<?= htmlspecialchars($_POST['confirm_password'] ?? '') ?>">
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-800">
                    <strong>Password Requirements:</strong>
                    <ul class="space-y-1 mt-2">
                        <li id="req-length" class="flex items-center">
                            <span class="mr-2">❌</span> At least 8 characters
                        </li>
                        <li id="req-uppercase" class="flex items-center">
                            <span class="mr-2">❌</span> One uppercase letter (A-Z)
                        </li>
                        <li id="req-lowercase" class="flex items-center">
                            <span class="mr-2">❌</span> One lowercase letter (a-z)
                        </li>
                        <li id="req-number" class="flex items-center">
                            <span class="mr-2">❌</span> One number (0-9)
                        </li>
                        <li id="req-space" class="flex items-center">
                            <span class="mr-2">❌</span> No spaces allowed
                        </li>
                    </ul>
                </div>

                <button type="submit" id="submit-btn" disabled
                        class="w-full bg-gray-400 text-white py-3 rounded-xl font-semibold cursor-not-allowed transition-all">
                    Change Password & Continue
                </button>
            </form>
        </div>
    </div>

    <script>
        const passwordInput = document.getElementById('new_password');
        const confirmInput = document.getElementById('confirm_password');
        const submitBtn = document.getElementById('submit-btn');
        
        if (passwordInput.value) {
            validatePassword();
        }
        
        passwordInput.addEventListener('input', validatePassword);
        confirmInput.addEventListener('input', validatePassword);
        
        function validatePassword() {
            const password = passwordInput.value;
            
            const hasLength = password.length >= 8;
            const hasUppercase = /[A-Z]/.test(password);
            const hasLowercase = /[a-z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const noSpaces = !/\s/.test(password);
            
            updateRequirement('req-length', hasLength);
            updateRequirement('req-uppercase', hasUppercase);
            updateRequirement('req-lowercase', hasLowercase);
            updateRequirement('req-number', hasNumber);
            updateRequirement('req-space', noSpaces);
            
            const allValid = hasLength && hasUppercase && hasLowercase && hasNumber && noSpaces;
            
            if (allValid) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('bg-gray-400', 'cursor-not-allowed');
                submitBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');
            } else {
                submitBtn.disabled = true;
                submitBtn.classList.add('bg-gray-400', 'cursor-not-allowed');
                submitBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
            }
        }
        
        function updateRequirement(id, isValid) {
            const element = document.getElementById(id);
            const icon = element.querySelector('span');
            
            if (isValid) {
                icon.textContent = '✅';
                element.classList.add('text-green-600');
                element.classList.remove('text-blue-800');
            } else {
                icon.textContent = '❌';
                element.classList.remove('text-green-600');
                element.classList.add('text-blue-800');
            }
        }
    </script>
</body>
</html>
