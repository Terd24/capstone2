# 🚀 Hosting Deployment Guide - K-12 vs College Grading System

## 📋 Prerequisites
- Access to your hosting control panel (cPanel, Plesk, etc.)
- Access to phpMyAdmin or MySQL command line
- Database backup (recommended before running migration)

---

## 🔧 Step-by-Step Deployment Instructions

### Step 1: Backup Your Database (IMPORTANT!)
Before making any changes, create a backup of your database:

1. Log into **phpMyAdmin** on your hosting
2. Select your database (e.g., `onecci_db`)
3. Click the **Export** tab
4. Click **Go** to download the backup
5. Save the backup file with today's date (e.g., `onecci_backup_2025-10-20.sql`)

---

### Step 2: Run the Migration SQL Script

#### Option A: Using phpMyAdmin (Recommended)

1. **Open phpMyAdmin** from your hosting control panel
2. **Select your database** from the left sidebar
3. Click the **SQL** tab at the top
4. **Copy and paste** the entire contents of `PRODUCTION_MIGRATION.sql` into the SQL query box
5. Click **Go** to execute the script
6. Wait for the success message

#### Option B: Using MySQL Command Line

```bash
mysql -u your_username -p your_database_name < PRODUCTION_MIGRATION.sql
```

---

### Step 3: Verify the Migration

After running the migration, verify it was successful:

1. In phpMyAdmin, select your database
2. Click on the `grades_record` table
3. Click the **Structure** tab
4. Verify these new columns exist:
   - ✅ `first_quarter` (DECIMAL 5,2)
   - ✅ `second_quarter` (DECIMAL 5,2)
   - ✅ `third_quarter` (DECIMAL 5,2)
   - ✅ `fourth_quarter` (DECIMAL 5,2)
   - ✅ `grading_system` (VARCHAR 10)

5. Check that these new tables exist:
   - ✅ `teacher_subject_assignments`
   - ✅ `subjects`
   - ✅ `subject_offerings`

---

### Step 4: Upload Updated PHP Files

Upload these files to your hosting server (via FTP or File Manager):

#### Core Files:
```
EmployeePortal/ManageGrades.php
EmployeePortal/api/get_student_grades.php
EmployeePortal/api/get_teacher_subjects.php
EmployeePortal/api/subject_offerings.php
includes/grading_helpers.php
```

#### API Files (if they don't exist, create them):
```
EmployeePortal/api/get_latest_term.php
EmployeePortal/delete_grade.php
```

---

### Step 5: Test the System

1. **Log in as a Teacher**
2. Go to **Manage Grades**
3. **Search for a student**
4. Try to **Add a Grade**:
   - For K-12 students: You should see 4 quarter fields
   - For College students: You should see 3 term fields (Prelim, Midterm, Finals)
5. **Save the grade** and verify it appears correctly
6. Try **editing** and **deleting** grades

---

## 🔍 Troubleshooting

### Issue: "Column already exists" error
**Solution:** This is normal if you've run the migration before. The script is designed to be safe to run multiple times.

### Issue: "Table doesn't exist" error
**Solution:** Make sure you're running the script on the correct database. Check your database name in phpMyAdmin.

### Issue: Grades not showing up
**Solution:** 
1. Check browser console for JavaScript errors (F12)
2. Verify the API files are uploaded correctly
3. Check file permissions (should be 644 for PHP files)

### Issue: "Access denied" error
**Solution:** Your database user needs these permissions:
- SELECT
- INSERT
- UPDATE
- DELETE
- CREATE
- ALTER
- INDEX

---

## 📊 What This Migration Does

### Database Changes:
1. ✅ Adds 4 quarter columns for K-12 grading
2. ✅ Adds grading_system column to track K12 vs COLLEGE
3. ✅ Creates teacher_subject_assignments table
4. ✅ Creates subjects and subject_offerings tables
5. ✅ Adds indexes for better performance
6. ✅ Updates existing records with appropriate grading system

### Features Added:
1. ✅ Automatic detection of K-12 vs College students
2. ✅ Dynamic form fields based on student type
3. ✅ Subject filtering by term and grade level
4. ✅ Teacher subject assignments
5. ✅ Improved grade calculation and display

---

## 🆘 Need Help?

If you encounter any issues:

1. **Check the error logs** in your hosting control panel
2. **Verify database connection** in `StudentLogin/db_conn.php`
3. **Check file permissions** (PHP files should be 644, directories 755)
4. **Clear browser cache** and try again

---

## ✅ Post-Deployment Checklist

- [ ] Database backup created
- [ ] Migration script executed successfully
- [ ] New columns verified in grades_record table
- [ ] New tables created (teacher_subject_assignments, subjects, subject_offerings)
- [ ] PHP files uploaded to server
- [ ] Tested adding grades for K-12 student
- [ ] Tested adding grades for College student
- [ ] Tested editing and deleting grades
- [ ] Verified grade calculations are correct

---

## 📝 Notes

- The migration is **idempotent** - safe to run multiple times
- Existing grades are **preserved** - no data loss
- The system **automatically detects** student type (K-12 vs College)
- Teachers can only see subjects assigned to them

---

**Migration Date:** October 20, 2025  
**Version:** 1.0  
**Status:** Production Ready ✅
