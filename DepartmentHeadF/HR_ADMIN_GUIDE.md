# HR Admin Guide - Creating Department Head Accounts

## ✅ UPDATED! HR Admin Can Now Create Department Head Accounts

The HR Dashboard has been updated to include "Department Head" as a role option when creating employee accounts.

---

## How to Create a Department Head Account (HR Admin)

### Step 1: Login as HR Admin
1. Go to: `http://localhost/onecci/admin_login.php`
2. Login with your HR credentials
3. You'll be redirected to: `http://localhost/onecci/HRF/Dashboard.php`

---

### Step 2: Add New Employee (or Use Existing)

#### Option A: Add New Employee with Account
1. Click the **"Add Employee"** button (green button, top right)
2. Fill in all employee information:
   - Personal Information (Name, ID, Position, etc.)
   - Contact Information (Phone, Email, Address)
3. In the **"SYSTEM ACCOUNT"** section:
   - ✅ Check the box: **"Create system account for this employee"**
   - The username and password will be auto-generated
   - **Role dropdown**: Select **"Department Head"** ⭐
4. Click **"Add Employee"**

#### Option B: Create Account for Existing Employee
1. Find the employee in the employee list
2. Click on their row to view details
3. If they don't have an account, you'll see a **"+ Create Account"** button
4. Click it and fill in:
   - Username
   - Password
   - **Role**: Select **"Department Head"** ⭐
5. Click **"Create Account"**

---

### Step 3: Verify Account Creation

1. You should see a success message
2. The employee's row will now show **"Has Account"** badge
3. The account is ready to use!

---

## Available Roles in HR Dashboard

When creating employee accounts, you can now choose from:

1. **Registrar** - Registrar staff
2. **Cashier** - Cashier staff
3. **Guidance** - Guidance counselor
4. **Attendance** - Attendance staff
5. **Teacher** - Teaching staff (requires RFID)
6. **Department Head** - Department Head (NEW!) ⭐

---

## What Department Head Can Access

Once the account is created, the Department Head can:

✅ Login at: `http://localhost/onecci/admin_login.php`

✅ Access Dashboard: `http://localhost/onecci/DepartmentHeadF/Dashboard.php`

✅ **Manage Teacher Schedule**
- Create and edit teacher work schedules
- Assign teachers to schedules
- Set different times for different days
- Assign subjects and sections

✅ **Teacher Attendance**
- View real-time teacher attendance
- Filter by date range and teacher
- See tardiness, undertime, overtime
- Generate reports

---

## Important Notes

### RFID Requirement
- **Teacher role** requires RFID number
- **Department Head role** does NOT require RFID
- The RFID field only appears when "Teacher" is selected

### Auto-Generated Credentials
When adding a new employee with account:
- Username is auto-generated from first and last name
- Password is auto-generated (secure random password)
- Employee will need to change password on first login

### Manual Account Creation
When creating account for existing employee:
- You manually enter username and password
- Make sure to give the credentials to the employee
- Recommend they change password after first login

---

## Editing Existing Accounts

To change an employee's role to Department Head:

1. Click on the employee in the list
2. In the account section, click **"Edit Account"**
3. Change the **Role** dropdown to **"Department Head"**
4. Click **"Update Account"**
5. The employee will now have Department Head access

---

## Troubleshooting

### "Department Head" option not showing
- **Solution**: Refresh the page (Ctrl+F5)
- Make sure you're using the latest version of HRF/Dashboard.php

### Account created but login fails
- **Solution**: 
  - Verify the role is exactly "department_head" in the database
  - Check that admin_login.php has been updated
  - Clear browser cache

### Employee can't access Department Head features
- **Solution**:
  - Verify their role is "department_head" (not "teacher" or other)
  - Have them logout and login again
  - Check that DepartmentHeadF folder exists

### RFID field showing for Department Head
- **Solution**: This shouldn't happen. If it does:
  - Select a different role, then select Department Head again
  - Refresh the page
  - The RFID field should only show for "Teacher" role

---

## Quick Reference

**HR Dashboard URL**: `http://localhost/onecci/HRF/Dashboard.php`

**Department Head Dashboard URL**: `http://localhost/onecci/DepartmentHeadF/Dashboard.php`

**Role Value**: `department_head` (with underscore)

**Display Name**: Department Head (with space)

---

## Security Best Practices

1. ✅ Use strong passwords (minimum 8 characters)
2. ✅ Force password change on first login
3. ✅ Only assign Department Head role to authorized personnel
4. ✅ Regularly review employee accounts
5. ✅ Remove accounts for terminated employees

---

## What Changed

### Before:
- HR could only create: Registrar, Cashier, Guidance, Attendance, Teacher
- Department Head accounts had to be created by Super Admin

### After:
- HR can now create all employee roles including Department Head
- Streamlined account creation process
- No need for Super Admin intervention

---

## Need More Help?

Check these files:
- `HOW_TO_CREATE_ACCOUNT.md` - Detailed account creation guide
- `STEP_BY_STEP_GUIDE.txt` - Visual step-by-step guide
- `SETUP_GUIDE.md` - Complete setup instructions
- `README.md` - Module overview

---

**Last Updated**: October 28, 2025

**Status**: ✅ Ready to Use!
