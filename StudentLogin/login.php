<?php
session_start();
$conn = new mysqli("localhost", "root", "", "onecci_db");

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
    }
}

// ❌ Stop caching (important for Back button issue)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// 🔑 Handle login submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json');
    $id_number = $_POST['id_number'];
    $password = $_POST['password'];

    $id_number = $conn->real_escape_string($id_number);

    // 1️⃣ Check guidance account
    $sql = "SELECT * FROM guidance_account WHERE username = '$id_number'";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['guidance_username'] = $row['username'];
            $_SESSION['guidance_name'] = $row['full_name'] ?? '';
            $_SESSION['role'] = 'guidance';
            echo json_encode(['status' => 'success','redirect' => '../GuidanceF/GuidanceDashboard.php']);
            exit;
        } else { echo json_encode(['status'=>'error','message'=>'Incorrect password.']); exit; }
    }

    // 2️⃣ Check student account
    $sql = "SELECT * FROM student_account WHERE id_number = '$id_number'";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['id_number'] = $row['id_number'];
            $_SESSION['full_name'] = $row['full_name'];
            $_SESSION['program'] = $row['program'];
            $_SESSION['year_section'] = $row['year_section'];
            $_SESSION['role'] = 'student';
            echo json_encode(['status'=>'success','redirect'=>'studentDashboard.php']);
            exit;
        } else { echo json_encode(['status'=>'error','message'=>'Incorrect password.']); exit; }
    }

    // 3️⃣ Check cashier account
    $sql = "SELECT * FROM cashier_account WHERE username = '$id_number'";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['cashier_username'] = $row['username'];
            $_SESSION['cashier_name'] = $row['full_name'];
            $_SESSION['role'] = 'cashier';
            echo json_encode(['status'=>'success','redirect'=>'../CashierF/Dashboard.php']);
            exit;
        } else { echo json_encode(['status'=>'error','message'=>'Incorrect password.']); exit; }
    }

    // 4️⃣ Check registrar account
    $sql = "SELECT * FROM registrar_account WHERE username = '$id_number'";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['registrar_username'] = $row['username'];
            $_SESSION['registrar_name'] = $row['registrar_name'];
            $_SESSION['registrar_id'] = $row['registrar_id'] ?? '';
            $_SESSION['role'] = 'registrar';
            echo json_encode(['status'=>'success','redirect'=>'../RegistrarF/RegistrarDashboard.php']);
            exit;
        } else { echo json_encode(['status'=>'error','message'=>'Incorrect password.']); exit; }
    }

    echo json_encode(['status'=>'error','message'=>'User not found.']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>CCI Education Services Group</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" type="image/png" href="images/Logo.png">
  <script src="https://unpkg.com/feather-icons"></script>
</head>
<body class="bg-white font-sans min-h-screen flex items-center justify-center">

  <div class="w-full max-w-4xl bg-white shadow-xl rounded-lg p-6 md:flex items-center justify-between">
    <div class="md:w-1/2 flex flex-col items-center text-center md:text-left">
      <h2 class="text-lg font-semibold">Student and Staff Login</h2>
      <p class="text-gray-500 text-sm mb-4">Make sure your account is secure</p>
      <img src="../images/Leftpic.png" alt="Students" class="w-60">
    </div>

    <div class="md:w-1/2 w-full mt-6 md:mt-0 flex flex-col items-center">
      <img src="../images/Logo.png" alt="CHED Logo" class="w-30 mb-4">
      <form id="loginForm" class="space-y-4 w-full">
        <div>
          <input id="idInput" type="text" placeholder="Id number" class="w-full px-4 py-2 border border-gray-300 rounded" required>
        </div>
        <div class="relative">
          <input id="passwordInput" type="password" placeholder="Password" class="w-full px-4 py-2 border border-gray-300 rounded pr-10" required>
          <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500">
            <i id="eyeIcon" data-feather="eye" class="w-5 h-5"></i>
          </button>
        </div>
        <p class="text-sm text-gray-400 text-right">Forgot Password?</p>
        <button type="submit" class="w-full bg-black text-white py-2 rounded hover:bg-gray-800">LOGIN</button>
      </form>
       <div class="text-center mt-4">
        <button onclick="location.href='../ParentLogin/ParentLogin.html'" class="text-blue-500 hover:underline py-3 rounded-lg">Parent/Guardian login</button>
      </div>
    </div>
  </div>

  <script>
    feather.replace();

    function togglePassword() {
      const passwordInput = document.getElementById("passwordInput");
      const eyeIcon = document.getElementById("eyeIcon");
      passwordInput.type = passwordInput.type === "password" ? "text" : "password";
      eyeIcon.setAttribute("data-feather", passwordInput.type === "password" ? "eye" : "eye-off");
      feather.replace();
    }

    document.getElementById("loginForm").addEventListener("submit", function(e) {
      e.preventDefault();
      const id = document.getElementById("idInput").value;
      const password = document.getElementById("passwordInput").value;

      fetch('login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id_number=${encodeURIComponent(id)}&password=${encodeURIComponent(password)}`
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          window.location.href = data.redirect;
        } else {
          alert(data.message);
        }
      });
    });
  </script>
</body>
</html>
