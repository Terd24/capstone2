<?php
session_start();
include 'db_conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $parent_id = $_POST['parent_id'] ?? '';
    $parent_password = $_POST['parent_password'] ?? '';

    // Prepare and execute SQL securely
    $query = "SELECT * FROM parent_account WHERE parent_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $parent_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if parent exists
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        // Verify hashed password
        if (password_verify($parent_password, $row['parent_password'])) {
            // Set session variables
            $_SESSION['parent_id'] = $row['parent_id'];
            $_SESSION['full_name'] = $row['full_name'];
            $_SESSION['child_id'] = $row['child_id']; // optional, if child info is needed

            // Redirect to parent dashboard
            header("Location: ParentDashboard.php");
            exit();
        } else {
            echo "<script>alert('Incorrect password.'); window.location.href='parentlogin.html';</script>";
        }
    } else {
        echo "<script>alert('Account not found.'); window.location.href='parentlogin.html';</script>";
    }

    $stmt->close();
}
?>