# VPN Configuration - Implementation Plan

## Overview
Implement VPN configuration for OpenVPN and WireGuard, allowing users to configure VPN connections for rsync and cloud services.

## Missing Features

### VPN Type Configuration
- [x] VPN type for rsync selection (none, OpenVPN, WireGuard)
- [x] VPN type for cloud selection (none, OpenVPN, WireGuard)
- [x] VPN timeout configuration (5, 10, 20, 30, 40, 50, 60, 90, 120, 300, 600 seconds)

### VPN Config File Management
- [x] VPN config file upload (for OpenVPN or WireGuard)
- [x] VPN config file type selector (OpenVPN, WireGuard, none)
- [x] VPN config file removal (per type)
- [x] File upload handling (ZIP extraction support)

### VPN Status
- [x] Display current VPN configuration status
- [x] Show which VPN type is configured (if any)

## Implementation Details

### Files to Create/Modify
- `webapp/src/pages/Setup.jsx` - Add VPN Configuration section
- `webapp/src/components/VPNConfig.jsx` - New component for VPN configuration
- `webapp/server/routes/setup.js` - Add API endpoints for VPN settings
- `webapp/server/routes/vpn.js` - New route file for VPN file operations
- `webapp/public/lang/*.json` - Add translation keys

### API Endpoints Needed
- `GET /setup/vpn` - Get VPN configuration
- `POST /setup/vpn` - Save VPN configuration
- `POST /setup/vpn/upload` - Upload VPN config file
- `POST /setup/vpn/remove` - Remove VPN config file
- `GET /setup/vpn/status` - Get VPN status

### Configuration Keys (from PHP setup.php)
- `conf_VPN_TYPE_RSYNC` (none, OpenVPN, WireGuard)
- `conf_VPN_TYPE_CLOUD` (none, OpenVPN, WireGuard)
- `conf_VPN_TIMEOUT` (seconds)

### VPN File Locations
- OpenVPN: `constants['const_VPN_DIR_OpenVPN']/constants['const_VPN_FILENAME_OpenVPN']`
- WireGuard: `constants['const_VPN_DIR_WireGuard']/constants['const_VPN_FILENAME_WireGuard']`

### File Security
- VPN config files require:
  - `chmod 700` (owner read/write/execute only)
  - `chown root:root`
  - `dos2unix` conversion

### Reference Implementation
- PHP setup: `scripts/setup.php` lines 1624-1701
- File handling: `scripts/setup.php` lines 373-388
- Settings upload: `scripts/setup.php` lines 468-478

### VPN Integration
- VPN is used automatically for rsync operations (if configured)
- VPN is used automatically for cloud operations (if configured)
- VPN timeout controls connection attempt duration

## Dependencies
- OpenVPN or WireGuard installed
- VPN config file from user
- File upload capability
- Secure file handling (permissions, ownership)

