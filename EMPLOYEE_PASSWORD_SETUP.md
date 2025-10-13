# Employee First-Time Password Change Setup

## Quick Setup

**Run this URL in your browser:**
```
http://localhost/onecci/setup_all_password_changes.php
```

This will automatically set up password change for BOTH students and employees!

## What's New

### For Employees (Registrar, Cashier, Guidance, HR, Attendance, Teacher)
When Admin/SuperAdmin creates an employee account:
1. Employee logs in with temporary password
2. Redirected to password change page
3. Must create strong password (8+ chars, uppercase, lowercase, number)
4. After changing, goes to their dashboard

## Files Created/Modified

### New Files:
- ✅ `EmployeePortal/change_password.php` - Employee password change page
- ✅ `setup_employee_password_change.sql` - SQL for employee table
- ✅ `setup_all_password_changes.php` - One-click setup for everything

### Modified Files:
- ✅ `StudentLogin/login.php` - Checks employee password change flag
- ✅ `AdminF/create_employee_account.php` - Sets flag when creating employees

## Testing

### Test Employee Password Change:
1. Login as SuperAdmin
2. Go to HR Accounts section
3. Create a new employee account (any role)
4. Logout
5. Login with that employee account
6. You should see the password change page!

### Test Student Password Change:
1. Login as Registrar
2. Create a new student account
3. Logout
4. Login with that student account
5. You should see the password change page!

## Password Requirements (Same for Students & Employees)
- ✅ Minimum 8 characters
- ✅ At least one uppercase letter (A-Z)
- ✅ At least one lowercase letter (a-z)
- ✅ At least one number (0-9)
- ✅ No spaces allowed
- ✅ No common passwords (password123, 123456, etc.)

## Upload to Hostinger

When ready, upload these files:
1. `EmployeePortal/change_password.php`
2. `StudentLogin/login.php`
3. `AdminF/create_employee_account.php`
4. `RegistrarF/Accounts/add_account.php`
5. `StudentLogin/change_password.php`

Then run the SQL on Hostinger's phpMyAdmin:
```sql
ALTER TABLE student_account 
ADD COLUMN IF NOT EXISTS must_change_password TINYINT(1) DEFAULT 1;

ALTER TABLE employee_accounts 
ADD COLUMN IF NOT EXISTS must_change_password TINYINT(1) DEFAULT 1;
```

## Done! 🎉
Both students and employees now have secure first-time password change!
