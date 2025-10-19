<?php
// Debug script to check document balance creation
include("StudentLogin/db_conn.php");

echo "<h2>Document Balance Debug</h2>";

// 1. Check document_fees table
echo "<h3>1. Document Fees Configuration:</h3>";
$result = $conn->query("SELECT * FROM document_fees WHERE is_active = 1");
if ($result && $result->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Document Name</th><th>Fee Amount</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['document_name']}</td><td>₱{$row['fee_amount']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'>No document fees found!</p>";
}

// 2. Check recent document requests
echo "<h3>2. Recent Document Requests:</h3>";
$result = $conn->query("SELECT * FROM document_requests ORDER BY date_requested DESC LIMIT 5");
if ($result && $result->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Student ID</th><th>Student Name</th><th>Document Type</th><th>Status</th><th>Date</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['student_id']}</td><td>{$row['student_name']}</td><td>{$row['document_type']}</td><td>{$row['status']}</td><td>{$row['date_requested']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>No document requests found</p>";
}

// 3. Check student_fee_items for document fees
echo "<h3>3. Document Fee Balances Created:</h3>";
$result = $conn->query("SELECT * FROM student_fee_items WHERE fee_type LIKE '%Document Request Fee%' ORDER BY date_added DESC LIMIT 10");
if ($result && $result->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Student ID</th><th>Student Name</th><th>Fee Type</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Term</th><th>Date Added</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $balance = $row['amount'] - $row['paid'];
        echo "<tr><td>{$row['id']}</td><td>{$row['id_number']}</td><td>{$row['student_name']}</td><td>{$row['fee_type']}</td><td>₱{$row['amount']}</td><td>₱{$row['paid']}</td><td>₱{$balance}</td><td>{$row['school_year_term']}</td><td>{$row['date_added']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'>No document fee balances found in student_fee_items!</p>";
}

// 4. Check for specific student (02200000001)
echo "<h3>4. Check Specific Student (02200000001):</h3>";
$student_id = '02200000001';
$result = $conn->query("SELECT * FROM student_fee_items WHERE id_number = '$student_id' ORDER BY date_added DESC");
if ($result && $result->num_rows > 0) {
    echo "<table border='1'><tr><th>Fee Type</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Term</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $balance = $row['amount'] - $row['paid'];
        echo "<tr><td>{$row['fee_type']}</td><td>₱{$row['amount']}</td><td>₱{$row['paid']}</td><td>₱{$balance}</td><td>{$row['school_year_term']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'>No fee items found for student 02200000001</p>";
}

// 5. Test the create_document_balance function
echo "<h3>5. Test Balance Creation:</h3>";
include("includes/create_document_balance.php");

// Find an approved request
$result = $conn->query("SELECT * FROM document_requests WHERE status = 'Approved' LIMIT 1");
if ($result && $result->num_rows > 0) {
    $request = $result->fetch_assoc();
    echo "<p>Testing with: Student {$request['student_id']}, Document: {$request['document_type']}</p>";
    
    $success = createDocumentBalance($conn, $request['student_id'], $request['document_type']);
    
    if ($success) {
        echo "<p style='color:green'>✅ Balance creation successful!</p>";
    } else {
        echo "<p style='color:red'>❌ Balance creation failed!</p>";
    }
    
    // Check if it was actually created
    $check = $conn->query("SELECT * FROM student_fee_items WHERE id_number = '{$request['student_id']}' AND fee_type LIKE '%{$request['document_type']}%' ORDER BY date_added DESC LIMIT 1");
    if ($check && $check->num_rows > 0) {
        $created = $check->fetch_assoc();
        echo "<p style='color:green'>Found created balance:</p>";
        echo "<pre>" . print_r($created, true) . "</pre>";
    } else {
        echo "<p style='color:red'>Balance was not found in database after creation!</p>";
    }
} else {
    echo "<p>No approved requests to test with</p>";
}

// 6. Check PHP error log
echo "<h3>6. Recent PHP Errors:</h3>";
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    $errors = file($error_log);
    $recent_errors = array_slice($errors, -20);
    echo "<pre>" . implode("", $recent_errors) . "</pre>";
} else {
    echo "<p>Error log not accessible</p>";
}

?>
