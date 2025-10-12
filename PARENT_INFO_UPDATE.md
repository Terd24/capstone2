# Parent Information UI Update

## Summary
Updated the view/edit student page (`RegistrarF/Accounts/view_student.php`) to match the parent information structure from the add account page. Parent names are now split into **First Name**, **Last Name**, and **Middle Name** fields for Father, Mother, and Guardian.

## Changes Made

### 1. PHP Processing Updates
- **Modified POST data handling** to capture separate name fields:
  - `father_first_name`, `father_last_name`, `father_middle_name`
  - `mother_first_name`, `mother_last_name`, `mother_middle_name`
  - `guardian_first_name`, `guardian_last_name`, `guardian_middle_name`
- Combined these fields into full names for database storage (maintains backward compatibility)

### 2. Name Parsing Function
- Added `parseFullName()` function to split existing combined names into separate fields
- Handles various name formats:
  - Single name → First name only
  - Two names → First and Last
  - Three+ names → First, Middle(s), Last

### 3. Validation Updates
- Updated required field validation to check first and last names separately
- Added pattern validation for each name field (letters and spaces only)
- Updated JavaScript validation to match new field names

### 4. UI Changes
Each parent section (Father, Mother, Guardian) now displays:
- **Row 1**: First Name*, Last Name*, Middle Name (Optional)
- **Row 2**: Occupation*, Contact

This matches the exact structure from `add_account.php`.

## Database Compatibility
- The combined `father_name`, `mother_name`, and `guardian_name` fields are still used in the database
- Separate fields are combined before saving: `first + middle + last`
- Existing records are parsed when loading the form
- No database schema changes required

## Benefits
1. **Consistency**: Edit/view page now matches add account page
2. **Better data structure**: Separate fields for better data management
3. **Improved validation**: Individual field validation
4. **Backward compatible**: Works with existing database structure
5. **User-friendly**: Clearer input fields with proper labels

## Testing Recommendations
1. View an existing student record - names should be properly split
2. Edit a student record - verify all name fields are editable
3. Save changes - verify names are properly combined and saved
4. Create new student - verify consistency between add and edit forms
5. Test validation - ensure all required fields are enforced
