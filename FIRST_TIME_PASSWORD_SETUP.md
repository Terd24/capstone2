# First-Time Login Password Change Feature

## What This Does
When a registrar creates a new student account, the student will be forced to change their password on first login.

## Setup Instructions

### Step 1: Add Database Column
Run this SQL in phpMyAdmin (both localhost AND Hostinger):

```sql
ALTER TABLE student_account 
ADD COLUMN IF NOT EXISTS must_change_password TINYINT(1) DEFAULT 1;
```

Or import the file: `setup_password_change.sql`

### Step 2: Test Locally
1. Go to `http://localhost/onecci/StudentLogin/login.php`
2. Create a new student account via Registrar
3. Login with that student account
4. You should be redirected to change password page
5. After changing password, you'll access the dashboard

### Step 3: Upload to Hostinger
Upload these files to Hostinger:
- `StudentLogin/login.php`
- `StudentLogin/change_password.php`
- `RegistrarF/Accounts/add_account.php`

### Step 4: Run SQL on Hostinger
1. Login to Hostinger
2. Go to phpMyAdmin
3. Select your database
4. Run the ALTER TABLE command above

## How It Works

1. **Registrar creates student** → `must_change_password = 1`
2. **Student logs in first time** → Redirected to `change_password.php`
3. **Student changes password** → `must_change_password = 0`
4. **Next login** → Goes directly to dashboard

## For Existing Students (Optional)

If you want existing students to also change their password:
```sql
UPDATE student_account SET must_change_password = 1;
```

If you want existing students to keep their passwords:
```sql
UPDATE student_account SET must_change_password = 0;
```

## Files Modified
- ✅ `StudentLogin/login.php` - Checks for first-time login
- ✅ `StudentLogin/change_password.php` - New password change page
- ✅ `RegistrarF/Accounts/add_account.php` - Sets flag when creating students
- ✅ `setup_password_change.sql` - Database setup script
