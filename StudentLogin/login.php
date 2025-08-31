<?php
session_start();
$conn = new mysqli("localhost", "root", "", "onecci_db");

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 🚫 If already logged in, redirect directly to dashboard
if (isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
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
        case 'parent':
            header("Location: ../ParentLogin/ParentDashboard.php");
            exit;
    }
}

// ❌ Stop caching (important for Back button issue)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// 🔑 Handle login submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['status'=>'error','message'=>'Please enter both username and password.']);
        exit;
    }

    // Use prepared statements for security
    // 1️⃣ Check student account
    $stmt = $conn->prepare("SELECT * FROM student_account WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
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
            echo json_encode(['status'=>'success','redirect'=>'studentDashboard.php']);
            exit;
        } else { 
            echo json_encode(['status'=>'error','message'=>'Incorrect password.']); 
            exit; 
        }
    }

    // 2️⃣ Check registrar account
    $stmt = $conn->prepare("SELECT * FROM registrar_account WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['registrar_id'] = $row['registrar_id'];
            $_SESSION['id_number'] = $row['id_number'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['registrar_name'] = $row['first_name'] . ' ' . $row['last_name'];
            $_SESSION['first_name'] = $row['first_name'];
            $_SESSION['last_name'] = $row['last_name'];
            $_SESSION['role'] = 'registrar';
            echo json_encode(['status'=>'success','redirect'=>'../RegistrarF/RegistrarDashboard.php']);
            exit;
        } else { 
            echo json_encode(['status'=>'error','message'=>'Incorrect password.']); 
            exit; 
        }
    }

    // 3️⃣ Check cashier account
    $stmt = $conn->prepare("SELECT * FROM cashier_account WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['cashier_id'] = $row['id'];
            $_SESSION['id_number'] = $row['id_number'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['cashier_name'] = $row['first_name'] . ' ' . $row['last_name'];
            $_SESSION['first_name'] = $row['first_name'];
            $_SESSION['last_name'] = $row['last_name'];
            $_SESSION['role'] = 'cashier';
            echo json_encode(['status'=>'success','redirect'=>'../CashierF/Dashboard.php']);
            exit;
        } else { 
            echo json_encode(['status'=>'error','message'=>'Incorrect password.']); 
            exit; 
        }
    }

    // 4️⃣ Check guidance account
    $stmt = $conn->prepare("SELECT * FROM guidance_account WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['guidance_id'] = $row['id'];
            $_SESSION['id_number'] = $row['id_number'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['guidance_name'] = $row['first_name'] . ' ' . $row['last_name'];
            $_SESSION['first_name'] = $row['first_name'];
            $_SESSION['last_name'] = $row['last_name'];
            $_SESSION['role'] = 'guidance';
            echo json_encode(['status' => 'success','redirect' => '../GuidanceF/GuidanceDashboard.php']);
            exit;
        } else { 
            echo json_encode(['status'=>'error','message'=>'Incorrect password.']); 
            exit; 
        }
    }

    // 5️⃣ Check parent account
    $stmt = $conn->prepare("SELECT * FROM parent_account WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
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
        } else { 
            echo json_encode(['status'=>'error','message'=>'Incorrect password.']); 
            exit; 
        }
    }

    echo json_encode(['status'=>'error','message'=>'Username not found.']);
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
  <link rel="icon" type="image/png" href="../images/Logo.png">
  <style>
    .school-gradient { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #1e40af 100%); }
    .card-shadow { box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
    .logo-glow { filter: drop-shadow(0 0 20px rgba(59, 130, 246, 0.3)); }
  </style>
</head>
<body class="school-gradient min-h-screen flex items-center justify-center p-4">

  <div class="w-full max-w-5xl bg-white rounded-3xl card-shadow overflow-hidden">
    <div class="md:flex">
      <!-- Left Side - Branding -->
      <div class="md:w-1/2 school-gradient text-white p-12 flex flex-col justify-center items-center text-center">
        <img src="../images/Logo.png" alt="Cornerstone College Inc." class="w-32 h-32 mb-6 logo-glow">
        <h1 class="text-3xl font-bold mb-2">Cornerstone College Inc.</h1>
        <p class="text-blue-200 text-lg mb-4">Student & Staff Portal</p>
        <p class="text-blue-100 text-sm">Established 2004</p>
        <div class="mt-8">
          <img src="../images/Leftpic.png" alt="Students" class="w-64 opacity-90">
        </div>
      </div>

      <!-- Right Side - Login Form -->
      <div class="md:w-1/2 p-12 flex flex-col justify-center">
        <div class="text-center mb-8">
          <h2 class="text-2xl font-bold text-gray-800 mb-2">Welcome Back</h2>
          <p class="text-gray-600">Sign in to access your account</p>
        </div>
        <form id="loginForm" class="space-y-6 w-full">
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
          
          <button type="submit" class="w-full school-gradient text-white py-3 rounded-xl font-semibold hover:opacity-90 transition-all transform hover:scale-[1.02]">
            Sign In
          </button>
        </form>
        
        <div class="text-center mt-6 pt-6 border-t border-gray-200">
          <p class="text-gray-600 text-sm mb-2">Are you a parent or guardian?</p>
          <button onclick="location.href='../ParentLogin/ParentLogin.html'" 
                  class="text-blue-600 hover:text-blue-800 font-medium text-sm hover:underline">
            Parent/Guardian Portal →
          </button>
        </div>
      </div>
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
