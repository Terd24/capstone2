# Requirements Document

## Introduction

This document specifies the requirements for implementing a dual grading system that supports both K-12 (Kindergarten through Grade 12) and College (1st through 4th Year) academic structures. The system will automatically detect the student's educational level and present the appropriate grading interface with correct calculation methods. K-12 students will use a quarterly grading system (4 quarters), while College students will use a term-based system (Prelim, Midterm, Finals). Both systems will use a 75% passing threshold and provide clear pass/fail indicators.

## Glossary

- **Grading_System**: The web-based application that manages student academic performance records
- **K12_Student**: A student enrolled in Kindergarten, Grade 1 through Grade 12
- **College_Student**: A student enrolled in 1st Year, 2nd Year, 3rd Year, or 4th Year college programs
- **Quarter**: A grading period used in K-12 education (1st Quarter, 2nd Quarter, 3rd Quarter, 4th Quarter)
- **Term**: A grading period used in College education (Prelim, Midterm, Finals)
- **Grade_Record**: A database entry containing a student's grades for a specific subject and academic period
- **Average_Grade**: The calculated mean of all entered grades for a subject
- **Passing_Threshold**: The minimum average grade of 75% required to pass a subject
- **Teacher_Portal**: The interface used by teachers to enter and manage student grades
- **Student_Portal**: The interface used by students to view their grades
- **Grade_Level**: The educational level of a student (e.g., "Grade 7", "2nd Year")
- **Academic_Track**: The program or strand a student is enrolled in (e.g., "STEM", "BSIT")

## Requirements

### Requirement 1

**User Story:** As a database administrator, I want the grades_record table to support both K-12 and College grading structures, so that the system can store grades for all student types.

#### Acceptance Criteria

1. WHEN THE Grading_System initializes the database schema, THE Grading_System SHALL add columns named first_quarter, second_quarter, third_quarter, and fourth_quarter to the grades_record table with DECIMAL(5,2) data type
2. WHEN THE Grading_System initializes the database schema, THE Grading_System SHALL add a column named grading_system to the grades_record table with VARCHAR(10) data type
3. WHEN THE Grading_System initializes the database schema, THE Grading_System SHALL preserve existing columns prelim, midterm, pre_finals, and finals in the grades_record table
4. WHEN THE Grading_System stores a grade value, THE Grading_System SHALL accept values between 0.00 and 100.00 inclusive
5. WHEN THE Grading_System stores a grading_system value, THE Grading_System SHALL accept only the values "K12" or "COLLEGE"

### Requirement 2

**User Story:** As a teacher, I want the system to automatically detect whether a student is K-12 or College, so that I don't have to manually specify the grading system for each student.

#### Acceptance Criteria

1. WHEN THE Grading_System retrieves a student record with grade_level containing "Kinder", THE Grading_System SHALL classify the student as K12_Student
2. WHEN THE Grading_System retrieves a student record with grade_level matching the pattern "Grade [1-9]" or "Grade 1[0-2]", THE Grading_System SHALL classify the student as K12_Student
3. WHEN THE Grading_System retrieves a student record with grade_level matching the pattern "[1-4](st|nd|rd|th) Year", THE Grading_System SHALL classify the student as College_Student
4. WHEN THE Grading_System creates or updates a Grade_Record for a K12_Student, THE Grading_System SHALL set the grading_system field to "K12"
5. WHEN THE Grading_System creates or updates a Grade_Record for a College_Student, THE Grading_System SHALL set the grading_system field to "COLLEGE"

### Requirement 3

**User Story:** As a teacher, I want to see different grade entry fields based on whether the student is K-12 or College, so that I can enter grades in the appropriate format for each student type.

#### Acceptance Criteria

1. WHEN THE Teacher_Portal displays the grade entry form for a K12_Student, THE Teacher_Portal SHALL display four input fields labeled "1st Quarter", "2nd Quarter", "3rd Quarter", and "4th Quarter"
2. WHEN THE Teacher_Portal displays the grade entry form for a College_Student, THE Teacher_Portal SHALL display three input fields labeled "Prelim", "Midterm", and "Finals"
3. WHEN THE Teacher_Portal displays the grade entry form for a K12_Student, THE Teacher_Portal SHALL hide the input fields for prelim, midterm, pre_finals, and finals
4. WHEN THE Teacher_Portal displays the grade entry form for a College_Student, THE Teacher_Portal SHALL hide the input fields for quarters
5. WHEN THE Teacher_Portal submits a grade entry form, THE Teacher_Portal SHALL send only the grade values corresponding to the student's grading system

### Requirement 4

**User Story:** As a teacher, I want to edit existing grades and see the current values pre-filled in the correct format, so that I can update grades accurately without confusion.

#### Acceptance Criteria

1. WHEN THE Teacher_Portal opens the edit grade modal for a K12_Student grade record, THE Teacher_Portal SHALL pre-fill the quarter input fields with values from first_quarter, second_quarter, third_quarter, and fourth_quarter columns
2. WHEN THE Teacher_Portal opens the edit grade modal for a College_Student grade record, THE Teacher_Portal SHALL pre-fill the term input fields with values from prelim, midterm, and finals columns
3. WHEN THE Teacher_Portal displays an edit grade modal, THE Teacher_Portal SHALL display the grade entry fields matching the grading_system value stored in the Grade_Record
4. WHEN THE Teacher_Portal updates a Grade_Record, THE Teacher_Portal SHALL preserve the original grading_system value
5. WHEN THE Teacher_Portal updates a Grade_Record, THE Teacher_Portal SHALL update only the grade columns corresponding to the grading_system value

### Requirement 5

**User Story:** As a student or teacher, I want to see the calculated average grade and pass/fail status for each subject, so that I can understand the academic performance.

#### Acceptance Criteria

1. WHEN THE Grading_System calculates the Average_Grade for a K12_Student subject, THE Grading_System SHALL compute the mean of all entered quarter grades
2. WHEN THE Grading_System calculates the Average_Grade for a College_Student subject, THE Grading_System SHALL compute the mean of all entered term grades
3. WHEN THE Grading_System displays a subject with Average_Grade greater than or equal to 75.00, THE Grading_System SHALL display the status "PASSED" in green color
4. WHEN THE Grading_System displays a subject with Average_Grade less than 75.00, THE Grading_System SHALL display the status "FAILED" in red color
5. IF not all required grades are entered for a subject, THEN THE Grading_System SHALL display the status "INCOMPLETE" in gray or yellow color

### Requirement 6

**User Story:** As a student, I want to view my grades in the format appropriate for my educational level, so that I can easily understand my academic performance.

#### Acceptance Criteria

1. WHEN THE Student_Portal displays grades for a K12_Student, THE Student_Portal SHALL show grade labels "1st Quarter", "2nd Quarter", "3rd Quarter", and "4th Quarter"
2. WHEN THE Student_Portal displays grades for a College_Student, THE Student_Portal SHALL show grade labels "Prelim", "Midterm", and "Finals"
3. WHEN THE Student_Portal displays a subject grade card, THE Student_Portal SHALL display the Average_Grade with two decimal places
4. WHEN THE Student_Portal displays a subject grade card, THE Student_Portal SHALL display the pass/fail status based on the Passing_Threshold of 75%
5. WHEN THE Student_Portal displays grades, THE Student_Portal SHALL show only the grade periods relevant to the student's grading system

### Requirement 7

**User Story:** As a system administrator, I want existing College student grades to remain functional after the system update, so that no historical data is lost or corrupted.

#### Acceptance Criteria

1. WHEN THE Grading_System migrates existing Grade_Record entries, THE Grading_System SHALL preserve all existing prelim, midterm, pre_finals, and finals values
2. WHEN THE Grading_System encounters a Grade_Record with NULL grading_system value, THE Grading_System SHALL determine the grading system based on the associated student's Grade_Level
3. WHEN THE Grading_System displays a Grade_Record created before the migration, THE Grading_System SHALL correctly identify and display it using the appropriate grading system format
4. WHEN THE Grading_System updates a pre-migration Grade_Record, THE Grading_System SHALL set the grading_system field to the correct value based on the student's current Grade_Level
5. WHEN THE Grading_System calculates averages for pre-migration College records, THE Grading_System SHALL use prelim, midterm, and finals values (excluding pre_finals from the average calculation)

### Requirement 8

**User Story:** As a teacher, I want the grade entry form to validate my inputs, so that I cannot enter invalid grade values.

#### Acceptance Criteria

1. WHEN THE Teacher_Portal receives a grade input value less than 0, THE Teacher_Portal SHALL reject the input and display an error message
2. WHEN THE Teacher_Portal receives a grade input value greater than 100, THE Teacher_Portal SHALL reject the input and display an error message
3. WHEN THE Teacher_Portal receives a non-numeric grade input value, THE Teacher_Portal SHALL reject the input and display an error message
4. WHEN THE Teacher_Portal submits a grade entry form with at least one valid grade value, THE Teacher_Portal SHALL accept the submission
5. WHEN THE Teacher_Portal submits a grade entry form with all empty grade fields, THE Teacher_Portal SHALL accept the submission and store NULL values for the grades
