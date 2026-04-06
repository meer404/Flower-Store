<?php
declare(strict_types=1);

/**
 * Terms of Service Page
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
    <title>Terms of Service - Bloom & Vine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?= getLuxuryTailwindConfig() ?>
</head>
<body class="bg-white min-h-screen flex flex-col" style="font-family: 'Inter', 'Segoe UI', sans-serif;">
    <?php include __DIR__ . '/src/header.php'; ?>

    <div class="flex-grow">
        <!-- Hero Section -->
        <div class="bg-gradient-to-br from-luxury-primary via-purple-900 to-luxury-primary text-white py-16 md:py-24">
            <div class="container mx-auto px-4 md:px-6">
                <h1 class="text-4xl md:text-5xl font-luxury font-bold mb-4">Terms of Service</h1>
                <p class="text-lg text-purple-100">Please read these terms carefully</p>
            </div>
        </div>

        <!-- Main Content -->
        <div class="container mx-auto px-4 md:px-6 py-12 md:py-16">
            <div class="max-w-3xl mx-auto prose prose-lg">
                <div class="space-y-8 text-gray-600">
                    <div>
                        <h2 class="text-2xl font-bold text-luxury-primary mb-4">1. Agreement to Terms</h2>
                        <p>
                            By accessing and using this website, you accept and agree to be bound by the terms and provision of this agreement. 
                            If you do not agree to abide by the above, please do not use this service.
                        </p>
                    </div>

                    <div>
                        <h2 class="text-2xl font-bold text-luxury-primary mb-4">2. Use License</h2>
                        <p>
                            Permission is granted to temporarily download one copy of the materials (information or software) on Bloom & Vine 
                            website for personal, non-commercial transitory viewing only. This is the grant of a license, not a transfer of title, 
                            and under this license you may not:
                        </p>
                        <ul class="list-disc pl-6 space-y-2 mt-3">
                            <li>Modify or copy the materials</li>
                            <li>Use the materials for any commercial purpose or for any public display</li>
                            <li>Attempt to decompile or reverse engineer any software contained on the website</li>
                            <li>Remove any copyright or other proprietary notations from the materials</li>
                            <li>Transfer the materials to another person or "mirror" the materials on any other server</li>
                        </ul>
                    </div>

                    <div>
                        <h2 class="text-2xl font-bold text-luxury-primary mb-4">3. Disclaimer</h2>
                        <p>
                            The materials on Bloom & Vine website are provided on an 'as is' basis. Bloom & Vine makes no warranties, 
                            expressed or implied, and hereby disclaims and negates all other warranties including, without limitation, 
                            implied warranties or conditions of merchantability, fitness for a particular purpose, or non-infringement of 
                            intellectual property or other violation of rights.
                        </p>
                    </div>

                    <div>
                        <h2 class="text-2xl font-bold text-luxury-primary mb-4">4. Limitations</h2>
                        <p>
                            In no event shall Bloom & Vine or its suppliers be liable for any damages (including, without limitation, 
                            damages for loss of data or profit, or due to business interruption) arising out of the use or inability to 
                            use the materials on Bloom & Vine's website.
                        </p>
                    </div>

                    <div>
                        <h2 class="text-2xl font-bold text-luxury-primary mb-4">5. Accuracy of Materials</h2>
                        <p>
                            The materials appearing on Bloom & Vine website could include technical, typographical, or photographic errors. 
                            Bloom & Vine does not warrant that any of the materials on its website are accurate, complete, or current. 
                            Bloom & Vine may make changes to the materials contained on its website at any time without notice.
                        </p>
                    </div>

                    <div>
                        <h2 class="text-2xl font-bold text-luxury-primary mb-4">6. Links</h2>
                        <p>
                            Bloom & Vine has not reviewed all of the sites linked to its website and is not responsible for the contents 
                            of any such linked site. The inclusion of any link does not imply endorsement by Bloom & Vine of the site. 
                            Use of any such linked website is at the user's own risk.
                        </p>
                    </div>

                    <div>
                        <h2 class="text-2xl font-bold text-luxury-primary mb-4">7. Modifications</h2>
                        <p>
                            Bloom & Vine may revise these terms of service for its website at any time without notice. 
                            By using this website, you are agreeing to be bound by the then current version of these terms of service.
                        </p>
                    </div>

                    <div>
                        <h2 class="text-2xl font-bold text-luxury-primary mb-4">8. Governing Law</h2>
                        <p>
                            These terms and conditions are governed by and construed in accordance with the laws of Kurdistan Region, Iraq, 
                            and you irrevocably submit to the exclusive jurisdiction of the courts in that location.
                        </p>
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
