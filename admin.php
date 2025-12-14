<?php
require_once 'error_handler.php';
session_start();
require_once 'config.php';

// --- Login/Logout Logic ---
$isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;

// --- Form Handling ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Login
    if (isset($_POST['password'])) {
        if ($_POST['password'] === ADMIN_PASSWORD) {
            $_SESSION['loggedin'] = true;
            $isLoggedIn = true;
        } else {
            $loginError = "Incorrect password.";
        }
    }
    // Logout
    if (isset($_POST['logout'])) {
        session_destroy();
        header("Location: admin.php");
        exit;
    }
    // Reply to complaint
    if ($isLoggedIn && isset($_POST['reply_to_psid'], $_POST['reply_message']) && !empty($_POST['reply_message'])) {
        require_once 'facebook_handler.php';
        $recipientPsid = $_POST['reply_to_psid'];
        $replyMessage = $_POST['reply_message'];
        sendTextMessage($recipientPsid, "رد من المطور:\n\n" . $replyMessage);
        $replySuccessMessage = "تم إرسال الرد بنجاح إلى {$recipientPsid}.";
    }
    // Broadcast message
    if ($isLoggedIn && isset($_POST['broadcast_message']) && !empty($_POST['broadcast_message'])) {
        require_once 'facebook_handler.php';
        require_once 'user_manager.php';
        $broadcastMessage = $_POST['broadcast_message'];
        $userFiles = glob(USER_DATA_DIR . '*.json');
        $sentCount = 0;
        foreach ($userFiles as $file) {
            $psid = basename($file, '.json');
            sendTextMessage($psid, $broadcastMessage);
            $sentCount++;
        }
        $broadcastSuccessMessage = "تم إرسال الرسالة العامة بنجاح إلى {$sentCount} مستخدم.";
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iAi Admin Panel</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background-color: #f4f4f9; color: #333; margin: 0; padding: 20px; text-align: right; }
        .container { max-width: 800px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1, h2 { color: #555; }
        form { margin-top: 20px; }
        input[type="password"], input[type="text"], textarea { width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        input[type="submit"] { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
        input[type="submit"]:hover { background-color: #0056b3; }
        .logout-btn { background-color: #dc3545; }
        .logout-btn:hover { background-color: #c82333; }
        .error { color: #dc3545; }
        .section { margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }
        .user-list, .complaint-list { list-style: none; padding: 0; }
        .user-list li, .complaint-list li { background: #fafafa; padding: 10px; border: 1px solid #eee; border-radius: 4px; margin-bottom: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>لوحة تحكم المساعد الإسلامي (iAi)</h1>

        <?php if ($isLoggedIn): ?>
            <!-- Main Admin Content -->
            <form method="POST">
                <input type="submit" name="logout" value="تسجيل الخروج" class="logout-btn">
            </form>

            <div class="section">
                <h2>المستخدمون</h2>
                <?php
                require_once 'user_manager.php';
                $userFiles = glob(USER_DATA_DIR . '*.json');
                $userCount = count($userFiles);
                ?>
                <p>العدد الكلي للمستخدمين: <strong><?php echo $userCount; ?></strong></p>
                <?php if ($userCount > 0): ?>
                    <ul class="user-list">
                        <?php foreach ($userFiles as $file): ?>
                            <li><?php echo basename($file, '.json'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="section">
                <h2>الشكاوى</h2>
                <?php if (isset($replySuccessMessage)): ?>
                    <p style="color: green;"><?php echo $replySuccessMessage; ?></p>
                <?php endif; ?>
                <?php
                $complaintsDir = __DIR__ . '/data/complaints/';
                $complaintFiles = is_dir($complaintsDir) ? glob($complaintsDir . '*.txt') : [];
                arsort($complaintFiles); // Sort by name, which includes date, descending
                ?>
                <p>عدد الشكاوى: <strong><?php echo count($complaintFiles); ?></strong></p>
                <?php if (count($complaintFiles) > 0): ?>
                    <ul class="complaint-list">
                        <?php foreach ($complaintFiles as $file): ?>
                            <li>
                                <strong><?php echo basename($file); ?></strong>
                                <pre><?php echo htmlspecialchars(file_get_contents($file)); ?></pre>
                                <form method="POST">
                                    <?php
                                    // Extract PSID from filename like '2025-12-12_07-20-17_12345.txt'
                                    preg_match('/_(\d+)\.txt$/', basename($file), $matches);
                                    $psidToReply = $matches[1] ?? '';
                                    ?>
                                    <input type="hidden" name="reply_to_psid" value="<?php echo $psidToReply; ?>">
                                    <textarea name="reply_message" placeholder="اكتب ردك هنا..." required></textarea>
                                    <input type="submit" value="إرسال الرد">
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="section">
                <h2>سجل أخطاء النظام</h2>
                <?php
                $logFilePath = __DIR__ . '/logs/app_errors.log';
                if (file_exists($logFilePath)) {
                    $logContent = htmlspecialchars(file_get_contents($logFilePath));
                    $lines = explode("\n", trim($logContent));
                    $reversedLines = array_reverse($lines);
                    echo "<pre>" . implode("\n", $reversedLines) . "</pre>";
                } else {
                    echo "<p>لم يتم تسجيل أي أخطاء.</p>";
                }
                ?>
            </div>

            <div class="section">
                <h2>إرسال رسالة عامة</h2>
                <?php if (isset($broadcastSuccessMessage)): ?>
                    <p style="color: green;"><?php echo $broadcastSuccessMessage; ?></p>
                <?php endif; ?>
                <form method="POST">
                    <textarea name="broadcast_message" placeholder="اكتب رسالتك العامة هنا..." required rows="5"></textarea>
                    <input type="submit" value="إرسال للجميع" onclick="return confirm('هل أنت متأكد أنك تريد إرسال هذه الرسالة إلى جميع المستخدمين؟');">
                </form>
            </div>

        <?php else: ?>
            <!-- Login Form -->
            <h2>تسجيل الدخول</h2>
            <form method="POST">
                <label for="password">كلمة المرور:</label>
                <input type="password" id="password" name="password" required>
                <input type="submit" value="الدخول">
            </form>
            <?php if (isset($loginError)): ?>
                <p class="error"><?php echo $loginError; ?></p>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</body>
</html>
