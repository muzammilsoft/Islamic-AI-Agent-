<?php
// Cron job for sending prayer time notifications.

define('SIMULATING', true); // Set to true for manual testing, false for production cron

require_once 'config.php';
require_once 'api_handler.php';
require_once 'user_manager.php'; // Defines USER_DATA_DIR
require_once 'facebook_handler.php';

echo "Cron script started at " . date('Y-m-d H:i:s') . " (UTC)\n";

$userFiles = glob(USER_DATA_DIR . '*.json');

if (empty($userFiles)) {
    echo "No users found in " . USER_DATA_DIR . ". Exiting.\n";
    exit;
}

echo "Found " . count($userFiles) . " user(s) to process.\n";

foreach ($userFiles as $userFile) {
    $psid = basename($userFile, '.json');
    $userProfile = getUserProfile($psid);

    if (empty($userProfile['city']) || empty($userProfile['country'])) {
        echo "Skipping user {$psid} (no city/country set).\n";
        continue;
    }

    echo "Processing user {$psid} for {$userProfile['city']}, {$userProfile['country']}...\n";

    $prayerData = getPrayerTimes($userProfile['city'], $userProfile['country']);

    if (!$prayerData || !isset($prayerData['meta']['timezone'])) {
        echo "Could not get prayer times for user {$psid}.\n";
        continue;
    }

    // Set the timezone to the user's city timezone
    date_default_timezone_set($prayerData['meta']['timezone']);
    $currentTime = time();
    $todayDate = date('Y-m-d');

    echo "Current time in {$prayerData['meta']['timezone']}: " . date('Y-m-d H:i:s') . "\n";

    $prayerTimings = $prayerData['timings'];
    $prayersToNotify = ['Fajr', 'Dhuhr', 'Asr', 'Maghrib', 'Isha'];

    foreach ($prayersToNotify as $prayerName) {
        $prayerTimeStr = $prayerTimings[$prayerName];
        $prayerTimestamp = strtotime($prayerTimeStr);

        // Check if the current time is within a 5-minute window of the prayer time
        if ($currentTime >= $prayerTimestamp && $currentTime <= $prayerTimestamp + 300) { // 5 minutes = 300 seconds

            $lastNotificationKey = "last_notification_{$todayDate}_{$prayerName}";

            if (isset($userProfile[$lastNotificationKey])) {
                echo "Already sent {$prayerName} notification to {$psid} today.\n";
                continue;
            }

            $notificationMessage = "حان الآن موعد أذان " . translatePrayerName($prayerName) . " حسب توقيت مدينة " . $userProfile['city'] . ".";
            sendTextMessage($psid, $notificationMessage);
            echo "SUCCESS: Sent {$prayerName} notification to {$psid}.\n";

            $userProfile[$lastNotificationKey] = true;
            updateUserProfile($psid, $userProfile);

            break;
        }
    }
    date_default_timezone_set('UTC');
}

echo "Cron script finished at " . date('Y-m-d H:i:s') . " (UTC)\n";

function translatePrayerName(string $prayerName): string {
    $translations = [
        'Fajr' => 'الفجر',
        'Dhuhr' => 'الظهر',
        'Asr' => 'العصر',
        'Maghrib' => 'المغرب',
        'Isha' => 'العشاء',
    ];
    return $translations[$prayerName] ?? $prayerName;
}
