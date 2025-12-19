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
    $userProfile = getUserProfile($senderId);

    if ($userProfile['state'] === STATE_AWAITING_COMPLAINT) {
        handleComplaintSubmission($userProfile, $messagingEvent);
        return;
    }

    // Handle postbacks from persistent menu
    if (isset($messagingEvent['postback']['payload'])) {
        handlePayload($senderId, $messagingEvent['postback']['payload']);
        return;
    }

    // Handle quick replies from messages
    if (isset($messagingEvent['message']['quick_reply']['payload'])) {
        handlePayload($senderId, $messagingEvent['message']['quick_reply']['payload']);
        return;
    }

    // Handle regular text messages
    if (isset($messagingEvent['message']['text'])) {
        $messageText = $messagingEvent['message']['text'];
        // Check if the message is an admin command
        if (defined('ADMIN_PSID') && $senderId === ADMIN_PSID && strpos($messageText, '/') === 0) {
            handleAdminCommand($senderId, $messageText);
        } else {
            handleAiInteraction($userProfile, $messageText);
        }
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
        // Use regex to find a JSON array `[...]` within the response string.
        preg_match('/\[\s*\{.*\}\s*\]/s', $initialAiResponse, $matches);
        $jsonString = $matches[0] ?? null;

        $toolCalls = $jsonString ? json_decode($jsonString, true) : null;

        // 2. Check for a valid array of tool calls
        if ($toolCalls && json_last_error() === JSON_ERROR_NONE && is_array($toolCalls)) {
            $toolResults = [];
            foreach ($toolCalls as $toolCall) {
                if (!isset($toolCall['tool'])) continue;

                $result = null;
                switch ($toolCall['tool']) {
                    case 'getPrayerTimes':
                        $city = $toolCall['city'] ?? 'Default';
                        $country = $toolCall['country'] ?? 'Default';
                        $prayerData = getPrayerTimes($city, $country);
                        $result = $prayerData
                            ? ['tool' => 'getPrayerTimes', 'status' => 'success', 'data' => $prayerData]
                            : ['tool' => 'getPrayerTimes', 'status' => 'error', 'message' => "لم أتمكن من العثور على مواقيت الصلاة لـ {$city}, {$country}."];
                        break;

                    case 'getQuranVerse':
                        $topic = $toolCall['topic'] ?? 'islam';
                        $verseData = getQuranVerse($topic);
                         $result = $verseData
                            ? ['tool' => 'getQuranVerse', 'status' => 'success', 'data' => $verseData]
                            : ['tool' => 'getQuranVerse', 'status' => 'error', 'message' => "لم أتمكن من العثور على آية حول '{$topic}'."];
                        break;
                }
                if ($result) {
                    $toolResults[] = $result;
                }
            }

            // 3. Second call to AI with all tool results for the final response
            if (!empty($toolResults)) {
                $finalAiResponse = callPollinationsAI($conversationHistory, $messageText, $toolResults);
                if ($finalAiResponse) {
                    $finalResponseMessage = $finalAiResponse;
                } else {
                    $finalResponseMessage = "عذراً، واجهت مشكلة أثناء تجميع الرد من الأدوات.";
                }
            } else {
                 $finalResponseMessage = "عذراً، لم أتمكن من تنفيذ الأدوات المطلوبة.";
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

/**
 * Sends the complete welcome sequence to a new user.
 */
function sendWelcomeMessage(string $senderId) {
    $welcomeText = "مرحباً بك في المساعد الإسلامي (iAi)!\n\nأنا هنا لمساعدتك في الإجابة على أسئلتك الدينية، وتوفير مواقيت الصلاة، والمزيد. \n\nتذكر دائماً أن هذا البوت هو أداة مساعدة، ويجب التحقق من الإجابات الفقهية الهامة من مصادر موثوقة. \n\nشارك البوت مع أصدقائك لتعم الفائدة وتكون صدقة جارية في ميزان حسناتك.";
    sendTextMessage($senderId, $welcomeText);

    $imageUrl = 'https://i.imgur.com/8QpL3Oa.png'; // NOTE: Replace with your public image URL
    sendImage($senderId, $imageUrl);

    // Direct the user to the persistent menu instead of sending quick replies.
    sendTextMessage($senderId, "يمكنك استخدام القائمة ☰ في الأسفل للوصول إلى الميزات الرئيسية أو ابدأ بالكتابة مباشرة للتحدث معي.");
}

/**
 * Handles incoming payloads from buttons and quick replies.
 */
function handlePayload(string $senderId, string $payload) {
    // These payloads now come from the permanent quick replies.
    switch ($payload) {
        case 'GET_STARTED_PAYLOAD':
            sendWelcomeMessage($senderId);
            break;
        case 'PRAYER_TIMES_QR':
            handlePrayerTimesRequest($senderId);
            break;
        case 'GET_ADHIKAR_QR':
            sendTextMessage($senderId, "هذا القسم قيد التطوير حالياً.");
            break;
        case 'DEVELOPER_INFO_QR':
            handleDeveloperInfo($senderId);
            break;
        case 'REPORT_ISSUE_QR':
            setUserState($senderId, STATE_AWAITING_COMPLAINT);
            sendTextMessage($senderId, "يسرنا سماع اقتراحاتك أو يؤسفنا وجود مشكلة. يرجى كتابة رسالة مفصلة حول المشكلة أو الخطأ الفقهي الذي تريد تصحيحه. سيتم إرسالها مباشرة إلى المطور.", []); // No quick replies needed
            break;
        // Keep old payloads for any lingering persistent menus
        case 'PRAYER_TIMES':
        case 'GET_ADHIKAR':
        case 'DEVELOPER_INFO':
        case 'REPORT_ISSUE':
            handlePayload($senderId, $payload . '_QR'); // Redirect to new QR handlers
            break;
        // Payloads from specific flows
        case 'CHANGE_CITY':
            sendTextMessage($senderId, "الرجاء إدخال اسم المدينة والدولة باللغة الإنجليزية، مفصولة بفاصلة. مثال: Khartoum, Sudan", []);
            break;
        case 'BACK_TO_AI':
            sendTextMessage($senderId, "لقد عدنا إلى وضع المساعد الذكي. كيف يمكنني مساعدتك؟");
            break;
        default:
            sendTextMessage($senderId, "أعتذر، لم أتعرف على هذا الإجراء.");
            break;
    }
}

/**
 * Handles the logic for the 'Developer Info' button.
 */
function handleDeveloperInfo(string $senderId) {
    $devInfoText = "المطور: مزمل يحيى (KG) Khartoum Ghoul\n"
                 . "وصف المطور: مطور تطبيقات و تطبيقات ويب.\n"
                 . "المشروع: المساعد الإسلامي - Islamic AI Assistant iAi\n"
                 . "الإصدار: 1.1.0\n" // Updated version
                 . "وصف المشروع: بوت فيسبوك ماسنجر يساعد المسلم في تعلم أمور دينه ويحثه على المحافظة عليها بالإجابة على أسئلتته بالذكاء الاصطناعي، بناء على مصادر موثوقة منها الكتاب والسنة ومنها مواقيت الصلاة.";
    $buttons = [['type' => 'web_url', 'url' => 'https://www.facebook.com/khartoum.ghoul', 'title' => 'تواصل مع المطور']];
    sendButtonTemplate($senderId, $devInfoText, $buttons);

    $backMessage = "اضغط على 'رجوع' للعودة إلى القائمة الرئيسية.";
    $quickReplies = [['content_type' => 'text', 'title' => 'رجوع ❌', 'payload' => 'BACK_TO_AI']];
    sendQuickReply($senderId, $backMessage, $quickReplies);
}

/**
 * Handles the logic for the 'Prayer Times' button.
 */
function handlePrayerTimesRequest(string $senderId) {
    $userProfile = getUserProfile($senderId);
    $city = $userProfile['city'] ?? 'Nyala';
    $country = $userProfile['country'] ?? 'Sudan';

    $prayerData = getPrayerTimes($city, $country);

    if ($prayerData) {
        $timings = $prayerData['timings'];
        $date = $prayerData['date']['readable'];
        $hijriDate = $prayerData['date']['hijri']['date'];
        $responseText = "🕋 مواقيت الصلاة لمدينة: {$city}, {$country}\n🗓️ {$date} | {$hijriDate}\n\n"
                      . "الفجر: " . formatTime($timings['Fajr']) . "\n"
                      . "الشروق: " . formatTime($timings['Sunrise']) . "\n"
                      . "الظهر: " . formatTime($timings['Dhuhr']) . "\n"
                      . "العصر: " . formatTime($timings['Asr']) . "\n"
                      . "المغرب: " . formatTime($timings['Maghrib']) . "\n"
                      . "العشاء: " . formatTime($timings['Isha']);
        $quickReplies = [
            ['content_type' => 'text', 'title' => 'تغيير المدينة', 'payload' => 'CHANGE_CITY'],
            ['content_type' => 'text', 'title' => 'رجوع ❌', 'payload' => 'BACK_TO_AI']
        ];
        sendQuickReply($senderId, $responseText, $quickReplies);
    } else {
        sendTextMessage($senderId, "عذراً، لم أتمكن من جلب مواقيت الصلاة حالياً. الرجاء المحاولة مرة أخرى لاحقاً.");
    }
}

/**
 * Handles the submission of a complaint message from a user.
 */
function handleComplaintSubmission(array $userProfile, array $messagingEvent) {
    $senderId = $userProfile['psid'];
    if (isset($messagingEvent['message']['text'])) {
        $complaintText = $messagingEvent['message']['text'];

        saveComplaint($senderId, $complaintText);

        if (defined('ADMIN_PSID') && ADMIN_PSID !== 'YOUR_ADMIN_PSID_HERE') {
            $adminMessage = "شكوى جديدة من المستخدم (PSID: {$senderId}):\n\n\"{$complaintText}\"";
            sendTextMessage(ADMIN_PSID, $adminMessage);
        }

        sendTextMessage($senderId, "شكراً لك. تم إرسال رسالتك إلى المطور بنجاح. سنعود الآن إلى الوضع الرئيسي.");
        setUserState($senderId, STATE_DEFAULT);
    } else {
        sendTextMessage($senderId, "يرجى إرسال الشكوى كنص فقط.");
    }
}

/**
 * Saves a complaint to a text file.
 */
function saveComplaint(string $psid, string $complaintText): void {
    $complaintsDir = __DIR__ . '/data/complaints/';
    if (!is_dir($complaintsDir)) {
        mkdir($complaintsDir, 0755, true);
    }
    $filePath = $complaintsDir . date('Y-m-d_H-i-s') . '_' . $psid . '.txt';
    $content = "User PSID: {$psid}\nTimestamp: " . date('c') . "\n\nComplaint:\n{$complaintText}";
    file_put_contents($filePath, $content);
}

/**
 * Handles incoming admin commands.
 */
function handleAdminCommand(string $adminId, string $commandText) {
    // Parse the command and arguments
    $parts = explode(' ', $commandText, 2);
    $command = $parts[0];
    $args = $parts[1] ?? '';

    switch ($command) {
        case '/help':
            $helpMessage = "🤖 Admin Commands:\n"
                         . "/help - Show this help message.\n"
                         . "/broadcast <message> - Send a message to all users.\n"
                         . "/reset - Clear complaints, logs, and all user conversation histories.";
            sendTextMessage($adminId, $helpMessage);
            break;

        case '/broadcast':
            if (empty($args)) {
                sendTextMessage($adminId, "⚠️ Usage: /broadcast <message>");
                break;
            }
            $allUsers = getAllUserPsids();
            $count = 0;
            foreach ($allUsers as $psid) {
                if ($psid !== $adminId) { // Don't send to the admin
                    sendTextMessage($psid, $args);
                    $count++;
                }
            }
            sendTextMessage($adminId, "✅ Broadcast sent to {$count} users.");
            break;

        case '/reset':
            // Clear complaints
            $complaintsCleared = clearDirectory(__DIR__ . '/data/complaints/');

            // Clear logs
            $logsCleared = clearDirectory(__DIR__ . '/logs/');

            // Clear conversation history for all users
            $usersReset = 0;
            $allUsers = getAllUserPsids();
            foreach ($allUsers as $psid) {
                $userProfile = getUserProfile($psid);
                if (isset($userProfile['conversation_history'])) {
                    $userProfile['conversation_history'] = [];
                    updateUserProfile($psid, $userProfile);
                    $usersReset++;
                }
            }

            $resetMessage = "🔄 System Reset:\n"
                          . "- Complaints cleared: {$complaintsCleared} files\n"
                          . "- Logs cleared: {$logsCleared} files\n"
                          . "- User conversation histories reset: {$usersReset} users";
            sendTextMessage($adminId, $resetMessage);
            break;

        default:
            sendTextMessage($adminId, "❓ Unknown command: '{$command}'. Type /help for a list of commands.");
            break;
    }
}

/**
 * Helper function to delete all files in a directory.
 * Returns the number of files deleted.
 */
function clearDirectory(string $dirPath): int {
    if (!is_dir($dirPath)) {
        return 0;
    }
    $files = glob($dirPath . '*');
    $count = 0;
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
            $count++;
        }
    }
    return $count;
}

/**
 * Helper function to format 24-hour time to 12-hour AM/PM format.
 */
function formatTime(string $time24): string {
    return date("h:i A", strtotime($time24));
}
