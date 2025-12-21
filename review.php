<?php
declare(strict_types=1);

/**
 * Add Review Page
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/src/language.php';
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/design_config.php';

requireLogin();

$pdo = getDB();
$productId = (int)sanitizeInput('product_id', 'GET', '0');
$userId = (int)$_SESSION['user_id'];

if ($productId <= 0) {
    redirect('shop.php', e('Invalid product'), 'error');
}

// Check if user already reviewed this product
$stmt = $pdo->prepare('SELECT id FROM reviews WHERE user_id = :user_id AND product_id = :product_id');
$stmt->execute(['user_id' => $userId, 'product_id' => $productId]);
if ($stmt->fetch()) {
    redirect('product.php?id=' . $productId, e('You have already reviewed this product'), 'error');
}

// Get product info
$stmt = $pdo->prepare('SELECT id, name_en, name_ku FROM products WHERE id = :id');
$stmt->execute(['id' => $productId]);
$product = $stmt->fetch();

if (!$product) {
    redirect('shop.php', e('Product not found'), 'error');
}

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = sanitizeInput('csrf_token', 'POST');
    $rating = (int)sanitizeInput('rating', 'POST', '0');
    $comment = sanitizeInput('comment', 'POST');
    
    if (!verifyCSRFToken($csrfToken)) {
        $error = t('error');
    } elseif ($rating < 1 || $rating > 5) {
        $error = e('Please select a rating');
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (:product_id, :user_id, :rating, :comment)');
            $stmt->execute([
                'product_id' => $productId,
                'user_id' => $userId,
                'rating' => $rating,
                'comment' => $comment
            ]);
            
            redirect('product.php?id=' . $productId, e('Review submitted successfully!'), 'success');
        } catch (PDOException $e) {
            error_log('Review error: ' . $e->getMessage());
            $error = t('error');
        }
    }
}

$csrfToken = generateCSRFToken();
$lang = getCurrentLang();
$dir = getHtmlDir();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e('Write a Review') ?> - Bloom & Vine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?= getLuxuryTailwindConfig() ?>
</head>
<body class="bg-white min-h-screen" style="font-family: 'Inter', 'Segoe UI', sans-serif;">
    <?php include __DIR__ . '/src/header.php'; ?>

    <div class="container mx-auto px-4 py-8 max-w-2xl">
        <h1 class="text-3xl font-bold text-primary mb-4"><?= e('Write a Review') ?></h1>
        
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <p class="text-gray-600 mb-2"><?= e('Product') ?>:</p>
            <h2 class="text-xl font-semibold text-primary"><?= e(getProductName($product)) ?></h2>
        </div>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= e($error) ?>
            </div>
        <?php endif; ?>
        
        <div class="bg-white rounded-lg shadow-lg p-6">
            <form method="POST" action="" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                
                <div>
                    <label class="block text-sm font-medium text-primary mb-2"><?= e('Rating') ?> *</label>
                    <div class="flex space-x-2" id="ratingStars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <button type="button" onclick="setRating(<?= e((string)$i) ?>)" 
                                    class="text-3xl text-gray-300 hover:text-yellow-400 focus:outline-none" 
                                    data-rating="<?= e((string)$i) ?>">☆</button>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="rating" id="rating" value="0" required>
                </div>
                
                <div>
                    <label for="comment" class="block text-sm font-medium text-primary mb-2"><?= e('Your Review') ?></label>
                    <textarea id="comment" name="comment" rows="6" 
                              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                              placeholder="<?= e('Share your experience with this product...') ?>"></textarea>
                </div>
                
                <div class="flex gap-4">
                    <button type="submit" 
                            class="flex-1 bg-primary text-white py-2 px-4 rounded-md hover:bg-opacity-90 transition duration-200">
                        <?= e('Submit Review') ?>
                    </button>
                    <a href="product.php?id=<?= e((string)$productId) ?>" 
                       class="flex-1 bg-gray-200 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-300 transition duration-200 text-center">
                        <?= e('Cancel') ?>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function setRating(rating) {
            document.getElementById('rating').value = rating;
            const stars = document.querySelectorAll('#ratingStars button');
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.textContent = '★';
                    star.classList.remove('text-luxury-border');
                    star.classList.add('text-luxury-accent');
                } else {
                    star.textContent = '☆';
                    star.classList.remove('text-luxury-accent');
                    star.classList.add('text-luxury-border');
                }
            });
        }
    </script>

    <!-- Footer -->
    <footer class="bg-primary text-white p-8 mt-12">
        <div class="container mx-auto text-center">
            <p>&copy; <?= e(date('Y')) ?> Bloom & Vine. <?= e('All rights reserved.') ?></p>
        </div>
    </footer>
</body>
</html>

