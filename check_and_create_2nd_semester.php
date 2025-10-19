<?php
session_start();
include("StudentLogin/db_conn.php");

// Require owner login
if (!isset($_SESSION['owner_id']) || $_SESSION['role'] !== 'owner') {
    die("Unauthorized. Please login as Owner first.");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Check & Create 2nd Semester</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-6xl mx-auto bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-3xl font-bold mb-6">Check & Create 2nd Semester Records</h1>

        <?php
        // Step 1: Check what exists
        echo "<div class='mb-8'>";
        echo "<h2 class='text-2xl font-bold mb-4'>Step 1: Current Database State</h2>";
        
        $query = "SELECT school_year, term, COUNT(*) as count 
                  FROM tuition_fee_structure 
                  GROUP BY school_year, term 
                  ORDER BY school_year DESC, term";
        $result = $conn->query($query);
        
        echo "<table class='w-full border-collapse border'>";
        echo "<thead><tr class='bg-blue-600 text-white'>";
        echo "<th class='border p-3'>School Year</th>";
        echo "<th class='border p-3'>Term</th>";
        echo "<th class='border p-3'>Count</th>";
        echo "</tr></thead><tbody>";
        
        $has_2nd_semester = false;
        while ($row = $result->fetch_assoc()) {
            $bg = $row['term'] == '2nd Semester' ? 'bg-green-100' : '';
            echo "<tr class='$bg'>";
            echo "<td class='border p-3 text-center'>{$row['school_year']}</td>";
            echo "<td class='border p-3 text-center'>{$row['term']}</td>";
            echo "<td class='border p-3 text-center font-bold'>{$row['count']}</td>";
            echo "</tr>";
            if ($row['term'] == '2nd Semester') $has_2nd_semester = true;
        }
        echo "</tbody></table>";
        echo "</div>";

        // Step 2: Check if we need to create 2nd semester
        if (!$has_2nd_semester) {
            echo "<div class='mb-8 p-4 bg-yellow-100 border-l-4 border-yellow-500'>";
            echo "<p class='font-bold text-yellow-800'>⚠️ No 2nd Semester records found!</p>";
            echo "<p class='text-yellow-700'>Creating them now...</p>";
            echo "</div>";

            // Create 2nd semester records
            echo "<div class='mb-8'>";
            echo "<h2 class='text-2xl font-bold mb-4'>Step 2: Creating 2nd Semester Records</h2>";
            
            $create_sql = "INSERT IGNORE INTO tuition_fee_structure 
                          (grade_level, academic_track, tuition_fee, other_fees, total_fee, school_year, term, created_at, updated_at)
                          SELECT grade_level, academic_track, tuition_fee, other_fees, total_fee, school_year, '2nd Semester', NOW(), NOW()
                          FROM tuition_fee_structure 
                          WHERE term = '1st Semester'";
            
            if ($conn->query($create_sql)) {
                $inserted = $conn->affected_rows;
                echo "<div class='p-4 bg-green-100 border-l-4 border-green-500'>";
                echo "<p class='font-bold text-green-800'>✓ Success!</p>";
                echo "<p class='text-green-700'>Created <strong>$inserted</strong> new 2nd Semester records</p>";
                echo "</div>";
            } else {
                echo "<div class='p-4 bg-red-100 border-l-4 border-red-500'>";
                echo "<p class='font-bold text-red-800'>✗ Error!</p>";
                echo "<p class='text-red-700'>" . $conn->error . "</p>";
                echo "</div>";
            }
            echo "</div>";

            // Show updated state
            echo "<div class='mb-8'>";
            echo "<h2 class='text-2xl font-bold mb-4'>Step 3: Updated Database State</h2>";
            
            $result2 = $conn->query($query);
            echo "<table class='w-full border-collapse border'>";
            echo "<thead><tr class='bg-blue-600 text-white'>";
            echo "<th class='border p-3'>School Year</th>";
            echo "<th class='border p-3'>Term</th>";
            echo "<th class='border p-3'>Count</th>";
            echo "</tr></thead><tbody>";
            
            while ($row = $result2->fetch_assoc()) {
                $bg = $row['term'] == '2nd Semester' ? 'bg-green-100' : '';
                echo "<tr class='$bg'>";
                echo "<td class='border p-3 text-center'>{$row['school_year']}</td>";
                echo "<td class='border p-3 text-center'>{$row['term']}</td>";
                echo "<td class='border p-3 text-center font-bold'>{$row['count']}</td>";
                echo "</tr>";
            }
            echo "</tbody></table>";
            echo "</div>";

            // Show sample records
            echo "<div class='mb-8'>";
            echo "<h2 class='text-2xl font-bold mb-4'>Step 4: Sample 2nd Semester Records</h2>";
            
            $sample = $conn->query("SELECT * FROM tuition_fee_structure WHERE term = '2nd Semester' ORDER BY school_year DESC, grade_level LIMIT 10");
            echo "<table class='w-full border-collapse border text-sm'>";
            echo "<thead><tr class='bg-blue-600 text-white'>";
            echo "<th class='border p-2'>Grade</th>";
            echo "<th class='border p-2'>Track</th>";
            echo "<th class='border p-2'>School Year</th>";
            echo "<th class='border p-2'>Term</th>";
            echo "<th class='border p-2'>Tuition</th>";
            echo "<th class='border p-2'>Other Fees</th>";
            echo "<th class='border p-2'>Total</th>";
            echo "</tr></thead><tbody>";
            
            while ($row = $sample->fetch_assoc()) {
                echo "<tr>";
                echo "<td class='border p-2'>{$row['grade_level']}</td>";
                echo "<td class='border p-2'>{$row['academic_track']}</td>";
                echo "<td class='border p-2'>{$row['school_year']}</td>";
                echo "<td class='border p-2 font-bold text-green-600'>{$row['term']}</td>";
                echo "<td class='border p-2'>₱" . number_format($row['tuition_fee'], 2) . "</td>";
                echo "<td class='border p-2'>₱" . number_format($row['other_fees'], 2) . "</td>";
                echo "<td class='border p-2 font-bold'>₱" . number_format($row['total_fee'], 2) . "</td>";
                echo "</tr>";
            }
            echo "</tbody></table>";
            echo "</div>";

        } else {
            echo "<div class='mb-8 p-4 bg-green-100 border-l-4 border-green-500'>";
            echo "<p class='font-bold text-green-800'>✓ 2nd Semester records already exist!</p>";
            echo "<p class='text-green-700'>Your database is properly configured.</p>";
            echo "</div>";
        }

        $conn->close();
        ?>

        <div class="mt-8 p-6 bg-blue-50 border-2 border-blue-300 rounded-lg">
            <h3 class="text-xl font-bold text-blue-900 mb-3">✓ All Done!</h3>
            <p class="text-blue-800 mb-4">Your database now has 2nd Semester records for all school years.</p>
            <p class="text-blue-800 mb-4">Go to your Owner Dashboard and filter by "2nd Semester" to see them!</p>
            <a href="OwnerF/Dashboard.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-bold transition">
                Go to Dashboard →
            </a>
        </div>
    </div>
</body>
</html>
