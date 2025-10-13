<?php
session_start();
require_once '../StudentLogin/db_conn.php';

// If already logged in as parent, redirect to dashboard
if (isset($_SESSION['role']) && $_SESSION['role'] === 'parent') {
    header("Location: ParentDashboard.php");
    exit;
}

// Handle login submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    header('Content-Type: application/json');
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Please enter both username and password.']);
        exit;
    }

    // Lookup parent by username
    $stmt = $conn->prepare("SELECT parent_id, username, password, child_id FROM parent_account WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $row['password'])) {
            $child_id = $row['child_id'];

            // Fetch child's name from student_account
            $child_stmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) AS full_name FROM student_account WHERE id_number = ?");
            $child_stmt->bind_param("s", $child_id);
            $child_stmt->execute();
            $child_res = $child_stmt->get_result();
            $child_name = 'Student';
            if ($child_res && $child_res->num_rows === 1) {
                $child_row = $child_res->fetch_assoc();
                $child_name = $child_row['full_name'] ?: 'Student';
            }
            $child_stmt->close();

            // Set session variables
            $_SESSION['parent_id'] = $row['parent_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['parent_name'] = $child_name;
            $_SESSION['child_id'] = $child_id;
            $_SESSION['child_name'] = $child_name;
            $_SESSION['role'] = 'parent';

            echo json_encode(['status' => 'success', 'redirect' => 'ParentDashboard.php']);
            exit;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Incorrect password.']);
            exit;
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Username not found.']);
        exit;
    }

    $stmt->close();
    exit;
}

// If not POST request, show the HTML login page
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Parent Login - Cornerstone College Inc.</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center p-4" onload="clearFormOnLoad()">
  <div class="w-full max-w-md">
    <!-- Header -->
    <div class="text-center mb-8">
      <img src="../images/LogoCCI.png" alt="Cornerstone College Inc." class="w-20 h-20 mx-auto mb-4">
      <h1 class="text-2xl font-bold text-gray-800 mb-2">Cornerstone College Inc.</h1>
      <p class="text-gray-600 text-sm">Parent Portal</p>
    </div>

    <!-- Login Card -->
    <div class="bg-white rounded-2xl shadow-lg p-8">
      <div class="text-center mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-2">Welcome Back</h2>
        <p class="text-gray-600 text-sm">Sign in to access your account</p>
      </div>

      <!-- Login Form -->
      <form id="loginForm" class="space-y-4">
        <div>
          <label for="usernameInput" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
          <input 
            id="usernameInput"
            type="text" 
            placeholder="Enter your username"
            autocomplete="off"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
            required
          >
        </div>
        
        <div class="relative">
          <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
          <input id="passwordInput" type="password" placeholder="Enter your password" autocomplete="off"
                 class="w-full px-4 py-3 border border-gray-300 rounded-xl pr-12 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" required>
          <button type="button" onclick="togglePassword()" class="absolute right-4 top-10 text-gray-400 hover:text-gray-600 transition-colors focus:outline-none">
            <svg id="eyeIcon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
            </svg>
          </button>
        </div>
        
        <!-- Error Message (positioned after password field) -->
        <div id="errorMessage" class="hidden bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm"></div>
        
        <button type="submit" class="w-full bg-[#0B2C62] text-white py-3 rounded-xl font-semibold hover:opacity-90 transition-all transform hover:scale-[1.02]">
          Sign In
        </button>
      </form>

      <!-- Footer Links -->
      <div class="text-center mt-6 pt-6 border-t border-gray-200">
        <p class="text-sm text-gray-600 mb-2">Are you a student or staff member?</p>
        <a href="../StudentLogin/login.php" class="text-blue-600 hover:text-blue-600 font-medium text-sm hover:underline">Student & Staff Portal</a>
      </div>
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

    // Handle form submission with AJAX
    document.getElementById("loginForm").addEventListener("submit", function(e) {
      e.preventDefault();
      
      const username = document.getElementById("usernameInput").value;
      const password = document.getElementById("passwordInput").value;

      fetch('ParentLogin.php', {
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
      })
      .catch(error => {
        const errorDiv = document.getElementById('errorMessage');
        errorDiv.textContent = 'An error occurred. Please try again.';
        errorDiv.classList.remove('hidden');
      });
    });
  </script>

</body>
</html>