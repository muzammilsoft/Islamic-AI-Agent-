<?php
// Simulation tool for testing the bot logic from the command line.

// --- USAGE ---
// php simulate.php <PSID> "<MESSAGE_TEXT>"
// Example: php simulate.php 12345 "Hello world"
// Example for quick reply payload: php simulate.php 12345 "PRAYER_TIMES" --quick-reply
// Example for persistent menu postback: php simulate.php 12345 "DEVELOPER_INFO" --postback

// We are in simulation mode
define('SIMULATING', true);

require_once 'error_handler.php';
require_once 'bot_logic.php';

// Check if the correct number of arguments is provided
if ($argc < 3) {
    echo "Usage: php simulate.php <PSID> \"<MESSAGE_TEXT>\" [--quick-reply]\n";
    exit(1);
}

// Get the PSID and message text from the command line arguments
$senderId = $argv[1];
$messageText = $argv[2];
$eventType = $argv[3] ?? '--text'; // --text, --quick-reply, --postback

echo "Simulating message from PSID: $senderId\n";
echo "Message: $messageText\n";

// Create a fake messaging event that mimics the structure of Facebook's webhook
$messagingEvent = [
    'sender' => [
        'id' => $senderId
    ],
    'recipient' => [
        'id' => 'PAGE_ID' // Not used in our current logic, but good to have
    ],
    'timestamp' => time() * 1000,
];

switch ($eventType) {
    case '--postback':
        $messagingEvent['postback'] = [
            'mid' => 'm_12345',
            'title' => 'Menu Title', // The text on the button
            'payload' => $messageText
        ];
        break;
    case '--quick-reply':
        $messagingEvent['message'] = [
            'mid' => 'm_12345',
            'text' => 'Quick Reply Text',
            'quick_reply' => [
                'payload' => $messageText
            ]
        ];
        break;
    default: // --text
        $messagingEvent['message'] = [
            'mid' => 'm_12345',
            'text' => $messageText
        ];
        break;
}


// Call the main message handler function from bot_logic.php
handleMessage($senderId, $messagingEvent);
