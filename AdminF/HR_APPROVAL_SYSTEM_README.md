# HR Account Approval System

## Overview
The Super Admin now requires Owner approval for adding or deleting HR employee accounts. This ensures proper oversight and control over HR personnel management.

## How It Works

### Adding HR Employees
1. **Super Admin** fills out the "Add HR Account" form in the HR Accounts Management section
2. Instead of immediately creating the account, an approval request is sent to the Owner
3. **Owner** sees the pending request in their Dashboard
4. **Owner** can review the details and either:
   - **Approve**: The HR employee and their account are created automatically
   - **Reject**: The request is denied with optional comments
5. **Super Admin** is notified of the decision

### Deleting HR Employees
1. **Super Admin** clicks "Delete Employee" on an HR account
2. An approval request is created and sent to the Owner
3. **Owner** reviews the deletion request
4. **Owner** can either:
   - **Approve**: The HR employee and their account are permanently deleted
   - **Reject**: The deletion is cancelled
5. **Super Admin** is notified of the decision

## Request Types
- `add_hr_employee` - Request to add a new HR employee with system account
- `delete_hr_employee` - Request to delete an existing HR employee

## Database Tables
- `owner_approval_requests` - Stores all approval requests
- `employees` - Employee records
- `employee_accounts` - Employee system accounts

## Files Involved
- `AdminF/add_hr_employee.php` - Creates approval request for adding HR employees
- `AdminF/delete_hr_employee.php` - Creates approval request for deleting HR employees
- `OwnerF/Dashboard.php` - Displays and processes approval requests
- `AdminF/SuperAdminDashboard.php` - HR management interface

## Testing
1. Login as Super Admin
2. Go to HR Accounts Management
3. Click "+ Add HR Account"
4. Fill out the form and submit
5. Verify approval request is created
6. Login as Owner
7. See the pending request in Dashboard
8. Approve or reject it
9. Verify the action is executed (if approved)

## Priority Levels
- **High**: All HR account add/delete requests are marked as high priority
- Requests are displayed in priority order in the Owner Dashboard

## Security
- Only Super Admin can create HR account requests
- Only Owner can approve/reject requests
- All actions are logged and auditable
- Passwords are hashed before storage (only when approved)

