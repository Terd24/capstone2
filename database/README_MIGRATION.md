# K-12 vs College Grading System Migration

## Overview
This migration adds support for both K-12 (quarterly) and College (term-based) grading systems.

## What's New
- **K-12 Students**: Use 4 quarters (1st Quarter, 2nd Quarter, 3rd Quarter, 4th Quarter)
- **College Students**: Use 3 terms (Prelim, Midterm, Finals)
- **Automatic Detection**: System automatically detects student level and shows appropriate grade fields
- **Pass/Fail Status**: 75% passing threshold with clear PASSED/FAILED/INCOMPLETE indicators

## Migration Steps

### Step 1: Run the Migration
Open your browser and navigate to:
```
http://localhost/onecci/database/run_migration.php
```

This will:
1. Add new columns to the `grades_record` table
2. Populate `grading_system` field for existing records
3. Create necessary indexes
4. Show verification results

### Step 2: Verify the Migration
The migration page will show:
- ✓ Quarter columns added successfully
- ✓ Grading_system column added successfully
- Records count by grading system

### Step 3: Test the System
1. Go to: `http://localhost/onecci/EmployeePortal/ManageGrades.php`
2. Search for a K-12 student (e.g., "Grade 7", "Grade 10")
   - You should see 4 quarter fields
3. Search for a College student (e.g., "1st Year", "2nd Year")
   - You should see 3 term fields (Prelim, Midterm, Finals)

## Database Changes

### New Columns Added to `grades_record`:
- `first_quarter` DECIMAL(5,2) - K-12 1st quarter grade
- `second_quarter` DECIMAL(5,2) - K-12 2nd quarter grade
- `third_quarter` DECIMAL(5,2) - K-12 3rd quarter grade
- `fourth_quarter` DECIMAL(5,2) - K-12 4th quarter grade
- `grading_system` VARCHAR(10) - "K12" or "COLLEGE"

### Existing Columns Preserved:
- `prelim` - College term 1
- `midterm` - College term 2
- `pre_finals` - Legacy (not used in calculations)
- `finals` - College term 3

## Student Classification Rules

### K-12 Students:
- Grade level contains "Kinder", "Kinder 1", "Kinder 2"
- Grade level matches "Grade 1" through "Grade 12"

### College Students:
- Grade level matches "1st Year", "2nd Year", "3rd Year", "4th Year"

## Grade Calculation

### K-12 Average:
```
Average = (1st Quarter + 2nd Quarter + 3rd Quarter + 4th Quarter) / 4
```

### College Average:
```
Average = (Prelim + Midterm + Finals) / 3
```
Note: `pre_finals` is NOT included in the average calculation.

### Pass/Fail Status:
- **PASSED**: Average ≥ 75%
- **FAILED**: Average < 75%
- **INCOMPLETE**: Not all required grades entered

## Rollback (If Needed)
If you need to rollback the migration:
1. Backup your database first
2. Run these SQL commands:
```sql
ALTER TABLE grades_record
DROP COLUMN first_quarter,
DROP COLUMN second_quarter,
DROP COLUMN third_quarter,
DROP COLUMN fourth_quarter,
DROP COLUMN grading_system;
```

## Support
If you encounter any issues:
1. Check the browser console for JavaScript errors
2. Check PHP error logs
3. Verify database connection in `StudentLogin/db_conn.php`
4. Ensure all files are uploaded correctly

## Files Modified
- `EmployeePortal/ManageGrades.php` - Updated with K-12/College support
- `RegistrarF/get_student_grades.php` - Updated to fetch all grade columns
- `includes/grading_helpers.php` - New helper functions
- `database/migrate_grading_system.sql` - Migration script
- `database/run_migration.php` - Migration runner
