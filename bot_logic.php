<?php
// Bot Logic Handler

require_once 'facebook_handler.php';
require_once 'user_manager.php';
require_once 'api_handler.php';

// --- Constants ---
const STATE_DEFAULT = 'default';
const STATE_AWAITING_COMPLAINT = 'awaiting_complaint';

/**
 * Main message handler. Determines if the user is new, in a specific state, or default.
 */
function handleMessage(string $senderId, array $messagingEvent) {
    $isNewUser = !file_exists(getUserFilePath($senderId));
    if ($isNewUser) {
        sendWelcomeMessage($senderId);
    }

    $userProfile = getUserProfile($senderId);

    // Handle state-specific interactions first
    if ($userProfile['state'] === STATE_AWAITING_COMPLAINT) {
        handleComplaintSubmission($userProfile, $messagingEvent);
        return;
    }

    // Handle regular interactions (payloads or text)
    if (isset($messagingEvent['message']['quick_reply']['payload'])) {
        handlePayload($senderId, $messagingEvent['message']['quick_reply']['payload']);
    } elseif (isset($messagingEvent['message']['text'])) {
        handleAiInteraction($userProfile, $messagingEvent['message']['text']);
    }
}

/**
 * Sends the complete welcome sequence to a new user.
 */
function sendWelcomeMessage(string $senderId) {
    $welcomeText = "مرحباً بك في المساعد الإسلامي (iAi)!\n\nأنا هنا لمساعدتك في الإجابة على أسئلتك الدينية، وتوفير مواقيت الصلاة، والمزيد. \n\nتذكر دائماً أن هذا البوت هو أداة مساعدة، ويجب التحقق من الإجابات الفقهية الهامة من مصادر موثوقة. \n\nشارك البوت مع أصدقائك لتعم الفائدة وتكون صدقة جارية في ميزان حسناتك.";
    sendTextMessage($senderId, $welcomeText);

    $imageUrl = 'https://i.imgur.com/8QpL3Oa.png'; // NOTE: Replace with your public image URL
    sendImage($senderId, $imageUrl);

    $quickRepliesText = "اختر أحد الخيارات السريعة أو ابدأ بالكتابة للتحدث مع المساعد الذكي:";
    $quickReplies = [
        ['content_type' => 'text', 'title' => '◇مواقيت الصلاة', 'payload' => 'PRAYER_TIMES'],
        ['content_type' => 'text', 'title' => '◇الأذكار', 'payload' => 'GET_ADHIKAR'],
        ['content_type' => 'text', 'title' => '◇إبلاغ عن خطأ', 'payload' => 'REPORT_ISSUE'],
        ['content_type' => 'text', 'title' => '◇المطور', 'payload' => 'DEVELOPER_INFO'],
    ];
    sendQuickReply($senderId, $quickRepliesText, $quickReplies);
}

/**
 * Handles incoming payloads from buttons and quick replies.
 */
function handlePayload(string $senderId, string $payload) {
    switch ($payload) {
        case 'PRAYER_TIMES':
            handlePrayerTimesRequest($senderId);
            break;
        case 'GET_ADHIKAR':
            $quickReplies = [['content_type' => 'text', 'title' => 'رجوع ❌', 'payload' => 'BACK_TO_AI']];
            sendQuickReply($senderId, "هذا القسم قيد التطوير حالياً.", $quickReplies);
            break;
        case 'DEVELOPER_INFO':
            handleDeveloperInfo($senderId);
            break;
        case 'REPORT_ISSUE':
            setUserState($senderId, STATE_AWAITING_COMPLAINT);
            sendTextMessage($senderId, "يسرنا سماع اقتراحاتك أو يؤسفنا وجود مشكلة. يرجى كتابة رسالة مفصلة حول المشكلة أو الخطأ الفقهي الذي تريد تصحيحه. سيتم إرسالها مباشرة إلى المطور.");
            break;
        case 'CHANGE_CITY':
            sendTextMessage($senderId, "الرجاء إدخال اسم المدينة والدولة باللغة الإنجليزية، مفصولة بفاصلة. مثال: Khartoum, Sudan");
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
 * Handles the main AI conversation logic, including context management.
 */
function handleAiInteraction(array $userProfile, string $messageText) {
    $senderId = $userProfile['psid'];
    $conversationHistory = $userProfile['conversation_history'] ?? [];

    $aiResponse = callPollinationsAI($conversationHistory, $messageText);

    if ($aiResponse) {
        sendTextMessage($senderId, $aiResponse);

        $conversationHistory[] = ['role' => 'user', 'content' => $messageText];
        $conversationHistory[] = ['role' => 'assistant', 'content' => $aiResponse];

        if (count($conversationHistory) > 10) { // Keep last 5 pairs
            $conversationHistory = array_slice($conversationHistory, -10);
        }

        $userProfile['conversation_history'] = $conversationHistory;
        updateUserProfile($senderId, $userProfile);
    } else {
        sendTextMessage($senderId, "عذراً، حدث خطأ أثناء محاولة التواصل مع المساعد الذكي. الرجاء المحاولة مرة أخرى لاحقاً.");
    }
}

/**
 * Handles the logic for the 'Developer Info' button.
 */
function handleDeveloperInfo(string $senderId) {
    $devInfoText = "المطور: مزمل يحيى (KG) Khartoum Ghoul\n"
                 . "وصف المطور: مطور تطبيقات و تطبيقات ويب.\n"
                 . "المشروع: المساعد الإسلامي - Islamic AI Assistant iAi\n"
                 . "الإصدار: 1.0v 25/10/2025\n"
                 . "وصف المشروع: بوت فيسبوك ماسنجر يساعد المسلم في تعلم أمور دينه ويحثه على المحافظة عليها بالإجابة على أسئلته بالذكاء الاصطناعي، بناء على مصادر موثوقة منها الكتاب والسنة ومنها مواقيت الصلاة.";
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
 * Helper function to format 24-hour time to 12-hour AM/PM format.
 */
function formatTime(string $time24): string {
    return date("h:i A", strtotime($time24));
}
