# Department Head Module - Setup Guide

## Quick Setup Instructions

### 1. Create Department Head Account

You have two options:

#### Option A: Using phpMyAdmin (Recommended)
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Select your database (usually `onecci_db`)
3. Click on "SQL" tab
4. Copy and paste the SQL from `setup_department_head.sql`
5. Update the password hash (see below)
6. Click "Go" to execute

#### Option B: Manual Entry
1. Go to `employees` table and add a new employee
2. Go to `employee_accounts` table and create an account with:
   - `employee_id`: The ID from step 1
   - `username`: Your choice (e.g., "depthead")
   - `password`: Hashed password (see below)
   - `role`: `department_head`

### 2. Generate Password Hash

To create a secure password hash:

1. Create a temporary PHP file (e.g., `hash_password.php`):
```php
<?php
echo password_hash('YourPasswordHere', PASSWORD_DEFAULT);
?>
```

2. Run it in your browser: `http://localhost/onecci/hash_password.php`
3. Copy the generated hash
4. Use it in the SQL INSERT statement or database entry
5. Delete the temporary file for security

### 3. Test Login

1. Go to: `http://localhost/onecci/admin_login.php`
2. Enter your credentials:
   - Username: `depthead` (or whatever you set)
   - Password: Your password
3. You should be redirected to: `http://localhost/onecci/DepartmentHeadF/Dashboard.php`

### 4. Access Features

From the Department Head Dashboard, you can:
- **Manage Teacher Schedule**: Create/edit teacher work schedules
- **Teacher Attendance**: View teacher attendance records

## Troubleshooting

### Login Issues
- Verify the employee exists in `employees` table
- Check that `employee_accounts` has the correct `employee_id` and `role = 'department_head'`
- Ensure password hash is correct

### Access Denied
- Clear browser cache and cookies
- Check session in browser developer tools
- Verify `$_SESSION['role']` is set to 'department_head'

### Database Errors
- Ensure all required tables exist (run the application once to auto-create)
- Check database connection in `StudentLogin/db_conn.php`

## Features Overview

### Manage Teacher Schedule
- Create schedules with same time for all days OR different times per day
- Assign teachers to schedules
- Assign subjects and sections to teachers
- View all teacher schedules in one place

### Teacher Attendance
- View real-time attendance records
- Filter by date range and teacher name
- See tardiness, undertime, and overtime calculations
- Export attendance reports

## Security Notes

- Always use strong passwords
- Never commit password hashes to version control
- Change default passwords immediately after setup
- Regularly review access logs

## Support

For issues or questions, contact your system administrator.
