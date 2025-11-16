# Home Page Enhancements - Implementation Plan

## Overview
Implement missing features on the Home page including secondary backup, social media configuration, stop backup button, and help section.

## Missing Features

### Secondary Backup Configuration
- [ ] Secondary backup selector in backup form
  - [ ] Source selection
  - [ ] Target selection
  - [ ] Integration with main backup operation
  - [ ] Pass secondary backup arguments to backup.py

### Social Media Configuration (on Main Page)
- [ ] Telegram chat ID configuration section
  - [ ] Chat ID input/selector
  - [ ] Helper button to get chat ID
  - [ ] Current chat ID display
- [ ] Matrix room ID configuration section
  - [ ] Room ID input/selector
  - [ ] Helper button to get room ID
  - [ ] Current room ID display

### Stop Backup Button
- [ ] Prominent stop backup button in main backup card
- [ ] Danger styling
- [ ] Confirmation or immediate action
- [ ] Feedback message

### Help Section
- [ ] Collapsible help section
- [ ] Help text explaining backup system usage
- [ ] Formatting and readability

## Implementation Details

### Files to Create/Modify
- `webapp/src/pages/Home.jsx` - Add missing features
- `webapp/src/components/SecondaryBackupSelector.jsx` - New component (or reuse from plan 03)
- `webapp/src/components/SocialMediaQuickConfig.jsx` - New component for quick social config
- `webapp/src/components/StopBackupButton.jsx` - New component for stop button
- `webapp/src/components/HelpSection.jsx` - New component for help
- `webapp/server/routes/backup.js` - Update to handle secondary backup
- `webapp/server/routes/social.js` - Add quick config endpoints
- `webapp/public/lang/*.json` - Add translation keys

### API Endpoints Needed
- `POST /backup/stop` - Stop all backups (already exists, verify)
- `GET /social/telegram/chat-id/helper` - Get Telegram chat ID helper
- `GET /social/matrix/room-id/helper` - Get Matrix room ID helper
- `POST /backup/start` - Update to accept secondary backup parameters

### Secondary Backup Integration
- Add to backup form data
- Pass as `--SecSourceName` and `--SecTargetName` arguments
- Only pass if both source and target are not 'none'

### Reference Implementation
- PHP main: `scripts/index.php` lines 379-391, 431-450, 258-260, 459-464, 472-491, 543-547
- PHP helpers: `scripts/sub-telegram-chat-id.php`, `scripts/sub-matrix-room-id.php`

### UI Layout
- Secondary backup in backup modifications section
- Social media config in separate collapsible sections
- Stop backup button prominently placed
- Help section at bottom of page

## Dependencies
- Secondary backup selector component (see plan 03)
- Social media integration (see plan 02)
- Backup stop functionality

