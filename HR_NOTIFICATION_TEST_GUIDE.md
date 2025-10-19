# HR Notification Testing Guide

## How to Test HR Notifications

### Step 1: Test the Notification System
1. Open your browser and go to: `http://localhost/onecci/AdminF/test_hr_notification.php`
2. This will run diagnostic tests to verify:
   - Functions exist
   - Database connection works
   - system_notifications table exists
   - Can insert test notifications
   - Recent notifications are visible

### Step 2: Test Adding an Employee
1. Login as Super Admin
2. Go to HR Accounts Management
3. Click "Add New HR Employee"
4. Fill in the form and submit
5. Check Owner Dashboard → System Notifications
6. You should see: "New HR Employee Added"

### Step 3: Test Editing an Employee
1. Login as Super Admin
2. Go to HR Accounts Management
3. Click "Edit" on any employee
4. Make changes and save
5. Check Owner Dashboard → System Notifications
6. You should see: "HR Employee Account Edited"

### Step 4: Test Deleting an Employee
1. Login as Super Admin
2. Go to HR Accounts Management
3. Click "Delete" on any employee
4. Confirm deletion
5. Check Owner Dashboard → System Notifications
6. You should see: "HR Employee Account Deleted"

## Troubleshooting

### If notifications are not appearing:

1. **Check PHP Error Logs**
   - Look in your PHP error log file
   - Check for any errors related to "logSystemNotification"

2. **Verify Database**
   - Run the test file: `test_hr_notification.php`
   - Check if system_notifications table exists
   - Verify table has correct columns

3. **Check Session Variables**
   - Make sure `$_SESSION['superadmin_name']` is set when logged in
   - Verify `$_SESSION['role']` is 'superadmin'

4. **Manual Database Check**
   ```sql
   SELECT * FROM system_notifications 
   WHERE module = 'HR Admin' 
   ORDER BY created_at DESC 
   LIMIT 10;
   ```

5. **Check File Permissions**
   - Ensure `includes/log_system_notification.php` is readable
   - Verify the file is being included correctly

### Common Issues:

**Issue**: "Function not found" error
- **Solution**: Make sure `include("../includes/log_system_notification.php");` is at the top of the file

**Issue**: Notifications not showing in Owner Dashboard
- **Solution**: 
  - Clear browser cache
  - Refresh the Owner Dashboard
  - Check if notifications are in database but marked as read

**Issue**: Database error
- **Solution**: 
  - Verify system_notifications table exists
  - Check if all required columns are present
  - Run the CREATE TABLE statement from OwnerF/Dashboard.php

## Expected Notification Format

When you edit an employee, you should see:

```
Title: HR Employee Account Edited
From: Admin Name (Super Admin) • Module: HR Admin
Message: Edited employee account: John Doe (ID: EMP-001)
Time: Oct 20, 2025 3:45 PM
Status: INFO
```

When you delete an employee, you should see:

```
Title: HR Employee Account Deleted
From: Admin Name (Super Admin) • Module: HR Admin
Message: Deleted employee account: Jane Smith (ID: EMP-002)
Time: Oct 20, 2025 4:00 PM
Status: WARNING
```

## Verification Checklist

- [ ] Test file runs without errors
- [ ] Can see test notification in database
- [ ] Add employee creates notification
- [ ] Edit employee creates notification
- [ ] Delete employee creates notification
- [ ] Notifications appear in Owner Dashboard
- [ ] Notification count badge updates
- [ ] Can mark notifications as read
- [ ] Timestamps are correct
- [ ] User names are displayed correctly

## Need Help?

If notifications still don't work after following this guide:
1. Run the test file and screenshot the results
2. Check PHP error logs for any errors
3. Verify the database query results
4. Make sure you're logged in as Super Admin with correct session variables
