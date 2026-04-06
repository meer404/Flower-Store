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
        <!-- Hero Section -->
        <div class="bg-gradient-to-br from-luxury-primary via-purple-900 to-luxury-primary text-white py-16 md:py-24">
            <div class="container mx-auto px-4 md:px-6">
                <h1 class="text-4xl md:text-5xl font-luxury font-bold mb-4"><?= e(t('contact_us')) ?></h1>
                <p class="text-lg text-purple-100">We'd love to hear from you. Get in touch with us today.</p>
            </div>
        </div>

        <!-- Main Content -->
        <div class="container mx-auto px-4 md:px-6 py-12 md:py-16">
            <?php
            $flash = getFlashMessage();
            if ($flash):
                echo alert($flash['message'], $flash['type']);
            endif;
            ?>

            <div class="grid md:grid-cols-3 gap-12 mb-16">
                <!-- Contact Form -->
                <div class="md:col-span-2">
                    <h2 class="text-2xl font-bold text-luxury-primary mb-6">Send us a Message</h2>
                    <form method="POST" class="bg-white rounded-lg border border-gray-200 p-8">
                        <div class="mb-6">
                            <label class="block text-gray-700 font-semibold mb-2" for="name">
                                Full Name *
                            </label>
                            <input 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-luxury-primary" 
                                type="text" 
                                name="name" 
                                id="name" 
                                required
                            >
                        </div>

                        <div class="mb-6">
                            <label class="block text-gray-700 font-semibold mb-2" for="email">
                                Email Address *
                            </label>
                            <input 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-luxury-primary" 
                                type="email" 
                                name="email" 
                                id="email" 
                                required
                            >
                        </div>

                        <div class="mb-6">
                            <label class="block text-gray-700 font-semibold mb-2" for="subject">
                                Subject *
                            </label>
                            <input 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-luxury-primary" 
                                type="text" 
                                name="subject" 
                                id="subject" 
                                required
                            >
                        </div>

                        <div class="mb-6">
                            <label class="block text-gray-700 font-semibold mb-2" for="message">
                                Message *
                            </label>
                            <textarea 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-luxury-primary" 
                                name="message" 
                                id="message" 
                                rows="6" 
                                required
                            ></textarea>
                        </div>

                        <button 
                            type="submit" 
                            name="submit" 
                            class="w-full bg-luxury-primary hover:bg-purple-800 text-white font-bold py-3 px-4 rounded-lg transition-colors"
                        >
                            Send Message
                        </button>
                    </form>
                </div>

                <!-- Contact Info -->
                <div>
                    <h2 class="text-2xl font-bold text-luxury-primary mb-8">Contact Information</h2>
                    
                    <div class="mb-8">
                        <h3 class="font-semibold text-gray-900 mb-2 flex items-center">
                            <i class="fas fa-phone text-luxury-accent me-3"></i> Phone
                        </h3>
                        <p class="text-gray-600"> +964 750 123 4567</p>
                    </div>

                    <div class="mb-8">
                        <h3 class="font-semibold text-gray-900 mb-2 flex items-center">
                            <i class="fas fa-envelope text-luxury-accent me-3"></i> Email
                        </h3>
                        <p class="text-gray-600"><a href="mailto:info@bloomandvine.com" class="hover:text-luxury-accent">info@bloomandvine.com</a></p>
                    </div>

                    <div class="mb-8">
                        <h3 class="font-semibold text-gray-900 mb-2 flex items-center">
                            <i class="fas fa-map-marker-alt text-luxury-accent me-3"></i> Location
                        </h3>
                        <p class="text-gray-600">Kurdistan Region, Iraq</p>
                    </div>

                    <div class="mb-8">
                        <h3 class="font-semibold text-gray-900 mb-4">Business Hours</h3>
                        <ul class="text-gray-600 space-y-2">
                            <li>Monday - Friday: 9AM - 6PM</li>
                            <li>Saturday: 10AM - 5PM</li>
                            <li>Sunday: Closed</li>
                        </ul>
                    </div>

                    <div class="bg-purple-50 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-900 mb-3">Follow Us</h3>
                        <div class="flex gap-3">
                            <a href="https://facebook.com" class="w-10 h-10 bg-luxury-primary text-white rounded-full flex items-center justify-center hover:bg-purple-800 transition-colors">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="https://instagram.com" class="w-10 h-10 bg-luxury-primary text-white rounded-full flex items-center justify-center hover:bg-purple-800 transition-colors">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="https://twitter.com" class="w-10 h-10 bg-luxury-primary text-white rounded-full flex items-center justify-center hover:bg-purple-800 transition-colors">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="https://wa.me" class="w-10 h-10 bg-luxury-primary text-white rounded-full flex items-center justify-center hover:bg-purple-800 transition-colors">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?= modernFooter() ?>
</body>
</html>
