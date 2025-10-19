<?php
session_start();
include("StudentLogin/db_conn.php");

// Require owner login
if (!isset($_SESSION['owner_id']) || $_SESSION['role'] !== 'owner') {
    die("Unauthorized access");
}

$executed = false;
$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_cleanup'])) {
    $executed = true;
    
    // Step 1: Delete the 3 school years
    $years_to_delete = ['2024-2025', '2025-2026', '2026-2027'];
    
    foreach ($years_to_delete as $year) {
        $stmt = $conn->prepare("DELETE FROM tuition_fee_structure WHERE school_year = ?");
        $stmt->bind_param("s", $year);
        
        if ($stmt->execute()) {
            $deleted = $stmt->affected_rows;
            $results[] = ['success' => true, 'message' => "Deleted $deleted records for school year: $year"];
        } else {
            $results[] = ['success' => false, 'message' => "Failed to delete school year: $year"];
        }
        $stmt->close();
    }
    
    // Check for existing Kinder records
    $check = $conn->query("SELECT COUNT(*) as count FROM tuition_fee_structure WHERE grade_level = 'Kinder'");
    $result = $check->fetch_assoc();
    $kinder_count = $result['count'];
    
    $results[] = ['info' => true, 'message' => "Found $kinder_count old 'Kinder' records remaining in database"];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cleanup & Fix Kinder Structure</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center p-6">
    <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-2xl w-full">
        
        <?php if (!$executed): ?>
            <!-- Confirmation Screen -->
            <div class="text-center mb-6">
                <div class="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Cleanup & Fix Kinder Structure</h1>
                <p class="text-gray-600">This will delete 3 school years and update the system structure</p>
            </div>
            
            <div class="bg-red-50 border-2 border-red-200 rounded-xl p-6 mb-6">
                <h2 class="font-bold text-red-900 mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    What will be deleted:
                </h2>
                <ul class="text-sm text-red-800 space-y-2">
                    <li class="flex items-start gap-2">
                        <span class="text-red-600 font-bold">×</span>
                        <span>School Year: <strong>2024-2025</strong> (all fee structures)</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-red-600 font-bold">×</span>
                        <span>School Year: <strong>2025-2026</strong> (all fee structures)</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-red-600 font-bold">×</span>
                        <span>School Year: <strong>2026-2027</strong> (all fee structures)</span>
                    </li>
                </ul>
            </div>
            
            <div class="bg-green-50 border-2 border-green-200 rounded-xl p-6 mb-6">
                <h2 class="font-bold text-green-900 mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    What will be updated:
                </h2>
                <ul class="text-sm text-green-800 space-y-2">
                    <li class="flex items-start gap-2">
                        <span class="text-green-600 font-bold">✓</span>
                        <span>Grade structure updated from <strong>"Kinder"</strong> to <strong>"Kinder 1"</strong> and <strong>"Kinder 2"</strong></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-green-600 font-bold">✓</span>
                        <span>New school years will have 34 fee structures (instead of 33)</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-green-600 font-bold">✓</span>
                        <span>Better organization and separate pricing for Kinder levels</span>
                    </li>
                </ul>
            </div>
            
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
                <p class="text-sm text-blue-800">
                    <strong>Note:</strong> This action cannot be undone. Make sure you have a backup if needed.
                    Student records will NOT be affected - only tuition fee structures.
                </p>
            </div>
            
            <form method="POST" onsubmit="return confirm('Are you sure you want to proceed with the cleanup?');">
                <div class="flex gap-3">
                    <button type="submit" name="confirm_cleanup" value="1" class="flex-1 bg-red-600 hover:bg-red-700 text-white py-3 rounded-lg font-bold transition transform hover:scale-105">
                        🗑️ Proceed with Cleanup
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
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Cleanup Complete!</h1>
                <p class="text-gray-600">The system has been updated successfully</p>
            </div>
            
            <div class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
                <h2 class="font-bold text-gray-900 mb-4">Results:</h2>
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
                    <li>Go to Owner Dashboard → Tuition Fees section</li>
                    <li>Click "+ Add School Year" button</li>
                    <li>Enter the school year (e.g., 2024-2025)</li>
                    <li>System will create fee structures with <strong>Kinder 1</strong> and <strong>Kinder 2</strong></li>
                    <li>Set the prices for each grade level</li>
                </ol>
            </div>
            
            <a href="OwnerF/Dashboard.php" class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center py-3 rounded-lg font-bold transition transform hover:scale-105">
                Go to Dashboard →
            </a>
        <?php endif; ?>
        
    </div>
</body>
</html>
