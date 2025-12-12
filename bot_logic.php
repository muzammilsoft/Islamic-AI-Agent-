<?php
// Bot Logic Handler

require_once 'facebook_handler.php';
require_once 'user_manager.php';
require_once 'api_handler.php';

// --- Constants ---
const STATE_DEFAULT = 'default';
const STATE_AWAITING_COMPLAINT = 'awaiting_complaint';

/**
 * Main message handler.
 */
function handleMessage(string $senderId, array $messagingEvent) {
    $isNewUser = !file_exists(getUserFilePath($senderId));
    if ($isNewUser) {
        sendWelcomeMessage($senderId);
    }

    $userProfile = getUserProfile($senderId);

    if ($userProfile['state'] === STATE_AWAITING_COMPLAINT) {
        handleComplaintSubmission($userProfile, $messagingEvent);
        return;
    }

    if (isset($messagingEvent['message']['quick_reply']['payload'])) {
        handlePayload($senderId, $messagingEvent['message']['quick_reply']['payload']);
    } elseif (isset($messagingEvent['message']['text'])) {
        handleAiInteraction($userProfile, $messagingEvent['message']['text']);
    }
}

/**
 * Handles the main AI conversation logic, including the full tool-use lifecycle.
 */
function handleAiInteraction(array $userProfile, string $messageText) {
    $senderId = $userProfile['psid'];
    $conversationHistory = $userProfile['conversation_history'] ?? [];
    $finalResponseMessage = "عذراً، حدث خطأ غير متوقع. يرجى المحاولة مرة أخرى."; // Default error message

    sendTypingIndicator($senderId, true);

    // 1. First call to AI
    $initialAiResponse = callPollinationsAI($conversationHistory, $messageText);

    if ($initialAiResponse) {
        // Clean the AI response to extract potential JSON
        $cleanedResponse = trim($initialAiResponse);
        if (strpos($cleanedResponse, '```json') === 0) {
            $cleanedResponse = str_replace(['```json', '```'], '', $cleanedResponse);
        }

        $toolCall = json_decode($cleanedResponse, true);

        // 2. Check for a valid tool call
        if (json_last_error() === JSON_ERROR_NONE && isset($toolCall['tool'])) {
            if ($toolCall['tool'] === 'getPrayerTimes') {
                $city = $toolCall['city'] ?? 'Default';
                $country = $toolCall['country'] ?? 'Default';

                $prayerData = getPrayerTimes($city, $country);
                $toolResult = $prayerData
                    ? ['status' => 'success', 'data' => $prayerData['timings']]
                    : ['status' => 'error', 'message' => "لم أتمكن من العثور على مواقيت الصلاة لـ {$city}, {$country}."];

                // 3. Second call to AI with tool result for final response
                // We provide the original user message again for context.
                $finalAiResponse = callPollinationsAI($conversationHistory, $messageText, $toolResult);

                if ($finalAiResponse) {
                    $finalResponseMessage = $finalAiResponse;
                } else {
                    $finalResponseMessage = "عذراً، واجهت مشكلة أثناء صياغة رد مواقيت الصلاة.";
                }
            }
        } else {
            // It's a direct text answer
            $finalResponseMessage = $initialAiResponse;
        }
    }

    // 4. Send the final compiled response to the user
    sendTextMessage($senderId, $finalResponseMessage);
    sendTypingIndicator($senderId, false);

    // 5. Update history
    $conversationHistory[] = ['role' => 'user', 'content' => $messageText];
    $conversationHistory[] = ['role' => 'assistant', 'content' => $finalResponseMessage];

    if (count($conversationHistory) > 10) {
        $conversationHistory = array_slice($conversationHistory, -10);
    }
    $userProfile['conversation_history'] = $conversationHistory;
    updateUserProfile($senderId, $userProfile);
}


// --- Functions for Payloads and other features ---
function sendWelcomeMessage(string $senderId) { /* ... */ }
function handlePayload(string $senderId, string $payload) { /* ... */ }
function handleDeveloperInfo(string $senderId) { /* ... */ }
function handlePrayerTimesRequest(string $senderId) { /* ... */ }
function handleComplaintSubmission(array $userProfile, array $messagingEvent) { /* ... */ }
function saveComplaint(string $psid, string $complaintText): void { /* ... */ }
function formatTime(string $time24): string { /* ... */ }
