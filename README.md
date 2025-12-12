# Islamic AI Assistant (iAi) | المساعد الإسلامي

This is a Facebook Messenger bot that acts as an Islamic AI assistant, built with PHP.
<br>
هذا بوت فيسبوك ماسنجر يعمل كمساعد إسلامي ذكي، تم بناؤه باستخدام لغة PHP.

---

## English

### Setup

1.  **Environment:** Ensure you have PHP installed (`php-cli` and `php-curl`).
2.  **Clone:** Clone this repository to your local machine or server.
3.  **Configuration:** Create a `config.php` file by copying the structure from the example below and fill in your actual tokens and IDs.
4.  **Webhook (for production):** Point your Facebook App's Messenger webhook to the `index.php` file on your public server.

#### `config.php` structure
```php
<?php
// Configuration file

// --- Facebook API ---
define('PAGE_ACCESS_TOKEN', 'YOUR_PAGE_ACCESS_TOKEN_HERE');
define('VERIFY_TOKEN', 'YOUR_VERIFY_TOKEN_HERE');

// --- Admin ---
define('ADMIN_PSID', 'YOUR_ADMIN_PSID_HERE');
define('ADMIN_PASSWORD', 'your_secret_password');

// --- AI Provider ---
define('POLLINATIONS_API_TOKEN', 'uXe8DFSTD9BLByKh');
```

### Development & Testing

To test the bot's logic without connecting it to Facebook, you can use the command-line simulation tool `simulate.php`.

**Usage:**
```bash
php simulate.php <PSID> "<MESSAGE>" [--quick-reply]
```
-   `<PSID>`: A unique identifier for the simulated user (e.g., `12345`).
-   `<MESSAGE>`: The text message or payload you want to send. Must be enclosed in quotes.
-   `--quick-reply`: An optional flag to treat the message as a quick reply payload.

**Examples:**
-   **Send a text message:** `php simulate.php 12345 "Hello, bot!"`
-   **Simulate a quick reply:** `php simulate.php 12345 "PRAYER_TIMES" --quick-reply`

---

## العربية

### الإعداد

1.  **بيئة العمل:** تأكد من أن PHP مثبت لديك (تحتاج إلى `php-cli` و `php-curl`).
2.  **النسخ:** قم بنسخ هذا المستودع إلى جهازك المحلي أو الخادم.
3.  **الإعدادات:** أنشئ ملف `config.php` عن طريق نسخ الهيكل من المثال أدناه، ثم املأ الحقول بالمعلومات الصحيحة الخاصة بك.
4.  **الربط (Webhook):** في وضع التشغيل الفعلي، قم بربط الـ Webhook الخاص بتطبيق فيسبوك بملف `index.php` على الخادم العام الخاص بك.

#### هيكل ملف `config.php`
```php
<?php
// ملف الإعدادات

// --- Facebook API ---
define('PAGE_ACCESS_TOKEN', 'YOUR_PAGE_ACCESS_TOKEN_HERE');
define('VERIFY_TOKEN', 'YOUR_VERIFY_TOKEN_HERE');

// --- الأدمن ---
define('ADMIN_PSID', 'YOUR_ADMIN_PSID_HERE');
define('ADMIN_PASSWORD', 'your_secret_password');

// --- AI Provider ---
define('POLLINATIONS_API_TOKEN', 'uXe8DFSTD9BLByKh');
```

### التطوير والاختبار

لاختبار منطق البوت دون الحاجة لربطه بفيسبوك، يمكنك استخدام أداة المحاكاة `simulate.php` عبر سطر الأوامر.

**طريقة الاستخدام:**
```bash
php simulate.php <PSID> "<MESSAGE>" [--quick-reply]
```
-   `<PSID>`: معرّف فريد للمستخدم الوهمي (مثال: `12345`).
-   `<MESSAGE>`: الرسالة النصية أو الحمولة (payload) التي تريد إرسالها. يجب أن تكون بين علامتي اقتباس.
-   `--quick-reply`: علامة اختيارية لجعل الرسالة تُعامل كحمولة رد سريع.

**أمثلة:**
-   **إرسال رسالة نصية:** `php simulate.php 12345 "السلام عليكم"`
-   **محاكاة رد سريع:** `php simulate.php 12345 "PRAYER_TIMES" --quick-reply`
