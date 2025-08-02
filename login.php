<?php
session_start();
$conn = new mysqli("localhost", "root", "", "onecci_db");

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_number = $_POST['id_number'];
    $password = $_POST['password'];

    $id_number = $conn->real_escape_string($id_number);

    $sql = "SELECT * FROM users WHERE id_number = '$id_number'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {
            $_SESSION['id_number'] = $row['id_number'];
            $_SESSION['full_name'] = $row['full_name'];
            $_SESSION['program'] = $row['program'];
            $_SESSION['year_section'] = $row['year_section'];

            echo json_encode([
                'status' => 'success',
                'redirect' => 'studentdashboard.php'
            ]);
            exit;
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Incorrect password.'
            ]);
            exit;
        }
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'User not found.'
        ]);
        exit;
    }
}
?>
