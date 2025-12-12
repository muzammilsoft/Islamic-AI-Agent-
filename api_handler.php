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
function callPollinationsAI(array $conversationHistory, string $userMessage, ?array $toolResult = null): ?string {
    $apiUrl = 'https://text.pollinations.ai/openai';

    $systemPrompt = "أنت مساعد إسلامي ذكي اسمك iAi. مهمتك هي الإجابة على أسئلة المستخدمين المتعلقة بالإسلام.
لديك أداة واحدة متاحة:
1. `getPrayerTimes(city: string, country: string)`: للحصول على مواقيت الصلاة لمدينة ودولة معينة.

إذا طلب منك المستخدم مواقيت الصلاة، لا تجب مباشرة. بدلاً من ذلك، قم بالرد فقط بنص JSON لاستدعاء الأداة.
مثال: إذا سأل المستخدم 'ما هي مواقيت الصلاة في الرياض؟'، يجب أن ترد بهذا الشكل بالضبط:
`{\"tool\":\"getPrayerTimes\",\"city\":\"Riyadh\",\"country\":\"Saudi Arabia\"}`

إذا لم تكن متأكداً من الدولة، حاول استنتاجها من السياق. لا تخترع معلومات.
إذا كانت رسالة المستخدم لا تتعلق بمواقيت الصلاة، قم بالرد كنص عادي ومهذب.

إذا تم تزويدك بنتيجة أداة (tool result)، مهمتك هي صياغة إجابة ودية وواضحة للمستخدم بناءً على هذه البيانات.";

    $messages = array_merge(
        [['role' => 'system', 'content' => $systemPrompt]],
        $conversationHistory,
        [['role' => 'user', 'content' => $userMessage]]
    );

    // If a tool result is provided, add it to the messages array for the AI to process.
    if ($toolResult) {
        $messages[] = ['role' => 'assistant', 'content' => json_encode(['tool_result' => $toolResult])];
    }

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
