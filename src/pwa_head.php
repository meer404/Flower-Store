<?php
$manifestUrl = url('manifest.json');
$pwaScriptUrl = url('pwa.js');
$swUrl = url('service-worker.js');
$offlineUrl = url('offline.html');
?>
<link rel="manifest" href="<?= e($manifestUrl) ?>">
<meta name="theme-color" content="#1f2937">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Bloom & Vine">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="pwa-sw" content="<?= e($swUrl) ?>">
<meta name="pwa-offline" content="<?= e($offlineUrl) ?>">
<link rel="apple-touch-icon" href="<?= e(url('uploads/icons/icon_192.png')) ?>">
<link rel="icon" type="image/png" sizes="192x192" href="<?= e(url('uploads/icons/icon_192.png')) ?>">
<link rel="icon" type="image/png" sizes="512x512" href="<?= e(url('uploads/icons/icon_512.png')) ?>">
<script src="<?= e($pwaScriptUrl) ?>" defer></script>

