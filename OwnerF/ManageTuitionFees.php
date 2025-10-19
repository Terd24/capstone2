<?php
session_start();
include("../StudentLogin/db_conn.php");

// Require owner login
if (!isset($_SESSION['owner_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: ../admin_login.php");
    exit;
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Get all tuition fee structures
if ($action === 'get_fees') {
    $query = "SELECT * FROM tuition_fee_structure ORDER BY school_year DESC, grade_level, academic_track";
    $result = $conn->query($query);
    
    $fees = [];
    while ($row = $result->fetch_assoc()) {
        $fees[] = $row;
    }
    
    echo json_encode(['success' => true, 'fees' => $fees]);
    exit;
}

// Update tuition fee
if ($action === 'update_fee') {
    $id = intval($_POST['id']);
    $tuition_fee = floatval($_POST['tuition_fee']);
    $other_fees = floatval($_POST['other_fees']);
    $total_fee = $tuition_fee + $other_fees;
    
    $stmt = $conn->prepare("UPDATE tuition_fee_structure SET tuition_fee = ?, other_fees = ?, total_fee = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("dddi", $tuition_fee, $other_fees, $total_fee, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Tuition fee updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update tuition fee']);
    }
    exit;
}

// Add new tuition fee structure
if ($action === 'add_fee') {
    $grade_level = trim($_POST['grade_level']);
    $academic_track = trim($_POST['academic_track']);
    $tuition_fee = floatval($_POST['tuition_fee']);
    $other_fees = floatval($_POST['other_fees']);
    $school_year = trim($_POST['school_year']);
    $total_fee = $tuition_fee + $other_fees;
    
    $stmt = $conn->prepare("INSERT INTO tuition_fee_structure (grade_level, academic_track, tuition_fee, other_fees, total_fee, school_year) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssddds", $grade_level, $academic_track, $tuition_fee, $other_fees, $total_fee, $school_year);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Tuition fee structure added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add tuition fee structure']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>
