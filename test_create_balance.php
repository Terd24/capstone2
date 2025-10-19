<?php
// Manual test script to create a document balance
include("StudentLogin/db_conn.php");
include("includes/create_document_balance.php");

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Manual Balance Creation Test</h2>";

// Test parameters - CHANGE THESE to match your test case
$test_student_id = '02200000001';  // Change to your student ID
$test_document_type = 'Form137';    // Change to your document type

echo "<p><strong>Testing with:</strong></p>";
echo "<ul>";
echo "<li>Student ID: $test_student_id</li>";
echo "<li>Document Type: $test_document_type</li>";
echo "</ul>";

// Check if document fee exists
echo "<h3>Step 1: Check Document Fee</h3>";
$fee_check = $conn->query("SELECT * FROM document_fees WHERE document_name = '$test_document_type' AND is_active = 1");
if ($fee_check && $fee_check->num_rows > 0) {
    $fee_row = $fee_check->fetch_assoc();
    echo "<p style='color:green'>✅ Document fee found: ₱{$fee_row['fee_amount']}</p>";
    echo "<pre>" . print_r($fee_row, true) . "</pre>";
} else {
    echo "<p style='color:red'>❌ No fee found for document: $test_document_type</p>";
    echo "<p>Available documents in document_fees:</p>";
    $all_fees = $conn->query("SELECT document_name, fee_amount FROM document_fees WHERE is_active = 1");
    if ($all_fees) {
        while ($row = $all_fees->fetch_assoc()) {
            echo "- {$row['document_name']}: ₱{$row['fee_amount']}<br>";
        }
    }
    exit;
}

// Check if student exists
echo "<h3>Step 2: Check Student</h3>";
$student_check = $conn->query("SELECT id_number, CONCAT(first_name, ' ', last_name) as full_name, grade_level FROM student_account WHERE id_number = '$test_student_id'");
if ($student_check && $student_check->num_rows > 0) {
    $student_row = $student_check->fetch_assoc();
    echo "<p style='color:green'>✅ Student found: {$student_row['full_name']}</p>";
    echo "<pre>" . print_r($student_row, true) . "</pre>";
} else {
    echo "<p style='color:red'>❌ Student not found: $test_student_id</p>";
    exit;
}

// Check existing fee items for this student
echo "<h3>Step 3: Check Existing Fee Items</h3>";
$existing_fees = $conn->query("SELECT * FROM student_fee_items WHERE id_number = '$test_student_id' ORDER BY date_added DESC LIMIT 5");
if ($existing_fees && $existing_fees->num_rows > 0) {
    echo "<p>Existing fee items:</p>";
    echo "<table border='1'><tr><th>Fee Type</th><th>Amount</th><th>Paid</th><th>Term</th><th>Date</th></tr>";
    while ($row = $existing_fees->fetch_assoc()) {
        echo "<tr><td>{$row['fee_type']}</td><td>₱{$row['amount']}</td><td>₱{$row['paid']}</td><td>{$row['school_year_term']}</td><td>{$row['date_added']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>No existing fee items for this student</p>";
}

// Create the balance
echo "<h3>Step 4: Create Balance</h3>";
echo "<p>Calling createDocumentBalance()...</p>";

$success = createDocumentBalance($conn, $test_student_id, $test_document_type);

if ($success) {
    echo "<p style='color:green; font-size:20px'>✅ Balance creation returned SUCCESS!</p>";
} else {
    echo "<p style='color:red; font-size:20px'>❌ Balance creation returned FAILED!</p>";
}

// Verify the balance was created
echo "<h3>Step 5: Verify Balance Was Created</h3>";
$verify = $conn->query("SELECT * FROM student_fee_items WHERE id_number = '$test_student_id' AND fee_type LIKE '%$test_document_type%' ORDER BY date_added DESC LIMIT 1");
if ($verify && $verify->num_rows > 0) {
    $created_balance = $verify->fetch_assoc();
    echo "<p style='color:green; font-size:18px'>✅ BALANCE FOUND IN DATABASE!</p>";
    echo "<table border='1'>";
    foreach ($created_balance as $key => $value) {
        echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
    }
    echo "</table>";
    
    echo "<h3>✅ SUCCESS! Balance was created successfully!</h3>";
    echo "<p>Now check the Cashier system for student: $test_student_id</p>";
} else {
    echo "<p style='color:red; font-size:18px'>❌ BALANCE NOT FOUND IN DATABASE!</p>";
    echo "<p>The function returned success but the balance is not in the database.</p>";
    echo "<p>This might indicate a database issue or the INSERT statement is not working.</p>";
}

// Show all fee items for this student
echo "<h3>Step 6: All Fee Items for This Student</h3>";
$all_items = $conn->query("SELECT * FROM student_fee_items WHERE id_number = '$test_student_id' ORDER BY date_added DESC");
if ($all_items && $all_items->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Fee Type</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Term</th><th>Date Added</th></tr>";
    while ($row = $all_items->fetch_assoc()) {
        $balance = $row['amount'] - $row['paid'];
        echo "<tr><td>{$row['id']}</td><td>{$row['fee_type']}</td><td>₱{$row['amount']}</td><td>₱{$row['paid']}</td><td>₱{$balance}</td><td>{$row['school_year_term']}</td><td>{$row['date_added']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>No fee items found for this student</p>";
}

?>
