# System Notification Tracking Feature

## Overview
The Owner Dashboard now tracks and displays all actions performed by HR Admin, Registrar, and Super Admin regarding account management (students and employees).

## What's New

### Tracked Actions

#### Registrar Actions:
- **Student Added**: When a new student account is created
- **Student Edited**: When student information is updated
- **Student Deleted**: When a student account is soft-deleted

#### HR Actions:
- **Employee Added**: When HR creates a new employee
- **Employee Edited**: When HR updates employee information
- **Employee Deleted**: When HR soft-deletes an employee account

#### Super Admin Actions:
- **Employee Added**: When Super Admin creates a new HR employee
- **Employee Edited**: When Super Admin updates HR employee information
- **Employee Deleted**: When Super Admin soft-deletes an HR employee account

### Notification Format

Each notification displays:
- **Title**: Action type (e.g., "Student Account Edited", "New HR Employee Added")
- **From**: Person who performed the action and their role
- **Module**: Which module the action occurred in (Registrar, HR Admin)
- **Message**: Detailed description including:
  - Action performed
  - Target person's name
  - Target person's ID number
- **Timestamp**: Date and time of the action
- **Status Badge**: Visual indicator (SUCCESS for additions, INFO for edits, WARNING for deletions)

### Example Notifications

```
Title: Student Account Edited
From: John Doe (Registrar) • Module: Registrar
Message: Edited student account: Maria Santos (ID: 2024-001)
Time: Oct 20, 2025 2:30 PM
```

```
Title: New HR Employee Added
From: Admin User (Super Admin) • Module: Super Admin
Message: Added new employee: Robert Cruz (ID: EMP-2025-001)
Time: Oct 20, 2025 3:15 PM
```

```
Title: Student Account Deleted
From: Jane Smith (Registrar) • Module: Registrar
Message: Deleted student account: Pedro Reyes (ID: 2024-050)
Time: Oct 20, 2025 4:00 PM
```

## Files Modified

### New Files Created:
1. **includes/log_system_notification.php**
   - Helper function `logSystemNotification()` - Logs actions to database
   - Helper function `formatActionMessage()` - Formats user-friendly messages

### Modified Files:

#### Registrar Module:
1. **RegistrarF/Accounts/add_account.php**
   - Logs notification when new student is added

2. **RegistrarF/Accounts/view_student.php**
   - Logs notification when student is edited
   - Logs notification when student is deleted

#### HR Module:
3. **HRF/add_employee.php**
   - Logs notification when HR adds new employee

4. **HRF/edit_employee.php**
   - Logs notification when HR edits employee

5. **HRF/delete_employee.php**
   - Logs notification when HR deletes employee

#### Super Admin Module:
6. **AdminF/add_hr_employee.php**
   - Logs notification when Super Admin adds new HR employee

7. **AdminF/edit_hr_employee.php**
   - Logs notification when Super Admin edits HR employee

8. **AdminF/delete_hr_employee.php**
   - Logs notification when Super Admin deletes HR employee

## Database Structure

The notifications are stored in the existing `system_notifications` table with these fields:
- `id`: Auto-increment primary key
- `title`: Notification title
- `message`: Detailed message
- `type`: Notification type (info, warning, success, error, critical)
- `module`: Module name (Registrar, HR Admin, etc.)
- `performed_by`: Name of person who performed the action
- `user_role`: Role of the person (Registrar, Super Admin, etc.)
- `action_type`: Type of action (student_added, employee_edited, etc.)
- `target_table`: Database table affected (student_account, employees)
- `target_id`: ID of the affected record
- `old_data`: JSON of old data (for edits/deletes)
- `new_data`: JSON of new data (for adds/edits)
- `is_read`: Boolean flag for read status
- `created_at`: Timestamp of when action occurred

## Benefits for Owner

1. **Full Visibility**: Owner can see all account management activities in real-time
2. **Accountability**: Every action is tracked with who performed it and when
3. **Audit Trail**: Complete history of changes for compliance and review
4. **Quick Overview**: Dashboard shows unread notifications count
5. **Detailed Information**: Can see what was changed and by whom

## How to Use

1. **View Notifications**: Click on "System Notifications" in the Owner Dashboard sidebar
2. **Read Details**: Click on any notification to see full details
3. **Mark as Read**: Click "Mark Read" button on individual notifications
4. **Mark All Read**: Click "Mark All Read" button to clear all unread notifications
5. **Filter**: Notifications are sorted with unread first, then by most recent

## Future Enhancements

Potential additions:
- Document status change notifications
- Schedule creation/modification notifications
- Fee structure change notifications
- Bulk action notifications
- Email notifications for critical actions
- Export notification history to PDF/Excel
