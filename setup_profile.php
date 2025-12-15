<?php
// One-time setup script for Facebook Messenger Profile settings.
// Run this from your terminal: php setup_profile.php

require_once 'config.php';
require_once 'facebook_handler.php';

function setupPersistentMenu() {
    $payload = [
        'persistent_menu' => [
            [
                'locale' => 'default',
                'composer_input_disabled' => false, // Allow user to type
                'call_to_actions' => [
                    [
                        'type' => 'postback',
                        'title' => '◇ مواقيت الصلاة',
                        'payload' => 'PRAYER_TIMES'
                    ],
                    [
                        'type' => 'postback',
                        'title' => '◇ الأذكار',
                        'payload' => 'GET_ADHIKAR'
                    ],
                    [
                        'type' => 'postback',
                        'title' => '◇ إبلاغ عن خطأ',
                        'payload' => 'REPORT_ISSUE'
                    ],
                    [
                        'type' => 'postback',
                        'title' => '◇ المطور',
                        'payload' => 'DEVELOPER_INFO'
                    ]
                ]
            ]
        ]
    ];

    $response = callMessengerProfileAPI($payload);
    echo "Setting Persistent Menu...\n";
    echo $response . "\n";
}

// You can add other profile settings here in the future,
// like a "Get Started" button.

echo "--- iAi Profile Setup ---\n";
setupPersistentMenu();
echo "-------------------------\n";
