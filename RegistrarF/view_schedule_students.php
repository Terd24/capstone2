<?php
session_start();
include("../StudentLogin/db_conn.php");

// Require registrar login
if (!isset($_SESSION['registrar_id'])) {
    header("Location: ../StudentLogin/login.php");
    exit;
}

$schedule_id = $_GET['schedule_id'] ?? null;
$section_name = $_GET['section_name'] ?? 'Unknown Section';

if (!$schedule_id) {
    header("Location: ManageSchedule.php");
    exit;
}

// Get schedule details
$schedule_stmt = $conn->prepare("SELECT * FROM class_schedules WHERE id = ?");
$schedule_stmt->bind_param("i", $schedule_id);
$schedule_stmt->execute();
$schedule_result = $schedule_stmt->get_result();
$schedule = $schedule_result->fetch_assoc();

// Get assigned students
$students_stmt = $conn->prepare("
    SELECT sa.id_number, CONCAT(sa.first_name, ' ', sa.last_name) as full_name, 
           sa.academic_track as program, sa.grade_level as year_section,
           ss.assigned_at
    FROM student_schedules ss
    JOIN student_account sa ON ss.student_id = sa.id_number
    WHERE ss.schedule_id = ?
    ORDER BY sa.first_name, sa.last_name
");
$students_stmt->bind_param("i", $schedule_id);
$students_stmt->execute();
$students_result = $students_stmt->get_result();

// Handle student removal
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'remove_student') {
    $student_id = $_POST['student_id'];
    
    // Remove from student_schedules
    $remove_stmt = $conn->prepare("DELETE FROM student_schedules WHERE student_id = ? AND schedule_id = ?");
    $remove_stmt->bind_param("si", $student_id, $schedule_id);
    
    if ($remove_stmt->execute()) {
        // Clear schedule from student_account and attendance_record
        $clear_student = $conn->prepare("UPDATE student_account SET class_schedule = NULL WHERE id_number = ?");
        $clear_student->bind_param("s", $student_id);
        $clear_student->execute();
        
        // Clear schedule from attendance_record
        $clear_attendance = $conn->prepare("UPDATE attendance_record SET schedule = NULL WHERE id_number = ?");
        $clear_attendance->bind_param("s", $student_id);
        $clear_attendance->execute();
        
        $success_msg = "Student removed from schedule successfully!";
    } else {
        $error_msg = "Failed to remove student from schedule.";
    }
    
    // Refresh the page to show updated list
    header("Location: view_schedule_students.php?schedule_id=$schedule_id&section_name=" . urlencode($section_name));
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Students - <?= htmlspecialchars($section_name) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .school-gradient { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #1e40af 100%); }
        .card-shadow { box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">

<!-- Header -->
<header class="bg-blue-600 text-white shadow-lg">
    <div class="container mx-auto px-6 py-4">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <button onclick="window.location.href='ManageSchedule.php'" class="bg-white bg-opacity-20 hover:bg-opacity-30 p-2 rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>
                <div>
                    <h2 class="text-lg font-semibold"><?= htmlspecialchars($section_name) ?> Students</h2>
                    <p class="text-blue-200 text-sm">
                        <?= date('g:i A', strtotime($schedule['start_time'])) ?> - <?= date('g:i A', strtotime($schedule['end_time'])) ?>
                        • <?= htmlspecialchars($schedule['days']) ?>
                    </p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <img src="../images/LogoCCI.png" alt="Cornerstone College Inc." class="h-12 w-12 rounded-full bg-white p-1">
                <div class="text-right">
                    <h1 class="text-xl font-bold">Cornerstone College Inc.</h1>
                    <p class="text-blue-200 text-sm">Schedule Management System</p>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Main Content -->
<div class="container mx-auto px-6 py-8">
    <!-- Success/Error Messages -->
    <?php if (isset($success_msg)): ?>
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
            <?= htmlspecialchars($success_msg) ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_msg)): ?>
        <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
            <?= htmlspecialchars($error_msg) ?>
        </div>
    <?php endif; ?>

    <!-- Students List -->
    <div class="bg-white rounded-xl card-shadow p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-gray-800">Assigned Students</h2>
            <div class="text-sm text-gray-600">
                Total: <?= $students_result->num_rows ?> student(s)
            </div>
        </div>
        
        <?php if ($students_result->num_rows > 0): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Number</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Program</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Year/GRADE LEVEL</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while ($student = $students_result->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-medium">
                                            <?= strtoupper(substr($student['full_name'], 0, 1)) ?>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($student['full_name']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($student['id_number']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($student['program'] ?? 'N/A') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($student['year_section'] ?? 'N/A') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('M j, Y g:i A', strtotime($student['assigned_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="removeStudent('<?= htmlspecialchars($student['id_number']) ?>', '<?= htmlspecialchars($student['full_name']) ?>')" 
                                            class="text-red-600 hover:text-red-900">
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-8">
                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <p class="text-gray-500 text-lg">No students assigned to this schedule</p>
                <p class="text-gray-400 text-sm">Use "Assign Schedule to Students" to add students</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Remove Student Modal -->
<div id="removeModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-6 w-full max-w-sm mx-4">
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Remove Student</h3>
            <p class="text-sm text-gray-500 mb-6">Are you sure you want to remove <span id="studentName" class="font-medium"></span> from this schedule?</p>
            <form id="removeForm" method="POST">
                <input type="hidden" name="action" value="remove_student">
                <input type="hidden" name="student_id" id="removeStudentId">
                <div class="flex gap-3">
                    <button type="button" onclick="hideRemoveModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg font-medium">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg font-medium">
                        Remove
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function removeStudent(studentId, studentName) {
    document.getElementById('removeStudentId').value = studentId;
    document.getElementById('studentName').textContent = studentName;
    document.getElementById('removeModal').classList.remove('hidden');
}

function hideRemoveModal() {
    document.getElementById('removeModal').classList.add('hidden');
}
</script>

</body>
</html>
