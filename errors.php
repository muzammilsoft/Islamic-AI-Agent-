<?php
// API endpoint for Jules to securely view the application error log.

require_once 'config.php';

// --- Security Check ---
// Check if the token is provided and if it matches the one in the config.
if (!isset($_GET['token']) || !defined('JULES_ACCESS_TOKEN') || $_GET['token'] !== JULES_ACCESS_TOKEN) {
    http_response_code(403);
    header('Content-Type: text/plain');
    echo "Forbidden: Access denied.";
    exit;
}

// --- Log File Display ---
header('Content-Type: text/plain; charset=utf-8');

$logFilePath = __DIR__ . '/logs/app_errors.log';

if (file_exists($logFilePath)) {
    $logContent = file_get_contents($logFilePath);

    // Reverse the lines to show the most recent errors first
    $lines = explode("\n", trim($logContent));
    $reversedLines = array_reverse($lines);

    echo "--- iAi Application Error Log (Most Recent First) ---\n";
    echo "--- Generated on: " . date('Y-m-d H:i:s') . " UTC ---\n\n";
    echo implode("\n", $reversedLines);

} else {
    echo "Log file not found. No errors have been recorded yet.";
}
