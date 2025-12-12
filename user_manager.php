<?php
// User Manager
// Manages user data stored in JSON files.

const USER_DATA_DIR = __DIR__ . '/data/users/';

/**
 * Gets the file path for a specific user.
 * @param string $psid The user's PSID.
 * @return string The full path to the user's JSON file.
 */
function getUserFilePath(string $psid): string {
    if (!is_dir(USER_DATA_DIR)) {
        mkdir(USER_DATA_DIR, 0755, true);
    }
    return USER_DATA_DIR . $psid . '.json';
}

/**
 * Gets the user's profile data.
 * @param string $psid The user's PSID.
 * @return array The user's data.
 */
function getUserProfile(string $psid): array {
    $filePath = getUserFilePath($psid);
    if (!file_exists($filePath)) {
        // Default user profile structure
        return [
            'psid' => $psid,
            'state' => 'default', // e.g., 'default', 'awaiting_complaint'
            'city' => null,
            'country' => null,
            'conversation_history' => []
        ];
    }
    $data = json_decode(file_get_contents($filePath), true);
    return $data;
}

/**
 * Updates and saves the user's profile data.
 * @param string $psid The user's PSID.
 * @param array $data The full user data array to save.
 */
function updateUserProfile(string $psid, array $data): void {
    $filePath = getUserFilePath($psid);
    file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * A simpler function to update just the user's state.
 * @param string $psid The user's PSID.
 * @param string $newState The new state to set.
 */
function setUserState(string $psid, string $newState): void {
    $profile = getUserProfile($psid);
    $profile['state'] = $newState;
    updateUserProfile($psid, $profile);
}
