# Network Configuration - Implementation Plan

## Overview
Implement network configuration including WiFi country code settings and rsync server configuration.

## Missing Features

### WiFi Configuration
- [ ] WiFi country code selector
  - [ ] Load country list from `/usr/share/zoneinfo/iso3166.tab`
  - [ ] Display format: `CODE Country Name`
  - [ ] Current selection display
  - [ ] Apply country code via `raspi-config`

### rsync Server Configuration
- [ ] rsync server address input field
- [ ] rsync port input field
- [ ] rsync user input field
- [ ] rsync password input field (password type, base64 encoded)
- [ ] rsync server module input field
- [ ] Password validation (min 5 chars, no special chars)

## Implementation Details

### Files to Create/Modify
- `webapp/src/pages/Setup.jsx` - Add Network Configuration section
- `webapp/src/components/NetworkConfig.jsx` - New component for network configuration
- `webapp/src/components/RSyncConfig.jsx` - New component for rsync configuration
- `webapp/server/routes/setup.js` - Add API endpoints for network settings
- `webapp/public/lang/*.json` - Add translation keys

### API Endpoints Needed
- `GET /setup/network` - Get network configuration
- `POST /setup/network` - Save network configuration
- `GET /setup/network/wifi-countries` - Get WiFi country code list
- `GET /setup/network/wifi-current` - Get current WiFi country code

### Configuration Keys (from PHP setup.php)
- `conf_WIFI_COUNTRY` (ISO 3166 country code)
- `conf_RSYNC_SERVER`
- `conf_RSYNC_PORT`
- `conf_RSYNC_USER`
- `conf_RSYNC_PASSWORD` (base64 encoded)
- `conf_RSYNC_SERVER_MODULE`

### WiFi Country Code
- Read current: `raspi-config nonint get_wifi_country`
- Set: `raspi-config nonint do_wifi_country <code>`
- Country list: Parse `/usr/share/zoneinfo/iso3166.tab`
- Format: `CODE\tCountry Name`

### rsync Password
- Password validation same as mail password
- Base64 encoded when saved
- Decoded when displaying in form

### Reference Implementation
- PHP setup: `scripts/setup.php` lines 113-117, 1422-1447
- WiFi country: `scripts/setup.php` lines 119-135
- rsync config: `scripts/setup.php` lines 1422-1447

### rsync Integration
- rsync server must be configured for rsync backup target to appear
- Configuration checked: all fields must be non-empty
- Used in backup operations as `cloud_rsync` target

## Dependencies
- raspi-config tool (for WiFi)
- ISO 3166 country code file
- rsync server access
- Password encryption/encoding

