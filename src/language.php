<?php
declare(strict_types=1);

/**
 * Language Handler
 * Bloom & Vine Flower Store
 * 
 * This file handles language switching and session management for
 * English (LTR) and Kurdish (RTL) support.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Allowed languages
$allowedLanguages = ['en', 'ku'];

// Get language from GET parameter or use session/default
$lang = isset($_GET['lang']) ? trim(strip_tags($_GET['lang'])) : '';

if (in_array($lang, $allowedLanguages, true)) {
    $_SESSION['lang'] = $lang;
} elseif (!isset($_SESSION['lang'])) {
    // Default to English if no language is set
    $_SESSION['lang'] = 'en';
}

// Language is now set in session, no need to return anything
// This file should be included at the top of pages that need language support

