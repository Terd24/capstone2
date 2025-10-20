# Design Document: K-12 vs College Grading System

## Overview

This design document outlines the implementation approach for a dual grading system that supports both K-12 (quarterly) and College (term-based) academic structures. The system will automatically detect student educational levels and present appropriate grading interfaces with correct calculation methods.

### Key Design Principles

1. **Backward Compatibility**: Existing College grade records must continue to function without data loss
2. **Automatic Detection**: Student classification (K-12 vs College) should be automatic based on grade_level
3. **Separation of Concerns**: Database schema supports both systems; application logic determines which to use
4. **User Experience**: Teachers and students see only the relevant grading interface for their context
5. **Data Integrity**: Validation ensures only valid grades (0-100) are stored

## Architecture

### System Components

```
┌─────────────────────────────────────────────────────────────┐
│                     Presentation Layer                       │
├──────────────────────────┬──────────────────────────────────┤
│   Teacher Portal         │      Student Portal              │
│   - ManageGrades.php     │      - Grades.php                │
│   - Grade Entry Forms    │      - Grade Display Cards       │
└──────────────────────────┴──────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                     Business Logic Layer                     │
├─────────────────────────────────────────────────────────────┤
│   - Student Classification Logic                            │
│   - Grade Calculation Engine                                │
│   - Validation Rules                                        │
│   - API Endpoints (get_student_grades.php, etc.)           │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                     Data Access Layer                        │
├─────────────────────────────────────────────────────────────┤
│   - Database Connection (db_conn.php)                       │
│   - CRUD Operations for grades_record                       │
│   - Student Account Queries                                 │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                     Database Layer                           │
├─────────────────────────────────────────────────────────────┤
│   MySQL Database: onecci_db                                 │
│   - grades_record table (enhanced)                          │
│   - student_account table                                   │
└─────────────────────────────────────────────────────────────┘
```

## Components and Interfaces

### 1. Database Schema Enhancement

#### grades_record Table Modifications

**New Columns to Add:**
```sql
ALTER TABLE grades_record
ADD COLUMN first_quarter DECIMAL(5,2) DEFAULT NULL AFTER finals,
ADD COLUMN second_quarter DECIMAL(5,2) DEFAULT NULL AFTER first_quarter,
ADD COLUMN third_quarter DECIMAL(5,2) DEFAULT NULL AFTER second_quarter,
ADD COLUMN fourth_quarter DECIMAL(5,2) DEFAULT NULL AFTER third_quarter,
ADD COLUMN grading_system VARCHAR(10) DEFAULT NULL AFTER fourth_quarter;
```

**Complete Table Structure:**
- `id` (INT, PRIMARY KEY, AUTO_INCREMENT)
- `id_number` (VARCHAR, student identifier)
- `subject` (VARCHAR, subject name)
- `school_year_term` (VARCHAR, e.g., "2024-2025 1st Term")
- `prelim` (DECIMAL(5,2), College term 1)
- `midterm` (DECIMAL(5,2), College term 2)
- `pre_finals` (DECIMAL(5,2), College term 3 - legacy, not used in average)
- `finals` (DECIMAL(5,2), College term 3)
- `first_quarter` (DECIMAL(5,2), K-12 quarter 1)
- `second_quarter` (DECIMAL(5,2), K-12 quarter 2)
- `third_quarter` (DECIMAL(5,2), K-12 quarter 3)
- `fourth_quarter` (DECIMAL(5,2), K-12 quarter 4)
- `grading_system` (VARCHAR(10), "K12" or "COLLEGE")
- `teacher_name` (VARCHAR, teacher who entered the grade)

**Indexes:**
- Index on `id_number` for fast student lookups
- Composite index on `(id_number, subject, school_year_term)` for unique grade records

### 2. Student Classification Logic

#### Classification Function (JavaScript)

```javascript
/**
 * Determines if a student is K-12 or College based on grade level
 * @param {string} gradeLevel - The student's grade level (e.g., "Grade 7", "2nd Year")
 * @returns {string} - "K12" or "COLLEGE"
 */
function determineGradingSystem(gradeLevel) {
    if (!gradeLevel) return "COLLEGE"; // Default fallback
    
    const level = gradeLevel.toLowerCase().trim();
    
    // K-12 patterns
    if (level.includes('kinder')) return "K12";
    if (/grade\s*[1-9]/.test(level)) return "K12";
    if (/grade\s*1[0-2]/.test(level)) return "K12";
    
    // College patterns
    if (/[1-4](st|nd|rd|th)\s*year/.test(level)) return "COLLEGE";
    
    return "COLLEGE"; // Default to college if uncertain
}
```

#### Classification Function (PHP)

```php
/**
 * Determines if a student is K-12 or College based on grade level
 * @param string $gradeLevel - The student's grade level
 * @return string - "K12" or "COLLEGE"
 */
function determineGradingSystem($gradeLevel) {
    if (empty($gradeLevel)) return "COLLEGE";
    
    $level = strtolower(trim($gradeLevel));
    
    // K-12 patterns
    if (strpos($level, 'kinder') !== false) return "K12";
    if (preg_match('/grade\s*[1-9]/', $level)) return "K12";
    if (preg_match('/grade\s*1[0-2]/', $level)) return "K12";
    
    // College patterns
    if (preg_match('/[1-4](st|nd|rd|th)\s*year/', $level)) return "COLLEGE";
    
    return "COLLEGE";
}
```

### 3. Grade Entry Interface (Teacher Portal)

#### Dynamic Form Rendering

The grade entry modal in `ManageGrades.php` will dynamically render fields based on the student's grading system.

**HTML Structure:**
```html
<div id="addGradeModal">
    <form id="addGradeForm">
        <input type="hidden" id="modal-student-id" name="student_id">
        <input type="hidden" id="modal-grading-system" name="grading_system">
        
        <!-- Subject and Term fields (common) -->
        <div>
            <label>Subject</label>
            <select name="subject" required></select>
        </div>
        
        <div>
            <label>School Year & Term</label>
            <select name="school_year_term" required></select>
        </div>
        
        <!-- K-12 Grade Fields (shown conditionally) -->
        <div id="k12-grades" class="hidden">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label>1st Quarter</label>
                    <input type="number" name="first_quarter" min="0" max="100" step="0.01">
                </div>
                <div>
                    <label>2nd Quarter</label>
                    <input type="number" name="second_quarter" min="0" max="100" step="0.01">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label>3rd Quarter</label>
                    <input type="number" name="third_quarter" min="0" max="100" step="0.01">
                </div>
                <div>
                    <label>4th Quarter</label>
                    <input type="number" name="fourth_quarter" min="0" max="100" step="0.01">
                </div>
            </div>
        </div>
        
        <!-- College Grade Fields (shown conditionally) -->
        <div id="college-grades" class="hidden">
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label>Prelim</label>
                    <input type="number" name="prelim" min="0" max="100" step="0.01">
                </div>
                <div>
                    <label>Midterm</label>
                    <input type="number" name="midterm" min="0" max="100" step="0.01">
                </div>
                <div>
                    <label>Finals</label>
                    <input type="number" name="finals" min="0" max="100" step="0.01">
                </div>
            </div>
        </div>
        
        <button type="submit">Save Grade</button>
    </form>
</div>
```

**JavaScript Logic:**
```javascript
function showAddGradeModal() {
    const gradingSystem = determineGradingSystem(currentStudentInfo.gradeLevelText);
    document.getElementById('modal-grading-system').value = gradingSystem;
    
    if (gradingSystem === 'K12') {
        document.getElementById('k12-grades').classList.remove('hidden');
        document.getElementById('college-grades').classList.add('hidden');
    } else {
        document.getElementById('k12-grades').classList.add('hidden');
        document.getElementById('college-grades').classList.remove('hidden');
    }
    
    document.getElementById('addGradeModal').classList.remove('hidden');
}
```

### 4. Grade Calculation Engine

#### Average Calculation Logic

**JavaScript Implementation:**
```javascript
/**
 * Calculates the average grade based on grading system
 * @param {object} grade - Grade record object
 * @returns {number|null} - Average grade or null if incomplete
 */
function calculateAverage(grade) {
    const gradingSystem = grade.grading_system || determineGradingSystem(currentStudentInfo.gradeLevelText);
    
    if (gradingSystem === 'K12') {
        const quarters = [
            parseFloat(grade.first_quarter),
            parseFloat(grade.second_quarter),
            parseFloat(grade.third_quarter),
            parseFloat(grade.fourth_quarter)
        ].filter(g => !isNaN(g) && g !== null);
        
        if (quarters.length === 0) return null;
        return quarters.reduce((sum, g) => sum + g, 0) / quarters.length;
    } else {
        // College: Use prelim, midterm, finals (NOT pre_finals)
        const terms = [
            parseFloat(grade.prelim),
            parseFloat(grade.midterm),
            parseFloat(grade.finals)
        ].filter(g => !isNaN(g) && g !== null);
        
        if (terms.length === 0) return null;
        return terms.reduce((sum, g) => sum + g, 0) / terms.length;
    }
}

/**
 * Determines pass/fail status
 * @param {number} average - The calculated average
 * @returns {object} - Status object with text, color, and bgColor
 */
function getGradeStatus(average) {
    if (average === null) {
        return { text: 'INCOMPLETE', color: 'text-gray-600', bgColor: 'bg-gray-100' };
    }
    if (average >= 75) {
        return { text: 'PASSED', color: 'text-green-600', bgColor: 'bg-green-100' };
    }
    return { text: 'FAILED', color: 'text-red-600', bgColor: 'bg-red-100' };
}
```

**PHP Implementation:**
```php
/**
 * Calculates the average grade based on grading system
 * @param array $grade - Grade record array
 * @return float|null - Average grade or null if incomplete
 */
function calculateAverage($grade) {
    $gradingSystem = $grade['grading_system'] ?? 'COLLEGE';
    
    if ($gradingSystem === 'K12') {
        $quarters = array_filter([
            $grade['first_quarter'] ?? null,
            $grade['second_quarter'] ?? null,
            $grade['third_quarter'] ?? null,
            $grade['fourth_quarter'] ?? null
        ], function($g) { return $g !== null && is_numeric($g); });
        
        if (empty($quarters)) return null;
        return array_sum($quarters) / count($quarters);
    } else {
        // College: Use prelim, midterm, finals
        $terms = array_filter([
            $grade['prelim'] ?? null,
            $grade['midterm'] ?? null,
            $grade['finals'] ?? null
        ], function($g) { return $g !== null && is_numeric($g); });
        
        if (empty($terms)) return null;
        return array_sum($terms) / count($terms);
    }
}
```

### 5. Grade Display Interface (Student Portal)

#### Grade Card Rendering

**PHP Template for Grades.php:**
```php
<?php foreach ($grades as $grade): ?>
    <?php
    $gradingSystem = $grade['grading_system'] ?? determineGradingSystem($_SESSION['grade_level']);
    $average = calculateAverage($grade);
    $status = getGradeStatus($average);
    ?>
    
    <div class="bg-white rounded-2xl shadow-lg p-6">
        <h3 class="text-lg font-bold"><?= htmlspecialchars($grade['subject']) ?></h3>
        <p class="text-sm text-gray-600">Teacher: <?= htmlspecialchars($grade['teacher_name']) ?></p>
        
        <div class="border-t pt-4 mt-4">
            <?php if ($gradingSystem === 'K12'): ?>
                <!-- K-12 Quarters Display -->
                <div class="grid grid-cols-4 gap-2">
                    <div class="text-center">
                        <div class="text-xs font-medium text-gray-500">1ST QUARTER</div>
                        <div class="bg-gray-50 rounded-lg py-2">
                            <span class="text-lg font-bold"><?= $grade['first_quarter'] ?? '-' ?></span>
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="text-xs font-medium text-gray-500">2ND QUARTER</div>
                        <div class="bg-gray-50 rounded-lg py-2">
                            <span class="text-lg font-bold"><?= $grade['second_quarter'] ?? '-' ?></span>
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="text-xs font-medium text-gray-500">3RD QUARTER</div>
                        <div class="bg-gray-50 rounded-lg py-2">
                            <span class="text-lg font-bold"><?= $grade['third_quarter'] ?? '-' ?></span>
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="text-xs font-medium text-gray-500">4TH QUARTER</div>
                        <div class="bg-gray-50 rounded-lg py-2">
                            <span class="text-lg font-bold"><?= $grade['fourth_quarter'] ?? '-' ?></span>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- College Terms Display -->
                <div class="grid grid-cols-3 gap-2">
                    <div class="text-center">
                        <div class="text-xs font-medium text-gray-500">PRELIM</div>
                        <div class="bg-gray-50 rounded-lg py-2">
                            <span class="text-lg font-bold"><?= $grade['prelim'] ?? '-' ?></span>
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="text-xs font-medium text-gray-500">MIDTERM</div>
                        <div class="bg-gray-50 rounded-lg py-2">
                            <span class="text-lg font-bold"><?= $grade['midterm'] ?? '-' ?></span>
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="text-xs font-medium text-gray-500">FINALS</div>
                        <div class="bg-gray-50 rounded-lg py-2">
                            <span class="text-lg font-bold"><?= $grade['finals'] ?? '-' ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Average and Status Display -->
            <?php if ($average !== null): ?>
                <div class="mt-4 pt-3 border-t">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-600">Average:</span>
                        <span class="text-xl font-bold <?= $status['color'] ?>">
                            <?= number_format($average, 2) ?>%
                        </span>
                    </div>
                    <div class="mt-2 text-center">
                        <span class="px-3 py-1 rounded-full text-sm font-medium <?= $status['bgColor'] ?> <?= $status['color'] ?>">
                            <?= $status['text'] ?>
                        </span>
                    </div>
                </div>
            <?php else: ?>
                <div class="mt-4 pt-3 border-t text-center">
                    <span class="px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-600">
                        INCOMPLETE
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
```

### 6. API Endpoints

#### get_student_grades.php Enhancement

**Request Parameters:**
- `student_id` (required): Student ID number
- `term` (optional): Specific school year term

**Response Structure:**
```json
{
    "success": true,
    "student": {
        "id_number": "2024-001",
        "name": "John Doe",
        "grade_level": "Grade 10",
        "program": "STEM",
        "grading_system": "K12"
    },
    "grades": [
        {
            "id": 1,
            "subject": "Mathematics",
            "teacher_name": "Jane Smith",
            "school_year_term": "2024-2025 1st Term",
            "grading_system": "K12",
            "first_quarter": 85.50,
            "second_quarter": 88.00,
            "third_quarter": 90.25,
            "fourth_quarter": 87.75,
            "average": 87.88,
            "status": "PASSED"
        }
    ],
    "terms": ["2024-2025 1st Term", "2023-2024 2nd Term"],
    "selected_term": "2024-2025 1st Term"
}
```

**Implementation Changes:**
```php
// Add grading system detection
$grading_system = determineGradingSystem($student['grade_level']);
$student['grading_system'] = $grading_system;

// Fetch all grade columns
$grades_query = "SELECT id, subject, teacher_name, school_year_term, grading_system,
                        prelim, midterm, pre_finals, finals,
                        first_quarter, second_quarter, third_quarter, fourth_quarter
                 FROM grades_record 
                 WHERE id_number = ? AND school_year_term = ?";

// Calculate average and status for each grade
foreach ($grades as &$grade) {
    $grade['average'] = calculateAverage($grade);
    $grade['status'] = getGradeStatus($grade['average']);
}
```

#### ManageGrades.php Form Submission Handler

**Enhanced POST Handler:**
```php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_number = $_POST['student_id'];
    $subject = $_POST['subject'];
    $school_year_term = $_POST['school_year_term'];
    $grading_system = $_POST['grading_system']; // "K12" or "COLLEGE"
    $teacher_name = $_POST['teacher'];
    
    if ($grading_system === 'K12') {
        $first_quarter = $_POST['first_quarter'] ?: null;
        $second_quarter = $_POST['second_quarter'] ?: null;
        $third_quarter = $_POST['third_quarter'] ?: null;
        $fourth_quarter = $_POST['fourth_quarter'] ?: null;
        
        // Check if record exists
        $check_stmt = $conn->prepare("SELECT id FROM grades_record WHERE id_number = ? AND subject = ? AND school_year_term = ?");
        $check_stmt->bind_param("sss", $id_number, $subject, $school_year_term);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update
            $update_stmt = $conn->prepare("UPDATE grades_record 
                                          SET first_quarter = ?, second_quarter = ?, third_quarter = ?, fourth_quarter = ?, 
                                              grading_system = ?, teacher_name = ? 
                                          WHERE id_number = ? AND subject = ? AND school_year_term = ?");
            $update_stmt->bind_param("ddddsssss", $first_quarter, $second_quarter, $third_quarter, $fourth_quarter, 
                                    $grading_system, $teacher_name, $id_number, $subject, $school_year_term);
            $update_stmt->execute();
        } else {
            // Insert
            $insert_stmt = $conn->prepare("INSERT INTO grades_record 
                                          (id_number, subject, school_year_term, first_quarter, second_quarter, 
                                           third_quarter, fourth_quarter, grading_system, teacher_name) 
                                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("sssddddss", $id_number, $subject, $school_year_term, $first_quarter, 
                                    $second_quarter, $third_quarter, $fourth_quarter, $grading_system, $teacher_name);
            $insert_stmt->execute();
        }
    } else {
        // College system - existing logic with grading_system field added
        $prelim = $_POST['prelim'] ?: null;
        $midterm = $_POST['midterm'] ?: null;
        $finals = $_POST['finals'] ?: null;
        
        // Similar check and insert/update logic with grading_system = 'COLLEGE'
    }
    
    echo json_encode(['success' => true, 'message' => 'Grade saved successfully']);
    exit;
}
```

## Data Models

### Grade Record Model

```typescript
interface GradeRecord {
    id: number;
    id_number: string;
    subject: string;
    school_year_term: string;
    teacher_name: string;
    grading_system: 'K12' | 'COLLEGE';
    
    // College fields
    prelim?: number | null;
    midterm?: number | null;
    pre_finals?: number | null;  // Legacy, not used in calculations
    finals?: number | null;
    
    // K-12 fields
    first_quarter?: number | null;
    second_quarter?: number | null;
    third_quarter?: number | null;
    fourth_quarter?: number | null;
    
    // Computed fields
    average?: number | null;
    status?: 'PASSED' | 'FAILED' | 'INCOMPLETE';
}
```

### Student Model

```typescript
interface Student {
    id_number: string;
    first_name: string;
    last_name: string;
    grade_level: string;  // e.g., "Grade 10", "2nd Year"
    academic_track?: string;  // e.g., "STEM", "BSIT"
    program?: string;
    grading_system: 'K12' | 'COLLEGE';  // Computed field
}
```

## Error Handling

### Validation Rules

1. **Grade Value Validation**
   - Must be numeric
   - Must be between 0 and 100 (inclusive)
   - Can be null (not entered yet)
   - Maximum 2 decimal places

2. **Grading System Validation**
   - Must be either "K12" or "COLLEGE"
   - Cannot be null when saving a grade record

3. **Student Classification**
   - If grade_level is null or unrecognized, default to "COLLEGE"
   - Log warning when classification is uncertain

### Error Messages

**Client-Side (JavaScript):**
```javascript
const ERROR_MESSAGES = {
    INVALID_GRADE: 'Grade must be a number between 0 and 100',
    MISSING_SUBJECT: 'Please select a subject',
    MISSING_TERM: 'Please select a school year and term',
    SAVE_FAILED: 'Failed to save grade. Please try again.',
    LOAD_FAILED: 'Failed to load grades. Please refresh the page.'
};
```

**Server-Side (PHP):**
```php
$ERROR_MESSAGES = [
    'INVALID_GRADE' => 'Grade must be a number between 0 and 100',
    'MISSING_STUDENT' => 'Student ID is required',
    'MISSING_SUBJECT' => 'Subject is required',
    'MISSING_TERM' => 'School year and term are required',
    'DB_ERROR' => 'Database error occurred',
    'UNAUTHORIZED' => 'You do not have permission to perform this action'
];
```

### Error Handling Flow

```
User Input → Client Validation → Server Validation → Database Operation
     ↓              ↓                    ↓                    ↓
   Error?        Error?              Error?              Error?
     ↓              ↓                    ↓                    ↓
Show inline    Show alert          Return JSON         Log & rollback
  message       message             error response      transaction
```

## Testing Strategy

### Unit Tests

1. **Student Classification Tests**
   - Test K-12 patterns: "Kinder 1", "Grade 1", "Grade 12"
   - Test College patterns: "1st Year", "4th Year"
   - Test edge cases: null, empty string, invalid formats

2. **Grade Calculation Tests**
   - Test K-12 average with all 4 quarters
   - Test K-12 average with partial quarters
   - Test College average with 3 terms
   - Test College average with partial terms
   - Test null/empty grade handling

3. **Status Determination Tests**
   - Test PASSED status (average >= 75)
   - Test FAILED status (average < 75)
   - Test INCOMPLETE status (no grades entered)
   - Test boundary conditions (exactly 75.00)

### Integration Tests

1. **Grade Entry Flow**
   - Teacher searches for K-12 student → sees quarter fields
   - Teacher searches for College student → sees term fields
   - Teacher enters grades → data saved correctly
   - Teacher edits existing grades → correct fields pre-filled

2. **Grade Display Flow**
   - K-12 student views grades → sees quarters
   - College student views grades → sees terms
   - Average calculated correctly for both systems
   - Pass/fail status displayed correctly

3. **Data Migration**
   - Existing College grades display correctly
   - Existing College grades can be edited
   - New K-12 grades can be added
   - Mixed grade records (K-12 and College) coexist

### Manual Testing Checklist

- [ ] Database migration script runs without errors
- [ ] Existing College student grades display correctly
- [ ] New K-12 student grades can be entered
- [ ] Grade entry form shows correct fields for K-12 students
- [ ] Grade entry form shows correct fields for College students
- [ ] Average calculation is correct for K-12 (4 quarters)
- [ ] Average calculation is correct for College (3 terms, excluding pre_finals)
- [ ] Pass/fail status displays correctly (75% threshold)
- [ ] INCOMPLETE status shows when not all grades entered
- [ ] Edit grade modal pre-fills correct values
- [ ] Student portal displays grades in correct format
- [ ] Teacher portal displays grades in correct format
- [ ] Parent portal displays grades in correct format (if applicable)
- [ ] Grade validation prevents invalid values (< 0 or > 100)
- [ ] System handles null/empty grades gracefully

### Performance Considerations

1. **Database Indexing**
   - Ensure index on `(id_number, subject, school_year_term)` for fast lookups
   - Monitor query performance with EXPLAIN

2. **Caching Strategy**
   - Cache student grading_system determination in session
   - Avoid repeated grade_level parsing

3. **Query Optimization**
   - Fetch all necessary columns in single query
   - Use prepared statements to prevent SQL injection
   - Limit result sets with pagination where appropriate

## Migration Strategy

### Phase 1: Database Schema Update

1. Run ALTER TABLE statements to add new columns
2. Verify columns added successfully
3. Create backup before proceeding

### Phase 2: Code Deployment

1. Deploy helper functions (determineGradingSystem, calculateAverage)
2. Update ManageGrades.php with dynamic form logic
3. Update get_student_grades.php with enhanced response
4. Update Grades.php (student portal) with conditional display

### Phase 3: Data Validation

1. Run script to populate grading_system for existing records
2. Verify all existing College grades still display correctly
3. Test with sample K-12 student accounts

### Phase 4: User Acceptance Testing

1. Teachers test grade entry for both K-12 and College students
2. Students verify grade display in their portals
3. Collect feedback and address issues

## Security Considerations

1. **Input Validation**
   - Sanitize all user inputs
   - Use prepared statements for all database queries
   - Validate grade values on both client and server

2. **Authorization**
   - Verify teacher role before allowing grade entry
   - Verify student can only view their own grades
   - Prevent unauthorized access to grade modification

3. **Data Integrity**
   - Use transactions for grade updates
   - Maintain audit trail of grade changes (future enhancement)
   - Prevent SQL injection with parameterized queries

## Future Enhancements

1. **Grade History Tracking**
   - Track who modified grades and when
   - Allow viewing grade change history

2. **Weighted Averages**
   - Support different weights for quarters/terms
   - Configurable grading formulas

3. **Grade Comments**
   - Allow teachers to add comments to grades
   - Display comments in student portal

4. **Bulk Grade Import**
   - Import grades from CSV/Excel
   - Validate and preview before saving

5. **Grade Analytics**
   - Class average comparisons
   - Performance trends over time
   - Subject-wise analysis
