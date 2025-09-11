<?php
session_start();
include '../StudentLogin/db_conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['parent_id'] ?? '';
    $password = $_POST['parent_password'] ?? '';

    // Prepare and execute SQL securely
    $query = "SELECT * FROM parent_account WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if parent exists
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        // Verify hashed password
        if (password_verify($password, $row['password'])) {
            // Set session variables
            $_SESSION['parent_id'] = $row['parent_id'];
            $_SESSION['parent_name'] = $row['first_name'] . ' ' . $row['last_name'];
            $_SESSION['child_id'] = $row['child_id'];
            $_SESSION['child_name'] = $row['child_name'];

            // Redirect to parent dashboard
            header("Location: ParentDashboard.php");
            exit();
        } else {
            echo "<script>alert('Incorrect password.'); window.location.href='ParentLogin.html';</script>";
        }
    } else {
        echo "<script>alert('Account not found.'); window.location.href='ParentLogin.html';</script>";
    }

    $stmt->close();
}
?>