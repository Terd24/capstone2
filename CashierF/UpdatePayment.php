<?php
session_start();
require_once '../StudentLogin/db_conn.php';

// Check if user is logged in and has cashier role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'cashier') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Validate required fields
if (!isset($_POST['fee_id']) || !isset($_POST['paid_amount']) || !isset($_POST['payment_method'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$fee_id = intval($_POST['fee_id']);
$paid_amount = floatval($_POST['paid_amount']);
$payment_method = trim($_POST['payment_method']);

// Validate paid amount
if ($paid_amount < 0) {
    echo json_encode(['success' => false, 'message' => 'Paid amount cannot be negative']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Get current fee item details
    $stmt = $conn->prepare("SELECT fee_type, amount FROM student_fee_items WHERE id = ?");
    $stmt->bind_param("i", $fee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Fee item not found');
    }
    
    $fee_item = $result->fetch_assoc();
    $amount_due = floatval($fee_item['amount']);
    
    // Validate that paid amount doesn't exceed amount due
    if ($paid_amount > $amount_due) {
        throw new Exception('Paid amount cannot exceed amount due (₱' . number_format($amount_due, 2) . ')');
    }
    
    // Get student info for payment record
    $stmt = $conn->prepare("SELECT id_number, school_year_term FROM student_fee_items WHERE id = ?");
    $stmt->bind_param("i", $fee_id);
    $stmt->execute();
    $student_info = $stmt->get_result()->fetch_assoc();
    
    // Update the fee item
    $stmt = $conn->prepare("UPDATE student_fee_items SET paid = ? WHERE id = ?");
    $stmt->bind_param("di", $paid_amount, $fee_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update payment');
    }
    
    // If fully paid, create payment record and delete fee item
    if ($paid_amount == $amount_due && $paid_amount > 0) {
        // Generate OR number
        $or_number = 'OR' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Insert payment record with specific fee type and payment method
        $stmt = $conn->prepare("INSERT INTO student_payments (id_number, school_year_term, fee_type, amount, payment_method, or_number, date) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssdss", $student_info['id_number'], $student_info['school_year_term'], $fee_item['fee_type'], $paid_amount, $payment_method, $or_number);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to create payment record');
        }
        
        // Delete the fee item since it's fully paid
        $stmt = $conn->prepare("DELETE FROM student_fee_items WHERE id = ?");
        $stmt->bind_param("i", $fee_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to remove paid fee item');
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Payment updated successfully for ' . $fee_item['fee_type']
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
