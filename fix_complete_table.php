<?php
// Complete fix for student_fee_items table
include("StudentLogin/db_conn.php");

echo "<h2>Complete Fix for student_fee_items Table</h2>";

// Step 1: Check current structure
echo "<h3>Step 1: Current Table Structure</h3>";
$result = $conn->query("DESCRIBE student_fee_items");
$existing_columns = [];
if ($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $existing_columns[] = $row['Field'];
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Default']}</td></tr>";
    }
    echo "</table>";
}

// Step 2: Check which columns are missing
echo "<h3>Step 2: Check Missing Columns</h3>";
$required_columns = [
    'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
    'id_number' => 'VARCHAR(50)',
    'student_name' => 'VARCHAR(200)',
    'grade_level' => 'VARCHAR(50)',
    'school_year_term' => 'VARCHAR(50)',
    'fee_type' => 'VARCHAR(200)',
    'amount' => 'DECIMAL(10,2)',
    'paid' => 'DECIMAL(10,2) DEFAULT 0',
    'date_added' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
];

$missing_columns = [];
foreach ($required_columns as $col => $type) {
    if (!in_array($col, $existing_columns)) {
        $missing_columns[$col] = $type;
        echo "<p style='color:red'>❌ Missing: $col ($type)</p>";
    } else {
        echo "<p style='color:green'>✅ Exists: $col</p>";
    }
}

// Step 3: Add missing columns
if (!empty($missing_columns)) {
    echo "<h3>Step 3: Adding Missing Columns</h3>";
    foreach ($missing_columns as $col => $type) {
        echo "<p>Adding column: $col...</p>";
        
        // Skip id column as it should already exist
        if ($col === 'id') continue;
        
        $sql = "ALTER TABLE student_fee_items ADD COLUMN $col $type";
        if ($conn->query($sql)) {
            echo "<p style='color:green'>✅ Added: $col</p>";
        } else {
            echo "<p style='color:red'>❌ Failed to add $col: " . $conn->error . "</p>";
        }
    }
} else {
    echo "<h3>Step 3: All Columns Present</h3>";
    echo "<p style='color:green'>✅ No missing columns!</p>";
}

// Step 4: Verify final structure
echo "<h3>Step 4: Final Table Structure</h3>";
$result = $conn->query("DESCRIBE student_fee_items");
if ($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $highlight = in_array($row['Field'], array_keys($missing_columns)) ? "style='background:lightgreen'" : "";
        echo "<tr $highlight><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Default']}</td></tr>";
    }
    echo "</table>";
    echo "<p><em>Green rows are newly added columns</em></p>";
}

// Step 5: Create a test balance
echo "<h3>Step 5: Create Test Balance</h3>";
$test_student_id = '02200000001';
$test_document = 'Form137';

// Get document fee
$fee_check = $conn->query("SELECT fee_amount FROM document_fees WHERE document_name = '$test_document' AND is_active = 1");
if ($fee_check && $fee_check->num_rows > 0) {
    $fee_row = $fee_check->fetch_assoc();
    $fee_amount = $fee_row['fee_amount'];
    
    // Get student info
    $student_check = $conn->query("SELECT CONCAT(first_name, ' ', last_name) as full_name, grade_level FROM student_account WHERE id_number = '$test_student_id'");
    if ($student_check && $student_check->num_rows > 0) {
        $student = $student_check->fetch_assoc();
        $student_name = $student['full_name'];
        $grade_level = $student['grade_level'];
        
        // Get term
        $term_check = $conn->query("SELECT school_year_term FROM student_fee_items WHERE id_number = '$test_student_id' ORDER BY id DESC LIMIT 1");
        if ($term_check && $term_check->num_rows > 0) {
            $term_row = $term_check->fetch_assoc();
            $school_year_term = $term_row['school_year_term'];
        } else {
            $school_year_term = "2025-2026 1st Semester";
        }
        
        echo "<p>Creating balance:</p>";
        echo "<ul>";
        echo "<li>Student: $student_name ($test_student_id)</li>";
        echo "<li>Grade: $grade_level</li>";
        echo "<li>Term: $school_year_term</li>";
        echo "<li>Document: $test_document</li>";
        echo "<li>Amount: ₱$fee_amount</li>";
        echo "</ul>";
        
        $fee_description = "Document Request Fee - $test_document";
        $stmt = $conn->prepare("INSERT INTO student_fee_items (id_number, student_name, grade_level, school_year_term, fee_type, amount, paid) VALUES (?, ?, ?, ?, ?, ?, 0)");
        $stmt->bind_param("sssssd", $test_student_id, $student_name, $grade_level, $school_year_term, $fee_description, $fee_amount);
        
        if ($stmt->execute()) {
            $insert_id = $conn->insert_id;
            echo "<p style='color:green; font-size:20px'>✅ Balance created successfully! ID: $insert_id</p>";
            
            // Show the created balance
            $verify = $conn->query("SELECT * FROM student_fee_items WHERE id = $insert_id");
            if ($verify && $verify->num_rows > 0) {
                $created = $verify->fetch_assoc();
                echo "<table border='1'>";
                foreach ($created as $key => $value) {
                    echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
                }
                echo "</table>";
            }
        } else {
            echo "<p style='color:red'>❌ Failed to create balance: " . $stmt->error . "</p>";
        }
    } else {
        echo "<p style='color:red'>Student not found</p>";
    }
} else {
    echo "<p style='color:red'>Document fee not configured</p>";
}

// Step 6: Show all balances
echo "<h3>Step 6: All Balances for Student $test_student_id</h3>";
$all = $conn->query("SELECT * FROM student_fee_items WHERE id_number = '$test_student_id' ORDER BY id DESC");
if ($all && $all->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Fee Type</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Term</th></tr>";
    while ($row = $all->fetch_assoc()) {
        $balance = $row['amount'] - $row['paid'];
        echo "<tr><td>{$row['id']}</td><td>{$row['fee_type']}</td><td>₱{$row['amount']}</td><td>₱{$row['paid']}</td><td>₱$balance</td><td>{$row['school_year_term']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>No balances found</p>";
}

echo "<h2 style='color:green'>✅ DONE! Now check Cashier system.</h2>";
echo "<p>The balance should now appear when you search for student: $test_student_id</p>";
?>
