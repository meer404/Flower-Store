<?php
declare(strict_types=1);

/**
 * Privacy Policy Page
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/src/language.php';
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/design_config.php';
require_once __DIR__ . '/src/components.php';

$lang = getCurrentLang();
$dir = getHtmlDir();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include __DIR__ . '/src/pwa_head.php'; ?>
    <title>Privacy Policy - Bloom & Vine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?= getLuxuryTailwindConfig() ?>
</head>
<body class="bg-white min-h-screen flex flex-col" style="font-family: 'Inter', 'Segoe UI', sans-serif;">
    <?php include __DIR__ . '/src/header.php'; ?>

    <div class="flex-grow">
        <!-- Hero Section -->
        <div class="bg-gradient-to-br from-luxury-primary via-purple-900 to-luxury-primary text-white py-16 md:py-24">
            <div class="container mx-auto px-4 md:px-6">
                <h1 class="text-4xl md:text-5xl font-luxury font-bold mb-4">Privacy Policy</h1>
                <p class="text-lg text-purple-100">Your privacy is important to us</p>
            </div>
        </div>

        <!-- Main Content -->
        <div class="container mx-auto px-4 md:px-6 py-12 md:py-16">
            <div class="max-w-3xl mx-auto prose prose-lg">
                <div class="space-y-8 text-gray-600">
                    <div>
                        <h2 class="text-2xl font-bold text-luxury-primary mb-4">1. Introduction</h2>
                        <p>
                            Welcome to Bloom & Vine ("we" or "us" or "our"). We are committed to protecting your privacy. 
                            This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you 
                            visit our website and use our services.
                        </p>
                    </div>

                    <div>
                        <h2 class="text-2xl font-bold text-luxury-primary mb-4">2. Information We Collect</h2>
                        <p>We may collect information about you in a variety of ways. The information we may collect on the site includes:</p>
                        <ul class="list-disc pl-6 space-y-2 mt-3">
                            <li><strong>Personal Data:</strong> Name, email address, phone number, and delivery address</li>
                            <li><strong>Payment Information:</strong> Credit card details (processed securely)</li>
                            <li><strong>Account Information:</strong> Username, password, and account preferences</li>
                            <li><strong>Usage Data:</strong> Browser type, IP address, pages visited, and time spent on pages</li>
                            <li><strong>Cookies:</strong> Information stored on your device to improve your experience</li>
                        </ul>
                    </div>

                    <div>
                        <h2 class="text-2xl font-bold text-luxury-primary mb-4">3. How We Use Your Information</h2>
                        <p>Having accurate information about you permits us to provide you with a smooth, efficient, and customized experience. Specifically, we may use information collected about you via the site to:</p>
                        <ul class="list-disc pl-6 space-y-2 mt-3">
                            <li>Process your transactions and send related information</li>
                            <li>Email you regarding your account or order</li>
                            <li>Fulfill and manage your orders</li>
                            <li>Generate a personal profile about you</li>
                            <li>Increase the efficiency and operation of the site</li>
                            <li>Monitor and analyze usage and trends to improve your experience</li>
                            <li>Notify you of updates to the site</li>
                            <li>Offer new products, services, and/or recommendations to you</li>
                        </ul>
                    </div>

                    <div>
                        <h2 class="text-2xl font-bold text-luxury-primary mb-4">4. Disclosure of Your Information</h2>
                        <p>We may share your information in the following situations:</p>
                        <ul class="list-disc pl-6 space-y-2 mt-3">
                            <li><strong>By Law or to Protect Rights:</strong> If required by law or if we believe in good faith that disclosure is necessary</li>
                            <li><strong>Third-Party Service Providers:</strong> We may share your information with vendors, consultants, and other service providers who need to know this information</li>
                            <li><strong>Business Transfers:</strong> If we are involved in a merger, acquisition, or bankruptcy, your information may be transferred</li>
                            <li><strong>With Your Consent:</strong> We will disclose and use your information when you give us explicit consent</li>
                        </ul>
                    </div>

                    <div>
                        <h2 class="text-2xl font-bold text-luxury-primary mb-4">5. Security of Your Information</h2>
                        <p>
                            We use administrative, technical, and physical security measures to protect your personal information. 
                            However, perfect security does not exist online. We will provide notice if we are aware of any security compromise 
                            that may affect your personally identifiable information.
                        </p>
                    </div>

                    <div>
                        <h2 class="text-2xl font-bold text-luxury-primary mb-4">6. Contact Us</h2>
                        <p>
                            If you have questions or comments about this Privacy Policy, please contact us at:
                        </p>
                        <div class="mt-4 bg-purple-50 p-4 rounded-lg">
                            <p><strong>Email:</strong> privacy@bloomandvine.com</p>
                            <p><strong>Phone:</strong> +964 750 123 4567</p>
                            <p><strong>Address:</strong> Kurdistan Region, Iraq</p>
                        </div>
                    </div>

                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <p class="text-sm text-gray-600">
                            Last Updated: <?= date('F j, Y') ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?= modernFooter() ?>
</body>
</html>
