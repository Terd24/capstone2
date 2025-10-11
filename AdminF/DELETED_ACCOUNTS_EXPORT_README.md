# Deleted Accounts Export System

## Overview
This system replaces permanent deletion with file export functionality, allowing Super Admins to save deleted account information to files instead of permanently removing them from the system.

## Features

### 1. Export to File Instead of Permanent Deletion
- **Student Accounts**: Exports complete student information including grades, fees, and payment history
- **Employee Accounts**: Exports employee information including attendance records and system account details
- **Comprehensive Data**: All related records are included in the export file

### 2. File Format
- **Format**: JSON (JavaScript Object Notation)
- **Structure**: Well-formatted with proper indentation for readability
- **Encoding**: UTF-8 with Unicode support
- **Naming**: `DELETED_[TYPE]_[ID]_[NAME]_[TIMESTAMP].json`

### 3. Export Contents

#### Student Export Includes:
- Complete student account information
- Parent account details
- All grades records
- Fee items and balances
- Payment history
- Export metadata (date, exported by, etc.)

#### Employee Export Includes:
- Complete employee information
- System account details (username, role)
- Attendance records
- Export metadata

### 4. Security Features
- **Access Control**: Only Super Admin can export files
- **Audit Logging**: All exports are logged in system_logs table
- **Data Validation**: Validates account existence and deletion status
- **Safe File Handling**: Prevents directory traversal and ensures secure file operations

## Usage Instructions

### For Super Admins:
1. Navigate to **Super Admin Dashboard** → **Deleted Items**
2. Find the deleted account you want to export
3. Click the **"Export to File"** button (blue button)
4. Confirm the export in the dialog box
5. The file will automatically download to your computer
6. The account remains in the deleted items list for potential restoration

### File Storage:
- **Server Copy**: Files are saved to `/exports/deleted_accounts/` directory
- **Download Copy**: Files are automatically downloaded to your computer
- **Backup**: Both copies serve as backup for the deleted account data

## File Structure Example

```json
{
    "account_type": "student",
    "export_date": "2025-10-11 14:30:00",
    "exported_by": "Super Administrator",
    "student_info": {
        "id_number": "S2025006",
        "first_name": "Carla",
        "last_name": "Mendoza",
        "academic_track": "ABM",
        "grade_level": "11-B",
        // ... complete student data
    },
    "grades_records": [
        // All grades for the student
    ],
    "fee_items": [
        // All fee items and balances
    ],
    "payment_history": [
        // Complete payment history
    ]
}
```

## Benefits

### 1. Data Preservation
- **No Data Loss**: Complete account information is preserved
- **Comprehensive Backup**: All related records included
- **Audit Trail**: Export actions are logged

### 2. Compliance & Legal
- **Record Keeping**: Maintains records for legal/audit purposes
- **Data Recovery**: Allows data recovery if needed
- **Transparency**: Clear audit trail of all actions

### 3. Administrative Control
- **Flexible Management**: Can export data without losing it
- **Space Management**: Removes data from active system while preserving it
- **Easy Access**: Files can be easily shared or archived

## Technical Requirements

### Database Tables Used:
- `student_account` - Student information
- `parent_account` - Parent information
- `employees` - Employee information
- `employee_accounts` - Employee system accounts
- `grades_record` - Student grades
- `student_fee_items` - Student fees
- `student_payments` - Payment history
- `teacher_attendance` - Employee attendance
- `system_logs` - Export audit logs

### File Permissions:
- Export directory must be writable by web server
- Proper file permissions for security

### Browser Compatibility:
- Modern browsers with JavaScript enabled
- File download capability required

## Maintenance

### Regular Tasks:
1. **Monitor Export Directory**: Check disk space usage
2. **Archive Old Exports**: Move old files to long-term storage
3. **Review Logs**: Check system_logs for export activities
4. **Backup Exports**: Include export files in backup procedures

### Troubleshooting:
- **Export Fails**: Check file permissions and disk space
- **Download Issues**: Verify browser settings and popup blockers
- **Missing Data**: Ensure all related tables exist and have proper relationships

## Security Considerations

### Access Control:
- Only Super Admin role can access export functionality
- Session validation on all export requests
- Input validation and sanitization

### Data Protection:
- Exported files contain sensitive information
- Secure file storage and transmission
- Proper file naming to prevent conflicts

### Audit Trail:
- All export actions logged with timestamp
- User identification and IP tracking
- Detailed action descriptions

## Support

For technical support or questions about the export system:
1. Check the system logs for error messages
2. Verify file permissions and directory structure
3. Contact system administrator for database issues
4. Review this documentation for usage guidelines
