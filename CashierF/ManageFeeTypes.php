<?php
session_start();

// Check if user is logged in and is a cashier
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'cashier') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Database connection - use same database as other files
$conn = new mysqli("localhost", "root", "", "onecci_db");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Convert to PDO for consistency with existing code
try {
    $pdo = new PDO("mysql:host=localhost;dbname=onecci_db", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // First check if table exists, create if not
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'fee_types'");
        if ($stmt->rowCount() == 0) {
            // Create the table
            $createTable = "CREATE TABLE fee_types (
                id INT AUTO_INCREMENT PRIMARY KEY,
                fee_name VARCHAR(100) NOT NULL UNIQUE,
                default_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            $pdo->exec($createTable);
        }
        
        // Fetch all active fee types
        $stmt = $pdo->prepare("SELECT * FROM fee_types WHERE is_active = 1 ORDER BY fee_name");
        $stmt->execute();
        $feeTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $feeTypes]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ensure table exists before any POST operations
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'fee_types'");
        if ($stmt->rowCount() == 0) {
            // Create the table
            $createTable = "CREATE TABLE fee_types (
                id INT AUTO_INCREMENT PRIMARY KEY,
                fee_name VARCHAR(100) NOT NULL UNIQUE,
                default_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            $pdo->exec($createTable);
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error creating table: ' . $e->getMessage()]);
        exit;
    }

    $action = $_POST['action'] ?? 'add';
    
    if ($action === 'add' || $action === 'edit') {
        // Add or edit fee type
        $fee_name = trim($_POST['fee_name'] ?? '');
        $default_amount = floatval($_POST['default_amount'] ?? 0);
        $id = intval($_POST['id'] ?? 0);
        
        // Debug logging
        error_log("Fee Type Debug - Action: $action, Name: $fee_name, Amount: $default_amount, ID: $id");
        
        // Validation
        if (empty($fee_name)) {
            echo json_encode(['success' => false, 'message' => 'Fee name is required']);
            exit;
        }
        
        if ($default_amount < 0) {
            echo json_encode(['success' => false, 'message' => 'Default amount must be non-negative']);
            exit;
        }
        
        try {
            if ($action === 'edit' && $id > 0) {
                // Check if fee type exists for editing
                $stmt = $pdo->prepare("SELECT id FROM fee_types WHERE id = ? AND is_active = 1");
                $stmt->execute([$id]);
                
                if ($stmt->rowCount() === 0) {
                    echo json_encode(['success' => false, 'message' => 'Fee type not found']);
                    exit;
                }
                
                // Check if name already exists (excluding current record)
                $stmt = $pdo->prepare("SELECT id FROM fee_types WHERE fee_name = ? AND id != ? AND is_active = 1");
                $stmt->execute([$fee_name, $id]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => false, 'message' => 'Fee type name already exists']);
                    exit;
                }
                
                // Update fee type
                $stmt = $pdo->prepare("UPDATE fee_types SET fee_name = ?, default_amount = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$fee_name, $default_amount, $id]);
                
                echo json_encode(['success' => true, 'message' => 'Fee type updated successfully']);
            } else {
                // Check if fee type already exists
                $stmt = $pdo->prepare("SELECT id FROM fee_types WHERE fee_name = ? AND is_active = 1");
                $stmt->execute([$fee_name]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => false, 'message' => 'Fee type already exists']);
                    exit;
                }
                
                // Insert new fee type
                $stmt = $pdo->prepare("INSERT INTO fee_types (fee_name, default_amount, is_active, created_at) VALUES (?, ?, 1, CURRENT_TIMESTAMP)");
                $stmt->execute([$fee_name, $default_amount]);
                
                echo json_encode(['success' => true, 'message' => 'Fee type added successfully']);
            }
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error saving fee type: ' . $e->getMessage()]);
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid fee type ID']);
            exit;
        }
        
        try {
            // Soft delete - mark as inactive
            $stmt = $pdo->prepare("UPDATE fee_types SET is_active = 0, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Fee type deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Fee type not found']);
            }
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error deleting fee type']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}
?>
