# Gmail Email Verification Setup Instructions

## Prerequisites
- Gmail account
- XAMPP installed
- Composer installed

## Step-by-Step Setup

### 1. Install Composer (if not installed)
1. Download from: https://getcomposer.org/download/
2. Run installer
3. Verify: Open Command Prompt and type `composer --version`

### 2. Install PHPMailer
```bash
cd C:\xampp\htdocs\onecci
composer require phpmailer/phpmailer
```

### 3. Set up Gmail App Password
1. Go to: https://myaccount.google.com/
2. Click "Security" → Enable "2-Step Verification"
3. Search for "App passwords"
4. Select "Mail" and "Windows Computer"
5. Click "Generate"
6. **Copy the 16-character password**

### 4. Configure Email Settings
1. Open `config/email_config.php`
2. Replace these values:
   ```php
   'smtp_username' => 'your-email@gmail.com',  // Your Gmail address
   'smtp_password' => 'xxxx xxxx xxxx xxxx',   // 16-character app password
   'from_email' => 'your-email@gmail.com',     // Your Gmail address
   ```

### 5. Test the Setup
1. Go to: http://localhost/onecci/HRF/Dashboard.php
2. Click "Add Employee"
3. Enter a Gmail address
4. Click "Verify Email"
5. Check the Gmail inbox for the verification code
6. Enter the code and verify

## Troubleshooting

### Email not sending?
1. Check if Composer installed PHPMailer:
   ```bash
   cd C:\xampp\htdocs\onecci
   composer show phpmailer/phpmailer
   ```

2. Verify Gmail settings:
   - 2-Step Verification is enabled
   - App password is correct (16 characters, no spaces)
   - Gmail account is not locked

3. Check PHP error log:
   - Location: `C:\xampp\apache\logs\error.log`
   - Look for PHPMailer errors

### Common Errors

**"SMTP connect() failed"**
- Check internet connection
- Verify SMTP settings in `config/email_config.php`
- Try port 465 with 'ssl' instead of 587 with 'tls'

**"Invalid credentials"**
- Regenerate App Password
- Make sure you're using App Password, not regular Gmail password
- Check for typos in email_config.php

**"Class 'PHPMailer' not found"**
- Run: `composer require phpmailer/phpmailer`
- Check if `vendor` folder exists in project root

## Security Notes
- Never commit `config/email_config.php` to public repositories
- Keep your App Password secure
- Regenerate App Password if compromised
- Use environment variables for production

## For Production
1. Remove `debug_code` from response in `send_verification_code.php`
2. Use environment variables instead of config file
3. Enable error logging
4. Set up email rate limiting
5. Add CAPTCHA to prevent abuse
