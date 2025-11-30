# Security & Authentication - Implementation Plan

## Overview
Implement password management and authentication configuration.

## Missing Features

### Password Management
- [ ] Global password set/change
  - [ ] New password input field
  - [ ] Confirm password input field
  - [ ] Password validation:
    - Minimum 5 characters
    - Maximum 63 characters (for WiFi compatibility)
    - No backslash, single quote, double quote, or space
    - Passwords must match
  - [ ] Password strength indicator
- [ ] Password removal option
  - [ ] Remove password checkbox
  - [ ] Confirmation message
- [ ] Password encryption type display/selection

### Password Application
- [ ] Apply password via `lib_password.py`
- [ ] Display warning about password change requiring reboot
- [ ] Handle password in settings upload/download

## Implementation Details

### Files to Create/Modify
- `webapp/src/pages/Setup.jsx` - Add Password Configuration section
- `webapp/src/components/PasswordConfig.jsx` - New component for password management
- `webapp/server/routes/setup.js` - Add API endpoints for password management
- `webapp/public/lang/*.json` - Add translation keys

### API Endpoints Needed
- `GET /setup/password` - Get password configuration status
- `POST /setup/password` - Set/change password
- `POST /setup/password/remove` - Remove password
- `GET /setup/password/validation` - Validate password rules

### Configuration Keys (from PHP setup.php)
- `conf_PASSWORD` (base64 encoded)
- `conf_PASSWORD_ENCRYPTION`

### Password Validation Rules
1. Minimum 5 characters
2. Maximum 63 characters (WiFi compatibility)
3. No backslash (\)
4. No single quote (')
5. No double quote (")
6. No space
7. Both password fields must match

### Password Storage
- Stored as base64 encoded in config.cfg
- Applied via `lib_password.py` script
- Requires sudo access

### Reference Implementation
- PHP setup: `scripts/setup.php` lines 137-157, 245-260, 1743-1770
- Password library: `scripts/lib_password.py`
- Settings upload: `scripts/setup.php` lines 506-513

### Security Considerations
- Password fields should use password input type
- Password should not be logged
- Password change requires system reboot to take effect
- Password removal also requires reboot

## Dependencies
- Password library (lib_password.py)
- Sudo access for password application
- System reboot capability

