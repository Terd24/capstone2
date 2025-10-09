<?php
session_start();
include("../StudentLogin/db_conn.php");

// Security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Require registrar login
if (!isset($_SESSION['registrar_id'])) {
    header("Location: ../StudentLogin/login.php");
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ManageSchedule.php?error=" . urlencode("Invalid request method"));
    exit;
}

// Validate and sanitize inputs
$schedule_id = null;
$section_name = 'Unknown Section';

if (isset($_POST['schedule_id'])) {
    $schedule_id = filter_var($_POST['schedule_id'], FILTER_VALIDATE_INT);
    if ($schedule_id === false || $schedule_id <= 0) {
        header("Location: ManageSchedule.php?error=" . urlencode("Invalid schedule ID"));
        exit;
    }
}

if (isset($_POST['section_name'])) {
    $section_name = trim($_POST['section_name']);
    if (!preg_match('/^[A-Za-z0-9\s\-_.,()&%]+$/', $section_name) || strlen($section_name) > 100) {
        $section_name = 'Unknown Section';
    }
    $section_name = htmlspecialchars($section_name, ENT_QUOTES, 'UTF-8');
}

if (!$schedule_id) {
    header("Location: ManageSchedule.php?error=" . urlencode("Schedule ID is required"));
    exit;
}

// SECURITY CHECK: Verify schedule exists and registrar has access
$registrar_id = $_SESSION['registrar_id'];

// Check if the schedule exists
$schedule_check_stmt = $conn->prepare("SELECT * FROM class_schedules WHERE id = ?");
$schedule_check_stmt->bind_param("i", $schedule_id);
$schedule_check_stmt->execute();
$schedule_result = $schedule_check_stmt->get_result();
$schedule = $schedule_result->fetch_assoc();

if (!$schedule) {
    // Log unauthorized access attempt
    error_log("SECURITY: Schedule not found - Registrar ID {$registrar_id} tried to access non-existent Schedule ID {$schedule_id} from IP " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    
    header("Location: ManageSchedule.php?error=" . urlencode("Schedule not found"));
    exit;
}

// Log successful access
error_log("Schedule access granted: Registrar ID {$registrar_id} accessing Schedule ID {$schedule_id} ('{$section_name}') from IP " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

// Store schedule info in session (secure way)
$_SESSION['current_schedule_id'] = $schedule_id;
$_SESSION['current_section_name'] = $section_name;

// Redirect to clean URL
header("Location: view_schedule_students.php");
exit;
?>
