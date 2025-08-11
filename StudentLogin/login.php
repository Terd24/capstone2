<?php
session_start();
$conn = new mysqli("localhost", "root", "", "onecci_db");

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_number = $_POST['id_number'];
    $password = $_POST['password'];

    $id_number = $conn->real_escape_string($id_number);

    // 1️⃣ Check guidance account
    $sql = "SELECT * FROM guidance_account WHERE username = '$id_number'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = 'guidance';

            echo json_encode([
                'status' => 'success',
                'redirect' => '../GuidanceF/GuidanceDashboard.html'
            ]);
            exit;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Incorrect password.']);
            exit;
        }
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

            echo json_encode([
                'status' => 'success',
                'redirect' => 'studentdashboard.php'
            ]);
            exit;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Incorrect password.']);
            exit;
        }
    }

    // 3️⃣ Check cashier account
    $sql = "SELECT * FROM cashier_account WHERE username = '$id_number'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {
            $_SESSION['username'] = $row['username'];
            $_SESSION['full_name'] = $row['full_name'];
            $_SESSION['role'] = 'cashier';

            echo json_encode([
                'status' => 'success',
                'redirect' => '../CashierF/Dashboard.html' 
            ]);
            exit;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Incorrect password.']);
            exit;
        }
    }

    echo json_encode(['status' => 'error', 'message' => 'User not found.']);
    exit;
}
?>
