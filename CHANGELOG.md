# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2025-12-15

### Added
- **Persistent Menu**: Implemented a persistent menu for easier access to core features (Prayer Times, Adhkar, Report Issue, Developer Info).
- **Setup Script (`setup_profile.php`)**: Created a one-time script to configure the Persistent Menu via the Facebook Messenger Profile API.
- **Changelog (`CHANGELOG.md`)**: Added this changelog to track project updates.

### Changed
- **Welcome Message**: Simplified the welcome message for new users to be more concise and direct them to the new persistent menu.
- **`README.md`**: Updated with instructions on how to use the new setup script.

## [1.0.0] - 2025-12-14

### Added
- Initial release of the Islamic AI Assistant (iAi).
- Core features include AI chat, prayer times, bug reporting, and developer info.
- Admin panel (`admin.php`) for user and complaint management.
- Error logging system with a secure API endpoint (`errors.php`) for diagnostics.
- Command-line simulation tool (`simulate.php`) for development and testing.
