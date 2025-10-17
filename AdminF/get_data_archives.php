<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../StudentLogin/db_conn.php';

$type = $_GET['type'] ?? 'login';
$search = $_GET['search'] ?? '';

if ($type === 'login') {
    try {
        // First check if table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'login_logs_archive'");
        if (!$tableCheck || $tableCheck->num_rows === 0) {
            echo json_encode(['success' => true, 'records' => [], 'type' => 'login', 'message' => 'Archive table not yet created']);
            exit;
        }
        
        // Query with LEFT JOINs to get user details
        // Join with employee_accounts and employees table to get full name
        // Join with parent_account and student_account to get parent's child info
        // Use login_activity to get the most recent id_number and role for each user
        $query = "SELECT 
                    lla.*,
                    COALESCE(
                        ea.employee_id,
                        pa.child_id,
                        la.id_number,
                        lla.username
                    ) as id_number,
                    COALESCE(
                        CONCAT(e.first_name, ' ', e.last_name),
                        CASE 
                            WHEN pa.child_id IS NOT NULL THEN CONCAT('Parent of ', COALESCE(CONCAT(sa.first_name, ' ', sa.last_name), pa.child_id))
                            ELSE lla.username
                        END
                    ) as full_name,
                    COALESCE(ea.role, la.role, 
                        CASE 
                            WHEN lla.user_type = 'student' THEN 'student'
                            WHEN lla.user_type = 'parent' THEN 'parent'
                            ELSE lla.user_type
                        END
                    ) as role
                  FROM login_logs_archive lla
                  LEFT JOIN employee_accounts ea ON lla.username = ea.username
                  LEFT JOIN employees e ON ea.employee_id = e.id_number
                  LEFT JOIN parent_account pa ON lla.username = pa.username
                  LEFT JOIN student_account sa ON pa.child_id = sa.id_number
                  LEFT JOIN (
                      SELECT username, id_number, role, user_type
                      FROM login_activity
                      WHERE id IN (
                          SELECT MAX(id) 
                          FROM login_activity 
                          GROUP BY username
                      )
                  ) la ON lla.username = la.username
                  WHERE COALESCE(ea.role, la.role, lla.user_type) NOT IN ('superadmin', 'owner')
                    AND lla.username NOT IN ('superadmin', 'owner')";
        
        if ($search) {
            $query .= " AND lla.username LIKE ?";
        }
        $query .= " ORDER BY lla.archived_at DESC LIMIT 100";
        
        if ($search) {
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $search_param = "%$search%";
            $stmt->bind_param('s', $search_param);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $conn->query($query);
            if (!$result) {
                throw new Exception("Query failed: " . $conn->error);
            }
        }
        
        $records = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                // Ensure all required fields exist with defaults
                $row['user_type'] = $row['user_type'] ?? 'unknown';
                $row['role'] = $row['role'] ?? $row['user_type'] ?? 'user';
                $row['id_number'] = $row['id_number'] ?? $row['username'];
                $row['full_name'] = $row['full_name'] ?? $row['username'];
                $row['logout_time'] = $row['logout_time'] ?? null;
                $row['session_duration'] = $row['session_duration'] ?? null;
                $records[] = $row;
            }
        }
        
        echo json_encode(['success' => true, 'records' => $records, 'type' => 'login']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    
} else {
    $query = "SELECT * FROM attendance_archive";
    if ($search) {
        $query .= " WHERE id_number LIKE ? OR name LIKE ?";
    }
    $query .= " ORDER BY archived_at DESC LIMIT 100";
    
    if ($search) {
        $stmt = $conn->prepare($query);
        $search_param = "%$search%";
        $stmt->bind_param('ss', $search_param, $search_param);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($query);
    }
    
    $records = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'records' => $records, 'type' => 'attendance']);
}

$conn->close();
?>
