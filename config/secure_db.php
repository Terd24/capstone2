<?php
/**
 * Secure Database Operations
 * Prevents SQL Injection with prepared statements
 */

require_once __DIR__ . '/../StudentLogin/db_conn.php';
require_once __DIR__ . '/security.php';

class SecureDB {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Secure SELECT query
     */
    public function select($table, $columns = '*', $where = [], $orderBy = '', $limit = '') {
        // Sanitize table name
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        
        // Build query
        $sql = "SELECT $columns FROM $table";
        
        // Add WHERE clause
        if (!empty($where)) {
            $conditions = [];
            foreach ($where as $key => $value) {
                $conditions[] = "$key = ?";
            }
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        // Add ORDER BY
        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }
        
        // Add LIMIT
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        
        // Prepare and execute
        $stmt = $this->conn->prepare($sql);
        if (!empty($where)) {
            $types = str_repeat('s', count($where));
            $stmt->bind_param($types, ...array_values($where));
        }
        $stmt->execute();
        
        return $stmt->get_result();
    }
    
    /**
     * Secure INSERT query
     */
    public function insert($table, $data) {
        // Sanitize table name
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        
        // Build query
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        
        // Prepare and execute
        $stmt = $this->conn->prepare($sql);
        $types = str_repeat('s', count($data));
        $stmt->bind_param($types, ...array_values($data));
        
        return $stmt->execute();
    }
    
    /**
     * Secure UPDATE query
     */
    public function update($table, $data, $where) {
        // Sanitize table name
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        
        // Build SET clause
        $setClauses = [];
        foreach ($data as $key => $value) {
            $setClauses[] = "$key = ?";
        }
        
        // Build WHERE clause
        $whereClauses = [];
        foreach ($where as $key => $value) {
            $whereClauses[] = "$key = ?";
        }
        
        $sql = "UPDATE $table SET " . implode(', ', $setClauses) . " WHERE " . implode(' AND ', $whereClauses);
        
        // Prepare and execute
        $stmt = $this->conn->prepare($sql);
        $allValues = array_merge(array_values($data), array_values($where));
        $types = str_repeat('s', count($allValues));
        $stmt->bind_param($types, ...$allValues);
        
        return $stmt->execute();
    }
    
    /**
     * Secure DELETE query
     */
    public function delete($table, $where) {
        // Sanitize table name
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        
        // Build WHERE clause
        $whereClauses = [];
        foreach ($where as $key => $value) {
            $whereClauses[] = "$key = ?";
        }
        
        $sql = "DELETE FROM $table WHERE " . implode(' AND ', $whereClauses);
        
        // Prepare and execute
        $stmt = $this->conn->prepare($sql);
        $types = str_repeat('s', count($where));
        $stmt->bind_param($types, ...array_values($where));
        
        return $stmt->execute();
    }
}

// Create global secure database instance
$secureDB = new SecureDB($conn);
?>
