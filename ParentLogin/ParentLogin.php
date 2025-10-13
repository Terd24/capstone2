<?php
session_start();
require_once '../StudentLogin/db_conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['parent_id'] ?? '';
    $password = $_POST['parent_password'] ?? '';

    // Lookup parent by username ONLY
    $query = "SELECT parent_id, username, password, child_id FROM parent_account WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $row['password'])) {
            $child_id = $row['child_id'];

            // Fetch child's name from student_account so we always display the student's name for the parent session
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
            // Parent display name should be the child's name per requirement
            $_SESSION['parent_name'] = $child_name;
            $_SESSION['child_id'] = $child_id;
            $_SESSION['child_name'] = $child_name;
            $_SESSION['role'] = 'parent';

            header("Location: ParentDashboard.php");
            exit();
        } else {
            echo "<script>alert('Incorrect password.'); window.location.href='ParentLogin.html';</script>";
        }
    } else {
        echo "<script>alert('Account not found.'); window.location.href='ParentLogin.html';</script>";
    }

    if (isset($stmt)) { $stmt->close(); }
}
?>