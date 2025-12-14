<?php
// Custom Error and Exception Handler for iAi Project

// Define the log file path
define('ERROR_LOG_FILE', __DIR__ . '/logs/app_errors.log');

// Ensure the logs directory exists
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

/**
 * Custom error handler to convert all PHP errors to ErrorExceptions.
 * This allows us to catch warnings, notices, etc., in the exception handler.
 */
function errorHandler(int $severity, string $message, string $file, int $line): void {
    // Ignore suppressed errors (e.g., using @operator)
    if (!(error_reporting() & $severity)) {
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
}

/**
 * Custom exception handler to log all uncaught exceptions.
 */
function exceptionHandler(Throwable $exception): void {
    $logMessage = sprintf(
        "[%s] %s: %s in %s on line %d\n",
        date('Y-m-d H:i:s'),
        get_class($exception),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine()
    );

    // Append the error to our custom log file
    error_log($logMessage, 3, ERROR_LOG_FILE);

    // Optional: Depending on the environment, you might want to show a generic error page.
    // For a bot, we might just want to log it and terminate gracefully.
    // http_response_code(500);
    // echo "An unexpected error occurred. It has been logged.";

    // In a production bot, you might want to send an error message to the user.
    // require_once 'facebook_handler.php'; // Be careful with circular dependencies
    // if (function_exists('sendTextMessage') && defined('ADMIN_PSID')) {
    //     sendTextMessage(ADMIN_PSID, "A critical error occurred: " . $exception->getMessage());
    // }

    // Prevent PHP's default handler from running
    exit;
}

// Set the custom handlers
set_error_handler('errorHandler');
set_exception_handler('exceptionHandler');
