# Social Media Integration - Implementation Plan

## Overview
Implement complete social media integration functionality, allowing users to configure and use Telegram, Mastodon, Bluesky, and Matrix for publishing images from the Little Backup Box.

## Missing Features

### General Social Media Settings
- [ ] Social publish date toggle
- [ ] Social publish filename toggle

### Telegram Configuration
- [ ] Telegram token input field
- [ ] Telegram chat ID input field
- [ ] Telegram chat identifier input field
- [ ] Telegram chat ID helper/selector (on main page and setup page)
- [ ] Test Telegram connection

### Mastodon Configuration
- [ ] Mastodon base URL input field
- [ ] Mastodon token input field
- [ ] Test Mastodon connection

### Bluesky Configuration
- [ ] Bluesky API base URL input field
- [ ] Bluesky identifier input field
- [ ] Bluesky app password input field
- [ ] Test Bluesky connection

### Matrix Configuration
- [ ] Matrix homeserver input field
- [ ] Matrix token input field
- [ ] Matrix room ID input field
- [ ] Matrix room identifier input field
- [ ] Matrix room ID helper/selector (on main page and setup page)
- [ ] Test Matrix connection

### View Page Social Media Features
- [ ] Social media publish checkboxes for each configured service
- [ ] Social media published status indicators
- [ ] Filter by social publish status
- [ ] Filter by social published status
- [ ] Social media icons with different states (nothing, publish, published, combined)

## Implementation Details

### Files to Create/Modify
- `webapp/src/pages/Setup.jsx` - Add Social Media Configuration section
- `webapp/src/components/SocialMediaConfig.jsx` - New component for social media configuration
- `webapp/src/pages/Home.jsx` - Add Telegram/Matrix chat/room ID configuration
- `webapp/src/pages/View.jsx` - Add social media publish functionality
- `webapp/src/components/SocialMediaPublish.jsx` - New component for social media publish controls
- `webapp/server/routes/setup.js` - Add API endpoints for social media settings
- `webapp/server/routes/social.js` - New route file for social media operations
- `webapp/public/lang/*.json` - Add translation keys

### API Endpoints Needed
- `GET /setup/social` - Get social media configuration
- `POST /setup/social` - Save social media configuration
- `GET /social/telegram/chat-id` - Get Telegram chat ID helper
- `POST /social/telegram/chat-id` - Set Telegram chat ID
- `GET /social/matrix/room-id` - Get Matrix room ID helper
- `POST /social/matrix/room-id` - Set Matrix room ID
- `POST /social/test/telegram` - Test Telegram connection
- `POST /social/test/mastodon` - Test Mastodon connection
- `POST /social/test/bluesky` - Test Bluesky connection
- `POST /social/test/matrix` - Test Matrix connection
- `POST /view/social/publish` - Publish image to social media
- `GET /view/social/status` - Get social media publish status

### Configuration Keys (from PHP setup.php)
- `conf_SOCIAL_PUBLISH_DATE`
- `conf_SOCIAL_PUBLISH_FILENAME`
- `conf_SOCIAL_TELEGRAM_TOKEN`
- `conf_SOCIAL_TELEGRAM_CHAT_ID`
- `conf_SOCIAL_TELEGRAM_CHAT_IDENTIFIER`
- `conf_SOCIAL_MASTODON_BASE_URL`
- `conf_SOCIAL_MASTODON_TOKEN`
- `conf_SOCIAL_BLUESKY_API_BASE_URL`
- `conf_SOCIAL_BLUESKY_IDENTIFIER`
- `conf_SOCIAL_BLUESKY_APP_PASSWORD`
- `conf_SOCIAL_MATRIX_HOMESERVER`
- `conf_SOCIAL_MATRIX_TOKEN`
- `conf_SOCIAL_MATRIX_ROOM_ID`
- `conf_SOCIAL_MATRIX_ROOM_IDENTIFIER`

### Reference Implementation
- PHP setup: `scripts/setup.php` lines 1559-1620
- PHP main page: `scripts/index.php` lines 431-450
- PHP view page: `scripts/view.php` lines 293-339, 610-611, 1225
- PHP helpers: `scripts/sub-telegram-chat-id.php`, `scripts/sub-matrix-room-id.php`
- PHP library: `scripts/lib_socialmedia*.py`

### Social Media Bit System
The PHP implementation uses a bitmask system for tracking publish status:
- Each social service has a bit position
- `social_publish` tracks which services to publish to
- `social_published` tracks which services have already published
- Uses bitwise operations: `(social_publish & (1 << bit)) != 0`

## Dependencies
- Backend Python libraries for each social media service
- Social media API credentials from users
- Image upload capability for social media services

