# 🎓 K-12 vs College Grading System - Quick Start Guide

## ✅ What's Been Implemented

Your grading system now supports **both K-12 and College** students automatically!

### Features:
- ✅ **Automatic Detection**: System detects if student is K-12 or College
- ✅ **K-12 Grading**: 4 quarters (1st, 2nd, 3rd, 4th Quarter)
- ✅ **College Grading**: 3 terms (Prelim, Midterm, Finals)
- ✅ **Smart Calculations**: Correct average calculation for each system
- ✅ **Pass/Fail Status**: 75% passing threshold with color-coded indicators
- ✅ **Backward Compatible**: All existing College grades still work

## 🚀 Getting Started

### Step 1: Run the Database Migration

**IMPORTANT**: You must run this first!

Open your browser and go to:
```
http://localhost/onecci/database/run_migration.php
```

This will:
- Add new columns for K-12 quarters
- Add grading_system field
- Update existing records
- Show verification results

### Step 2: Test the System

Go to the Manage Grades page:
```
http://localhost/onecci/EmployeePortal/ManageGrades.php
```

#### Test with K-12 Student:
1. Search for a student with grade level like:
   - "Kinder 1" or "Kinder 2"
   - "Grade 1" through "Grade 12"
2. Click "Add New Grade"
3. You should see **4 quarter fields**:
   - 1st Quarter
   - 2nd Quarter
   - 3rd Quarter
   - 4th Quarter

#### Test with College Student:
1. Search for a student with grade level like:
   - "1st Year"
   - "2nd Year"
   - "3rd Year"
   - "4th Year"
2. Click "Add New Grade"
3. You should see **3 term fields**:
   - Prelim
   - Midterm
   - Finals

## 📊 How It Works

### Student Classification

The system automatically detects the student type based on their `grade_level`:

**K-12 Students:**
- Contains "Kinder", "Kinder 1", "Kinder 2"
- Matches "Grade 1" to "Grade 12"

**College Students:**
- Matches "1st Year", "2nd Year", "3rd Year", "4th Year"

### Grade Calculations

**K-12 Average:**
```
Average = (1st Quarter + 2nd Quarter + 3rd Quarter + 4th Quarter) / 4
```

**College Average:**
```
Average = (Prelim + Midterm + Finals) / 3
```

### Pass/Fail Status

- 🟢 **PASSED**: Average ≥ 75%
- 🔴 **FAILED**: Average < 75%
- ⚪ **INCOMPLETE**: Not all grades entered

## 📁 Files Created/Modified

### New Files:
- `includes/grading_helpers.php` - Helper functions for grading system
- `database/migrate_grading_system.sql` - Database migration script
- `database/run_migration.php` - Migration runner with verification
- `EmployeePortal/api/get_student_grades.php` - API endpoint for fetching grades

### Modified Files:
- `EmployeePortal/ManageGrades.php` - Updated with K-12/College support
- `RegistrarF/get_student_grades.php` - Updated to fetch all grade columns

### Backup Created:
- `EmployeePortal/ManageGrades.php.backup` - Original file backup

## 🎯 Usage Examples

### Adding K-12 Grades:
1. Search for "Grade 7" student
2. Click "Add New Grade"
3. Select subject and term
4. Enter grades in quarter fields (e.g., 85, 88, 90, 87)
5. Click "Save Grade"
6. Average will be calculated: (85+88+90+87)/4 = 87.5%
7. Status: **PASSED** (green)

### Adding College Grades:
1. Search for "2nd Year" student
2. Click "Add New Grade"
3. Select subject and term
4. Enter grades in term fields (e.g., 80, 85, 88)
5. Click "Save Grade"
6. Average will be calculated: (80+85+88)/3 = 84.33%
7. Status: **PASSED** (green)

### Editing Grades:
1. Click "Edit" on any grade card
2. The correct fields (quarters or terms) will be pre-filled
3. Modify the grades
4. Click "Update Grade"

## 🔍 Verification Checklist

After running the migration, verify:

- [ ] Migration page shows success messages
- [ ] K-12 student shows 4 quarter fields
- [ ] College student shows 3 term fields
- [ ] Existing College grades still display correctly
- [ ] Average calculation is correct
- [ ] Pass/fail status displays correctly
- [ ] Edit function pre-fills correct fields
- [ ] Grade cards show appropriate labels

## ⚠️ Important Notes

1. **Run Migration First**: The system won't work until you run the database migration
2. **Backup Created**: Your original ManageGrades.php is backed up as ManageGrades.php.backup
3. **Existing Data Safe**: All existing College grades are preserved and will continue to work
4. **No Manual Changes Needed**: Student classification is automatic based on grade_level

## 🐛 Troubleshooting

### Issue: "Column not found" error
**Solution**: Run the database migration at `http://localhost/onecci/database/run_migration.php`

### Issue: Still seeing old 4-field layout for College students
**Solution**: Clear your browser cache and refresh the page

### Issue: Grades not saving
**Solution**: 
1. Check browser console for JavaScript errors
2. Verify database connection in `StudentLogin/db_conn.php`
3. Ensure migration completed successfully

### Issue: Wrong fields showing for student type
**Solution**: Check the student's `grade_level` field in the database. It should match the patterns:
- K-12: "Kinder", "Grade 1", "Grade 7", etc.
- College: "1st Year", "2nd Year", etc.

## 📞 Need Help?

If you encounter any issues:
1. Check the migration verification page
2. Look at browser console for errors (F12)
3. Check PHP error logs
4. Verify student grade_level values in database

## 🎉 You're All Set!

Your grading system now supports both K-12 and College students with automatic detection and appropriate grade entry forms. Enjoy the new functionality!
