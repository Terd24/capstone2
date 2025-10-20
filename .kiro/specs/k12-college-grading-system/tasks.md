# Implementation Plan: K-12 vs College Grading System

- [-] 1. Database schema migration



  - [ ] 1.1 Create database migration script
    - Create SQL script to add first_quarter, second_quarter, third_quarter, fourth_quarter columns to grades_record table
    - Add grading_system column (VARCHAR(10)) to grades_record table
    - Add indexes for performance optimization
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

  - [ ] 1.2 Create data migration script for existing records
    - Write PHP script to populate grading_system field for existing grade records based on student grade_level
    - Ensure all existing College records are marked with grading_system = 'COLLEGE'
    - _Requirements: 7.1, 7.2, 7.3, 7.4_

  - [ ] 1.3 Execute migration and verify data integrity
    - Run migration script on database
    - Verify all columns added successfully
    - Verify existing data preserved



    - _Requirements: 1.1, 1.2, 1.3, 7.1_

- [ ] 2. Implement student classification logic
  - [ ] 2.1 Create PHP helper function for grading system determination
    - Write determineGradingSystem() function in a shared utilities file
    - Implement pattern matching for K-12 levels (Kinder, Grade 1-12)
    - Implement pattern matching for College levels (1st-4th Year)
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

  - [ ] 2.2 Create JavaScript helper function for grading system determination
    - Write determineGradingSystem() function in ManageGrades.php JavaScript section
    - Implement same pattern matching logic as PHP version
    - Ensure consistency between PHP and JavaScript implementations
    - _Requirements: 2.1, 2.2, 2.3_

  - [ ]* 2.3 Write unit tests for classification logic
    - Test K-12 patterns: "Kinder 1", "Grade 1", "Grade 12"
    - Test College patterns: "1st Year", "2nd Year", "3rd Year", "4th Year"
    - Test edge cases: null, empty string, invalid formats
    - _Requirements: 2.1, 2.2, 2.3_

- [ ] 3. Implement grade calculation engine
  - [ ] 3.1 Create PHP grade calculation functions
    - Write calculateAverage() function for both K-12 (4 quarters) and College (3 terms)
    - Write getGradeStatus() function to determine PASSED/FAILED/INCOMPLETE
    - Ensure College calculation excludes pre_finals column
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 7.5_

  - [ ] 3.2 Create JavaScript grade calculation functions
    - Write calculateAverage() function matching PHP logic
    - Write getGradeStatus() function matching PHP logic
    - Ensure consistency between PHP and JavaScript implementations
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

  - [ ]* 3.3 Write unit tests for calculation logic
    - Test K-12 average with all 4 quarters entered
    - Test K-12 average with partial quarters

    - Test College average with all 3 terms entered
    - Test College average with partial terms
    - Test status determination (PASSED >= 75, FAILED < 75, INCOMPLETE)
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [x] 4. Update teacher grade entry interface (ManageGrades.php)


  - [x] 4.1 Modify HTML structure for dynamic grade fields

    - Add hidden div for K-12 grade inputs (4 quarters)
    - Add hidden div for College grade inputs (3 terms)
    - Add hidden input for grading_system value
    - Update form field labels and structure
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

  - [x] 4.2 Implement dynamic form rendering logic

    - Update showAddGradeModal() to detect student grading system
    - Show/hide appropriate grade input fields based on grading system
    - Set grading_system hidden field value
    - _Requirements: 3.1, 3.2, 3.3, 3.4_

  - [x] 4.3 Update grade edit functionality

    - Modify editGradeModal() to pre-fill correct fields based on grading_system
    - Ensure K-12 grades load into quarter fields
    - Ensure College grades load into term fields
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

  - [x] 4.4 Implement client-side validation

    - Add validation for grade values (0-100 range)
    - Add validation for numeric input only
    - Display error messages for invalid inputs
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_

  - [x] 4.5 Update grade display cards in teacher view

    - Modify loadStudentGrades() to display correct grade labels
    - Show quarters for K-12 students
    - Show terms for College students
    - Display calculated average and pass/fail status
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [ ] 5. Update grade submission backend (ManageGrades.php POST handler)
  - [ ] 5.1 Modify POST request handler for K-12 grades
    - Accept first_quarter, second_quarter, third_quarter, fourth_quarter parameters
    - Accept grading_system parameter
    - Validate grade values on server side
    - _Requirements: 3.5, 8.1, 8.2, 8.3, 8.4, 8.5_

  - [ ] 5.2 Update database INSERT query for K-12 grades
    - Include quarter columns in INSERT statement
    - Include grading_system column in INSERT statement
    - Use prepared statements for security
    - _Requirements: 1.1, 1.2, 1.5, 2.4_

  - [ ] 5.3 Update database UPDATE query for K-12 grades
    - Include quarter columns in UPDATE statement
    - Preserve grading_system value during updates
    - Use prepared statements for security
    - _Requirements: 1.1, 1.2, 4.4, 4.5_

  - [ ] 5.4 Update College grade handling to include grading_system
    - Add grading_system = 'COLLEGE' to existing College INSERT/UPDATE queries
    - Ensure backward compatibility with existing College grade logic
    - _Requirements: 2.5, 7.1, 7.4_

- [ ] 6. Update grade retrieval API (get_student_grades.php)
  - [ ] 6.1 Modify SQL query to fetch all grade columns
    - Include first_quarter, second_quarter, third_quarter, fourth_quarter in SELECT
    - Include grading_system column in SELECT
    - Maintain existing prelim, midterm, pre_finals, finals columns
    - _Requirements: 1.1, 1.2, 1.3_

  - [ ] 6.2 Add grading system detection to student data
    - Call determineGradingSystem() for each student
    - Include grading_system in student object returned to client
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

  - [ ] 6.3 Calculate average and status for each grade record
    - Call calculateAverage() for each grade record
    - Call getGradeStatus() for each grade record
    - Include average and status in response JSON
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

  - [ ] 6.4 Handle legacy records without grading_system value
    - Detect NULL grading_system values
    - Determine grading system from student grade_level
    - Return appropriate grade data
    - _Requirements: 7.2, 7.3, 7.4_

- [ ] 7. Update student grade display interface (Grades.php)
  - [ ] 7.1 Add PHP helper functions to Grades.php
    - Include determineGradingSystem() function
    - Include calculateAverage() function
    - Include getGradeStatus() function
    - _Requirements: 2.1, 2.2, 2.3, 5.1, 5.2_

  - [ ] 7.2 Modify grade card rendering for K-12 students
    - Add conditional logic to detect K-12 grading system
    - Display 4 quarter columns with labels
    - Show quarter grade values
    - _Requirements: 6.1, 6.2, 6.5_

  - [ ] 7.3 Modify grade card rendering for College students
    - Add conditional logic to detect College grading system
    - Display 3 term columns with labels (Prelim, Midterm, Finals)
    - Show term grade values
    - Exclude pre_finals from display
    - _Requirements: 6.2, 6.5, 7.5_

  - [ ] 7.4 Update average and status display
    - Calculate and display average for each subject
    - Display PASSED status in green for average >= 75
    - Display FAILED status in red for average < 75
    - Display INCOMPLETE status in gray for missing grades
    - _Requirements: 5.3, 5.4, 5.5, 6.3, 6.4_

- [ ] 8. Update parent grade display interface (ParentGrades.php if exists)
  - [ ] 8.1 Apply same grade display logic as student portal
    - Implement conditional rendering for K-12 vs College
    - Display quarters for K-12 students
    - Display terms for College students
    - Show average and pass/fail status
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

- [ ] 9. Create database migration utilities
  - [ ] 9.1 Create backup script before migration
    - Write script to backup grades_record table
    - Store backup with timestamp
    - _Requirements: 7.1_

  - [ ] 9.2 Create rollback script
    - Write script to remove added columns if needed
    - Restore from backup if necessary
    - _Requirements: 7.1_

- [ ] 10. Integration testing and validation
  - [ ] 10.1 Test K-12 grade entry flow end-to-end
    - Teacher searches for K-12 student
    - Teacher sees quarter input fields
    - Teacher enters grades and saves
    - Verify data saved correctly in database
    - Student views grades and sees quarters
    - _Requirements: 2.1, 2.2, 2.3, 3.1, 3.2, 6.1, 6.2_

  - [ ] 10.2 Test College grade entry flow end-to-end
    - Teacher searches for College student
    - Teacher sees term input fields
    - Teacher enters grades and saves
    - Verify data saved correctly in database
    - Student views grades and sees terms
    - _Requirements: 2.3, 3.2, 6.2, 7.3_

  - [ ] 10.3 Test grade editing for both systems
    - Edit existing K-12 grade record
    - Verify quarter fields pre-filled correctly
    - Edit existing College grade record
    - Verify term fields pre-filled correctly
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

  - [ ] 10.4 Test average calculation and status display
    - Verify K-12 average calculated from 4 quarters
    - Verify College average calculated from 3 terms (excluding pre_finals)
    - Verify PASSED status for average >= 75
    - Verify FAILED status for average < 75
    - Verify INCOMPLETE status for missing grades
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

  - [ ] 10.5 Test backward compatibility with existing data
    - Verify existing College grades display correctly
    - Verify existing College grades can be edited
    - Verify average calculation works for legacy records
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

  - [ ] 10.6 Test validation and error handling
    - Test grade input validation (0-100 range)
    - Test non-numeric input rejection
    - Test empty form submission
    - Test database error handling
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_
