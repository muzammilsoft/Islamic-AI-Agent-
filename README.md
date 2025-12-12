# Islamic AI Assistant (iAi)

This is a Facebook Messenger bot that acts as an Islamic AI assistant, built with PHP.

## Setup

1.  **Environment:** Ensure you have PHP installed (`php-cli` and `php-curl`).
2.  **Clone:** Clone this repository to your local machine or server.
3.  **Configuration:** Create a `config.php` file by copying the structure from the example below and fill in your actual tokens and IDs.
4.  **Webhook (for production):** Point your Facebook App's Messenger webhook to the `index.php` file on your public server.

### `config.php` structure
```php
<?php
// Configuration file

// --- Facebook API ---
define('PAGE_ACCESS_TOKEN', 'YOUR_PAGE_ACCESS_TOKEN_HERE');
define('VERIFY_TOKEN', 'YOUR_VERIFY_TOKEN_HERE');

// --- Admin ---
define('ADMIN_PSID', 'YOUR_ADMIN_PSID_HERE');

// --- AI Provider ---
define('POLLINATIONS_API_TOKEN', 'uXe8DFSTD9BLByKh');
```

## Development & Testing

To test the bot's logic without connecting it to Facebook, you can use the command-line simulation tool.

### Using the Simulation Tool (`simulate.php`)

The `simulate.php` script allows you to send messages and payloads directly to the bot's logic handler from your terminal.

**Usage:**
```bash
php simulate.php <PSID> "<MESSAGE>" [--quick-reply]
```

-   `<PSID>`: A unique identifier for the simulated user (e.g., `12345`).
-   `<MESSAGE>`: The text message or payload you want to send. Must be enclosed in quotes.
-   `--quick-reply`: An optional flag to treat the message as a quick reply payload instead of a regular text message.

**Examples:**

1.  **Send a simple text message:**
    ```bash
    php simulate.php 12345 "Hello, bot!"
    ```

2.  **Simulate a quick reply button press:**
    ```bash
    php simulate.php 12345 "PRAYER_TIMES" --quick-reply
    ```

The tool will print the bot's JSON response directly to the console.
