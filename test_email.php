<?php
/**
 * Email Configuration Test Script
 * Run this to verify your Gmail SMTP setup is working
 * Access: http://localhost/onecci/test_email.php
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// Load configuration
$email_config = require 'config/email_config.php';

echo "<h1>Email Configuration Test</h1>";
echo "<p>Testing Gmail SMTP connection...</p>";

// Check if configuration is set
if ($email_config['smtp_username'] === 'your-email@gmail.com') {
    echo "<p style='color: red;'><strong>ERROR:</strong> Please configure your Gmail settings in <code>config/email_config.php</code></p>";
    echo "<p>You need to:</p>";
    echo "<ol>";
    echo "<li>Replace 'your-email@gmail.com' with your actual Gmail address</li>";
    echo "<li>Replace 'your-app-password-here' with your 16-character App Password</li>";
    echo "</ol>";
    exit;
}

// Create test email
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
    $mail->SMTPDebug = 2; // Enable verbose debug output
    
    echo "<pre>";
    
    // Recipients
    $mail->setFrom($email_config['from_email'], $email_config['from_name']);
    $mail->addAddress($email_config['smtp_username']); // Send to yourself for testing
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Test Email - CCI System';
    $mail->Body = '<h1>Success!</h1><p>Your Gmail SMTP configuration is working correctly.</p>';
    $mail->AltBody = 'Success! Your Gmail SMTP configuration is working correctly.';
    
    // Send
    $mail->send();
    
    echo "</pre>";
    echo "<p style='color: green;'><strong>SUCCESS!</strong> Test email sent successfully.</p>";
    echo "<p>Check your inbox: <strong>" . $email_config['smtp_username'] . "</strong></p>";
    
} catch (Exception $e) {
    echo "</pre>";
    echo "<p style='color: red;'><strong>ERROR:</strong> Email could not be sent.</p>";
    echo "<p><strong>Error message:</strong> {$mail->ErrorInfo}</p>";
    echo "<hr>";
    echo "<h3>Troubleshooting:</h3>";
    echo "<ul>";
    echo "<li>Make sure 2-Step Verification is enabled on your Gmail account</li>";
    echo "<li>Verify you're using an App Password (not your regular Gmail password)</li>";
    echo "<li>Check that the App Password is correct (16 characters, no spaces)</li>";
    echo "<li>Ensure your internet connection is working</li>";
    echo "</ul>";
}
?>
