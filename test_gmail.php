<?php
/**
 * Gmail Configuration Test
 * Run this script to verify your Gmail setup is working
 * 
 * Access from browser: http://localhost/Flower-Store/test_gmail.php
 */

// Load composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Load environment if .env exists
if (file_exists(__DIR__ . '/.env')) {
    $env = parse_ini_file(__DIR__ . '/.env');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}

require_once __DIR__ . '/src/gmail_config.php';
require_once __DIR__ . '/src/functions.php';

$testStatus = [];

// Check 1: PHPMailer installed
$testStatus['phpmailer'] = [
    'name' => 'PHPMailer Installation',
    'result' => class_exists('\PHPMailer\PHPMailer\PHPMailer'),
    'message' => class_exists('\PHPMailer\PHPMailer\PHPMailer') 
        ? 'PHPMailer is installed' 
        : 'PHPMailer not found. Run: composer install'
];

// Check 2: Gmail config validation
$configValid = validateGmailConfig();
$testStatus['config'] = [
    'name' => 'Gmail Configuration',
    'result' => $configValid === true,
    'message' => $configValid === true 
        ? 'Gmail credentials configured' 
        : implode(', ', (array)$configValid)
];

// Check 3: Try sending a test email
$testStatus['send'] = [
    'name' => 'Test Email Sending',
    'result' => null,
    'message' => 'Not tested yet'
];

if ($testStatus['phpmailer']['result'] && $testStatus['config']['result']) {
    $testEmail = sanitizeInput('test_email', 'POST', '');
    
    if (!empty($testEmail)) {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = GMAIL_SMTP_HOST;
            $mail->Port = GMAIL_SMTP_PORT;
            $mail->SMTPSecure = GMAIL_SMTP_SECURE;
            $mail->SMTPAuth = true;
            $mail->Username = GMAIL_EMAIL;
            $mail->Password = GMAIL_PASSWORD;
            $mail->setFrom(GMAIL_EMAIL, MAIL_FROM_NAME);
            $mail->addAddress($testEmail);
            $mail->Subject = 'Test Email from Bloom & Vine';
            $mail->isHTML(true);
            $mail->Body = '<h2>Test Email</h2><p>This is a test email from your Bloom & Vine store.</p><p>If you received this, Gmail is configured correctly!</p>';
            $mail->AltBody = 'This is a test email from Bloom & Vine.';
            
            if ($mail->send()) {
                $testStatus['send'] = [
                    'name' => 'Test Email Sending',
                    'result' => true,
                    'message' => 'Test email sent successfully to ' . htmlspecialchars($testEmail)
                ];
            }
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            $testStatus['send'] = [
                'name' => 'Test Email Sending',
                'result' => false,
                'message' => 'PHPMailer Error: ' . htmlspecialchars($e->getMessage())
            ];
        } catch (Exception $e) {
            $testStatus['send'] = [
                'name' => 'Test Email Sending',
                'result' => false,
                'message' => 'Error: ' . htmlspecialchars($e->getMessage())
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gmail Configuration Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-purple-50 to-blue-50 min-h-screen p-6">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">📧 Gmail Configuration Test</h1>
            <p class="text-gray-600 mb-8">Verify your Gmail setup is working correctly</p>

            <!-- Test Results -->
            <div class="space-y-4 mb-8">
                <?php foreach ($testStatus as $test): ?>
                    <div class="p-4 rounded-lg border-l-4 <?= $test['result'] === null ? 'bg-gray-50 border-gray-400' : ($test['result'] ? 'bg-green-50 border-green-500' : 'bg-red-50 border-red-500') ?>">
                        <div class="flex items-start gap-3">
                            <span class="text-2xl">
                                <?= $test['result'] === true ? '✓' : ($test['result'] === false ? '✕' : '⏳') ?>
                            </span>
                            <div class="flex-1">
                                <h3 class="font-bold text-lg <?= $test['result'] === false ? 'text-red-800' : ($test['result'] === true ? 'text-green-800' : 'text-gray-800') ?>">
                                    <?= htmlspecialchars($test['name']) ?>
                                </h3>
                                <p class="text-sm <?= $test['result'] === false ? 'text-red-700' : ($test['result'] === true ? 'text-green-700' : 'text-gray-700') ?>">
                                    <?= htmlspecialchars($test['message']) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Current Configuration Display -->
            <div class="bg-gray-50 p-4 rounded-lg mb-8 border border-gray-200">
                <h3 class="font-bold text-gray-900 mb-3">Current Configuration:</h3>
                <table class="w-full text-sm font-mono">
                    <tr class="border-b border-gray-300">
                        <td class="text-gray-600 py-2">Gmail Email:</td>
                        <td class="text-right font-bold text-gray-900"><?= htmlspecialchars(GMAIL_EMAIL) ?></td>
                    </tr>
                    <tr>
                        <td class="text-gray-600 py-2">SMTP Host:</td>
                        <td class="text-right font-bold text-gray-900"><?= htmlspecialchars(GMAIL_SMTP_HOST) ?></td>
                    </tr>
                    <tr class="border-t border-gray-300">
                        <td class="text-gray-600 py-2">SMTP Port:</td>
                        <td class="text-right font-bold text-gray-900"><?= GMAIL_SMTP_PORT ?></td>
                    </tr>
                </table>
            </div>

            <!-- Send Test Email Form -->
            <?php if ($testStatus['phpmailer']['result'] && $testStatus['config']['result']): ?>
                <div class="bg-blue-50 p-6 rounded-lg border border-blue-200">
                    <h3 class="font-bold text-lg text-blue-900 mb-3">Send Test Email</h3>
                    <form method="POST" class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Test Email Address:</label>
                            <input 
                                type="email" 
                                name="test_email" 
                                placeholder="your-email@example.com"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                                required
                            >
                        </div>
                        <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition-colors">
                            Send Test Email
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Help Links -->
            <div class="mt-8 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                <h3 class="font-bold text-amber-900 mb-2">📚 Need Help?</h3>
                <ul class="text-sm text-amber-800 space-y-1">
                    <li>• Read: <a href="GMAIL_SETUP.md" target="_blank" class="text-blue-600 hover:underline">GMAIL_SETUP.md</a></li>
                    <li>• Gmail App Passwords: <a href="https://myaccount.google.com/apppasswords" target="_blank" class="text-blue-600 hover:underline">myaccount.google.com/apppasswords</a></li>
                    <li>• Check .env file exists with correct credentials</li>
                    <li>• Run: <code>composer install</code> to install PHPMailer</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
