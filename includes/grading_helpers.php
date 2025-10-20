<?php
/**
 * Grading System Helper Functions
 * Provides utilities for K-12 vs College grading system
 */

/**
 * Determines if a student is K-12 or College based on grade level
 * @param string $gradeLevel - The student's grade level (e.g., "Grade 7", "2nd Year")
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
    
    return "COLLEGE"; // Default to college if uncertain
}

/**
 * Calculates the average grade based on grading system
 * @param array $grade - Grade record array
 * @return float|null - Average grade or null if incomplete
 */
function calculateAverage($grade) {
    $gradingSystem = $grade['grading_system'] ?? determineGradingSystem($grade['grade_level'] ?? '');
    
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
        // College: Use prelim, midterm, finals (NOT pre_finals)
        $terms = array_filter([
            $grade['prelim'] ?? null,
            $grade['midterm'] ?? null,
            $grade['finals'] ?? null
        ], function($g) { return $g !== null && is_numeric($g); });
        
        if (empty($terms)) return null;
        return array_sum($terms) / count($terms);
    }
}

/**
 * Determines pass/fail status based on average
 * @param float|null $average - The calculated average
 * @return array - Status array with text, color, and bgColor
 */
function getGradeStatus($average) {
    if ($average === null) {
        return [
            'text' => 'INCOMPLETE',
            'color' => 'text-gray-600',
            'bgColor' => 'bg-gray-100'
        ];
    }
    if ($average >= 75) {
        return [
            'text' => 'PASSED',
            'color' => 'text-green-600',
            'bgColor' => 'bg-green-100'
        ];
    }
    return [
        'text' => 'FAILED',
        'color' => 'text-red-600',
        'bgColor' => 'bg-red-100'
    ];
}

/**
 * Validates a grade value
 * @param mixed $grade - The grade value to validate
 * @return bool - True if valid, false otherwise
 */
function isValidGrade($grade) {
    if ($grade === null || $grade === '') return true; // Allow empty grades
    if (!is_numeric($grade)) return false;
    $grade = floatval($grade);
    return $grade >= 0 && $grade <= 100;
}

/**
 * Formats a grade for display
 * @param mixed $grade - The grade value
 * @return string - Formatted grade or '-' if null
 */
function formatGrade($grade) {
    if ($grade === null || $grade === '') return '-';
    return number_format(floatval($grade), 2);
}
?>
