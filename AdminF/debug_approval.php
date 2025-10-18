<?php
session_start();
require_once '../StudentLogin/db_conn.php';

// Check if soft delete columns exist
echo "<h2>Checking Database Structure</h2>";

// Check employees table
$result = $conn->query("SHOW COLUMNS FROM employees LIKE 'deleted_at'");
echo "<p>employees.deleted_at exists: " . ($result->num_rows > 0 ? "YES" : "NO") . "</p>";

$result = $conn->query("SHOW COLUMNS FROM employee_accounts LIKE 'deleted_at'");
echo "<p>employee_accounts.deleted_at exists: " . ($result->num_rows > 0 ? "YES" : "NO") . "</p>";

// Check for deleted employees
echo "<h2>Deleted Employees</h2>";
$result = $conn->query("SELECT id_number, first_name, last_name, deleted_at, deleted_by, deletion_reason FROM employees WHERE deleted_at IS NOT NULL");
if ($result->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Deleted At</th><th>Deleted By</th><th>Reason</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id_number']) . "</td>";
        echo "<td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['deleted_at']) . "</td>";
        echo "<td>" . htmlspecialchars($row['deleted_by']) . "</td>";
        echo "<td>" . htmlspecialchars($row['deletion_reason']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No deleted employees found</p>";
}

// Check active employees
echo "<h2>Active Employees</h2>";
$result = $conn->query("SELECT id_number, first_name, last_name, department FROM employees WHERE deleted_at IS NULL");
echo "<p>Total active employees: " . $result->num_rows . "</p>";
if ($result->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Department</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id_number']) . "</td>";
        echo "<td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['department']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check approval requests
echo "<h2>Approval Requests</h2>";
$result = $conn->query("SELECT id, request_title, request_type, status, target_id FROM owner_approval_requests ORDER BY id DESC LIMIT 5");
if ($result->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Title</th><th>Type</th><th>Status</th><th>Target ID</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['request_title']) . "</td>";
        echo "<td>" . htmlspecialchars($row['request_type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
        echo "<td>" . htmlspecialchars($row['target_id']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

$conn->close();
?>
