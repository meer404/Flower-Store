<?php
// Simple test endpoint to verify POST data is being received

header('Content-Type: text/plain');

$output = "=== POST Test ===\n";
$output .= "Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
$output .= "Time: " . date('Y-m-d H:i:s') . "\n";
$output .= "POST Data:\n";
$output .= json_encode($_POST, JSON_PRETTY_PRINT) . "\n";
$output .= "REQUEST Data:\n";
$output .= json_encode($_REQUEST, JSON_PRETTY_PRINT) . "\n";
$output .= "Content-Type header: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set') . "\n";
$output .= "Content-Length header: " . ($_SERVER['CONTENT_LENGTH'] ?? 'not set') . "\n";

file_put_contents(__DIR__ . '/test_post.log', $output . "\n\n", FILE_APPEND);

echo "POST test logged. Check test_post.log\n";
?>
