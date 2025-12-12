<?php
// Facebook Messenger Platform handler

require_once 'config.php';

/**
 * Sends a text message to the user.
 * @param string $recipientId The PSID of the recipient.
 * @param string $messageText The text of the message to send.
 */
function sendTextMessage(string $recipientId, string $messageText): void {
    $messageData = [
        'recipient' => ['id' => $recipientId],
        'message' => ['text' => $messageText],
        'messaging_type' => 'RESPONSE'
    ];
    callSendAPI($messageData);
}

/**
 * Sends a typing indicator to the user.
 * @param string $recipientId The PSID of the recipient.
 * @param bool $isOn Whether to turn the indicator on or off.
 */
function sendTypingIndicator(string $recipientId, bool $isOn = true): void {
    $action = $isOn ? 'typing_on' : 'typing_off';
    $messageData = [
        'recipient' => ['id' => $recipientId],
        'sender_action' => $action
    ];
    callSendAPI($messageData);
}

/**
 * Sends an image from a local path.
 * Note: Facebook requires the image to be accessible via a public URL.
 * This function assumes the asset is publicly available at a URL.
 * @param string $recipientId The PSID of the recipient.
 * @param string $imageUrl The public URL of the image.
 */
function sendImage(string $recipientId, string $imageUrl): void {
    $messageData = [
        'recipient' => ['id' => $recipientId],
        'message' => [
            'attachment' => [
                'type' => 'image',
                'payload' => [
                    'url' => $imageUrl,
                    'is_reusable' => true
                ]
            ]
        ]
    ];
    callSendAPI($messageData);
}

/**
 * Sends a message with a button template.
 * @param string $recipientId The PSID of the recipient.
 * @param string $messageText The text of the message to send.
 * @param array $buttons An array of button objects.
 */
function sendButtonTemplate(string $recipientId, string $messageText, array $buttons): void {
    $messageData = [
        'recipient' => ['id' => $recipientId],
        'message' => [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'button',
                    'text' => $messageText,
                    'buttons' => $buttons
                ]
            ]
        ]
    ];
    callSendAPI($messageData);
}

/**
 * Sends a message with quick reply buttons.
 * @param string $recipientId The PSID of the recipient.
 * @param string $messageText The text of the message to send.
 * @param array $quickReplies An array of quick reply button objects.
 */
function sendQuickReply(string $recipientId, string $messageText, array $quickReplies): void {
    $messageData = [
        'recipient' => ['id' => $recipientId],
        'message' => [
            'text' => $messageText,
            'quick_replies' => $quickReplies
        ],
        'messaging_type' => 'RESPONSE'
    ];
    callSendAPI($messageData);
}

/**
 * A wrapper for the cURL call to the Messenger Send API.
 * If in simulation mode, it prints the output instead of sending it.
 * @param array $messageData The message data to be sent.
 */
function callSendAPI(array $messageData): void {
    if (defined('SIMULATING') && SIMULATING) {
        echo "\n--- BOT RESPONSE ---" . PHP_EOL;
        echo json_encode($messageData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        echo PHP_EOL . "--------------------\n" . PHP_EOL;
        return;
    }

    $ch = curl_init('https://graph.facebook.com/v18.0/me/messages?access_token=' . PAGE_ACCESS_TOKEN);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($messageData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log('Error sending message: ' . $error);
    }
    // error_log('Facebook API Response: ' . $response);
}
