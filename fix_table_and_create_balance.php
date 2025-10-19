<?php
// Complete fix: Add missing column and create balance
include("StudentLogin/db_conn.php");

echo "<h2>Fix student_fee_items Table and Create Balance</h2>";

// Step 1: Check current table structure
echo "<h3>Step 1: Check Current Table Structure</h3>";
$result = $conn->query("DESCRIBE student_fee_items");
if ($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    $has_date_added = false;
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td></tr>";
        if ($row['Field'] === 'date_added') {
            $has_date_added = true;
        }
    }
    echo "</table>";
    
    if ($has_date_added) {
        echo "<p style='color:green'>✅ date_added column exists</p>";
    } else {
        echo "<p style='color:red'>❌ date_added column is MISSING</p>";
    }
} else {
    echo "<p style='color:red'>Error checking table structure</p>";
}

// Step 2: Add date_added column if missing
echo "<h3>Step 2: Add Missing Column (if needed)</h3>";
$check = $conn->query("SHOW COLUMNS FROM student_fee_items LIKE 'date_added'");
if ($check && $check->num_rows === 0) {
    echo "<p>Adding date_added column...</p>";
    $add_column = $conn->query("ALTER TABLE student_fee_items ADD COLUMN date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    if ($add_column) {
        echo "<p style='color:green'>✅ Column added successfully!</p>";
    } else {
        echo "<p style='color:red'>❌ Failed to add column: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color:green'>✅ Column already exists, no need to add</p>";
}

// Step 3: Verify table structure now
echo "<h3>Step 3: Verify Table Structure</h3>";
$result = $conn->query("DESCRIBE student_fee_items");
if ($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $highlight = ($row['Field'] === 'date_added') ? "style='background:lightgreen'" : "";
        echo "<tr $highlight><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
    }
    echo "</table>";
}

// Step 4: Test balance creation
echo "<h3>Step 4: Test Balance Creation</h3>";
$test_student_id = '02200000001';
$test_document = 'Form137';

echo "<p>Testing with Student: $test_student_id, Document: $test_document</p>";

// Check if document fee exists
$fee_check = $conn->query("SELECT fee_amount FROM document_fees WHERE document_name = '$test_document' AND is_active = 1");
if ($fee_check && $fee_check->num_rows > 0) {
    $fee_row = $fee_check->fetch_assoc();
    $fee_amount = $fee_row['fee_amount'];
    echo "<p>Document fee: ₱$fee_amount</p>";
    
    if ($fee_amount > 0) {
        // Get student info
        $student_check = $conn->query("SELECT CONCAT(first_name, ' ', last_name) as full_name, grade_level FROM student_account WHERE id_number = '$test_student_id'");
        if ($student_check && $student_check->num_rows > 0) {
            $student = $student_check->fetch_assoc();
            $student_name = $student['full_name'];
            $grade_level = $student['grade_level'];
            
            // Get term
            $term_check = $conn->query("SELECT school_year_term FROM student_fee_items WHERE id_number = '$test_student_id' ORDER BY school_year_term DESC LIMIT 1");
            if ($term_check && $term_check->num_rows > 0) {
                $term_row = $term_check->fetch_assoc();
                $school_year_term = $term_row['school_year_term'];
            } else {
                $current_year = date('Y');
                $next_year = $current_year + 1;
                $school_year_term = "$current_year-$next_year 1st Semester";
            }
            
            echo "<p>Creating balance with:</p>";
            echo "<ul>";
            echo "<li>Student: $student_name</li>";
            echo "<li>Grade: $grade_level</li>";
            echo "<li>Term: $school_year_term</li>";
            echo "<li>Amount: ₱$fee_amount</li>";
            echo "</ul>";
            
            // Create balance
            $fee_description = "Document Request Fee - $test_document";
            $stmt = $conn->prepare("INSERT INTO student_fee_items (id_number, student_name, grade_level, school_year_term, fee_type, amount, paid) VALUES (?, ?, ?, ?, ?, ?, 0)");
            $stmt->bind_param("sssssd", $test_student_id, $student_name, $grade_level, $school_year_term, $fee_description, $fee_amount);
            
            if ($stmt->execute()) {
                $insert_id = $conn->insert_id;
                echo "<p style='color:green; font-size:18px'>✅ Balance created successfully! ID: $insert_id</p>";
                
                // Verify
                $verify = $conn->query("SELECT * FROM student_fee_items WHERE id = $insert_id");
                if ($verify && $verify->num_rows > 0) {
                    $created = $verify->fetch_assoc();
                    echo "<p style='color:green'>✅ Verified in database:</p>";
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
        echo "<p>Fee is ₱0, no balance needed</p>";
    }
} else {
    echo "<p style='color:red'>Document fee not configured</p>";
}

// Step 5: Show all balances for this student
echo "<h3>Step 5: All Balances for Student $test_student_id</h3>";
$all_balances = $conn->query("SELECT * FROM student_fee_items WHERE id_number = '$test_student_id' ORDER BY id DESC");
if ($all_balances && $all_balances->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Fee Type</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Term</th><th>Date Added</th></tr>";
    while ($row = $all_balances->fetch_assoc()) {
        $balance = $row['amount'] - $row['paid'];
        $date_added = isset($row['date_added']) ? $row['date_added'] : 'N/A';
        echo "<tr><td>{$row['id']}</td><td>{$row['fee_type']}</td><td>₱{$row['amount']}</td><td>₱{$row['paid']}</td><td>₱$balance</td><td>{$row['school_year_term']}</td><td>$date_added</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>No balances found</p>";
}

echo "<h3>✅ Done! Now check the Cashier system.</h3>";
?>
