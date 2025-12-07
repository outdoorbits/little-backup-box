# Timezone & Language Configuration - Implementation Plan

## Overview
Implement timezone selection to complement the existing language selection in the Setup page.

## Missing Features

### Timezone Configuration
- [ ] Timezone selector
  - [ ] Load timezones from system (`timedatectl list-timezones`)
  - [ ] Current selection display
  - [ ] Apply timezone via `raspi-config`
  - [ ] Timezone change detection and application

### Language Configuration
- ✅ Language selection (already implemented)
- [ ] Verify browser detection option works
- [ ] Verify all language files are available

## Implementation Details

### Files to Create/Modify
- `webapp/src/pages/Setup.jsx` - Add Timezone Configuration section
- `webapp/src/components/TimezoneSelector.jsx` - New component for timezone selection
- `webapp/server/routes/setup.js` - Add API endpoints for timezone
- `webapp/public/lang/*.json` - Verify all translations exist

### API Endpoints Needed
- `GET /setup/timezone` - Get current timezone
- `POST /setup/timezone` - Save timezone
- `GET /setup/timezone/list` - Get available timezones

### Configuration Keys (from PHP setup.php)
- `conf_TIME_ZONE` (timezone string, e.g., "Europe/Berlin")
- `conf_LANGUAGE` (already exists)

### Timezone Application
- Read current: System timezone
- Set: `raspi-config nonint do_change_timezone <timezone>`
- Change detection: Compare old vs new timezone
- Apply immediately on save

### Reference Implementation
- PHP setup: `scripts/setup.php` lines 39, 108-111, 572-584
- Timezone list: `timedatectl list-timezones`

### Timezone Format
- Standard timezone format (e.g., "America/New_York", "Europe/London")
- Full list from system command
- Sorted alphabetically

## Dependencies
- raspi-config tool
- System timezone database
- Timezone application capability

