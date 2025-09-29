<?php
session_start();
include '../StudentLogin/db_conn.php';

// Require HR login or Super Admin access
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'hr' && $_SESSION['role'] !== 'superadmin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'];
    $employee_id = $_POST['employee_id'];
    
    try {
        switch($action) {
            case 'reset_password':
                $new_password = $_POST['new_password'];
                if (strlen($new_password) < 6) {
                    throw new Exception("Password must be at least 6 characters long");
                }
                
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE employee_accounts SET password = ? WHERE employee_id = ?");
                $stmt->bind_param("ss", $hashed_password, $employee_id);
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Password reset successfully']);
                } else {
                    throw new Exception("Failed to reset password");
                }
                break;
                
            case 'remove_account':
                $stmt = $conn->prepare("DELETE FROM employee_accounts WHERE employee_id = ?");
                $stmt->bind_param("s", $employee_id);
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Account removed successfully']);
                } else {
                    throw new Exception("Failed to remove account");
                }
                break;
                
            case 'update_account':
                $username = $_POST['username'];
                $role = $_POST['role'];
                
                // Check if username is already taken by another account
                $check_stmt = $conn->prepare("SELECT id FROM employee_accounts WHERE username = ? AND employee_id != ?");
                $check_stmt->bind_param("ss", $username, $employee_id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                
                if ($result->num_rows > 0) {
                    throw new Exception("Username is already taken");
                }
                
                // Update account with or without password
                if (isset($_POST['new_password']) && !empty($_POST['new_password'])) {
                    $new_password = $_POST['new_password'];
                    if (strlen($new_password) < 6) {
                        throw new Exception("Password must be at least 6 characters long");
                    }
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE employee_accounts SET username = ?, role = ?, password = ? WHERE employee_id = ?");
                    $stmt->bind_param("ssss", $username, $role, $hashed_password, $employee_id);
                } else {
                    $stmt = $conn->prepare("UPDATE employee_accounts SET username = ?, role = ? WHERE employee_id = ?");
                    $stmt->bind_param("sss", $username, $role, $employee_id);
                }
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Account updated successfully']);
                } else {
                    throw new Exception("Failed to update account");
                }
                break;
                
            default:
                throw new Exception("Invalid action");
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
