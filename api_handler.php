<?php
// API Handler
// This file will contain functions to interact with external APIs.

/**
 * Fetches prayer times for a specific city and country.
 *
 * @param string $city The name of the city.
 * @param string $country The name of the country.
 * @return array|null An associative array with the prayer times or null on failure.
 */
function getPrayerTimes(string $city, string $country): ?array {
    $currentDate = date('d-m-Y');
    // Method 8: Egyptian General Authority of Survey
    $url = "https://api.aladhan.com/v1/timingsByCity/{$currentDate}?city=" . urlencode($city) . "&country=" . urlencode($country) . "&method=8";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if (isset($data['data']['timings'])) {
            return $data['data']; // Return the whole data object
        }
    }

    return null;
}


/**
 * Calls the Pollinations AI to get a response.
 *
 * @param array $conversationHistory The history of the conversation.
 * @param string $userMessage The latest message from the user.
 * @return string|null The AI's response text or null on failure.
 */
function callPollinationsAI(array $conversationHistory, string $userMessage): ?string {
    $apiUrl = 'https://text.pollinations.ai/openai';

    $systemPrompt = "أنت مساعد إسلامي ذكي اسمك iAi. مهمتك هي الإجابة على أسئلة المستخدمين المتعلقة بالإسلام. كن مهذباً ومساعداً. يجب أن تكون إجاباتك دقيقة ومبنية على مصادر موثوقة. لا تجب على أسئلة خارج نطاق الإسلام.";

    $messages = array_merge(
        [['role' => 'system', 'content' => $systemPrompt]],
        $conversationHistory,
        [['role' => 'user', 'content' => $userMessage]]
    );

    $payload = [
        'model' => 'openai', // As specified
        'messages' => $messages,
        'token' => POLLINATIONS_API_TOKEN,
        'private' => false
    ];

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if (isset($data['choices'][0]['message']['content'])) {
            return $data['choices'][0]['message']['content'];
        }
    }

    // Optional: Log error response for debugging
    // error_log("Pollinations AI Error: " . $response);

    return null;
}
