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

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Get all tuition fee structures
if ($action === 'get_fees') {
    $query = "SELECT * FROM tuition_fee_structure ORDER BY school_year DESC, term, grade_level, academic_track";
    $result = $conn->query($query);
    
    $fees = [];
    while ($row = $result->fetch_assoc()) {
        $fees[] = $row;
    }
    
    echo json_encode(['success' => true, 'fees' => $fees]);
    exit;
}

// Update tuition fee
if ($action === 'update_fee') {
    $id = intval($_POST['id']);
    $tuition_fee = floatval($_POST['tuition_fee']);
    $other_fees = floatval($_POST['other_fees']);
    $term = trim($_POST['term'] ?? '1st Semester');
    $total_fee = $tuition_fee + $other_fees;
    
    $stmt = $conn->prepare("UPDATE tuition_fee_structure SET tuition_fee = ?, other_fees = ?, total_fee = ?, term = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("dddsi", $tuition_fee, $other_fees, $total_fee, $term, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Tuition fee updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update tuition fee']);
    }
    exit;
}

// Delete school year
if ($action === 'delete_school_year') {
    $school_year = trim($_POST['school_year']);
    
    $stmt = $conn->prepare("DELETE FROM tuition_fee_structure WHERE school_year = ?");
    $stmt->bind_param("s", $school_year);
    
    if ($stmt->execute()) {
        $deleted = $stmt->affected_rows;
        echo json_encode(['success' => true, 'message' => "Deleted $deleted fee structures for school year $school_year", 'deleted' => $deleted]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete school year']);
    }
    exit;
}

// Add new school year with all combinations
if ($action === 'add_school_year') {
    try {
        $school_year = trim($_POST['school_year']);
        $copy_from_year = trim($_POST['copy_from_year'] ?? '');
        
        // Define all grade/track combinations
        $grade_structures = [
        ['grade_level' => 'Kinder 1', 'academic_track' => 'Pre-Elementary'],
        ['grade_level' => 'Kinder 2', 'academic_track' => 'Pre-Elementary'],
        ['grade_level' => 'Grade 1', 'academic_track' => 'Elementary'],
        ['grade_level' => 'Grade 2', 'academic_track' => 'Elementary'],
        ['grade_level' => 'Grade 3', 'academic_track' => 'Elementary'],
        ['grade_level' => 'Grade 4', 'academic_track' => 'Elementary'],
        ['grade_level' => 'Grade 5', 'academic_track' => 'Elementary'],
        ['grade_level' => 'Grade 6', 'academic_track' => 'Elementary'],
        ['grade_level' => 'Grade 7', 'academic_track' => 'Junior High School'],
        ['grade_level' => 'Grade 8', 'academic_track' => 'Junior High School'],
        ['grade_level' => 'Grade 9', 'academic_track' => 'Junior High School'],
        ['grade_level' => 'Grade 10', 'academic_track' => 'Junior High School'],
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
        ['grade_level' => '1st Year', 'academic_track' => 'BPEd (Bachelor of Physical Education)'],
        ['grade_level' => '2nd Year', 'academic_track' => 'BPEd (Bachelor of Physical Education)'],
        ['grade_level' => '3rd Year', 'academic_track' => 'BPEd (Bachelor of Physical Education)'],
        ['grade_level' => '4th Year', 'academic_track' => 'BPEd (Bachelor of Physical Education)'],
        ['grade_level' => '1st Year', 'academic_track' => 'BECEd (Bachelor of Early Childhood Education)'],
        ['grade_level' => '2nd Year', 'academic_track' => 'BECEd (Bachelor of Early Childhood Education)'],
        ['grade_level' => '3rd Year', 'academic_track' => 'BECEd (Bachelor of Early Childhood Education)'],
        ['grade_level' => '4th Year', 'academic_track' => 'BECEd (Bachelor of Early Childhood Education)'],
    ];
    
    $terms = ['1st Semester', '2nd Semester'];
    $inserted = 0;
    
    // If copying from previous year, get those prices
    $copy_data = [];
    if (!empty($copy_from_year)) {
        $copy_query = "SELECT grade_level, academic_track, term, tuition_fee, other_fees, total_fee FROM tuition_fee_structure WHERE school_year = ?";
        $copy_stmt = $conn->prepare($copy_query);
        $copy_stmt->bind_param("s", $copy_from_year);
        $copy_stmt->execute();
        $copy_result = $copy_stmt->get_result();
        while ($row = $copy_result->fetch_assoc()) {
            $key = $row['grade_level'] . '|' . $row['academic_track'] . '|' . $row['term'];
            $copy_data[$key] = [
                'tuition_fee' => $row['tuition_fee'],
                'other_fees' => $row['other_fees'],
                'total_fee' => $row['total_fee']
            ];
        }
        $copy_stmt->close();
    }
    
        // Use INSERT IGNORE to skip duplicates automatically
        $stmt = $conn->prepare("INSERT IGNORE INTO tuition_fee_structure (grade_level, academic_track, tuition_fee, other_fees, total_fee, school_year, term, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        
        foreach ($grade_structures as $structure) {
            foreach ($terms as $term) {
                // Get prices from copy data or use 0.00
                $key = $structure['grade_level'] . '|' . $structure['academic_track'] . '|' . $term;
                $tuition_fee = isset($copy_data[$key]) ? $copy_data[$key]['tuition_fee'] : 0.00;
                $other_fees = isset($copy_data[$key]) ? $copy_data[$key]['other_fees'] : 0.00;
                $total_fee = isset($copy_data[$key]) ? $copy_data[$key]['total_fee'] : 0.00;
                
                $stmt->bind_param("ssdddss", $structure['grade_level'], $structure['academic_track'], $tuition_fee, $other_fees, $total_fee, $school_year, $term);
                
                // Execute and count successful inserts
                if ($stmt->execute() && $stmt->affected_rows > 0) {
                    $inserted++;
                }
            }
        }
    
        $stmt->close();
        $copy_msg = !empty($copy_from_year) ? " (copied prices from $copy_from_year)" : " with ₱0.00";
        echo json_encode(['success' => true, 'message' => "School year $school_year added with $inserted fee structures$copy_msg", 'inserted' => $inserted]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>
