# Gmail Configuration Guide for Email Sending

This guide will help you set up Gmail to send emails through your Flower Store application.

## Step 1: Install PHPMailer

Run this command in your project root:

```bash
composer install
```

Or if you're updating an existing installation:

```bash
composer require phpmailer/phpmailer:^6.9
```

## Step 2: Create Gmail App Password

Since Gmail doesn't allow direct password login for third-party apps, you need an **App Password**. Follow these steps:

### 2a. Enable 2-Step Verification (if not already enabled)
1. Go to [myaccount.google.com](https://myaccount.google.com)
2. Click **Security** in the left menu
3. Under "How you sign in to Google", enable **2-Step Verification**
4. Follow the prompts to complete setup

### 2b. Generate App Password
1. Go to [myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords)
2. Select **Mail** as the app
3. Select **Windows Computer** (or your OS) as the device
4. Google will generate a 16-character password
5. **Copy this password** - you'll need it in Step 3

## Step 3: Set Environment Variables

### On Windows (XAMPP):

**Option A: Using .env file (Recommended)**
1. Create a file named `.env` in your project root:
```
GMAIL_EMAIL=your-email@gmail.com
GMAIL_PASSWORD=your-16-character-app-password
```

2. Add this to your [src/functions.php](../src/functions.php) at the very top:
```php
// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}
```

**Option B: Edit php.ini (Alternative)**
1. Open `C:\xampp\php\php.ini`
2. Find the `[mail function]` section
3. Add these lines:
```ini
GMAIL_EMAIL=your-email@gmail.com
GMAIL_PASSWORD=your-16-character-app-password
```

**Option C: Hardcode in gmail_config.php (NOT RECOMMENDED)**
1. Edit [src/gmail_config.php](../src/gmail_config.php)
2. Replace:
```php
define('GMAIL_EMAIL', getenv('GMAIL_EMAIL') ?: 'your-email@gmail.com');
define('GMAIL_PASSWORD', getenv('GMAIL_PASSWORD') ?: 'your-app-password-here');
```

With your actual credentials:
```php
define('GMAIL_EMAIL', 'your-email@gmail.com');
define('GMAIL_PASSWORD', 'your-16-character-app-password');
```

## Step 4: Test Email Sending

1. Go to **Admin Dashboard → Contact Messages**
2. Click "View Details" on any message
3. Enter a test reply and click "📧 Send Reply & Notify Customer"
4. Check the customer's email inbox (and spam folder)

## Step 5: Configure System Settings (Optional)

1. Go to **Admin Dashboard → Settings**
2. Update:
   - `site_name`: Your store name
   - `site_email`: Your Gmail address (for consistency)

## Troubleshooting

### "PHPMailer not installed"
Run: `composer install`

### "SMTP connection failed"
- Verify Gmail credentials are correct
- Check that 2-Step Verification is enabled
- Ensure you used an **App Password**, not your regular Gmail password
- Check firewall settings (port 587 must be open)

### "Email sent but not received"
- Check customer's spam/junk folder
- Verify customer email address is correct
- Check Gmail error logs: `logs/` folder

### "Gmail says 'Less secure app'"
This is normal. The app password bypasses this security check.

## Environment Variables Summary

| Variable | Value | Example |
|----------|-------|---------|
| `GMAIL_EMAIL` | Your Gmail address | `store@gmail.com` |
| `GMAIL_PASSWORD` | 16-char app password | `abcd efgh ijkl mnop` |

## Files Modified

- ✅ `composer.json` - Added PHPMailer dependency
- ✅ `src/gmail_config.php` - New Gmail configuration file
- ✅ `admin/contact_messages.php` - Updated email sending function

## Support

For issues with Gmail App Passwords, visit: [support.google.com](https://support.google.com/accounts/answer/185833)

For PHPMailer documentation: [phpmailer.github.io](https://phpmailer.github.io/)
