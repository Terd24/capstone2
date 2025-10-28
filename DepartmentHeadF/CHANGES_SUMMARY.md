# Department Head Module - Changes Summary

## What Was Done

### 1. Created New Module Structure
- **New Folder**: `DepartmentHeadF/`
- **Purpose**: Separate module for Department Head functionality

### 2. Files Created/Moved

#### New Files Created:
- `DepartmentHeadF/Dashboard.php` - Main dashboard with module cards
- `DepartmentHeadF/logout.php` - Logout functionality
- `DepartmentHeadF/README.md` - Module documentation
- `DepartmentHeadF/SETUP_GUIDE.md` - Setup instructions
- `DepartmentHeadF/setup_department_head.sql` - SQL script for account creation

#### Files Moved from HRF:
- `HRF/ManageEmployeeSchedule.php` → `DepartmentHeadF/ManageTeacherSchedule.php`
  - Updated authentication: `hr` → `department_head`
  - Updated page title: "Employee Schedule" → "Teacher Schedule"
  - Updated portal name: "HR Portal" → "Department Head Portal"
  
- `HRF/EmployeeAttendance.php` → `DepartmentHeadF/TeacherAttendance.php`
  - Updated authentication: `hr` → `department_head`
  - Updated labels: "Employee" → "Teacher"
  - Updated portal name: "HR Portal" → "Department Head Portal"

### 3. Files Modified

#### `HRF/Dashboard.php`
**Removed:**
```php
<button onclick="window.location.href='../HRF/ManageEmployeeSchedule.php'">
    Manage Teacher Schedule
</button>
<button onclick="window.location.href='../HRF/EmployeeAttendance.php'">
    Teacher Attendance
</button>
```

**Result**: HR Dashboard now only shows employee management, not teacher-specific features

#### `admin_login.php`
**Added:**
```php
case 'department_head':
    header("Location: DepartmentHeadF/Dashboard.php");
    exit;
```

**Added in role routing:**
```php
case 'department_head':
    $_SESSION['dept_head_id'] = $employee['id'];
    $_SESSION['dept_head_name'] = $full_name;
    $redirect_url = "DepartmentHeadF/Dashboard.php";
    break;
```

**Result**: System now recognizes and routes department_head role

#### `StudentLogin/login.php`
**Modified:**
```php
// Added 'department_head' to employee roles array
if (in_array($role, ['superadmin', 'owner', 'hr', 'registrar', 'cashier', 'guidance', 'attendance', 'teacher', 'department_head'])) {
```

**Result**: Department heads are redirected to admin_login.php

### 4. Authentication Changes

#### Old System (HR):
- Role: `hr`
- Session: `$_SESSION['hr_name']`
- Access: HR Dashboard with all features

#### New System (Department Head):
- Role: `department_head`
- Session: `$_SESSION['dept_head_name']`
- Access: Department Head Dashboard with teacher-specific features only

### 5. Feature Separation

#### HR Module (HRF/) - Now Handles:
- Employee management (add, edit, delete)
- Employee accounts creation
- General employee records
- Non-teaching staff management

#### Department Head Module (DepartmentHeadF/) - Now Handles:
- Teacher schedule management
- Teacher attendance tracking
- Teacher-specific operations
- Academic department oversight

## Benefits

1. **Separation of Concerns**: HR and Department Head roles have distinct responsibilities
2. **Better Security**: Each role only accesses relevant features
3. **Clearer Navigation**: Dedicated dashboards for each role
4. **Scalability**: Easy to add more department head features without cluttering HR module
5. **Role-Based Access**: Proper authentication for each module

## Database Requirements

### Required Tables (Auto-created):
- `employee_work_schedules`
- `employee_schedules`
- `employee_day_schedules`
- `teacher_attendance`
- `teacher_subjects`
- `teacher_sections`

### Required Account:
- Entry in `employee_accounts` table with `role = 'department_head'`

## Testing Checklist

- [ ] Department Head can login via admin_login.php
- [ ] Department Head dashboard loads correctly
- [ ] Manage Teacher Schedule opens and functions
- [ ] Teacher Attendance opens and displays records
- [ ] HR Dashboard no longer shows teacher schedule/attendance buttons
- [ ] HR can still manage all employees
- [ ] Logout works correctly
- [ ] Session management is secure

## URLs

- **Login**: `http://localhost/onecci/admin_login.php`
- **Dashboard**: `http://localhost/onecci/DepartmentHeadF/Dashboard.php`
- **Teacher Schedule**: `http://localhost/onecci/DepartmentHeadF/ManageTeacherSchedule.php`
- **Teacher Attendance**: `http://localhost/onecci/DepartmentHeadF/TeacherAttendance.php`

## Next Steps

1. Create a Department Head account using the SQL script
2. Test login and navigation
3. Verify all features work correctly
4. Train Department Head users on the new module
5. Update user documentation

## Rollback Plan (If Needed)

If you need to revert changes:
1. Copy files back from DepartmentHeadF to HRF
2. Restore the two buttons in HRF/Dashboard.php
3. Remove department_head cases from admin_login.php and StudentLogin/login.php
4. Delete DepartmentHeadF folder

## Notes

- Original HRF files remain unchanged (ManageEmployeeSchedule.php and EmployeeAttendance.php still exist in HRF)
- Department Head module uses copies with updated authentication
- No database schema changes required
- Backward compatible with existing data
