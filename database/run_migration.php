<?php
/**
 * Database Migration Runner
 * Executes the grading system migration script
 */

include('../StudentLogin/db_conn.php');

echo "<!DOCTYPE html><html><head><title>Database Migration</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .warning{color:orange;} table{border-collapse:collapse;margin:10px 0;} td,th{border:1px solid #ddd;padding:8px;}</style>";
echo "</head><body>";

echo "<h2>K-12 vs College Grading System Migration</h2>";
echo "<p>Starting migration...</p><hr>";

$success_count = 0;
$error_count = 0;
$warning_count = 0;

// Step 1: Add first_quarter column
echo "<p><strong>Step 1:</strong> Adding first_quarter column...</p>";
$result = $conn->query("ALTER TABLE grades_record ADD COLUMN first_quarter DECIMAL(5,2) DEFAULT NULL");
if ($result) {
    echo "<p class='success'>✓ first_quarter column added</p>";
    $success_count++;
} else {
    if (strpos($conn->error, 'Duplicate column') !== false) {
        echo "<p class='warning'>⚠ first_quarter column already exists (skipping)</p>";
        $warning_count++;
    } else {
        echo "<p class='error'>✗ Error: " . $conn->error . "</p>";
        $error_count++;
    }
}

// Step 2: Add second_quarter column
echo "<p><strong>Step 2:</strong> Adding second_quarter column...</p>";
$result = $conn->query("ALTER TABLE grades_record ADD COLUMN second_quarter DECIMAL(5,2) DEFAULT NULL");
if ($result) {
    echo "<p class='success'>✓ second_quarter column added</p>";
    $success_count++;
} else {
    if (strpos($conn->error, 'Duplicate column') !== false) {
        echo "<p class='warning'>⚠ second_quarter column already exists (skipping)</p>";
        $warning_count++;
    } else {
        echo "<p class='error'>✗ Error: " . $conn->error . "</p>";
        $error_count++;
    }
}

// Step 3: Add third_quarter column
echo "<p><strong>Step 3:</strong> Adding third_quarter column...</p>";
$result = $conn->query("ALTER TABLE grades_record ADD COLUMN third_quarter DECIMAL(5,2) DEFAULT NULL");
if ($result) {
    echo "<p class='success'>✓ third_quarter column added</p>";
    $success_count++;
} else {
    if (strpos($conn->error, 'Duplicate column') !== false) {
        echo "<p class='warning'>⚠ third_quarter column already exists (skipping)</p>";
        $warning_count++;
    } else {
        echo "<p class='error'>✗ Error: " . $conn->error . "</p>";
        $error_count++;
    }
}

// Step 4: Add fourth_quarter column
echo "<p><strong>Step 4:</strong> Adding fourth_quarter column...</p>";
$result = $conn->query("ALTER TABLE grades_record ADD COLUMN fourth_quarter DECIMAL(5,2) DEFAULT NULL");
if ($result) {
    echo "<p class='success'>✓ fourth_quarter column added</p>";
    $success_count++;
} else {
    if (strpos($conn->error, 'Duplicate column') !== false) {
        echo "<p class='warning'>⚠ fourth_quarter column already exists (skipping)</p>";
        $warning_count++;
    } else {
        echo "<p class='error'>✗ Error: " . $conn->error . "</p>";
        $error_count++;
    }
}

// Step 5: Add grading_system column
echo "<p><strong>Step 5:</strong> Adding grading_system column...</p>";
$result = $conn->query("ALTER TABLE grades_record ADD COLUMN grading_system VARCHAR(10) DEFAULT NULL");
if ($result) {
    echo "<p class='success'>✓ grading_system column added</p>";
    $success_count++;
} else {
    if (strpos($conn->error, 'Duplicate column') !== false) {
        echo "<p class='warning'>⚠ grading_system column already exists (skipping)</p>";
        $warning_count++;
    } else {
        echo "<p class='error'>✗ Error: " . $conn->error . "</p>";
        $error_count++;
    }
}

// Step 6: Add index for grading_system
echo "<p><strong>Step 6:</strong> Adding index for grading_system...</p>";
$result = $conn->query("ALTER TABLE grades_record ADD INDEX idx_grading_system (grading_system)");
if ($result) {
    echo "<p class='success'>✓ Index added for grading_system</p>";
    $success_count++;
} else {
    if (strpos($conn->error, 'Duplicate key') !== false) {
        echo "<p class='warning'>⚠ Index already exists (skipping)</p>";
        $warning_count++;
    } else {
        echo "<p class='error'>✗ Error: " . $conn->error . "</p>";
        $error_count++;
    }
}

// Step 7: Add composite index
echo "<p><strong>Step 7:</strong> Adding composite index...</p>";
$result = $conn->query("ALTER TABLE grades_record ADD INDEX idx_student_subject_term (id_number, subject, school_year_term)");
if ($result) {
    echo "<p class='success'>✓ Composite index added</p>";
    $success_count++;
} else {
    if (strpos($conn->error, 'Duplicate key') !== false) {
        echo "<p class='warning'>⚠ Composite index already exists (skipping)</p>";
        $warning_count++;
    } else {
        echo "<p class='error'>✗ Error: " . $conn->error . "</p>";
        $error_count++;
    }
}

// Step 8: Update existing records
echo "<p><strong>Step 8:</strong> Updating existing records with grading_system...</p>";
$update_sql = "UPDATE grades_record gr
INNER JOIN student_account sa ON gr.id_number = sa.id_number
SET gr.grading_system = CASE
    WHEN LOWER(sa.grade_level) LIKE '%kinder%' THEN 'K12'
    WHEN LOWER(sa.grade_level) REGEXP 'grade[[:space:]]*[1-9]' THEN 'K12'
    WHEN LOWER(sa.grade_level) REGEXP 'grade[[:space:]]*1[0-2]' THEN 'K12'
    WHEN LOWER(sa.grade_level) REGEXP '[1-4](st|nd|rd|th)[[:space:]]*year' THEN 'COLLEGE'
    ELSE 'COLLEGE'
END
WHERE gr.grading_system IS NULL";

$result = $conn->query($update_sql);
if ($result) {
    $affected = $conn->affected_rows;
    echo "<p class='success'>✓ Updated $affected records with grading_system</p>";
    $success_count++;
} else {
    echo "<p class='error'>✗ Error updating records: " . $conn->error . "</p>";
    $error_count++;
}

echo "<hr>";
echo "<h3>Migration Summary</h3>";
echo "<p><strong>Successful operations:</strong> $success_count</p>";
echo "<p><strong>Warnings (already exists):</strong> $warning_count</p>";
echo "<p><strong>Failed operations:</strong> $error_count</p>";

// Verification
echo "<hr>";
echo "<h3>Verification</h3>";

// Check quarter columns
$result = $conn->query("SHOW COLUMNS FROM grades_record WHERE Field LIKE '%quarter%'");
if ($result && $result->num_rows >= 4) {
    echo "<p class='success'>✓ All 4 quarter columns exist</p>";
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>" . $row['Field'] . " (" . $row['Type'] . ")</li>";
    }
    echo "</ul>";
} else {
    echo "<p class='error'>✗ Quarter columns missing or incomplete</p>";
}

// Check grading_system column
$result = $conn->query("SHOW COLUMNS FROM grades_record WHERE Field = 'grading_system'");
if ($result && $result->num_rows > 0) {
    echo "<p class='success'>✓ grading_system column exists</p>";
    $row = $result->fetch_assoc();
    echo "<p>Type: " . $row['Type'] . "</p>";
} else {
    echo "<p class='error'>✗ grading_system column not found</p>";
}

// Check data distribution
$result = $conn->query("SELECT grading_system, COUNT(*) as count FROM grades_record GROUP BY grading_system");
if ($result) {
    echo "<h4>Records by Grading System:</h4>";
    echo "<table>";
    echo "<tr><th>Grading System</th><th>Count</th></tr>";
    $has_data = false;
    while ($row = $result->fetch_assoc()) {
        $has_data = true;
        $system = $row['grading_system'] ?? 'NULL';
        echo "<tr><td>" . htmlspecialchars($system) . "</td><td>" . $row['count'] . "</td></tr>";
    }
    echo "</table>";
    if (!$has_data) {
        echo "<p class='warning'>⚠ No grade records found in database</p>";
    }
}

echo "<hr>";
if ($error_count == 0) {
    echo "<p class='success'><strong>✓ Migration completed successfully!</strong></p>";
} else {
    echo "<p class='error'><strong>✗ Migration completed with errors. Please review above.</strong></p>";
}
echo "<p><a href='../EmployeePortal/ManageGrades.php' style='display:inline-block;padding:10px 20px;background:#4CAF50;color:white;text-decoration:none;border-radius:5px;margin-top:10px;'>Go to Manage Grades</a></p>";

echo "</body></html>";

$conn->close();
?>
