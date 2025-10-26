<?php
// Prevent any output before JSON
ob_start();

session_start();

// Check if user is logged in as registrar
if (!isset($_SESSION['registrar_id']) || $_SESSION['role'] !== 'registrar') {
    ob_end_clean();
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../StudentLogin/db_conn.php';

// Clear any output buffer and set JSON header
ob_end_clean();
header('Content-Type: application/json');

try {
    // Check if document_requests table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'document_requests'");
    if ($table_check->num_rows === 0) {
        throw new Exception("Table 'document_requests' does not exist");
    }

    // Get the last check timestamp from the request
    $last_check = isset($_GET['last_check']) ? $_GET['last_check'] : date('Y-m-d H:i:s', strtotime('-5 minutes'));

    // Check for new document requests since last check
    $query = "SELECT * FROM document_requests 
        WHERE date_requested > ? 
        ORDER BY date_requested DESC";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Database prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $last_check);
    if (!$stmt->execute()) {
        throw new Exception("Query execution failed: " . $stmt->error);
    }
    $result = $stmt->get_result();

    $new_requests = [];
    while ($row = $result->fetch_assoc()) {
        $new_requests[] = [
            'id' => $row['id'],
            'student_id' => $row['student_id'] ?? '',
            'student_name' => $row['student_name'] ?? '',
            'document_type' => $row['document_type'] ?? '',
            'purpose' => $row['purpose'] ?? '',
            'status' => $row['status'] ?? '',
            'date_requested' => $row['date_requested'] ?? '',
            'date_claimed' => $row['date_claimed'] ?? null,
            'is_read' => $row['is_read'] ?? 0,
            'timestamp' => strtotime($row['date_requested'] ?? 'now')
        ];
    }

    // Get current counts
    $counts_query = "SELECT 
        COUNT(*) as `all`,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status IN ('Ready to Claim', 'Ready for Claiming') THEN 1 ELSE 0 END) as ready,
        SUM(CASE WHEN status = 'Claimed' THEN 1 ELSE 0 END) as claimed,
        SUM(CASE WHEN status IN ('Declined', 'Decline') THEN 1 ELSE 0 END) as declined
        FROM document_requests";

    $counts_result = $conn->query($counts_query);
    if (!$counts_result) {
        throw new Exception("Counts query failed: " . $conn->error);
    }
    $counts = $counts_result->fetch_assoc();

    // Check if is_read column exists
    $column_check = $conn->query("SHOW COLUMNS FROM document_requests LIKE 'is_read'");
    $has_is_read = ($column_check && $column_check->num_rows > 0);
    
    // Get unread count for recent requests (not claimed/declined and within 3 days)
    if ($has_is_read) {
        $unread_query = "SELECT COUNT(*) as unread_count 
            FROM document_requests 
            WHERE is_read = 0 
            AND status NOT IN ('Claimed', 'Declined', 'Decline') 
            AND date_requested >= DATE_SUB(NOW(), INTERVAL 3 DAY)";
    } else {
        // Fallback if is_read column doesn't exist
        $unread_query = "SELECT COUNT(*) as unread_count 
            FROM document_requests 
            WHERE status NOT IN ('Claimed', 'Declined', 'Decline') 
            AND date_requested >= DATE_SUB(NOW(), INTERVAL 3 DAY)";
    }
    
    $unread_result = $conn->query($unread_query);
    if (!$unread_result) {
        throw new Exception("Unread query failed: " . $conn->error);
    }
    $unread_count = $unread_result->fetch_assoc()['unread_count'];

    echo json_encode([
        'success' => true,
        'new_requests' => $new_requests,
        'counts' => $counts,
        'unread_count' => $unread_count,
        'current_time' => date('Y-m-d H:i:s')
    ]);

    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}
?>
