<?php
// Main webhook file

require_once 'error_handler.php';
require_once 'config.php';
require_once 'bot_logic.php';

// Handle webhook verification
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['hub_mode']) && isset($_GET['hub_verify_token']) && isset($_GET['hub_challenge'])) {
    if ($_GET['hub_mode'] === 'subscribe' && $_GET['hub_verify_token'] === VERIFY_TOKEN) {
        http_response_code(200);
        echo $_GET['hub_challenge'];
        exit;
    } else {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

// Handle incoming messages
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['entry'][0]['messaging'][0])) {
        $messagingEvent = $input['entry'][0]['messaging'][0];
        $senderId = $messagingEvent['sender']['id'];

        // Pass the event to the bot logic handler
        handleMessage($senderId, $messagingEvent);
    }

    http_response_code(200);
    echo 'EVENT_RECEIVED';
} else {
    http_response_code(404);
    echo 'Not Found';
}
