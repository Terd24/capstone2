<?php
session_start();

// Require Super Admin login
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    header('Location: ../StudentLogin/login.php');
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'onecci_db');
if ($conn->connect_error) {
    die('DB connection failed: ' . $conn->connect_error);
}

// Get deleted students
$deleted_students = [];
try {
    $stmt = $conn->prepare("SELECT id_number, first_name, last_name, middle_name, grade_level, academic_track, deleted_at, deleted_by, deleted_reason FROM student_account WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC");
    if ($stmt) {
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $deleted_students[] = $row;
        }
        $stmt->close();
    }
} catch (Exception $e) {
    // Table might not have soft delete columns yet
}

// Get deleted employees
$deleted_employees = [];
try {
    $stmt = $conn->prepare("SELECT id_number, first_name, last_name, middle_name, position, department, deleted_at, deleted_by, deleted_reason FROM employees WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC");
    if ($stmt) {
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $deleted_employees[] = $row;
        }
        $stmt->close();
    }
} catch (Exception $e) {
    // Table might not have soft delete columns yet
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deleted Items Management - Cornerstone College</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen font-sans">
    <!-- Header -->
    <div class="bg-[#0B2C62] text-white shadow-lg">
        <div class="flex justify-between items-center px-6 py-6">
            <div class="flex items-center space-x-4">
                <a href="SuperAdminDashboard.php" class="hover:bg-white hover:bg-opacity-10 p-2 rounded transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <h1 class="text-xl font-semibold">Deleted Items Management</h1>
            </div>
            
            <div class="flex items-center space-x-4">
                <img src="../images/LogoCCI.png" alt="Cornerstone College Inc." class="h-10 w-10 rounded-full bg-white p-1">
                <div class="text-right">
                    <h2 class="text-base font-bold">Cornerstone College Inc.</h2>
                    <p class="text-blue-200 text-sm">System Maintenance Portal</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto p-6">

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="bg-red-500 text-white rounded p-4">
                <div class="flex items-center">
                    <div class="bg-white bg-opacity-20 rounded p-2 mr-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium">Deleted Students</h3>
                        <p class="text-2xl font-bold"><?= count($deleted_students) ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-orange-500 text-white rounded p-4">
                <div class="flex items-center">
                    <div class="bg-white bg-opacity-20 rounded p-2 mr-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M12 7a3 3 0 110-6 3 3 0 010 6z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium">Deleted Employees</h3>
                        <p class="text-2xl font-bold"><?= count($deleted_employees) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Deleted Students Table -->
        <?php if (count($deleted_students) > 0): ?>
        <div class="bg-white rounded shadow mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">🔴 Deleted Students (<?= count($deleted_students) ?>)</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase w-1/4">Student Info</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase w-1/4">Program</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase w-1/4">Deleted Info</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase w-1/4">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($deleted_students as $student): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center mr-3">
                                        <span class="text-red-600 font-bold">
                                            <?= strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)) ?>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                                        </div>
                                        <div class="text-sm text-gray-500">ID: <?= htmlspecialchars($student['id_number']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900"><?= htmlspecialchars($student['academic_track'] ?: 'N/A') ?></div>
                                <div class="text-sm text-gray-500"><?= htmlspecialchars($student['grade_level'] ?: 'N/A') ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm">
                                    <div class="font-medium text-gray-900">
                                        <?= date('M j, Y g:i A', strtotime($student['deleted_at'])) ?>
                                    </div>
                                    <div class="text-gray-500">By: <?= htmlspecialchars($student['deleted_by'] ?: 'Unknown') ?></div>
                                    <?php if ($student['deleted_reason']): ?>
                                        <div class="text-gray-500 text-xs mt-1">
                                            Reason: <?= htmlspecialchars($student['deleted_reason']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex gap-2">
                                    <button onclick="restoreStudent('<?= htmlspecialchars($student['id_number']) ?>')" 
                                            class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm">
                                        Restore
                                    </button>
                                    <button onclick="requestPermanentDelete('<?= htmlspecialchars($student['id_number']) ?>', 'student')" 
                                            class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm">
                                        Delete Permanently
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div class="bg-white rounded shadow p-6 text-center mb-6">
            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No Deleted Students</h3>
            <p class="text-gray-500">No student records have been deleted.</p>
        </div>
        <?php endif; ?>

        <!-- Deleted Employees Table -->
        <?php if (count($deleted_employees) > 0): ?>
        <div class="bg-white rounded shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">🟠 Deleted Employees (<?= count($deleted_employees) ?>)</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase w-1/4">Employee Info</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase w-1/4">Position</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase w-1/4">Deleted Info</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase w-1/4">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($deleted_employees as $employee): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center mr-3">
                                        <span class="text-orange-600 font-bold">
                                            <?= strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1)) ?>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?>
                                        </div>
                                        <div class="text-sm text-gray-500">ID: <?= htmlspecialchars($employee['id_number']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900"><?= htmlspecialchars($employee['position'] ?: 'N/A') ?></div>
                                <div class="text-sm text-gray-500"><?= htmlspecialchars($employee['department'] ?: 'N/A') ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm">
                                    <div class="font-medium text-gray-900">
                                        <?= date('M j, Y g:i A', strtotime($employee['deleted_at'])) ?>
                                    </div>
                                    <div class="text-gray-500">By: <?= htmlspecialchars($employee['deleted_by'] ?: 'Unknown') ?></div>
                                    <?php if ($employee['deleted_reason']): ?>
                                        <div class="text-gray-500 text-xs mt-1">
                                            Reason: <?= htmlspecialchars($employee['deleted_reason']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex gap-2">
                                    <button onclick="restoreEmployee('<?= htmlspecialchars($employee['id_number']) ?>')" 
                                            class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm">
                                        Restore
                                    </button>
                                    <button onclick="requestPermanentDelete('<?= htmlspecialchars($employee['id_number']) ?>', 'employee')" 
                                            class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm">
                                        Delete Permanently
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div class="bg-white rounded shadow p-6 text-center">
            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M12 7a3 3 0 110-6 3 3 0 010 6z"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No Deleted Employees</h3>
            <p class="text-gray-500">No employee records have been deleted.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- JavaScript for Restore and Delete Functions -->
    <script>
    // Restore deleted student
    function restoreStudent(studentId) {
        if (confirm('Are you sure you want to restore this student record?')) {
            fetch('restore_record.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'restore',
                    record_type: 'student',
                    record_id: studentId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Student record restored successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while restoring the record.');
            });
        }
    }

    // Restore deleted employee
    function restoreEmployee(employeeId) {
        if (confirm('Are you sure you want to restore this employee record?')) {
            fetch('restore_record.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'restore',
                    record_type: 'employee',
                    record_id: employeeId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Employee record restored successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while restoring the record.');
            });
        }
    }

    // Request permanent deletion
    function requestPermanentDelete(recordId, recordType) {
        const reason = prompt('Please provide a reason for permanent deletion:');
        if (reason && reason.trim()) {
            if (confirm('This will send a request to the School Owner for approval. Continue?')) {
                fetch('request_permanent_delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'request_permanent_delete',
                        record_type: recordType,
                        record_id: recordId,
                        reason: reason.trim()
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Permanent deletion request sent to School Owner for approval.');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while sending the request.');
                });
            }
        }
    }
    </script>
</body>
</html>
