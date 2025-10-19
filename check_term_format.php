<?php
// Check school year term format issue
include("StudentLogin/db_conn.php");

echo "<h2>School Year Term Format Check</h2>";

$student_id = '02200000001'; // Change to your student ID

echo "<h3>1. What term is Cashier looking for?</h3>";
$cashier_term = $conn->query("SELECT school_year_term FROM student_fee_items WHERE id_number = '$student_id' ORDER BY school_year_term DESC LIMIT 1");
if ($cashier_term && $cashier_term->num_rows > 0) {
    $term = $cashier_term->fetch_assoc();
    echo "<p style='color:green'>Cashier is looking for term: <strong>{$term['school_year_term']}</strong></p>";
    $expected_term = $term['school_year_term'];
} else {
    echo "<p style='color:orange'>No existing fee items found. Will use current year.</p>";
    $current_year = date('Y');
    $next_year = $current_year + 1;
    $expected_term = "$current_year-$next_year 1st Semester";
    echo "<p>Expected term: <strong>$expected_term</strong></p>";
}

echo "<h3>2. All unique terms in student_fee_items:</h3>";
$all_terms = $conn->query("SELECT DISTINCT school_year_term, COUNT(*) as count FROM student_fee_items GROUP BY school_year_term ORDER BY school_year_term DESC");
if ($all_terms && $all_terms->num_rows > 0) {
    echo "<table border='1'><tr><th>Term</th><th>Count</th></tr>";
    while ($row = $all_terms->fetch_assoc()) {
        echo "<tr><td>{$row['school_year_term']}</td><td>{$row['count']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>No terms found</p>";
}

echo "<h3>3. Fee items for student $student_id:</h3>";
$student_fees = $conn->query("SELECT fee_type, amount, paid, school_year_term, date_added FROM student_fee_items WHERE id_number = '$student_id' ORDER BY date_added DESC");
if ($student_fees && $student_fees->num_rows > 0) {
    echo "<table border='1'><tr><th>Fee Type</th><th>Amount</th><th>Paid</th><th>Term</th><th>Date</th></tr>";
    while ($row = $student_fees->fetch_assoc()) {
        $highlight = ($row['school_year_term'] === $expected_term) ? "style='background:lightgreen'" : "";
        echo "<tr $highlight><td>{$row['fee_type']}</td><td>₱{$row['amount']}</td><td>₱{$row['paid']}</td><td>{$row['school_year_term']}</td><td>{$row['date_added']}</td></tr>";
    }
    echo "</table>";
    echo "<p><em>Green rows match the expected term</em></p>";
} else {
    echo "<p style='color:red'>No fee items found for this student!</p>";
}

echo "<h3>4. Document Request Fees (if any):</h3>";
$doc_fees = $conn->query("SELECT * FROM student_fee_items WHERE fee_type LIKE '%Document Request Fee%' ORDER BY date_added DESC LIMIT 10");
if ($doc_fees && $doc_fees->num_rows > 0) {
    echo "<table border='1'><tr><th>Student ID</th><th>Student Name</th><th>Fee Type</th><th>Amount</th><th>Term</th><th>Date</th></tr>";
    while ($row = $doc_fees->fetch_assoc()) {
        echo "<tr><td>{$row['id_number']}</td><td>{$row['student_name']}</td><td>{$row['fee_type']}</td><td>₱{$row['amount']}</td><td>{$row['school_year_term']}</td><td>{$row['date_added']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'>No document request fees found in database!</p>";
    echo "<p>This means the balance creation is not working.</p>";
}

echo "<h3>5. Test: What term would be used for new balance?</h3>";
$test_term_query = $conn->query("SELECT school_year_term FROM student_fee_items WHERE id_number = '$student_id' ORDER BY school_year_term DESC LIMIT 1");
if ($test_term_query && $test_term_query->num_rows > 0) {
    $test_term = $test_term_query->fetch_assoc();
    echo "<p>Would use existing term: <strong>{$test_term['school_year_term']}</strong></p>";
} else {
    $current_year = date('Y');
    $next_year = $current_year + 1;
    $fallback_term = "$current_year-$next_year 1st Semester";
    echo "<p>Would use fallback term: <strong>$fallback_term</strong></p>";
}

echo "<h3>6. Recommendation:</h3>";
echo "<p>When creating a document balance, it should use term: <strong>$expected_term</strong></p>";
echo "<p>Make sure the balance is created with this exact term format.</p>";

?>
