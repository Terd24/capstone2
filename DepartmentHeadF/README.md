# Department Head Module

## Overview
This module provides Department Head functionality for managing teacher schedules and attendance.

## Features
1. **Manage Teacher Schedule** - Create and manage teacher work schedules, assign subjects and sections
2. **Teacher Attendance** - View and monitor teacher attendance records and reports

## Files
- `Dashboard.php` - Main dashboard for Department Head
- `ManageTeacherSchedule.php` - Teacher schedule management (moved from HRF)
- `TeacherAttendance.php` - Teacher attendance tracking (moved from HRF)
- `logout.php` - Logout functionality

## Access
- Role: `department_head`
- Login via: `admin_login.php`
- URL: `http://localhost/onecci/DepartmentHeadF/Dashboard.php`

## Database Requirements
- Employee account with role = 'department_head' in `employee_accounts` table
- Uses existing tables: `employee_work_schedules`, `employee_schedules`, `teacher_attendance`, etc.

## Changes Made
1. Created new DepartmentHeadF folder
2. Moved teacher schedule and attendance features from HRF module
3. Updated authentication to use 'department_head' role
4. Removed teacher schedule/attendance buttons from HR Dashboard
5. Updated admin_login.php to handle department_head role
