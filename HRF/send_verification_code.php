<?php
session_start();
header('Content-Type: application/json');

// Load PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$email = $_POST['email'] ?? '';

// Validate email
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

// Check if it's a Gmail address
if (!preg_match('/@gmail\.com$/i', $email)) {
    echo json_encode(['success' => false, 'message' => 'Only Gmail addresses are supported for verification']);
    exit;
}

// Generate 6-digit verification code
$verification_code = sprintf('%06d', mt_rand(0, 999999));

// Store in session with expiry (5 minutes)
$_SESSION['email_verification'] = [
    'email' => $email,
    'code' => $verification_code,
    'expires' => time() + 300 // 5 minutes
];

// Load email configuration
$email_config = require '../config/email_config.php';

// Create PHPMailer instance
$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = $email_config['smtp_host'];
    $mail->SMTPAuth = true;
    $mail->Username = $email_config['smtp_username'];
    $mail->Password = $email_config['smtp_password'];
    $mail->SMTPSecure = $email_config['smtp_secure'];
    $mail->Port = $email_config['smtp_port'];
    
    // Recipients
    $mail->setFrom($email_config['from_email'], $email_config['from_name']);
    $mail->addAddress($email);
    $mail->addReplyTo($email_config['reply_to'], $email_config['from_name']);
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Email Verification Code - Cornerstone College Inc.';
    $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #0B2C62; color: white; padding: 20px; text-align: center; }
                .content { background-color: #f9f9f9; padding: 30px; border-radius: 5px; margin-top: 20px; }
                .code { font-size: 32px; font-weight: bold; color: #0B2C62; text-align: center; letter-spacing: 5px; padding: 20px; background-color: white; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Email Verification</h1>
                </div>
                <div class='content'>
                    <p>Hello,</p>
                    <p>You have requested to verify your email address for Cornerstone College Inc. HR Portal.</p>
                    <p>Your verification code is:</p>
                    <div class='code'>$verification_code</div>
                    <p><strong>This code will expire in 5 minutes.</strong></p>
                    <p>If you did not request this code, please ignore this email.</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " Cornerstone College Inc. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
    ";
    $mail->AltBody = "Your verification code is: $verification_code\n\nThis code will expire in 5 minutes.\n\nIf you did not request this code, please ignore this email.";
    
    // Send email
    $mail->send();
    
    // Return success (without debug code in production)
    echo json_encode([
        'success' => true,
        'message' => 'Verification code sent to ' . $email
        // Remove 'debug_code' in production for security
        // 'debug_code' => $verification_code // Only for testing
    ]);
    
} catch (Exception $e) {
    error_log("Email sending failed: " . $mail->ErrorInfo);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send verification email. Please try again later.'
    ]);
}
?>
