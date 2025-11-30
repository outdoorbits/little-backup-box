# Mail/Notification System - Implementation Plan

## Overview
Implement complete email notification system with SMTP configuration, HTML email support, and notification triggers.

## Missing Features

### Mail Notification Settings
- [x] Mail notifications toggle (backup notifications)
- [x] Mail IP notifications toggle (IP address change notifications)
- [x] Mail HTML format toggle

### SMTP Configuration
- [x] SMTP server input field
- [x] SMTP port input field (with default shown)
- [x] Mail security selection (STARTTLS, SSL)
- [x] Mail user input field
- [x] Mail password input field (password type, with virtual keyboard support)
- [x] Mail sender (FROM) input field
- [x] Mail recipient (TO) input field
- [x] Mail timeout configuration

### Mail Testing
- [x] Test mail button (already exists, verify functionality)
- [x] Test mail success/error feedback

**Note:** Mail configuration has been moved to the Integrations page (`webapp/src/pages/Integrations.jsx`).

## Implementation Details

### Files to Create/Modify
- `webapp/src/pages/Integrations.jsx` - Mail Configuration section (moved from Setup.jsx)
- `webapp/server/routes/setup.js` - API endpoints for mail settings (already exists)
- `webapp/public/lang/*.json` - Translation keys (already exist)

### API Endpoints Needed
- `GET /setup/mail` - Get mail configuration
- `POST /setup/mail` - Save mail configuration
- `POST /setup/test-mail` - Send test email (already exists)

### Configuration Keys (from PHP setup.php)
- `conf_MAIL_IP`
- `conf_MAIL_NOTIFICATIONS`
- `conf_MAIL_HTML`
- `conf_MAIL_TIMEOUT_SEC`
- `conf_SMTP_SERVER`
- `conf_SMTP_PORT`
- `conf_MAIL_SECURITY` (STARTTLS or SSL)
- `conf_MAIL_USER`
- `conf_MAIL_PASSWORD` (base64 encoded)
- `conf_MAIL_FROM`
- `conf_MAIL_TO`

### Password Handling
- Password is base64 encoded when saved
- Password validation: min 5 chars, no backslash, single quote, double quote, or space
- Password field shows decoded value when editing

### Reference Implementation
- PHP setup: `scripts/setup.php` lines 1359-1413
- PHP library: `scripts/lib_mail.py`
- Test mail: Executes `lib_mail.py` with subject and content

### Email Features
- HTML email support (if enabled)
- Backup completion notifications
- IP address change notifications
- Test email functionality

## Dependencies
- SMTP server access
- Python mail library (lib_mail.py)
- Virtual keyboard support for password fields

