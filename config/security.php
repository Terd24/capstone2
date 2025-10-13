<?php
/**
 * Security Configuration and Helper Functions
 * Protects against SQL Injection, XSS, CSRF, and unauthorized access
 */

// Prevent direct access
if (!defined('SECURITY_LOADED')) {
    define('SECURITY_LOADED', true);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate CSRF Token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}

/**
 * Sanitize Input - Prevent XSS
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate Email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate Phone Number
 */
function validatePhone($phone) {
    return preg_match('/^[0-9\-\+\(\)\s]{7,20}$/', $phone);
}

/**
 * Validate Date
 */
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['role']) && !empty($_SESSION['role']);
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    if (!isLoggedIn()) {
        return false;
    }
    return strtolower($_SESSION['role']) === strtolower($role);
}

/**
 * Check if user has any of the specified roles
 */
function hasAnyRole($roles) {
    if (!isLoggedIn()) {
        return false;
    }
    $userRole = strtolower($_SESSION['role']);
    foreach ($roles as $role) {
        if ($userRole === strtolower($role)) {
            return true;
        }
    }
    return false;
}

/**
 * Require Login - Redirect if not logged in
 */
function requireLogin($redirectUrl = '../StudentLogin/login.php') {
    if (!isLoggedIn()) {
        header("Location: $redirectUrl");
        exit;
    }
}

/**
 * Require Specific Role - Redirect if unauthorized
 */
function requireRole($role, $redirectUrl = '../StudentLogin/login.php') {
    if (!hasRole($role)) {
        header("Location: $redirectUrl");
        exit;
    }
}

/**
 * Require Any Role - Redirect if unauthorized
 */
function requireAnyRole($roles, $redirectUrl = '../StudentLogin/login.php') {
    if (!hasAnyRole($roles)) {
        header("Location: $redirectUrl");
        exit;
    }
}

/**
 * Prevent SQL Injection - Use with prepared statements
 * This is a helper to validate input before using in queries
 */
function validateSQLInput($input, $type = 'string') {
    switch ($type) {
        case 'int':
            return filter_var($input, FILTER_VALIDATE_INT) !== false;
        case 'float':
            return filter_var($input, FILTER_VALIDATE_FLOAT) !== false;
        case 'email':
            return validateEmail($input);
        case 'alphanumeric':
            return preg_match('/^[a-zA-Z0-9]+$/', $input);
        case 'string':
        default:
            return is_string($input);
    }
}

/**
 * Rate Limiting - Prevent brute force attacks
 */
function checkRateLimit($action, $maxAttempts = 5, $timeWindow = 300) {
    $key = $action . '_' . $_SERVER['REMOTE_ADDR'];
    
    if (!isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = [
            'attempts' => 0,
            'first_attempt' => time()
        ];
    }
    
    $data = $_SESSION['rate_limit'][$key];
    
    // Reset if time window has passed
    if (time() - $data['first_attempt'] > $timeWindow) {
        $_SESSION['rate_limit'][$key] = [
            'attempts' => 1,
            'first_attempt' => time()
        ];
        return true;
    }
    
    // Check if exceeded max attempts
    if ($data['attempts'] >= $maxAttempts) {
        return false;
    }
    
    // Increment attempts
    $_SESSION['rate_limit'][$key]['attempts']++;
    return true;
}

/**
 * Log Security Event
 */
function logSecurityEvent($event, $details = '') {
    $logFile = __DIR__ . '/../logs/security.log';
    $logDir = dirname($logFile);
    
    // Create logs directory if it doesn't exist
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $user = $_SESSION['username'] ?? 'Guest';
    $logEntry = "[$timestamp] IP: $ip | User: $user | Event: $event | Details: $details\n";
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

/**
 * Secure Headers - Prevent clickjacking, XSS, etc.
 */
function setSecurityHeaders() {
    // Prevent clickjacking
    header('X-Frame-Options: SAMEORIGIN');
    
    // XSS Protection
    header('X-XSS-Protection: 1; mode=block');
    
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Referrer Policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy (adjust as needed)
    header("Content-Security-Policy: default-src 'self' https://cdn.tailwindcss.com; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com; style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com;");
}

/**
 * Validate File Upload
 */
function validateFileUpload($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf'], $maxSize = 5242880) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'Invalid file upload'];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File too large'];
    }
    
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extension, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    return ['success' => true];
}

/**
 * Generate Secure Random String
 */
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Set security headers on every page load
setSecurityHeaders();
?>
