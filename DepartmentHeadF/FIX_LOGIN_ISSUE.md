# Fix: Department Head Login Redirects to Employee Portal

## Problem
When logging in with a Department Head account, the system redirects to the Employee Portal (Teacher Portal) instead of the Department Head Dashboard.

## Root Causes Found

### 1. ❌ Missing Role in Validation (create_account.php)
**File**: `HRF/create_account.php`
**Issue**: The valid_roles array didn't include 'department_head'
**Status**: ✅ FIXED

### 2. ❌ Missing Role in Database ENUM (add_employee.php)
**File**: `HRF/add_employee.php`
**Issue**: The employee_accounts table ENUM constraint didn't include 'department_head'
**Status**: ✅ FIXED

### 3. ⚠️ Existing Database May Need Update
**Issue**: If the employee_accounts table already exists, it may still have the old ENUM without 'department_head'
**Status**: ⚠️ NEEDS MANUAL FIX

---

## Solution Steps

### Step 1: Update Database ENUM (REQUIRED!)

Run this script to update your database:

**URL**: `http://localhost/onecci/DepartmentHeadF/update_database.php`

This will:
- Check if employee_accounts table exists
- Check if 'department_head' is in the ENUM
- Update the ENUM if needed
- Show you the before/after

**OR** run this SQL directly in phpMyAdmin:

```sql
ALTER TABLE employee_accounts 
MODIFY role ENUM('registrar','cashier','guidance','attendance','hr','teacher','department_head') NOT NULL;
```

---

### Step 2: Check Existing Account Role

If you already created a Department Head account, check what role was actually saved:

**URL**: `http://localhost/onecci/DepartmentHeadF/check_role.php?username=YOUR_USERNAME`

Replace `YOUR_USERNAME` with the actual username.

This will show you:
- The exact role value in the database
- Whether it matches 'department_head'
- Hex representation to check for hidden characters

---

### Step 3: Fix the Role (If Needed)

If the role is wrong (e.g., saved as 'teacher' or something else), fix it:

**URL**: `http://localhost/onecci/DepartmentHeadF/fix_role.php`

Or run this SQL in phpMyAdmin:

```sql
UPDATE employee_accounts 
SET role = 'department_head' 
WHERE username = 'YOUR_USERNAME';
```

---

### Step 4: Test Login

1. Logout if currently logged in
2. Clear browser cache (Ctrl+Shift+Delete)
3. Go to: `http://localhost/onecci/admin_login.php`
4. Login with Department Head credentials
5. You should be redirected to: `http://localhost/onecci/DepartmentHeadF/Dashboard.php`

---

## Why This Happened

When you created the Department Head account through the HR Dashboard:

1. The form sent `role = 'department_head'` ✅
2. But `create_account.php` validated against a hardcoded list that didn't include it ❌
3. The validation failed, so the role might have been rejected or defaulted to something else
4. Even if it passed validation, the database ENUM constraint would have rejected it ❌

---

## What Was Fixed

### Files Updated:

1. **HRF/create_account.php**
   - Added 'department_head' to $valid_roles array
   - Now accepts department_head role

2. **HRF/add_employee.php**
   - Updated ENUM in CREATE TABLE statement
   - Updated ENUM in ALTER TABLE statement
   - Now includes 'department_head' in database schema

3. **admin_login.php** (already correct)
   - Has proper routing for department_head role
   - Redirects to DepartmentHeadF/Dashboard.php

4. **HRF/Dashboard.php** (already correct)
   - Has 'department_head' in all role dropdowns

---

## Verification Checklist

After running the fixes:

- [ ] Database ENUM includes 'department_head'
- [ ] Existing account has role = 'department_head'
- [ ] Login redirects to DepartmentHeadF/Dashboard.php
- [ ] Can access Manage Teacher Schedule
- [ ] Can access Teacher Attendance
- [ ] Logout works correctly

---

## Quick Fix Commands

### Check Database ENUM:
```sql
SHOW COLUMNS FROM employee_accounts LIKE 'role';
```

### Update Database ENUM:
```sql
ALTER TABLE employee_accounts 
MODIFY role ENUM('registrar','cashier','guidance','attendance','hr','teacher','department_head') NOT NULL;
```

### Check Account Role:
```sql
SELECT username, role FROM employee_accounts WHERE username = 'YOUR_USERNAME';
```

### Fix Account Role:
```sql
UPDATE employee_accounts 
SET role = 'department_head' 
WHERE username = 'YOUR_USERNAME';
```

---

## Prevention

For future accounts:
- The code has been fixed, so new Department Head accounts will work correctly
- The database ENUM has been updated to accept 'department_head'
- No manual intervention needed for new accounts

---

## Still Having Issues?

1. **Check browser console** (F12) for JavaScript errors
2. **Check PHP error logs** for server-side errors
3. **Verify session variables** using browser dev tools
4. **Clear all browser data** and try again
5. **Check that DepartmentHeadF folder exists** and files are accessible

---

## Support Files

- `update_database.php` - Automated database update script
- `check_role.php` - Check what role is actually in database
- `fix_role.php` - Fix incorrect role in database
- `FIX_LOGIN_ISSUE.md` - This file

---

**Last Updated**: October 28, 2025

**Status**: ✅ Code Fixed, Database Update Required
