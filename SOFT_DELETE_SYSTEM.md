# Soft Delete System - Implementation Guide

## Overview
The system now implements **soft delete** functionality for both students and employees. When records are deleted by Registrar or HR Admin, they are not permanently removed from the database. Instead, they are marked as deleted and moved to the Super Admin's "Deleted Items" section for review and potential restoration.

## How It Works

### 1. Database Structure
Three columns have been added to both `student_account` and `employees` tables:
- `deleted_at` (DATETIME) - Timestamp when the record was deleted
- `deleted_by` (VARCHAR) - Name of the user who deleted the record
- `deleted_reason` (TEXT) - Reason for deletion

### 2. Delete Operations

#### Student Deletion (Registrar)
**File:** `RegistrarF/Accounts/view_student.php`
- When a registrar deletes a student, the record is marked with:
  - `deleted_at` = Current timestamp
  - `deleted_by` = Registrar's name
  - `deleted_reason` = "Deleted by registrar for administrative purposes"
- The student disappears from the Registrar's view but remains in the database
- The record appears in Super Admin's "Deleted Items" section

#### Employee Deletion (HR Admin)
**File:** `HRF/delete_employee.php`
- When HR Admin deletes an employee, the record is marked with:
  - `deleted_at` = Current timestamp
  - `deleted_by` = HR Admin's name
  - `deleted_reason` = "Deleted by HR for administrative purposes"
- The employee disappears from HR's view but remains in the database
- The record appears in Super Admin's "Deleted Items" section

### 3. Viewing Deleted Items

#### Super Admin Dashboard
**File:** `AdminF/SuperAdminDashboard.php`
- Navigate to "Deleted Items" section
- View all deleted students and employees
- See deletion details (who deleted, when, and why)

#### Standalone Page
**File:** `AdminF/deleted_items.php`
- Dedicated page for managing deleted items
- Shows summary cards with counts
- Displays detailed tables for both students and employees

### 4. Restoration Process

**File:** `AdminF/restore_record.php`

To restore a deleted record:
1. Click the "Restore" button next to the deleted item
2. Confirm the restoration
3. The record's soft delete fields are cleared:
   - `deleted_at` = NULL
   - `deleted_by` = NULL
   - `deleted_reason` = NULL
4. The record reappears in the original system (Registrar or HR)
5. Action is logged in system_logs

### 5. Export Functionality

**File:** `AdminF/export_deleted_account.php`

Before permanent deletion, records can be exported:
- Exports complete record with all related data
- For students: includes grades, fees, payments, attendance
- For employees: includes attendance records
- Saves as JSON file with timestamp
- File stored in `exports/deleted_accounts/` directory

## Setup Instructions

### Step 1: Run Database Migration
Visit: `AdminF/setup_soft_delete.php`

This will:
- Add soft delete columns to `student_account` table
- Add soft delete columns to `employees` table
- Create `system_logs` table if it doesn't exist

### Step 2: Verify Implementation
1. Test student deletion from Registrar system
2. Test employee deletion from HR system
3. Check Super Admin "Deleted Items" section
4. Test restoration functionality

## File Structure

```
AdminF/
├── setup_soft_delete.php          # Database migration script
├── deleted_items.php               # Standalone deleted items page
├── restore_record.php              # Restoration endpoint
├── export_deleted_account.php      # Export functionality
└── SuperAdminDashboard.php         # Includes deleted items section

RegistrarF/
└── Accounts/
    └── view_student.php            # Student soft delete implementation

HRF/
└── delete_employee.php             # Employee soft delete implementation
```

## Benefits

1. **Data Safety**: No accidental permanent deletions
2. **Audit Trail**: Complete record of who deleted what and when
3. **Recovery**: Easy restoration of mistakenly deleted records
4. **Compliance**: Maintains data for administrative review
5. **Accountability**: Tracks deletion actions by user

## Query Examples

### Get All Deleted Students
```sql
SELECT * FROM student_account 
WHERE deleted_at IS NOT NULL 
ORDER BY deleted_at DESC;
```

### Get All Active Students
```sql
SELECT * FROM student_account 
WHERE deleted_at IS NULL;
```

### Restore a Student
```sql
UPDATE student_account 
SET deleted_at = NULL, deleted_by = NULL, deleted_reason = NULL 
WHERE id_number = 'S2025006';
```

### Get All Deleted Employees
```sql
SELECT * FROM employees 
WHERE deleted_at IS NOT NULL 
ORDER BY deleted_at DESC;
```

## Important Notes

1. **Automatic Column Creation**: The delete operations automatically create the soft delete columns if they don't exist
2. **Backward Compatibility**: Existing queries that don't filter by `deleted_at` will still work
3. **Performance**: Add indexes on `deleted_at` column for better performance:
   ```sql
   CREATE INDEX idx_deleted_at ON student_account(deleted_at);
   CREATE INDEX idx_deleted_at ON employees(deleted_at);
   ```

## Future Enhancements

1. **Permanent Deletion**: Add approval workflow for permanent deletion
2. **Auto-Archive**: Automatically archive deleted records after X days
3. **Bulk Operations**: Restore or export multiple records at once
4. **Email Notifications**: Notify Super Admin when records are deleted
5. **Deletion Reports**: Generate reports of deletion activities

## Troubleshooting

### Records Not Appearing in Deleted Items
- Check if soft delete columns exist in the table
- Run `setup_soft_delete.php` to add missing columns
- Verify the `deleted_at` field is not NULL

### Restoration Not Working
- Check Super Admin permissions
- Verify `restore_record.php` is accessible
- Check system_logs for error messages

### Export Failing
- Ensure `exports/deleted_accounts/` directory exists and is writable
- Check file permissions (755 for directories, 644 for files)
- Verify PHP has write access to the exports directory

## Support

For issues or questions about the soft delete system, contact the IT department or refer to the system logs at `AdminF/SuperAdminDashboard.php` → System Maintenance section.
