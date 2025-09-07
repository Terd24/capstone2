<?php
session_start();
include '../StudentLogin/db_conn.php';
header('Content-Type: application/json');

// Check if user is logged in and is a cashier
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'cashier') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get form data
$id_number = $_POST['id_number'] ?? '';
$school_year_term = $_POST['school_year_term'] ?? '';

// Validate required fields
if (empty($id_number) || empty($school_year_term)) {
    echo json_encode(['success' => false, 'message' => 'Student ID and school year/term are required']);
    exit;
}

// Validate fee items are provided
if (!isset($_POST['fee_items']) || empty($_POST['fee_items'])) {
    echo json_encode(['success' => false, 'message' => 'At least one fee item is required']);
    exit;
}

try {
    // Check if student exists
    $student_check = $conn->prepare("SELECT id_number FROM student_account WHERE id_number = ?");
    $student_check->bind_param("s", $id_number);
    $student_check->execute();
    $student_result = $student_check->get_result();
    
    if ($student_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit;
    }
    $student_check->close();

    // Begin transaction
    $conn->begin_transaction();

    // Insert new fee items with amount_due and paid
    $fee_items = json_decode($_POST['fee_items'], true);
    if ($fee_items && is_array($fee_items)) {
        $insert_fee = $conn->prepare("INSERT INTO student_fee_items (id_number, school_year_term, fee_type, amount, paid) VALUES (?, ?, ?, ?, ?)");
        
        foreach ($fee_items as $item) {
            if (!empty($item['fee_type']) && isset($item['amount_due'])) {
                $amount_due = floatval($item['amount_due']);
                $paid = floatval($item['paid'] ?? 0);
                
                // Validate amounts
                if ($amount_due < 0) {
                    throw new Exception('Amount due cannot be negative for ' . $item['fee_type']);
                }
                if ($paid < 0) {
                    throw new Exception('Paid amount cannot be negative for ' . $item['fee_type']);
                }
                if ($paid > $amount_due) {
                    throw new Exception('Paid amount cannot exceed amount due for ' . $item['fee_type']);
                }
                
                $insert_fee->bind_param("sssdd", $id_number, $school_year_term, $item['fee_type'], $amount_due, $paid);
                
                if (!$insert_fee->execute()) {
                    throw new Exception('Failed to insert fee item: ' . $item['fee_type']);
                }
                
                // If fully paid, create payment record
                if ($paid == $amount_due && $paid > 0) {
                    // Generate OR number
                    $or_number = 'OR' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                    
                    // Get payment method from fee item data
                    $payment_method = isset($item['payment_method']) ? $item['payment_method'] : 'Cash';
                    
                    // Insert payment record with specific fee type and payment method
                    $payment_stmt = $conn->prepare("INSERT INTO student_payments (id_number, school_year_term, fee_type, amount, payment_method, or_number, date) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $payment_stmt->bind_param("sssdss", $id_number, $school_year_term, $item['fee_type'], $paid, $payment_method, $or_number);
                    
                    if (!$payment_stmt->execute()) {
                        throw new Exception('Failed to create payment record for ' . $item['fee_type']);
                    }
                }
            }
        }
        $insert_fee->close();
    }

    // Commit transaction
    $conn->commit();
    
    // Get the inserted fee IDs for temporary visibility tracking
    $added_fees = [];
    
    // Query the recently inserted fees to get their actual IDs
    $get_fees_stmt = $conn->prepare("SELECT id, fee_type, amount, paid FROM student_fee_items WHERE id_number = ? AND school_year_term = ? ORDER BY id DESC LIMIT ?");
    $fee_count = count($fee_items);
    $get_fees_stmt->bind_param("ssi", $id_number, $school_year_term, $fee_count);
    $get_fees_stmt->execute();
    $fees_result = $get_fees_stmt->get_result();
    
    while ($fee_row = $fees_result->fetch_assoc()) {
        $added_fees[] = [
            'id' => $fee_row['id'],
            'fee_type' => $fee_row['fee_type'],
            'amount_due' => $fee_row['amount'],
            'paid' => $fee_row['paid']
        ];
    }
    $get_fees_stmt->close();
    
    // Return success with added fees info for frontend
    echo json_encode([
        'success' => true, 
        'message' => 'Balance updated successfully',
        'added_fees' => $added_fees
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("AddBalance Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>
