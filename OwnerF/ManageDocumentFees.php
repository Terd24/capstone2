<?php
session_start();

// Check if user is logged in and is owner
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Database connection
include("../StudentLogin/db_conn.php");

// Convert to PDO for consistency
try {
    $pdo = new PDO("mysql:host=localhost;dbname=onecci_db", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Create document_fees table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS document_fees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        document_name VARCHAR(100) NOT NULL UNIQUE,
        fee_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_document_name (document_name),
        INDEX idx_active (is_active)
    )");
} catch(PDOException $e) {
    // Table might already exist
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'list';
    
    if ($action === 'list') {
        try {
            // Get all document types from document_types table
            $stmt = $pdo->query("SELECT name FROM document_types WHERE is_requestable = 1 ORDER BY name");
            $documentTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Get existing fees
            $stmt = $pdo->prepare("SELECT document_name, fee_amount FROM document_fees WHERE is_active = 1");
            $stmt->execute();
            $existingFees = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $existingFees[$row['document_name']] = $row['fee_amount'];
            }
            
            // Combine data
            $result = [];
            foreach ($documentTypes as $docName) {
                $result[] = [
                    'document_name' => $docName,
                    'fee_amount' => $existingFees[$docName] ?? 0.00
                ];
            }
            
            echo json_encode(['success' => true, 'data' => $result]);
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_fee') {
        $document_name = trim($_POST['document_name'] ?? '');
        $fee_amount = floatval($_POST['fee_amount'] ?? 0);
        
        if (empty($document_name)) {
            echo json_encode(['success' => false, 'message' => 'Document name is required']);
            exit;
        }
        
        if ($fee_amount < 0) {
            echo json_encode(['success' => false, 'message' => 'Fee amount must be non-negative']);
            exit;
        }
        
        try {
            // Check if fee already exists
            $stmt = $pdo->prepare("SELECT id FROM document_fees WHERE document_name = ? AND is_active = 1");
            $stmt->execute([$document_name]);
            
            if ($stmt->rowCount() > 0) {
                // Update existing fee
                $stmt = $pdo->prepare("UPDATE document_fees SET fee_amount = ?, updated_at = CURRENT_TIMESTAMP WHERE document_name = ? AND is_active = 1");
                $stmt->execute([$fee_amount, $document_name]);
            } else {
                // Insert new fee
                $stmt = $pdo->prepare("INSERT INTO document_fees (document_name, fee_amount, is_active) VALUES (?, ?, 1)");
                $stmt->execute([$document_name, $fee_amount]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Document fee updated successfully']);
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error updating fee: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}
?>
