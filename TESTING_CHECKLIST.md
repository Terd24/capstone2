# ✅ Testing Checklist - Maintenance Mode

## Pre-Test Setup
- [ ] Make sure you can login as Super Admin
- [ ] Have a test student account ready
- [ ] Open two different browsers (or one normal + one incognito)

---

## Test 1: Enable Maintenance Mode ✅

### Steps:
1. [ ] Login as Super Admin
2. [ ] Navigate to System Maintenance section
3. [ ] Toggle "System Maintenance" switch to **ON**
4. [ ] Click "Update Configuration" button
5. [ ] Confirm the action in modal

### Expected Result:
- [ ] Success message appears
- [ ] Message shows "Maintenance mode: enabled"
- [ ] No errors in console

---

## Test 2: Student Cannot Login ❌

### Steps:
1. [ ] Open new browser/incognito window
2. [ ] Go to: `http://localhost/onecci/StudentLogin/login.php`
3. [ ] Try to login with student credentials

### Expected Result:
- [ ] **Maintenance page appears** (like your 3rd image)
- [ ] Shows school logo
- [ ] Shows warning icon
- [ ] Shows "System Under Maintenance" message
- [ ] Shows "Cornerstone College Inc." info box
- [ ] Has "Admin Login" link at bottom
- [ ] Student CANNOT access dashboard

---

## Test 3: Admin Can Still Login ✅

### Steps:
1. [ ] Go to: `http://localhost/onecci/admin_login.php`
2. [ ] Login with Super Admin credentials

### Expected Result:
- [ ] Login successful
- [ ] Redirected to Super Admin Dashboard
- [ ] All features work normally
- [ ] Can access System Maintenance section

---

## Test 4: Active Student Session Gets Logged Out 🔄

### Steps:
1. [ ] First, disable maintenance mode
2. [ ] Login as student in one browser
3. [ ] Keep student dashboard open
4. [ ] In another browser, login as Super Admin
5. [ ] Enable maintenance mode
6. [ ] Go back to student browser
7. [ ] Refresh the page or click any link

### Expected Result:
- [ ] Student gets logged out automatically
- [ ] Redirected to maintenance page
- [ ] Cannot access dashboard anymore

---

## Test 5: Disable Maintenance Mode ✅

### Steps:
1. [ ] Login as Super Admin (if not already)
2. [ ] Go to System Maintenance section
3. [ ] Toggle "System Maintenance" switch to **OFF**
4. [ ] Click "Update Configuration" button
5. [ ] Confirm the action

### Expected Result:
- [ ] Success message appears
- [ ] Message shows "Maintenance mode: disabled"
- [ ] No errors

---

## Test 6: Student Can Login Again ✅

### Steps:
1. [ ] Go to student login page
2. [ ] Login with student credentials

### Expected Result:
- [ ] Login successful
- [ ] Redirected to student dashboard
- [ ] Everything works normally
- [ ] No maintenance page

---

## Test 7: Check Status Page 📊

### Steps:
1. [ ] Go to: `http://localhost/onecci/AdminF/test_maintenance.php`

### Expected Result:
- [ ] Shows current maintenance status
- [ ] Shows who last updated it
- [ ] Shows when it was updated
- [ ] Clear indication if ON or OFF

---

## Test 8: Emergency Toggle 🚨

### Steps:
1. [ ] Login as Super Admin
2. [ ] Go to: `http://localhost/onecci/AdminF/emergency_maintenance_toggle.php`
3. [ ] Click "Enable Maintenance" button
4. [ ] Verify status changes
5. [ ] Click "Disable Maintenance" button
6. [ ] Verify status changes back

### Expected Result:
- [ ] Both buttons work
- [ ] Status updates immediately
- [ ] Success messages appear
- [ ] Color changes (red for ON, green for OFF)

---

## Test 9: Database Backup ✅

### Steps:
1. [ ] Go to System Maintenance section
2. [ ] Click "Create Database Backup" button
3. [ ] Confirm the action

### Expected Result:
- [ ] Success message appears
- [ ] Shows backup filename
- [ ] Shows backup file size
- [ ] Backup file created in `/backups` directory

---

## Test 10: Clear Login Logs ✅

### Steps:
1. [ ] Go to System Maintenance section
2. [ ] Select start date and end date
3. [ ] Click "Clear Login Logs" button
4. [ ] Confirm the action

### Expected Result:
- [ ] Success message appears
- [ ] Shows number of records deleted
- [ ] Date inputs are cleared
- [ ] No errors

---

## Test 11: Clear Attendance Records ✅

### Steps:
1. [ ] Go to System Maintenance section
2. [ ] Select start date and end date
3. [ ] Click "Clear Attendance Records" button
4. [ ] Confirm the action

### Expected Result:
- [ ] Success message appears
- [ ] Shows number of records deleted
- [ ] Date inputs are cleared
- [ ] No errors

---

## Final Verification ✅

- [ ] All 4 buttons work without errors
- [ ] Maintenance mode blocks students
- [ ] Maintenance mode allows admins
- [ ] Maintenance page looks professional
- [ ] All actions are logged
- [ ] No console errors
- [ ] Database updates correctly

---

## If Something Doesn't Work 🔧

1. Check browser console for errors (F12)
2. Check PHP error logs
3. Verify database connection
4. Check `test_maintenance.php` for current status
5. Try `emergency_maintenance_toggle.php` for quick fix
6. Clear browser cache and try again

---

## Success Criteria ✅

All tests should pass with:
- ✅ No errors in browser console
- ✅ No PHP errors
- ✅ Professional UI/UX
- ✅ Immediate feedback on actions
- ✅ Proper access control
- ✅ Database updates correctly
