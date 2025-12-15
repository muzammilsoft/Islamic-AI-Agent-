<?php
// One-time setup script for Facebook Messenger Profile settings.
// Run this from your terminal: php setup_profile.php

require_once 'config.php';
require_once 'facebook_handler.php';

function setupProfile() {
    // This payload sets both the "Get Started" button and the Persistent Menu.
    // Facebook requires the "Get Started" button to be present for the Persistent Menu to show.
    $payload = [
        'get_started' => [
            'payload' => 'GET_STARTED_PAYLOAD'
        ],
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
    echo "Setting up Get Started button and Persistent Menu...\n";
    echo $response . "\n";
}


echo "--- iAi Profile Setup ---\n";
setupProfile();
echo "-------------------------\n";
