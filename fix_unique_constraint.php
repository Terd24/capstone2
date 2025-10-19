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
    <title>Fix Unique Constraint</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-3xl font-bold mb-6">Fix Database Constraint</h1>

        <div class="mb-6 p-4 bg-yellow-100 border-l-4 border-yellow-500">
            <p class="font-bold text-yellow-800">Problem Found!</p>
            <p class="text-yellow-700">The database has a unique constraint that prevents multiple terms for the same grade/track/year.</p>
            <p class="text-yellow-700 mt-2">We need to drop this constraint and create a new one that includes the term field.</p>
        </div>

        <?php
        echo "<h2 class='text-2xl font-bold mb-4'>Step 1: Drop Old Constraint</h2>";
        
        $drop = $conn->query("ALTER TABLE tuition_fee_structure DROP INDEX unique_grade_track_year");
        
        if ($drop) {
            echo "<div class='p-4 mb-6 bg-green-100 border-l-4 border-green-500'>";
            echo "<p class='font-bold text-green-800'>✓ Success!</p>";
            echo "<p class='text-green-700'>Dropped old unique constraint</p>";
            echo "</div>";
        } else {
            echo "<div class='p-4 mb-6 bg-red-100 border-l-4 border-red-500'>";
            echo "<p class='font-bold text-red-800'>✗ Error!</p>";
            echo "<p class='text-red-700'>" . $conn->error . "</p>";
            echo "</div>";
        }

        echo "<h2 class='text-2xl font-bold mb-4'>Step 2: Create New Constraint (with term)</h2>";
        
        $create = $conn->query("ALTER TABLE tuition_fee_structure 
                               ADD UNIQUE KEY unique_grade_track_year_term (grade_level, academic_track, school_year, term)");
        
        if ($create) {
            echo "<div class='p-4 mb-6 bg-green-100 border-l-4 border-green-500'>";
            echo "<p class='font-bold text-green-800'>✓ Success!</p>";
            echo "<p class='text-green-700'>Created new unique constraint that includes term field</p>";
            echo "</div>";
        } else {
            echo "<div class='p-4 mb-6 bg-red-100 border-l-4 border-red-500'>";
            echo "<p class='font-bold text-red-800'>✗ Error!</p>";
            echo "<p class='text-red-700'>" . $conn->error . "</p>";
            echo "</div>";
        }

        echo "<h2 class='text-2xl font-bold mb-4'>Step 3: Create 2nd Semester Records</h2>";
        
        $first_sem = $conn->query("SELECT * FROM tuition_fee_structure WHERE term = '1st Semester'");
        $inserted = 0;
        
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
            
            if ($insert->execute() && $insert->affected_rows > 0) {
                $inserted++;
            }
        }

        echo "<div class='p-4 mb-6 bg-green-100 border-l-4 border-green-500'>";
        echo "<p class='font-bold text-green-800'>✓ Success!</p>";
        echo "<p class='text-green-700'>Created <strong>$inserted</strong> new 2nd Semester records</p>";
        echo "</div>";

        echo "<h2 class='text-2xl font-bold mb-4'>Step 4: Verify Results</h2>";
        
        $final = $conn->query("SELECT school_year, term, COUNT(*) as count FROM tuition_fee_structure GROUP BY school_year, term ORDER BY school_year DESC, term");
        echo "<table class='w-full border mb-6'>";
        echo "<tr class='bg-blue-600 text-white'><th class='border p-3'>School Year</th><th class='border p-3'>Term</th><th class='border p-3'>Count</th></tr>";
        while ($row = $final->fetch_assoc()) {
            $bg = $row['term'] == '2nd Semester' ? 'bg-green-100' : '';
            echo "<tr class='$bg'><td class='border p-3 text-center'>{$row['school_year']}</td><td class='border p-3 text-center font-bold'>{$row['term']}</td><td class='border p-3 text-center text-2xl font-bold text-green-600'>{$row['count']}</td></tr>";
        }
        echo "</table>";

        $conn->close();
        ?>

        <div class="mt-8 p-6 bg-blue-50 border-2 border-blue-300 rounded-lg">
            <h3 class="text-2xl font-bold text-blue-900 mb-3">✓ All Done!</h3>
            <p class="text-blue-800 mb-2">The database constraint has been fixed.</p>
            <p class="text-blue-800 mb-2">2nd Semester records have been created.</p>
            <p class="text-blue-800 mb-4">Now you can filter by "2nd Semester" in the Owner Dashboard!</p>
            <a href="OwnerF/Dashboard.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-8 py-4 rounded-lg font-bold text-lg">
                Go to Dashboard →
            </a>
        </div>
    </div>
</body>
</html>
