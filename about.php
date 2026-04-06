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
                <?php 
        $langFile = __DIR__ . "/src/pages/{$lang}/about.php";
        if (file_exists($langFile)) {
            include $langFile;
        } else {
            include __DIR__ . "/src/pages/en/about.php";
        }
        ?>
    </div>

    <?= modernFooter() ?>