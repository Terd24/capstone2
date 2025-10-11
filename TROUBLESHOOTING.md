# 🔧 Troubleshooting Guide

## Error: "An error occurred while updating configuration"

### Step 1: Check Your Session
Go to: `http://localhost/onecci/AdminF/debug_session.php`

**Expected Output:**
```json
{
    "role": "superadmin",
    "is_superadmin": true
}
```

**If role is NOT "superadmin":**
- Logout and login again via `admin_login.php`
- Make sure you're using Super Admin credentials

### Step 2: Test All Buttons
Go to: `http://localhost/onecci/AdminF/test_all_buttons.php`

This page will test all 4 buttons individually and show you:
- ✅ Which buttons work
- ❌ Which buttons fail
- 📋 Detailed error messages
- 🔍 Full response data

### Step 3: Check Database Connection
The test page will also show if:
- Database connection is working
- Tables exist
- Session is valid

## Common Issues & Solutions

### Issue 1: "Unauthorized access"
**Solution:** 
- Make sure you're logged in as Super Admin
- Check `debug_session.php` to verify your role
- Logout and login again

### Issue 2: "Database connection failed"
**Solution:**
- Check if MySQL is running
- Verify database credentials (localhost, root, '', onecci_db)
- Check if onecci_db database exists

### Issue 3: Buttons show error modal
**Solution:**
- Open browser console (F12) to see detailed error
- Check `test_all_buttons.php` for specific error messages
- Verify PHP error logs

### Issue 4: "Failed to update configuration"
**Solution:**
- Check if system_config table exists
- Run the test page to create tables automatically
- Check database permissions

## Quick Fixes

### Fix 1: Reset Maintenance Mode Manually
```sql
-- Run this in phpMyAdmin or MySQL console
DELETE FROM system_config WHERE config_key = 'maintenance_mode';
```

### Fix 2: Create Tables Manually
```sql
CREATE TABLE IF NOT EXISTS system_config (
    config_key VARCHAR(50) PRIMARY KEY,
    config_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by VARCHAR(50)
);

CREATE TABLE IF NOT EXISTS system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(100),
    user_type VARCHAR(50),
    user_id VARCHAR(50),
    details TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Fix 3: Clear Browser Cache
1. Press Ctrl+Shift+Delete
2. Clear cache and cookies
3. Reload the page

## Testing Checklist

- [ ] Can access `debug_session.php` and see role = "superadmin"
- [ ] Can access `test_all_buttons.php` without errors
- [ ] Test button 1 (Update Config) shows success
- [ ] Test button 2 (Backup) shows success
- [ ] Test button 3 (Clear Logs) shows success
- [ ] Test button 4 (Clear Attendance) shows success
- [ ] All buttons in actual dashboard work

## Still Not Working?

1. **Check Browser Console (F12)**
   - Look for JavaScript errors
   - Check Network tab for failed requests
   - See actual error responses

2. **Check PHP Error Logs**
   - Look in your PHP error log file
   - Check Apache/Nginx error logs

3. **Test Individual Files**
   - Try accessing each PHP file directly
   - Check if they return JSON responses

## Contact Information

If all else fails:
1. Take a screenshot of the error
2. Check browser console for errors
3. Run `test_all_buttons.php` and screenshot results
4. Check `debug_session.php` output
