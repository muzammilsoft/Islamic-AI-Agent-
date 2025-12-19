<?php
// One-time setup script for Facebook Messenger Profile settings.
// Run this from your terminal: php setup_profile.php

require_once 'config.php';
require_once 'facebook_handler.php';

function setupGetStartedButton() {
    // This payload sets the "Get Started" button, which is the entry point for new users.
    $payload = [
        'get_started' => [
            'payload' => 'GET_STARTED_PAYLOAD'
        ]
    ];

    // To remove the persistent menu, we send a request with the fields to disable.
    $deletePayload = [
        'fields' => [
            'persistent_menu'
        ]
    ];

    echo "Setting up Get Started button...\n";
    $response = callMessengerProfileAPI($payload);
    echo $response . "\n";

    echo "Removing Persistent Menu (if it exists)...\n";
    $deleteResponse = callMessengerProfileAPI($deletePayload, 'DELETE');
    echo $deleteResponse . "\n";
}

echo "--- iAi Profile Setup ---\n";
setupGetStartedButton();
echo "-------------------------\n";
