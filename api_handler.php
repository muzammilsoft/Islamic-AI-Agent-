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
 * Fetches a relevant Quran verse based on a topic.
 * NOTE: This is a simplified implementation. The API doesn't support direct topic search,
 * so we map topics to specific sura/ayah numbers as a workaround.
 *
 * @param string $topic The topic to search for (e.g., "patience", "charity").
 * @return array|null An associative array with the verse details or null on failure.
 */
function getQuranVerse(string $topic): ?array {
    // Simple topic-to-verse mapping. Can be expanded.
    $topicMap = [
        'patience' => ['sura' => 2, 'ayah' => 153],
        'charity' => ['sura' => 2, 'ayah' => 271],
        'justice' => ['sura' => 4, 'ayah' => 135],
        'prayer' => ['sura' => 29, 'ayah' => 45],
        // Add more topics here
    ];

    $topicKey = strtolower(trim($topic));
    if (!isset($topicMap[$topicKey])) {
        return ['error' => "موضوع '{$topic}' غير مدعوم حاليًا."];
    }

    $sura = $topicMap[$topicKey]['sura'];
    $ayah = $topicMap[$topicKey]['ayah'];

    $url = "https://quranenc.com/api/v1/translation/aya/arabic_moyassar/{$sura}/{$ayah}";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if (isset($data['result'])) {
            return [
                'topic' => $topic,
                'sura_number' => $data['result']['sura'],
                'ayah_number' => $data['result']['aya'],
                'arabic_text' => $data['result']['arabic_text'],
                'translation' => $data['result']['translation'],
            ];
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
function callPollinationsAI(array $conversationHistory, string $userMessage, ?array $toolResults = null): ?string {
    $apiUrl = 'https://text.pollinations.ai/openai';

    $systemPrompt = <<<PROMPT
أنت مساعد إسلامي ذكي وقوي اسمك iAi. مهمتك هي مساعدة المستخدمين من خلال الإجابة على أسئلتهم وتقديم المعلومات الدينية الصحيحة.

لديك مجموعة من الأدوات التي يمكنك استخدامها. يمكنك استخدام أداة واحدة أو أكثر في نفس الوقت حسب حاجة سؤال المستخدم.

**-- الأدوات المتاحة --**

1.  **`getPrayerTimes`**: للحصول على مواقيت الصلاة.
    - **المعلمات**: `city` (string), `country` (string).
    - **مثال**: `{"tool": "getPrayerTimes", "city": "Mecca", "country": "Saudi Arabia"}`

2.  **`getQuranVerse`**: للبحث عن آيات قرآنية تتعلق بموضوع معين.
    - **المعلمات**: `topic` (string, in English).
    - **مثال**: `{"tool": "getQuranVerse", "topic": "patience"}`

**-- قواعد الاستخدام --**

1.  **تحليل السؤال**: حلل سؤال المستخدم بعناية. إذا كان السؤال يتطلب معلومات من أدواتك، يجب عليك استخدامها.
2.  **صياغة الطلب**: يجب أن يكون ردك *فقط* على هيئة مصفوفة JSON تحتوي على كائن واحد أو أكثر من كائنات استدعاء الأدوات. **لا تضف أي نص آخر خارج مصفوفة JSON**.
3.  **دائماً استخدم مصفوفة**: حتى لو كنت ستستخدم أداة واحدة فقط، يجب أن تضعها داخل مصفوفة `[]`.

**-- أمثلة --**

- **سؤال المستخدم**: "متى صلاة العصر في القاهرة؟"
- **ردك**: `[{"tool": "getPrayerTimes", "city": "Cairo", "country": "Egypt"}]`

- **سؤال المستخدم**: "أريد مواقيت الصلاة في دبي، وبعض الآيات عن فضل الصدقة"
- **ردك**: `[{"tool": "getPrayerTimes", "city": "Dubai", "country": "UAE"}, {"tool": "getQuranVerse", "topic": "charity"}]`

- **سؤال المستخدم**: "السلام عليكم"
- **ردك**: (لا تستخدم أداة، أجب كنص عادي) "وعليكم السلام! كيف يمكنني مساعدتك اليوم؟"

**-- مرحلة صياغة الإجابة --**

عندما يتم تزويدك بـ `tool_results`، فهذا يعني أنك في المرحلة الثانية. مهمتك الآن هي صياغة إجابة نهائية، شاملة، ومنسقة للمستخدم باللغة العربية، بناءً على النتائج التي حصلت عليها. ادمج المعلومات من كل الأدوات في رد واحد متكامل.
PROMPT;


    $messages = array_merge(
        [['role' => 'system', 'content' => $systemPrompt]],
        $conversationHistory,
        [['role' => 'user', 'content' => $userMessage]]
    );

    // If tool results are provided, add them to the messages array for the AI to process.
    if ($toolResults) {
        // We wrap the results in a single assistant message.
        $messages[] = ['role' => 'assistant', 'content' => json_encode(['tool_results' => $toolResults])];
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
