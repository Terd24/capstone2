<?php
/**
 * Log System Notification
 * 
 * This function logs actions performed by HR Admin, Registrar, and Super Admin
 * to notify the Owner about account changes and other important activities.
 * 
 * @param mysqli $conn Database connection
 * @param string $title Notification title
 * @param string $message Notification message
 * @param string $type Notification type: 'info', 'warning', 'success', 'error', 'critical'
 * @param string $module Module where action occurred (e.g., 'Registrar', 'HR', 'Admin')
 * @param string $performed_by Name of person who performed the action
 * @param string $user_role Role of person who performed the action
 * @param string $action_type Type of action (e.g., 'student_edited', 'student_deleted', 'employee_added')
 * @param string $target_table Target table affected (optional)
 * @param string $target_id Target record ID (optional)
 * @param array $old_data Old data before change (optional)
 * @param array $new_data New data after change (optional)
 * @return bool Success status
 */
function logSystemNotification($conn, $title, $message, $type, $module, $performed_by, $user_role, $action_type, $target_table = null, $target_id = null, $old_data = null, $new_data = null) {
    try {
        // Convert arrays to JSON if provided
        $old_data_json = $old_data ? json_encode($old_data) : null;
        $new_data_json = $new_data ? json_encode($new_data) : null;
        
        $stmt = $conn->prepare("INSERT INTO system_notifications 
            (title, message, type, module, performed_by, user_role, action_type, target_table, target_id, old_data, new_data, is_read, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, FALSE, NOW())");
        
        $stmt->bind_param("sssssssssss", 
            $title, 
            $message, 
            $type, 
            $module, 
            $performed_by, 
            $user_role, 
            $action_type, 
            $target_table, 
            $target_id, 
            $old_data_json, 
            $new_data_json
        );
        
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (Exception $e) {
        error_log("Failed to log system notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Format a user-friendly action message
 * 
 * @param string $action_type Type of action
 * @param string $target_name Name of the target (student/employee name)
 * @param string $target_id ID of the target
 * @return string Formatted message
 */
function formatActionMessage($action_type, $target_name, $target_id) {
    $messages = [
        'student_added' => "Added new student: $target_name (ID: $target_id)",
        'student_edited' => "Edited student account: $target_name (ID: $target_id)",
        'student_deleted' => "Deleted student account: $target_name (ID: $target_id)",
        'student_restored' => "Restored student account: $target_name (ID: $target_id)",
        'employee_added' => "Added new employee: $target_name (ID: $target_id)",
        'employee_edited' => "Edited employee account: $target_name (ID: $target_id)",
        'employee_deleted' => "Deleted employee account: $target_name (ID: $target_id)",
        'employee_restored' => "Restored employee account: $target_name (ID: $target_id)",
        'schedule_created' => "Created new schedule: $target_name",
        'schedule_edited' => "Edited schedule: $target_name",
        'schedule_deleted' => "Deleted schedule: $target_name",
        'document_status_changed' => "Changed document status for: $target_name (ID: $target_id)",
    ];
    
    return $messages[$action_type] ?? "Performed action: $action_type on $target_name (ID: $target_id)";
}
?>
