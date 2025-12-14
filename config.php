<?php
// Islamic AI Assistant (iAi) - Configuration File

// --- INSTRUCTIONS ---
// 1. Fill in the values for the constants below.
// 2. Rename this file to "config.php".

// --- Facebook API ---
// Found in your Facebook App's "Messenger > Settings" section.
define('PAGE_ACCESS_TOKEN', 'YOUR_PAGE_ACCESS_TOKEN_HERE');

// A random string you create yourself. Used to verify the webhook.
define('VERIFY_TOKEN', 'YOUR_UNIQUE_VERIFY_TOKEN_HERE');


// --- Admin Panel ---
// Your personal Facebook PSID. The bot will send you complaint notifications here.
// You can get your PSID by messaging your page and checking the server logs/webhook requests.
define('ADMIN_PSID', 'YOUR_ADMIN_PSID_HERE');

// Choose a secure password to access the admin.php panel.
define('ADMIN_PASSWORD', 'your_secret_password');


// --- AI Provider ---
// The API token for Pollinations AI.
define('POLLINATIONS_API_TOKEN', 'uXe8DFSTD9BLByKh');


// --- Jules's Error Log Access ---
// A unique token for Jules to access the error log via errors.php
define('JULES_ACCESS_TOKEN', 'jules_dev_access_token_5a7b9c2d8e1f3g4h');
