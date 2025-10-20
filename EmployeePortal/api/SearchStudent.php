<?php
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$role = $_SESSION['role'] ?? '';
if ($role !== 'teacher' && !isset($_SESSION['registrar_id'])) {
  echo json_encode(['error'=>'Unauthorized']);
  exit;
}

require_once("../../StudentLogin/db_conn.php");

$q = trim($_GET['query'] ?? '');
if ($q === ''){ echo json_encode(['students'=>[]]); exit; }

try{
  $like = "%$q%";
  
  // For teachers, filter by their assigned section
  if ($role === 'teacher') {
    $employee_id = $_SESSION['id_number'] ?? '';
    
    // Get teacher's assigned section from their schedule
    $section_query = "SELECT ws.schedule_name 
                      FROM employees e
                      LEFT JOIN employee_schedules es ON e.id_number = es.employee_id
                      LEFT JOIN employee_work_schedules ws ON es.schedule_id = ws.id
                      WHERE e.id_number = ? LIMIT 1";
    $section_stmt = $conn->prepare($section_query);
    $section_stmt->bind_param('s', $employee_id);
    $section_stmt->execute();
    $section_result = $section_stmt->get_result();
    $teacher_section = $section_result->fetch_assoc();
    
    if ($teacher_section && !empty($teacher_section['schedule_name'])) {
      $assigned_sections = $teacher_section['schedule_name'];
      
      // Split multiple sections (comma-separated) and trim whitespace
      $sections_array = array_map('trim', explode(',', $assigned_sections));
      
      // Search students who are assigned to ANY of the teacher's class schedules
      // Join with student_schedules and class_schedules tables
      $placeholders = implode(',', array_fill(0, count($sections_array), '?'));
      
      $query = "SELECT DISTINCT sa.id_number, sa.first_name, sa.last_name, 
                       CONCAT(sa.first_name,' ',sa.last_name) AS full_name, 
                       sa.academic_track AS program, 
                       sa.grade_level AS year_section,
                       cs.section_name
                FROM student_account sa
                INNER JOIN student_schedules ss ON sa.id_number = ss.student_id
                INNER JOIN class_schedules cs ON ss.schedule_id = cs.id
                WHERE cs.section_name IN ($placeholders) 
                AND (sa.id_number LIKE ? OR sa.first_name LIKE ? OR sa.last_name LIKE ? OR CONCAT(sa.first_name,' ',sa.last_name) LIKE ?) 
                LIMIT 50";
      
      $stmt = $conn->prepare($query);
      
      // Build bind_param types string
      $types = str_repeat('s', count($sections_array)) . 'ssss';
      
      // Merge sections array with like parameters
      $params = array_merge($sections_array, [$like, $like, $like, $like]);
      
      // Bind parameters dynamically
      $stmt->bind_param($types, ...$params);
    } else {
      // Teacher has no assigned section - return empty result
      echo json_encode(['students'=>[], 'message'=>'No section assigned to your account. Please contact HR.']);
      exit;
    }
  } else {
    // For non-teachers (registrar), show all students
    $stmt = $conn->prepare("SELECT id_number, first_name, last_name, CONCAT(first_name,' ',last_name) AS full_name, academic_track AS program, grade_level AS year_section 
                            FROM student_account 
                            WHERE (id_number LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR CONCAT(first_name,' ',last_name) LIKE ?) 
                            LIMIT 50");
    $stmt->bind_param('ssss', $like, $like, $like, $like);
  }
  
  $stmt->execute();
  $res = $stmt->get_result();
  $out = [];
  while($row=$res->fetch_assoc()){ $out[]=$row; }
  echo json_encode(['students'=>$out]);
  exit;
}catch(Throwable $e){
  echo json_encode(['error'=>'Server error: ' . $e->getMessage()]);
  exit;
}
