<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$email = $_POST['email'] ?? '';
$code = $_POST['code'] ?? '';

// Check if verification data exists in session
if (!isset($_SESSION['email_verification'])) {
    echo json_encode(['success' => false, 'message' => 'No verification request found. Please request a new code.']);
    exit;
}

$verification_data = $_SESSION['email_verification'];

// Check if code has expired
if (time() > $verification_data['expires']) {
    unset($_SESSION['email_verification']);
    echo json_encode(['success' => false, 'message' => 'Verification code has expired. Please request a new code.']);
    exit;
}

// Check if email matches
if ($email !== $verification_data['email']) {
    echo json_encode(['success' => false, 'message' => 'Email does not match verification request']);
    exit;
}

// Check if code matches
if ($code !== $verification_data['code']) {
    echo json_encode(['success' => false, 'message' => 'Invalid verification code']);
    exit;
}

// Mark email as verified
$_SESSION['verified_emails'][$email] = time();

// Clear verification data
unset($_SESSION['email_verification']);

echo json_encode([
    'success' => true,
    'message' => 'Email verified successfully'
]);
?>
