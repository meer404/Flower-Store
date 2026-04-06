<?php
/**
 * Gmail Configuration
 * Bloom & Vine Flower Store
 * 
 * This file stores Gmail STMP settings for sending emails
 */

// Gmail SMTP Configuration
define('GMAIL_SMTP_HOST', 'smtp.gmail.com');
define('GMAIL_SMTP_PORT', 587);
define('GMAIL_SMTP_SECURE', 'tls');

// Gmail Account Email (change this to your Gmail address)
define('GMAIL_EMAIL', getenv('GMAIL_EMAIL') ?: 'your-email@gmail.com');

// Gmail App Password (create at: https://myaccount.google.com/apppasswords)
// DO NOT use your regular Gmail password!
define('GMAIL_PASSWORD', getenv('GMAIL_PASSWORD') ?: 'your-app-password-here');

// From Name
define('MAIL_FROM_NAME', 'Bloom & Vine');

/**
 * Validate Gmail configuration
 * 
 * @return array|true Returns array of errors or true if valid
 */
function validateGmailConfig(): array|true {
    $errors = [];
    
    if (GMAIL_EMAIL === 'your-email@gmail.com') {
        $errors[] = 'Gmail email not configured. Set GMAIL_EMAIL environment variable.';
    }
    
    if (GMAIL_PASSWORD === 'your-app-password-here') {
        $errors[] = 'Gmail app password not configured. Set GMAIL_PASSWORD environment variable.';
    }
    
    if (!filter_var(GMAIL_EMAIL, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid Gmail email format.';
    }
    
    return empty($errors) ? true : $errors;
}
?>
