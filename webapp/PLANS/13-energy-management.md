# Energy Management - Implementation Plan

## Overview
Implement energy management configuration for automatic power-off after idle time.

## Missing Features

### Power-Off Configuration
- [ ] Power-off idle time selector
  - [ ] Options: 0 (disabled), 2, 5, 10, 15, 20, 30 minutes
  - [ ] Display format: "X minutes" or "None" for 0
  - [ ] Current selection display

### Power-Off Integration
- [ ] Power-off option in backup operations (already exists in Home page)
- [ ] Power-off configuration affects automatic backups

## Implementation Details

### Files to Create/Modify
- `webapp/src/pages/Setup.jsx` - Add Energy Management section
- `webapp/src/components/EnergyConfig.jsx` - New component for energy configuration
- `webapp/server/routes/setup.js` - Add API endpoints for energy settings
- `webapp/public/lang/*.json` - Add translation keys

### API Endpoints Needed
- `GET /setup/energy` - Get energy configuration
- `POST /setup/energy` - Save energy configuration

### Configuration Keys (from PHP setup.php)
- `conf_POWER_OFF_IDLE_TIME` (minutes, 0 = disabled)

### Reference Implementation
- PHP setup: `scripts/setup.php` lines 772-789
- Idletime script: `scripts/cron_idletime.py`

### Power-Off Behavior
- 0 = Disabled (no automatic power-off)
- > 0 = Minutes of idle time before power-off
- Idletime is paused during backup operations
- Idletime lockfile prevents power-off during operations

## Dependencies
- Idletime cron script
- Power management system
- Lockfile mechanism

