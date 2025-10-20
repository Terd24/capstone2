<?php
/**
 * Safe Database Migration Runner
 * Checks for existing columns before adding them
 */

include('../StudentLogin/db_conn.php');

echo "<!DOCTYPE html><html><head><title>Database Migration</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;} .container{background:white;padding:20px;border-radius:8px;max-width:900px;margin:0 auto;} .success{color:green;padding:10px;background:#e8f5e9;border-left:4px solid green;margin:10px 0;} .error{color:red;padding:10px;background:#ffebee;border-left:4px solid red;margin:10px 0;} .warning{color:orange;padding:10px;background:#fff3e0;border-left:4px solid orange;margin:10px 0;} .info{color:blue;padding:10px;background:#e3f2fd;border-left:4px solid blue;margin:10px 0;} table{border-collapse:collapse;margin:10px 0;width:100%;} td,th{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#2196F3;color:white;} .btn{display:inline-block;padding:12px 24px;background:#4CAF50;color:white;text-decoration:none;border-radius:5px;margin-top:20px;font-weight:bold;} .btn:hover{background:#45a049;}</style>";
echo "</head><body><div class='container'>";

echo "<h2>🎓 K-12 vs College Grading System Migration</h2>";
echo "<p>This migration will add support for both K-12 (quarterly) and College (term-based) grading systems.</p><hr>";

$success_count = 0;
$error_count = 0;
$warning_count = 0;

// Helper function to check if column exists
function columnExists($conn, $table, $column) {
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && $result->num_rows > 0;
}

// Helper function to check if index exists
function indexExists($conn, $table, $index) {
    $result = $conn->query("SHOW INDEX FROM `$table` WHERE Key_name = '$index'");
    return $result && $result->num_rows > 0;
}

// Step 1: Add first_quarter column
echo "<div class='info'><strong>Step 1:</strong> Checking first_quarter column...</div>";
if (columnExists($conn, 'grades_record', 'first_quarter')) {
    echo "<div class='warning'>⚠ first_quarter column already exists (skipping)</div>";
    $warning_count++;
} else {
    $result = $conn->query("ALTER TABLE grades_record ADD COLUMN first_quarter DECIMAL(5,2) DEFAULT NULL");
    if ($result) {
        echo "<div class='success'>✓ first_quarter column added successfully</div>";
        $success_count++;
    } else {
        echo "<div class='error'>✗ Error: " . $conn->error . "</div>";
        $error_count++;
    }
}

// Step 2: Add second_quarter column
echo "<div class='info'><strong>Step 2:</strong> Checking second_quarter column...</div>";
if (columnExists($conn, 'grades_record', 'second_quarter')) {
    echo "<div class='warning'>⚠ second_quarter column already exists (skipping)</div>";
    $warning_count++;
} else {
    $result = $conn->query("ALTER TABLE grades_record ADD COLUMN second_quarter DECIMAL(5,2) DEFAULT NULL");
    if ($result) {
        echo "<div class='success'>✓ second_quarter column added successfully</div>";
        $success_count++;
    } else {
        echo "<div class='error'>✗ Error: " . $conn->error . "</div>";
        $error_count++;
    }
}

// Step 3: Add third_quarter column
echo "<div class='info'><strong>Step 3:</strong> Checking third_quarter column...</div>";
if (columnExists($conn, 'grades_record', 'third_quarter')) {
    echo "<div class='warning'>⚠ third_quarter column already exists (skipping)</div>";
    $warning_count++;
} else {
    $result = $conn->query("ALTER TABLE grades_record ADD COLUMN third_quarter DECIMAL(5,2) DEFAULT NULL");
    if ($result) {
        echo "<div class='success'>✓ third_quarter column added successfully</div>";
        $success_count++;
    } else {
        echo "<div class='error'>✗ Error: " . $conn->error . "</div>";
        $error_count++;
    }
}

// Step 4: Add fourth_quarter column
echo "<div class='info'><strong>Step 4:</strong> Checking fourth_quarter column...</div>";
if (columnExists($conn, 'grades_record', 'fourth_quarter')) {
    echo "<div class='warning'>⚠ fourth_quarter column already exists (skipping)</div>";
    $warning_count++;
} else {
    $result = $conn->query("ALTER TABLE grades_record ADD COLUMN fourth_quarter DECIMAL(5,2) DEFAULT NULL");
    if ($result) {
        echo "<div class='success'>✓ fourth_quarter column added successfully</div>";
        $success_count++;
    } else {
        echo "<div class='error'>✗ Error: " . $conn->error . "</div>";
        $error_count++;
    }
}

// Step 5: Add grading_system column
echo "<div class='info'><strong>Step 5:</strong> Checking grading_system column...</div>";
if (columnExists($conn, 'grades_record', 'grading_system')) {
    echo "<div class='warning'>⚠ grading_system column already exists (skipping)</div>";
    $warning_count++;
} else {
    $result = $conn->query("ALTER TABLE grades_record ADD COLUMN grading_system VARCHAR(10) DEFAULT NULL");
    if ($result) {
        echo "<div class='success'>��� grading_system column added successfully</div>";
        $success_count++;
    } else {
        echo "<div class='error'>✗ Error: " . $conn->error . "</div>";
        $error_count++;
    }
}

// Step 6: Add index for grading_system
echo "<div class='info'><strong>Step 6:</strong> Checking index for grading_system...</div>";
if (indexExists($conn, 'grades_record', 'idx_grading_system')) {
    echo "<div class='warning'>⚠ Index idx_grading_system already exists (skipping)</div>";
    $warning_count++;
} else {
    $result = $conn->query("ALTER TABLE grades_record ADD INDEX idx_grading_system (grading_system)");
    if ($result) {
        echo "<div class='success'>✓ Index added for grading_system</div>";
        $success_count++;
    } else {
        echo "<div class='error'>✗ Error: " . $conn->error . "</div>";
        $error_count++;
    }
}

// Step 7: Add composite index
echo "<div class='info'><strong>Step 7:</strong> Checking composite index...</div>";
if (indexExists($conn, 'grades_record', 'idx_student_subject_term')) {
    echo "<div class='warning'>⚠ Composite index already exists (skipping)</div>";
    $warning_count++;
} else {
    $result = $conn->query("ALTER TABLE grades_record ADD INDEX idx_student_subject_term (id_number, subject, school_year_term)");
    if ($result) {
        echo "<div class='success'>✓ Composite index added successfully</div>";
        $success_count++;
    } else {
        echo "<div class='error'>✗ Error: " . $conn->error . "</div>";
        $error_count++;
    }
}

// Step 8: Update existing records
echo "<div class='info'><strong>Step 8:</strong> Updating existing records with grading_system...</div>";
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
    echo "<div class='success'>✓ Updated $affected records with grading_system</div>";
    $success_count++;
} else {
    echo "<div class='error'>✗ Error updating records: " . $conn->error . "</div>";
    $error_count++;
}

echo "<hr>";
echo "<h3>📊 Migration Summary</h3>";
echo "<table>";
echo "<tr><th>Status</th><th>Count</th></tr>";
echo "<tr><td>✓ Successful operations</td><td><strong>$success_count</strong></td></tr>";
echo "<tr><td>⚠ Warnings (already exists)</td><td><strong>$warning_count</strong></td></tr>";
echo "<tr><td>✗ Failed operations</td><td><strong>$error_count</strong></td></tr>";
echo "</table>";

// Verification
echo "<hr>";
echo "<h3>🔍 Verification</h3>";

// Check all columns exist
$required_columns = ['first_quarter', 'second_quarter', 'third_quarter', 'fourth_quarter', 'grading_system'];
$all_exist = true;
echo "<h4>Required Columns:</h4><ul>";
foreach ($required_columns as $col) {
    if (columnExists($conn, 'grades_record', $col)) {
        echo "<li style='color:green;'>✓ $col exists</li>";
    } else {
        echo "<li style='color:red;'>✗ $col missing</li>";
        $all_exist = false;
    }
}
echo "</ul>";

if ($all_exist) {
    echo "<div class='success'><strong>✓ All required columns exist!</strong></div>";
} else {
    echo "<div class='error'><strong>✗ Some columns are missing. Please review errors above.</strong></div>";
}

// Check data distribution
$result = $conn->query("SELECT grading_system, COUNT(*) as count FROM grades_record GROUP BY grading_system");
if ($result && $result->num_rows > 0) {
    echo "<h4>📈 Records by Grading System:</h4>";
    echo "<table>";
    echo "<tr><th>Grading System</th><th>Count</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $system = $row['grading_system'] ?? 'NULL';
        $badge = $system === 'K12' ? '🎒' : ($system === 'COLLEGE' ? '🎓' : '❓');
        echo "<tr><td>$badge " . htmlspecialchars($system) . "</td><td><strong>" . $row['count'] . "</strong></td></tr>";
    }
    echo "</table>";
} else {
    echo "<div class='warning'>⚠ No grade records found in database (this is normal for new installations)</div>";
}

echo "<hr>";
if ($error_count == 0) {
    echo "<div class='success'><h3>🎉 Migration Completed Successfully!</h3>";
    echo "<p>Your grading system now supports both K-12 (quarterly) and College (term-based) grading.</p></div>";
    echo "<a href='../EmployeePortal/ManageGrades.php' class='btn'>🚀 Go to Manage Grades</a>";
} else {
    echo "<div class='error'><h3>⚠ Migration Completed with Errors</h3>";
    echo "<p>Please review the errors above and try again, or contact support.</p></div>";
}

echo "</div></body></html>";

$conn->close();
?>
