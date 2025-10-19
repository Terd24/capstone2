<?php
session_start();
include("StudentLogin/db_conn.php");

// Require owner login
if (!isset($_SESSION['owner_id']) || $_SESSION['role'] !== 'owner') {
    die("Unauthorized access");
}

$executed = false;
$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_migration'])) {
    $executed = true;
    
    // Step 1: Get all existing "Kinder" records
    $query = "SELECT * FROM tuition_fee_structure WHERE grade_level = 'Kinder'";
    $result = $conn->query($query);
    $kinder_records = [];
    
    while ($row = $result->fetch_assoc()) {
        $kinder_records[] = $row;
    }
    
    $results[] = ['info' => true, 'message' => "Found " . count($kinder_records) . " existing 'Kinder' records"];
    
    if (count($kinder_records) > 0) {
        // Step 2: Update existing "Kinder" to "Kinder 1"
        $update_stmt = $conn->prepare("UPDATE tuition_fee_structure SET grade_level = 'Kinder 1' WHERE grade_level = 'Kinder'");
        
        if ($update_stmt->execute()) {
            $updated = $update_stmt->affected_rows;
            $results[] = ['success' => true, 'message' => "Updated $updated records from 'Kinder' to 'Kinder 1'"];
        } else {
            $results[] = ['success' => false, 'message' => "Failed to update Kinder records"];
        }
        $update_stmt->close();
        
        // Step 3: Create "Kinder 2" records by duplicating "Kinder 1"
        $insert_stmt = $conn->prepare("
            INSERT INTO tuition_fee_structure 
            (grade_level, academic_track, tuition_fee, other_fees, total_fee, school_year, term, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $inserted = 0;
        foreach ($kinder_records as $record) {
            $grade_level = 'Kinder 2';
            $academic_track = $record['academic_track'];
            $tuition_fee = $record['tuition_fee'];
            $other_fees = $record['other_fees'];
            $total_fee = $record['total_fee'];
            $school_year = $record['school_year'];
            $term = $record['term'];
            
            // Check if Kinder 2 already exists for this combination
            $check = $conn->prepare("SELECT id FROM tuition_fee_structure WHERE grade_level = 'Kinder 2' AND academic_track = ? AND school_year = ? AND term = ?");
            $check->bind_param("sss", $academic_track, $school_year, $term);
            $check->execute();
            
            if ($check->get_result()->num_rows == 0) {
                $insert_stmt->bind_param("ssdddss", $grade_level, $academic_track, $tuition_fee, $other_fees, $total_fee, $school_year, $term);
                
                if ($insert_stmt->execute()) {
                    $inserted++;
                }
            }
            $check->close();
        }
        
        $insert_stmt->close();
        $results[] = ['success' => true, 'message' => "Created $inserted new 'Kinder 2' records"];
        
        // Step 4: Verify the migration
        $verify_k1 = $conn->query("SELECT COUNT(*) as count FROM tuition_fee_structure WHERE grade_level = 'Kinder 1'")->fetch_assoc()['count'];
        $verify_k2 = $conn->query("SELECT COUNT(*) as count FROM tuition_fee_structure WHERE grade_level = 'Kinder 2'")->fetch_assoc()['count'];
        $verify_old = $conn->query("SELECT COUNT(*) as count FROM tuition_fee_structure WHERE grade_level = 'Kinder'")->fetch_assoc()['count'];
        
        $results[] = ['info' => true, 'message' => "Verification: Kinder 1 = $verify_k1, Kinder 2 = $verify_k2, Old Kinder = $verify_old"];
    } else {
        $results[] = ['info' => true, 'message' => "No 'Kinder' records found. System already uses Kinder 1 and Kinder 2."];
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migrate Kinder Structure</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-purple-50 to-pink-100 min-h-screen flex items-center justify-center p-6">
    <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-2xl w-full">
        
        <?php if (!$executed): ?>
            <!-- Confirmation Screen -->
            <div class="text-center mb-6">
                <div class="w-20 h-20 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Migrate Kinder Structure</h1>
                <p class="text-gray-600">Convert existing "Kinder" records to "Kinder 1" and "Kinder 2"</p>
            </div>
            
            <div class="bg-blue-50 border-2 border-blue-200 rounded-xl p-6 mb-6">
                <h2 class="font-bold text-blue-900 mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    What this will do:
                </h2>
                <ol class="text-sm text-blue-800 space-y-2 list-decimal list-inside">
                    <li>Find all existing <strong>"Kinder"</strong> records in your database</li>
                    <li>Rename them to <strong>"Kinder 1"</strong></li>
                    <li>Duplicate them to create <strong>"Kinder 2"</strong> records</li>
                    <li>Keep all prices and settings the same</li>
                    <li>Verify the migration was successful</li>
                </ol>
            </div>
            
            <div class="bg-gradient-to-r from-purple-50 to-pink-50 border border-purple-200 rounded-xl p-6 mb-6">
                <h2 class="font-bold text-purple-900 mb-3">Example:</h2>
                <div class="space-y-3">
                    <div class="bg-white rounded-lg p-3 border border-gray-200">
                        <div class="text-xs text-gray-500 mb-1">BEFORE:</div>
                        <div class="font-mono text-sm">
                            <span class="text-red-600">Kinder</span> | Pre-Elementary | 2024-2025 | 1st Semester | ₱5,000
                        </div>
                    </div>
                    <div class="text-center text-purple-600">↓ Migration ↓</div>
                    <div class="bg-white rounded-lg p-3 border border-gray-200 space-y-2">
                        <div>
                            <div class="text-xs text-gray-500 mb-1">AFTER (2 records):</div>
                            <div class="font-mono text-sm">
                                <span class="text-green-600">Kinder 1</span> | Pre-Elementary | 2024-2025 | 1st Semester | ₱5,000
                            </div>
                        </div>
                        <div class="font-mono text-sm">
                            <span class="text-green-600">Kinder 2</span> | Pre-Elementary | 2024-2025 | 1st Semester | ₱5,000
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-6">
                <p class="text-sm text-yellow-800">
                    <strong>⚠️ Important:</strong> This will double your Kinder records. If you have 6 Kinder records, 
                    you'll have 6 Kinder 1 + 6 Kinder 2 = 12 total records after migration.
                </p>
            </div>
            
            <form method="POST" onsubmit="return confirm('Are you sure you want to migrate the Kinder structure?');">
                <div class="flex gap-3">
                    <button type="submit" name="confirm_migration" value="1" class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-3 rounded-lg font-bold transition transform hover:scale-105">
                        🔄 Start Migration
                    </button>
                    <a href="OwnerF/Dashboard.php" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 text-center py-3 rounded-lg font-bold transition">
                        Cancel
                    </a>
                </div>
            </form>
            
        <?php else: ?>
            <!-- Results Screen -->
            <div class="text-center mb-6">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Migration Complete!</h1>
                <p class="text-gray-600">Your Kinder structure has been updated</p>
            </div>
            
            <div class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
                <h2 class="font-bold text-gray-900 mb-4">Migration Results:</h2>
                <div class="space-y-2">
                    <?php foreach ($results as $result): ?>
                        <?php if (isset($result['success'])): ?>
                            <div class="flex items-start gap-3 p-3 rounded-lg <?= $result['success'] ? 'bg-green-50' : 'bg-red-50' ?>">
                                <span class="text-lg"><?= $result['success'] ? '✓' : '✗' ?></span>
                                <span class="text-sm <?= $result['success'] ? 'text-green-800' : 'text-red-800' ?>">
                                    <?= htmlspecialchars($result['message']) ?>
                                </span>
                            </div>
                        <?php elseif (isset($result['info'])): ?>
                            <div class="flex items-start gap-3 p-3 rounded-lg bg-blue-50">
                                <span class="text-lg">ℹ️</span>
                                <span class="text-sm text-blue-800">
                                    <?= htmlspecialchars($result['message']) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-6 mb-6">
                <h2 class="font-bold text-blue-900 mb-3">📝 Next Steps:</h2>
                <ol class="text-sm text-blue-800 space-y-2 list-decimal list-inside">
                    <li>Go to Owner Dashboard → Tuition Fees</li>
                    <li>Verify you see both <strong>Kinder 1</strong> and <strong>Kinder 2</strong></li>
                    <li>Optionally run <code class="bg-white px-2 py-1 rounded">cleanup_and_fix_kinder.php</code> to delete old school years</li>
                    <li>Add new school years with the updated structure</li>
                </ol>
            </div>
            
            <div class="flex gap-3">
                <a href="OwnerF/Dashboard.php" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-center py-3 rounded-lg font-bold transition transform hover:scale-105">
                    Go to Dashboard →
                </a>
                <a href="cleanup_and_fix_kinder.php" class="flex-1 bg-red-600 hover:bg-red-700 text-white text-center py-3 rounded-lg font-bold transition">
                    Run Cleanup Script
                </a>
            </div>
        <?php endif; ?>
        
    </div>
</body>
</html>
