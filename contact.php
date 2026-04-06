<?php
declare(strict_types=1);

/**
 * Contact Us Page
 * Bloom & Vine Flower Store
 */

// Load composer dependencies
require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/src/language.php';
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/design_config.php';
require_once __DIR__ . '/src/components.php';

$lang = getCurrentLang();
$dir = getHtmlDir();
$message = '';

// Ensure contact_messages table exists
try {
    $pdo = getDB();
    $pdo->query("SELECT 1 FROM contact_messages LIMIT 1");
} catch (Exception $e) {
    // Table doesn't exist, create it
    try {
        $pdo = getDB();
        $sql = file_get_contents(__DIR__ . '/database/add_contact_messages.sql');
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
    } catch (Exception $ex) {
        // Silently fail - table will be created on admin page access
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $name = sanitizeInput('name', 'POST', '');
    $email = sanitizeInput('email', 'POST', '');
    $subject = sanitizeInput('subject', 'POST', '');
    $messageBody = sanitizeInput('message', 'POST', '');

    // Validate email format
    $emailValid = filter_var($email, FILTER_VALIDATE_EMAIL);

    if ($name && $emailValid && $subject && $messageBody) {
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare('
                INSERT INTO contact_messages (full_name, email, subject, message, ip_address, user_agent)
                VALUES (:name, :email, :subject, :message, :ip_address, :user_agent)
            ');
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'subject' => $subject,
                'message' => $messageBody,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
            $message = setFlashMessage('✓ Thank you for your message! We will get back to you soon.', 'success');
        } catch (PDOException $e) {
            error_log('Contact message submission error: ' . $e->getMessage());
            $message = setFlashMessage('✕ An error occurred. Please try again later.', 'error');
        }
    } else {
        $invalidEmail = !$emailValid && $email ? 'Invalid email address. ' : '';
        $message = setFlashMessage('✕ Please fill in all fields correctly. ' . $invalidEmail, 'error');
    }
}
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include __DIR__ . '/src/pwa_head.php'; ?>
    <title><?= e(t('contact_us')) ?> - Bloom & Vine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?= getLuxuryTailwindConfig() ?>
</head>
<body class="bg-white min-h-screen flex flex-col" style="font-family: 'Inter', 'Segoe UI', sans-serif;">
    <?php include __DIR__ . '/src/header.php'; ?>

    <div class="flex-grow">
                <?php 
        $langFile = __DIR__ . "/src/pages/{$lang}/contact.php";
        if (file_exists($langFile)) {
            include $langFile;
        } else {
            include __DIR__ . "/src/pages/en/contact.php";
        }
        ?>
    </div>

    <?= modernFooter() ?>