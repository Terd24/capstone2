# 🔧 Maintenance Mode Implementation Guide

## Overview
The system now has a fully functional maintenance mode that prevents students from logging in while allowing administrators to access the system.

## How It Works

### When Maintenance Mode is ENABLED:
- ✅ **Admins** (SuperAdmin, HR, Owner) can login via `admin_login.php`
- ❌ **Students** see a maintenance page and cannot login
- ❌ **Logged-in students** are automatically logged out and redirected to maintenance page
- ✅ **All admin dashboards** remain accessible

### When Maintenance Mode is DISABLED:
- ✅ Everyone can login normally
- ✅ System operates as usual

## How to Use

### 1. Enable Maintenance Mode
1. Login as Super Admin
2. Go to: `http://localhost/onecci/AdminF/SuperAdminDashboard.php#system-maintenance`
3. Toggle the "System Maintenance" switch to ON
4. Click "Update Configuration" button
5. Confirm the action
6. ✅ Maintenance mode is now ACTIVE

### 2. Disable Maintenance Mode
1. Login as Super Admin (you can still login during maintenance)
2. Go to System Maintenance section
3. Toggle the "System Maintenance" switch to OFF
4. Click "Update Configuration" button
5. Confirm the action
6. ✅ System is back to normal

### 3. Test Maintenance Mode
Visit: `http://localhost/onecci/AdminF/test_maintenance.php`

This page shows:
- Current maintenance mode status
- Who last updated it
- When it was updated
- What users can/cannot do

## Technical Details

### Files Created/Modified:

#### New Files:
1. **AdminF/update_configuration.php** - Handles maintenance mode toggle
2. **AdminF/clear_login_logs.php** - Clears login logs by date range
3. **AdminF/clear_attendance_records.php** - Clears attendance records by date range
4. **AdminF/create_backup.php** - Creates database backups
5. **AdminF/test_maintenance.php** - Test page to check maintenance status
6. **maintenance.php** - Maintenance page shown to students
7. **check_maintenance.php** - Helper functions for maintenance checks

#### Modified Files:
1. **AdminF/SuperAdminDashboard.php** - Updated JavaScript functions to call backend
2. **StudentLogin/studentDashboard.php** - Added maintenance mode check
3. **StudentLogin/login.php** - Already had maintenance check (verified)

### Database:

#### Table: `system_config`
```sql
CREATE TABLE system_config (
    config_key VARCHAR(50) PRIMARY KEY,
    config_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by VARCHAR(50)
);
```

#### Maintenance Mode Values:
- `config_key` = 'maintenance_mode'
- `config_value` = '1' (enabled) or '0' (disabled)

## Testing Steps

### Test 1: Enable Maintenance Mode
1. Login as Super Admin
2. Go to System Maintenance
3. Enable maintenance mode
4. Try to login as a student → Should see maintenance page ✅
5. Try to login as admin → Should work normally ✅

### Test 2: Disable Maintenance Mode
1. Login as Super Admin (during maintenance)
2. Go to System Maintenance
3. Disable maintenance mode
4. Try to login as a student → Should work normally ✅

### Test 3: Active Session During Maintenance
1. Login as a student
2. Keep the dashboard open
3. Have Super Admin enable maintenance mode
4. Refresh student dashboard → Should be logged out and see maintenance page ✅

## Security Features

✅ All endpoints check for Super Admin role
✅ Session validation on every request
✅ SQL injection protection using prepared statements
✅ All actions logged to `system_logs` table
✅ Admins can always access the system

## Troubleshooting

### Issue: Maintenance page not showing
**Solution:** Check if `system_config` table exists and has the maintenance_mode record

### Issue: Can't disable maintenance mode
**Solution:** Login via `admin_login.php` (admins bypass maintenance mode)

### Issue: Students can still login during maintenance
**Solution:** 
1. Check `test_maintenance.php` to verify status
2. Ensure `config_value` is '1' in database
3. Clear browser cache

## Quick Links

- **Super Admin Dashboard:** `http://localhost/onecci/AdminF/SuperAdminDashboard.php`
- **System Maintenance:** `http://localhost/onecci/AdminF/SuperAdminDashboard.php#system-maintenance`
- **Test Maintenance Status:** `http://localhost/onecci/AdminF/test_maintenance.php`
- **Admin Login:** `http://localhost/onecci/admin_login.php`
- **Student Login:** `http://localhost/onecci/StudentLogin/login.php`

## Notes

- No approval workflow needed - changes take effect immediately
- All actions are logged for audit purposes
- Maintenance mode only affects student logins
- Admin/HR/Owner accounts are never blocked
- The maintenance page is user-friendly and professional
