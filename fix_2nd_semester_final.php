<?php
session_start();
include("StudentLogin/db_conn.php");

if (!isset($_SESSION['owner_id']) || $_SESSION['role'] !== 'owner') {
    die("Unauthorized");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix 2nd Semester - Final</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-6xl mx-auto bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-3xl font-bold mb-6">Fix 2nd Semester Records - Final Solution</h1>

        <?php
        // Check table structure
        echo "<h2 class='text-2xl font-bold mb-4'>Checking Table Structure...</h2>";
        $structure = $conn->query("DESCRIBE tuition_fee_structure");
        echo "<table class='w-full border mb-6 text-sm'>";
        echo "<tr class='bg-gray-200'><th class='border p-2'>Field</th><th class='border p-2'>Type</th><th class='border p-2'>Key</th></tr>";
        while ($row = $structure->fetch_assoc()) {
            echo "<tr><td class='border p-2'>{$row['Field']}</td><td class='border p-2'>{$row['Type']}</td><td class='border p-2'>{$row['Key']}</td></tr>";
        }
        echo "</table>";

        // Check for unique constraints
        echo "<h2 class='text-2xl font-bold mb-4'>Checking Constraints...</h2>";
        $indexes = $conn->query("SHOW INDEX FROM tuition_fee_structure");
        echo "<table class='w-full border mb-6 text-sm'>";
        echo "<tr class='bg-gray-200'><th class='border p-2'>Key Name</th><th class='border p-2'>Column</th><th class='border p-2'>Non Unique</th></tr>";
        while ($row = $indexes->fetch_assoc()) {
            echo "<tr><td class='border p-2'>{$row['Key_name']}</td><td class='border p-2'>{$row['Column_name']}</td><td class='border p-2'>{$row['Non_unique']}</td></tr>";
        }
        echo "</table>";

        // Show current data
        echo "<h2 class='text-2xl font-bold mb-4'>Current Data (All Records)</h2>";
        $all = $conn->query("SELECT * FROM tuition_fee_structure ORDER BY school_year DESC, term, grade_level LIMIT 20");
        echo "<table class='w-full border mb-6 text-xs'>";
        echo "<tr class='bg-gray-200'><th class='border p-1'>ID</th><th class='border p-1'>Grade</th><th class='border p-1'>Track</th><th class='border p-1'>Year</th><th class='border p-1'>Term</th><th class='border p-1'>Fee</th></tr>";
        while ($row = $all->fetch_assoc()) {
            $bg = $row['term'] == '2nd Semester' ? 'bg-green-100' : '';
            echo "<tr class='$bg'><td class='border p-1'>{$row['id']}</td><td class='border p-1'>{$row['grade_level']}</td><td class='border p-1 text-xs'>{$row['academic_track']}</td><td class='border p-1'>{$row['school_year']}</td><td class='border p-1 font-bold'>{$row['term']}</td><td class='border p-1'>₱{$row['total_fee']}</td></tr>";
        }
        echo "</table>";

        // Try different approach - delete and recreate
        echo "<h2 class='text-2xl font-bold mb-4 text-red-600'>Attempting to Create 2nd Semester Records...</h2>";
        
        // First, let's try to insert one by one
        $first_sem = $conn->query("SELECT * FROM tuition_fee_structure WHERE term = '1st Semester'");
        $inserted = 0;
        $errors = [];
        
        while ($row = $first_sem->fetch_assoc()) {
            $insert = $conn->prepare("INSERT INTO tuition_fee_structure 
                (grade_level, academic_track, tuition_fee, other_fees, total_fee, school_year, term, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, '2nd Semester', NOW(), NOW())");
            
            $insert->bind_param("ssddds", 
                $row['grade_level'], 
                $row['academic_track'], 
                $row['tuition_fee'], 
                $row['other_fees'], 
                $row['total_fee'], 
                $row['school_year']
            );
            
            if ($insert->execute()) {
                if ($insert->affected_rows > 0) {
                    $inserted++;
                }
            } else {
                $errors[] = $conn->error;
            }
        }

        echo "<div class='p-4 mb-6 " . ($inserted > 0 ? "bg-green-100 border-green-500" : "bg-red-100 border-red-500") . " border-l-4'>";
        echo "<p class='font-bold'>" . ($inserted > 0 ? "✓ Success!" : "✗ Failed") . "</p>";
        echo "<p>Inserted: <strong>$inserted</strong> records</p>";
        if (!empty($errors)) {
            echo "<p class='text-red-600 text-sm mt-2'>Errors: " . implode(", ", array_unique($errors)) . "</p>";
        }
        echo "</div>";

        // Show final state
        echo "<h2 class='text-2xl font-bold mb-4'>Final State</h2>";
        $final = $conn->query("SELECT school_year, term, COUNT(*) as count FROM tuition_fee_structure GROUP BY school_year, term ORDER BY school_year DESC, term");
        echo "<table class='w-full border mb-6'>";
        echo "<tr class='bg-blue-600 text-white'><th class='border p-3'>School Year</th><th class='border p-3'>Term</th><th class='border p-3'>Count</th></tr>";
        while ($row = $final->fetch_assoc()) {
            $bg = $row['term'] == '2nd Semester' ? 'bg-green-100' : '';
            echo "<tr class='$bg'><td class='border p-3 text-center'>{$row['school_year']}</td><td class='border p-3 text-center font-bold'>{$row['term']}</td><td class='border p-3 text-center text-xl font-bold'>{$row['count']}</td></tr>";
        }
        echo "</table>";

        $conn->close();
        ?>

        <div class="mt-8 p-6 bg-blue-50 border-2 border-blue-300 rounded-lg">
            <h3 class="text-xl font-bold text-blue-900 mb-3">Next Steps</h3>
            <p class="text-blue-800 mb-4">Go to your Owner Dashboard and check if 2nd Semester now shows records.</p>
            <a href="OwnerF/Dashboard.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-bold">
                Go to Dashboard →
            </a>
        </div>
    </div>
</body>
</html>
