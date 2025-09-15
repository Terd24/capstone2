<?php
session_start();

// Check if user is logged in and is guidance
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guidance') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Database connection
$conn = new mysqli("localhost", "root", "", "onecci_db");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Convert to PDO for consistency
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
        $stmt = $pdo->query("SHOW TABLES LIKE 'violation_types'");
        if ($stmt->rowCount() == 0) {
            // Create the table
            $createTable = "CREATE TABLE violation_types (
                id INT AUTO_INCREMENT PRIMARY KEY,
                violation_name VARCHAR(100) NOT NULL UNIQUE,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            $pdo->exec($createTable);
            
            // Insert default violations
            $defaultViolations = [
                'Dress Code Violation',
                'Late Arrival',
                'Disruptive Behavior',
                'Cheating',
                'Bullying',
                'Vandalism',
                'Fighting',
                'Smoking/Vaping',
                'Inappropriate Language',
                'Skipping Class'
            ];
            
            $stmt = $pdo->prepare("INSERT INTO violation_types (violation_name) VALUES (?)");
            foreach ($defaultViolations as $violation) {
                $stmt->execute([$violation]);
            }
        }
        
        // Fetch all active violation types
        $stmt = $pdo->prepare("SELECT * FROM violation_types WHERE is_active = 1 ORDER BY violation_name");
        $stmt->execute();
        $violationTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $violationTypes]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ensure table exists before any POST operations
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'violation_types'");
        if ($stmt->rowCount() == 0) {
            // Create the table with default violations (same as GET)
            $createTable = "CREATE TABLE violation_types (
                id INT AUTO_INCREMENT PRIMARY KEY,
                violation_name VARCHAR(100) NOT NULL UNIQUE,
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
        // Add or edit violation type
        $violation_name = trim($_POST['violation_name'] ?? '');
        $id = intval($_POST['id'] ?? 0);
        
        // Validation
        if (empty($violation_name)) {
            echo json_encode(['success' => false, 'message' => 'Violation name is required']);
            exit;
        }
        
        try {
            if ($action === 'edit' && $id > 0) {
                // Check if violation type exists for editing
                $stmt = $pdo->prepare("SELECT id FROM violation_types WHERE id = ? AND is_active = 1");
                $stmt->execute([$id]);
                
                if ($stmt->rowCount() === 0) {
                    echo json_encode(['success' => false, 'message' => 'Violation type not found']);
                    exit;
                }
                
                // Check if name already exists (excluding current record)
                $stmt = $pdo->prepare("SELECT id FROM violation_types WHERE violation_name = ? AND id != ? AND is_active = 1");
                $stmt->execute([$violation_name, $id]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => false, 'message' => 'Violation type name already exists']);
                    exit;
                }
                
                // Update violation type
                $stmt = $pdo->prepare("UPDATE violation_types SET violation_name = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$violation_name, $id]);
                
                echo json_encode(['success' => true, 'message' => 'Violation type updated successfully']);
            } else {
                // Check if violation type already exists
                $stmt = $pdo->prepare("SELECT id FROM violation_types WHERE violation_name = ? AND is_active = 1");
                $stmt->execute([$violation_name]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => false, 'message' => 'Violation type already exists']);
                    exit;
                }
                
                // Insert new violation type
                $stmt = $pdo->prepare("INSERT INTO violation_types (violation_name, is_active, created_at) VALUES (?, 1, CURRENT_TIMESTAMP)");
                $stmt->execute([$violation_name]);
                
                echo json_encode(['success' => true, 'message' => 'Violation type added successfully']);
            }
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error saving violation type: ' . $e->getMessage()]);
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid violation type ID']);
            exit;
        }
        
        try {
            // Soft delete - mark as inactive
            $stmt = $pdo->prepare("UPDATE violation_types SET is_active = 0, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Violation type deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Violation type not found']);
            }
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error deleting violation type']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}
?>
