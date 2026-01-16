<?php
/**
 * Admin Footer Component
 * Minimal footer for Admin pages
 */
?>
<footer class="bg-white border-t border-gray-200 mt-auto">
    <div class="px-6 py-4 flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-gray-500">
        <div class="flex items-center gap-1">
            <span>&copy; <?= date('Y') ?> Bloom & Vine.</span>
            <span class="hidden md:inline">|</span>
            <span class="font-medium text-purple-600">Admin Panel v2.0</span>
        </div>
        <div class="flex items-center gap-6">
            <a href="#" class="hover:text-purple-600 transition-colors">Support</a>
            <a href="#" class="hover:text-purple-600 transition-colors">Documentation</a>
            <a href="../index.php" class="hover:text-purple-600 transition-colors">Visit Store</a>
        </div>
    </div>
</footer>
