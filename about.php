<?php
declare(strict_types=1);

/**
 * About Us Page
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
    <title><?= e(t('about_us')) ?> - Bloom & Vine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?= getLuxuryTailwindConfig() ?>
</head>
<body class="bg-white min-h-screen flex flex-col" style="font-family: 'Inter', 'Segoe UI', sans-serif;">
    <?php include __DIR__ . '/src/header.php'; ?>

    <div class="flex-grow">
        <!-- Hero Section -->
        <div class="bg-gradient-to-br from-luxury-primary via-purple-900 to-luxury-primary text-white py-16 md:py-24">
            <div class="container mx-auto px-4 md:px-6">
                <h1 class="text-4xl md:text-5xl font-luxury font-bold mb-4"><?= e(t('about_us')) ?></h1>
                <p class="text-lg text-purple-100"><?= e(t('premium_flowers')) ?></p>
            </div>
        </div>

        <!-- Main Content -->
        <div class="container mx-auto px-4 md:px-6 py-12 md:py-16">
            <!-- Our Story -->
            <div class="grid md:grid-cols-2 gap-12 mb-16 items-center">
                <div>
                    <h2 class="text-3xl font-bold text-luxury-primary mb-6">Our Story</h2>
                    <p class="text-gray-600 mb-4 leading-relaxed">
                        Bloom & Vine was founded with a simple mission: to bring the beauty and joy of premium flowers to everyone in Kurdistan. 
                        With over a decade of experience in the floral industry, our team is passionate about creating stunning arrangements 
                        that make every occasion special.
                    </p>
                    <p class="text-gray-600 mb-4 leading-relaxed">
                        We work with the finest local and international flower suppliers to ensure that every arrangement is crafted with 
                        the highest quality blooms and creative expertise. Our commitment to excellence has made us the trusted choice for 
                        weddings, corporate events, and personal celebrations.
                    </p>
                    <p class="text-gray-600 leading-relaxed">
                        Today, Bloom & Vine continues to grow, bringing fresh perspectives and innovative designs to the world of floral artistry.
                    </p>
                </div>
                <div class="rounded-lg overflow-hidden shadow-lg h-96 bg-gradient-to-br from-luxury-accent to-yellow-500 flex items-center justify-center">
                    <i class="fas fa-spa text-white text-8xl opacity-50"></i>
                </div>
            </div>

            <!-- Our Values -->
            <div class="mb-16">
                <h2 class="text-3xl font-bold text-luxury-primary mb-12 text-center">Our Core Values</h2>
                <div class="grid md:grid-cols-3 gap-8">
                    <!-- Quality -->
                    <div class="text-center p-6 rounded-lg border border-luxury-primary/20 hover:border-luxury-primary transition-colors">
                        <div class="text-4xl text-luxury-accent mb-4">
                            <i class="fas fa-star"></i>
                        </div>
                        <h3 class="text-xl font-bold text-luxury-primary mb-3">Quality</h3>
                        <p class="text-gray-600">
                            We only use the freshest, most beautiful flowers sourced from trusted suppliers around the world.
                        </p>
                    </div>

                    <!-- Customer Service -->
                    <div class="text-center p-6 rounded-lg border border-luxury-primary/20 hover:border-luxury-primary transition-colors">
                        <div class="text-4xl text-luxury-accent mb-4">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h3 class="text-xl font-bold text-luxury-primary mb-3">Customer Care</h3>
                        <p class="text-gray-600">
                            Your satisfaction is our priority. We provide exceptional service before, during, and after your purchase.
                        </p>
                    </div>

                    <!-- Sustainability -->
                    <div class="text-center p-6 rounded-lg border border-luxury-primary/20 hover:border-luxury-primary transition-colors">
                        <div class="text-4xl text-luxury-accent mb-4">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <h3 class="text-xl font-bold text-luxury-primary mb-3">Sustainability</h3>
                        <p class="text-gray-600">
                            We are committed to eco-friendly practices and responsible sourcing for a better tomorrow.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Team Section -->
            <div class="mb-16">
                <h2 class="text-3xl font-bold text-luxury-primary mb-12 text-center">Our Team</h2>
                <p class="text-center text-gray-600 mb-8 max-w-2xl mx-auto">
                    Our talented team of florists, designers, and customer service professionals are dedicated to making every moment special.
                </p>
            </div>

            <!-- Why Choose Us -->
            <div class="bg-purple-50 rounded-lg p-8 md:p-12">
                <h2 class="text-3xl font-bold text-luxury-primary mb-8 text-center">Why Choose Bloom & Vine?</h2>
                <div class="grid md:grid-cols-2 gap-8">
                    <div class="flex gap-4">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-12 w-12 rounded-md bg-luxury-accent">
                                <i class="fas fa-check text-white"></i>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Fresh Flowers</h3>
                            <p class="text-gray-600 mt-2">Delivered fresh daily from our curated suppliers</p>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-12 w-12 rounded-md bg-luxury-accent">
                                <i class="fas fa-check text-white"></i>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Expert Design</h3>
                            <p class="text-gray-600 mt-2">Professional arrangements by experienced florists</p>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-12 w-12 rounded-md bg-luxury-accent">
                                <i class="fas fa-check text-white"></i>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Free Delivery</h3>
                            <p class="text-gray-600 mt-2">Complimentary delivery for orders over $50</p>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-12 w-12 rounded-md bg-luxury-accent">
                                <i class="fas fa-check text-white"></i>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">24/7 Support</h3>
                            <p class="text-gray-600 mt-2">Always available to help with your questions</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?= modernFooter() ?>
</body>
</html>
