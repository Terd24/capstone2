<?php
session_start();
include("../StudentLogin/db_conn.php");

// Require owner login
if (!isset($_SESSION['owner_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: ../admin_login.php");
    exit;
}

// Add term column if it doesn't exist
$conn->query("ALTER TABLE tuition_fee_structure ADD COLUMN IF NOT EXISTS term VARCHAR(20) DEFAULT '1st Semester'");

// Define all possible combinations
$school_years = ['2024-2025', '2025-2026', '2026-2027'];
$terms = ['1st Semester', '2nd Semester'];

$grade_structures = [
    // Pre-Elementary
    ['grade_level' => 'Kinder 1', 'academic_track' => 'Pre-Elementary'],
    ['grade_level' => 'Kinder 2', 'academic_track' => 'Pre-Elementary'],
    
    // Elementary
    ['grade_level' => 'Grade 1', 'academic_track' => 'Elementary'],
    ['grade_level' => 'Grade 2', 'academic_track' => 'Elementary'],
    ['grade_level' => 'Grade 3', 'academic_track' => 'Elementary'],
    ['grade_level' => 'Grade 4', 'academic_track' => 'Elementary'],
    ['grade_level' => 'Grade 5', 'academic_track' => 'Elementary'],
    ['grade_level' => 'Grade 6', 'academic_track' => 'Elementary'],
    
    // Junior High School
    ['grade_level' => 'Grade 7', 'academic_track' => 'Junior High School'],
    ['grade_level' => 'Grade 8', 'academic_track' => 'Junior High School'],
    ['grade_level' => 'Grade 9', 'academic_track' => 'Junior High School'],
    ['grade_level' => 'Grade 10', 'academic_track' => 'Junior High School'],
    
    // Senior High School - All Strands
    ['grade_level' => 'Grade 11', 'academic_track' => 'ABM'],
    ['grade_level' => 'Grade 12', 'academic_track' => 'ABM'],
    ['grade_level' => 'Grade 11', 'academic_track' => 'GAS'],
    ['grade_level' => 'Grade 12', 'academic_track' => 'GAS'],
    ['grade_level' => 'Grade 11', 'academic_track' => 'HUMSS'],
    ['grade_level' => 'Grade 12', 'academic_track' => 'HUMSS'],
    ['grade_level' => 'Grade 11', 'academic_track' => 'STEM'],
    ['grade_level' => 'Grade 12', 'academic_track' => 'STEM'],
    ['grade_level' => 'Grade 11', 'academic_track' => 'ICT'],
    ['grade_level' => 'Grade 12', 'academic_track' => 'ICT'],
    ['grade_level' => 'Grade 11', 'academic_track' => 'HE'],
    ['grade_level' => 'Grade 12', 'academic_track' => 'HE'],
    ['grade_level' => 'Grade 11', 'academic_track' => 'SPORTS'],
    ['grade_level' => 'Grade 12', 'academic_track' => 'SPORTS'],
    
    // College - All Courses
    ['grade_level' => '1st Year', 'academic_track' => 'Bachelor of Physical Education (BPed)'],
    ['grade_level' => '2nd Year', 'academic_track' => 'Bachelor of Physical Education (BPed)'],
    ['grade_level' => '3rd Year', 'academic_track' => 'Bachelor of Physical Education (BPed)'],
    ['grade_level' => '4th Year', 'academic_track' => 'Bachelor of Physical Education (BPed)'],
    ['grade_level' => '1st Year', 'academic_track' => 'Bachelor of Early Childhood Education (BECEd)'],
    ['grade_level' => '2nd Year', 'academic_track' => 'Bachelor of Early Childhood Education (BECEd)'],
    ['grade_level' => '3rd Year', 'academic_track' => 'Bachelor of Early Childhood Education (BECEd)'],
    ['grade_level' => '4th Year', 'academic_track' => 'Bachelor of Early Childhood Education (BECEd)'],
];

$inserted = 0;
$skipped = 0;

// Check if already initialized
$check_query = "SELECT COUNT(*) as count FROM tuition_fee_structure";
$check_result = $conn->query($check_query);
$existing_count = $check_result->fetch_assoc()['count'];

if ($existing_count > 0) {
    $message = "Database already has $existing_count fee structures. Do you want to add missing combinations only?";
    $mode = 'update'; // Will only insert missing combinations
} else {
    $mode = 'initialize'; // Will insert all
}

// Prepare insert statement
$insert_stmt = $conn->prepare("
    INSERT INTO tuition_fee_structure 
    (grade_level, academic_track, tuition_fee, other_fees, total_fee, school_year, term) 
    VALUES (?, ?, 0.00, 0.00, 0.00, ?, ?)
    ON DUPLICATE KEY UPDATE id=id
");

// Check for existing combination
$check_stmt = $conn->prepare("
    SELECT id FROM tuition_fee_structure 
    WHERE grade_level = ? AND academic_track = ? AND school_year = ? AND term = ?
");

foreach ($school_years as $year) {
    foreach ($terms as $term) {
        foreach ($grade_structures as $structure) {
            $grade_level = $structure['grade_level'];
            $academic_track = $structure['academic_track'];
            
            // Check if combination already exists
            $check_stmt->bind_param("ssss", $grade_level, $academic_track, $year, $term);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows == 0) {
                // Insert new combination
                $insert_stmt->bind_param("ssss", $grade_level, $academic_track, $year, $term);
                if ($insert_stmt->execute()) {
                    $inserted++;
                }
            } else {
                $skipped++;
            }
        }
    }
}

$check_stmt->close();
$insert_stmt->close();

$total_combinations = count($school_years) * count($terms) * count($grade_structures);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initialize Tuition Fees - Cornerstone College Inc.</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-6">
    <div class="bg-white rounded-xl shadow-lg p-8 max-w-2xl w-full">
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Tuition Fee Initialization Complete!</h1>
            <p class="text-gray-600">All grade levels and tracks have been pre-populated</p>
        </div>
        
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
            <h2 class="font-bold text-blue-900 mb-4">Summary:</h2>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-700">Total Possible Combinations:</span>
                    <span class="font-bold text-gray-900"><?= $total_combinations ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-700">New Records Inserted:</span>
                    <span class="font-bold text-green-600"><?= $inserted ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-700">Already Existing (Skipped):</span>
                    <span class="font-bold text-gray-600"><?= $skipped ?></span>
                </div>
            </div>
        </div>
        
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <h3 class="font-semibold text-yellow-900 mb-2">📝 What was created:</h3>
            <ul class="text-sm text-yellow-800 space-y-1">
                <li>✓ Kinder 1 & Kinder 2 (Pre-Elementary)</li>
                <li>✓ Grade 1-6 (Elementary)</li>
                <li>✓ Grade 7-10 (Junior High School)</li>
                <li>✓ Grade 11-12 (ABM, GAS, HUMSS, STEM, ICT, HE, SPORTS)</li>
                <li>✓ 1st-4th Year (BPed, BECEd)</li>
                <li>✓ For school years: <?= implode(', ', $school_years) ?></li>
                <li>✓ For both 1st and 2nd Semester</li>
            </ul>
        </div>
        
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
            <h3 class="font-semibold text-gray-900 mb-2">💡 Next Steps:</h3>
            <ol class="text-sm text-gray-700 space-y-2 list-decimal list-inside">
                <li>Go to Owner Dashboard → Tuition Fees</li>
                <li>All grade levels are now visible with ₱0.00 fees</li>
                <li>Click the edit button on each card to set the actual prices</li>
                <li>No need to manually add new records!</li>
            </ol>
        </div>
        
        <div class="flex gap-3">
            <a href="Dashboard.php" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-center py-3 rounded-lg font-medium transition">
                Go to Dashboard
            </a>
            <button onclick="window.location.reload()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 py-3 rounded-lg font-medium transition">
                Run Again
            </button>
        </div>
        
        <p class="text-xs text-gray-500 text-center mt-4">
            This script can be run multiple times safely. It will only add missing combinations.
        </p>
    </div>
</body>
</html>
