# 🚀 Fix Buttons NOW - Simple Steps

## Step 1: Test Your Session (30 seconds)

Open this URL:
```
http://localhost/onecci/AdminF/debug_session.php
```

**What you should see:**
- `"role": "superadmin"` ✅
- `"is_superadmin": true` ✅

**If you see something else:**
1. Logout
2. Go to: `http://localhost/onecci/admin_login.php`
3. Login as Super Admin
4. Try again

---

## Step 2: Test All Buttons (2 minutes)

Open this URL:
```
http://localhost/onecci/AdminF/test_all_buttons.php
```

**What to do:**
1. Click each button one by one
2. See which ones work ✅ and which fail ❌
3. Read the error messages

**Expected Results:**
- Button 1 (Update Config): ✅ Success
- Button 2 (Backup): ✅ Success  
- Button 3 (Clear Logs): ✅ Success
- Button 4 (Clear Attendance): ✅ Success

---

## Step 3: Go Back to Dashboard

If all tests pass:
```
http://localhost/onecci/AdminF/SuperAdminDashboard.php#system-maintenance
```

Try the buttons again - they should work now!

---

## If Buttons Still Don't Work

### Quick Fix #1: Clear Browser Cache
1. Press `Ctrl + Shift + Delete`
2. Clear cache
3. Reload page

### Quick Fix #2: Check Browser Console
1. Press `F12`
2. Go to "Console" tab
3. Look for red errors
4. Screenshot and check what it says

### Quick Fix #3: Force Refresh
1. Press `Ctrl + F5` (Windows)
2. Or `Cmd + Shift + R` (Mac)

---

## Emergency: Enable/Disable Maintenance Manually

If you just need to toggle maintenance mode:
```
http://localhost/onecci/AdminF/emergency_maintenance_toggle.php
```

Big buttons to:
- 🔴 Enable Maintenance
- 🟢 Disable Maintenance

---

## What Each File Does

| File | Purpose |
|------|---------|
| `debug_session.php` | Check if you're logged in correctly |
| `test_all_buttons.php` | Test all 4 buttons individually |
| `emergency_maintenance_toggle.php` | Quick enable/disable maintenance |
| `test_maintenance.php` | Check current maintenance status |

---

## Expected Behavior

### When Maintenance is ON:
- ❌ Students CANNOT login
- ✅ Admins CAN login
- 📄 Students see maintenance page

### When Maintenance is OFF:
- ✅ Everyone can login normally

---

## Quick Links

- **Debug Session:** `http://localhost/onecci/AdminF/debug_session.php`
- **Test Buttons:** `http://localhost/onecci/AdminF/test_all_buttons.php`
- **Emergency Toggle:** `http://localhost/onecci/AdminF/emergency_maintenance_toggle.php`
- **Check Status:** `http://localhost/onecci/AdminF/test_maintenance.php`
- **Dashboard:** `http://localhost/onecci/AdminF/SuperAdminDashboard.php#system-maintenance`

---

## Still Seeing Errors?

1. Open `test_all_buttons.php`
2. Click each button
3. Screenshot the results
4. Check what the error message says
5. The error message will tell you exactly what's wrong

---

## Most Common Issues

### "Unauthorized access"
→ You're not logged in as Super Admin
→ Go to `admin_login.php` and login again

### "Database connection failed"
→ MySQL is not running
→ Start XAMPP/WAMP

### "Failed to update configuration"
→ Database table doesn't exist
→ The test page will create it automatically

### Buttons don't respond
→ Clear browser cache
→ Press Ctrl+F5 to force refresh
