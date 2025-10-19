<?php
session_start();
include("StudentLogin/db_conn.php");

// Require owner login
if (!isset($_SESSION['owner_id']) || $_SESSION['role'] !== 'owner') {
    die("Unauthorized access. Please login as Owner.");
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Create 2nd Semester Records</title>
    <script src='https://cdn.tailwindcss.com'></script>
</head>
<body class='bg-gray-100 p-8'>
    <div class='max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-8'>";

echo "<h1 class='text-2xl font-bold mb-4'>Creating 2nd Semester Records...</h1>";

// Step 1: Check current state
echo "<h2 class='text-xl font-bold mt-6 mb-3'>Step 1: Current State</h2>";
$check = $conn->query("SELECT school_year, term, COUNT(*) as count FROM tuition_fee_structure GROUP BY school_year, term ORDER BY school_year DESC, term");
echo "<table class='w-full border mb-4'>";
echo "<tr class='bg-gray-200'><th class='border p-2'>School Year</th><th class='border p-2'>Term</th><th class='border p-2'>Count</th></tr>";
while ($row = $check->fetch_assoc()) {
    echo "<tr><td class='border p-2'>{$row['school_year']}</td><td class='border p-2'>{$row['term']}</td><td class='border p-2'>{$row['count']}</td></tr>";
}
echo "</table>";

// Step 2: Create 2nd Semester records
echo "<h2 class='text-xl font-bold mt-6 mb-3'>Step 2: Creating 2nd Semester Records...</h2>";

$sql = "INSERT IGNORE INTO tuition_fee_structure 
    (grade_level, academic_track, tuition_fee, other_fees, total_fee, school_year, term, created_at, updated_at)
SELECT 
    grade_level,
    academic_track,
    tuition_fee,
    other_fees,
    total_fee,
    school_year,
    '2nd Semester',
    NOW(),
    NOW()
FROM tuition_fee_structure
WHERE term = '1st Semester'";

if ($conn->query($sql)) {
    $inserted = $conn->affected_rows;
    echo "<p class='text-green-600 font-bold'>✓ Success! Created $inserted new 2nd Semester records</p>";
} else {
    echo "<p class='text-red-600 font-bold'>✗ Error: " . $conn->error . "</p>";
}

// Step 3: Show final state
echo "<h2 class='text-xl font-bold mt-6 mb-3'>Step 3: Final State</h2>";
$final = $conn->query("SELECT school_year, term, COUNT(*) as count FROM tuition_fee_structure GROUP BY school_year, term ORDER BY school_year DESC, term");
echo "<table class='w-full border mb-4'>";
echo "<tr class='bg-gray-200'><th class='border p-2'>School Year</th><th class='border p-2'>Term</th><th class='border p-2'>Count</th></tr>";
while ($row = $final->fetch_assoc()) {
    $color = $row['term'] == '2nd Semester' ? 'bg-green-100' : '';
    echo "<tr class='$color'><td class='border p-2'>{$row['school_year']}</td><td class='border p-2'>{$row['term']}</td><td class='border p-2'>{$row['count']}</td></tr>";
}
echo "</table>";

// Step 4: Verify by showing sample 2nd semester records
echo "<h2 class='text-xl font-bold mt-6 mb-3'>Step 4: Sample 2nd Semester Records</h2>";
$sample = $conn->query("SELECT * FROM tuition_fee_structure WHERE term = '2nd Semester' LIMIT 5");
echo "<table class='w-full border text-sm'>";
echo "<tr class='bg-gray-200'><th class='border p-2'>Grade</th><th class='border p-2'>Track</th><th class='border p-2'>School Year</th><th class='border p-2'>Term</th><th class='border p-2'>Tuition</th></tr>";
while ($row = $sample->fetch_assoc()) {
    echo "<tr><td class='border p-2'>{$row['grade_level']}</td><td class='border p-2'>{$row['academic_track']}</td><td class='border p-2'>{$row['school_year']}</td><td class='border p-2'>{$row['term']}</td><td class='border p-2'>₱{$row['tuition_fee']}</td></tr>";
}
echo "</table>";

echo "<div class='mt-8 p-4 bg-green-100 border border-green-400 rounded'>
    <h3 class='font-bold text-green-800 mb-2'>✓ Done!</h3>
    <p class='text-green-700'>All school years now have 2nd Semester records.</p>
    <p class='text-green-700 mt-2'>Go to Owner Dashboard → Tuition Fees and filter by '2nd Semester' to see them!</p>
</div>";

echo "<div class='mt-4'>
    <a href='OwnerF/Dashboard.php' class='inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700'>
        Go to Dashboard →
    </a>
</div>";

echo "</div></body></html>";

$conn->close();
?>
