# System Management - Implementation Plan

## Overview
Implement system management features including updates, settings import/export, exit functionality, and debug configuration.

## Missing Features

### Update Management
- [x] ~~Check for updates button (already exists, verify)~~ - Moved to Maintenance page
- [x] ~~Update installation button~~ - Implemented in Maintenance page
- [x] ~~Update availability display~~ - Implemented in Maintenance page
- [x] ~~Branch indicator (main vs development)~~ - Implemented in Maintenance page

### Settings Import/Export
- [ ] Download settings (ZIP export)
  - [ ] Include config.cfg
  - [ ] Include rclone config file
  - [ ] Include button private config file
  - [ ] Include VPN config files (if present)
  - [ ] Include background images directory
- [ ] Upload settings (ZIP import)
  - [ ] File upload handler
  - [ ] ZIP extraction
  - [ ] File validation
  - [ ] Apply imported settings
  - [ ] Password handling from imported settings
  - [ ] Config file rewriting for version compatibility

### Exit LBB
- [ ] Exit LBB button
- [ ] Stop backup on exit option
- [ ] Pause idletime on exit
- [ ] Stop Firefox on exit

### Debug Configuration
- [ ] Log level selection
- [ ] Log sync protocol toggle
- [ ] Display images keep toggle

### Comitup Configuration (if available)
- [ ] Comitup reset link (if comitup-cli available)
- [ ] Comitup information display

## Implementation Details

### Files to Create/Modify
- `webapp/src/pages/Setup.jsx` - Add System Management sections
- `webapp/src/components/UpdateManager.jsx` - New component for updates
- `webapp/src/components/SettingsImportExport.jsx` - New component for settings management
- `webapp/src/components/ExitLBB.jsx` - New component for exit functionality
- `webapp/src/components/DebugConfig.jsx` - New component for debug settings
- `webapp/server/routes/setup.js` - Add API endpoints for system management
- `webapp/server/routes/system.js` - New route file for system operations
- `webapp/public/lang/*.json` - Add translation keys

### API Endpoints Needed
- `GET /setup/update/check` - Check for updates (already exists)
- `POST /setup/update/install` - Install update
- `GET /setup/update/status` - Get update status
- `GET /setup/settings/download` - Download settings ZIP
- `POST /setup/settings/upload` - Upload settings ZIP
- `POST /setup/exit` - Exit LBB
- `GET /setup/debug` - Get debug configuration
- `POST /setup/debug` - Save debug configuration
- `GET /setup/comitup` - Get Comitup status

### Configuration Keys (from PHP setup.php)
- `conf_LOGLEVEL`
- `conf_LOG_SYNC`
- `conf_DIPLAY_IMAGES_KEEP`
- `conf_SOFTWARE_DATE_INSTALLED`
- `conf_SOFTWARE_DATE_AVAILABLE`

### Settings ZIP Structure
```
settings.zip
├── config.cfg
├── rclone.conf (if exists)
├── buttons.private.cfg (if exists)
├── openvpn.conf (if exists)
├── wireguard.conf (if exists)
└── bg-images/
    └── *.jpg, *.png (background images)
```

### Reference Implementation
- PHP setup: `scripts/setup.php` lines 91-98, 100-524, 1769-1783, 1798-1862
- Settings download: `scripts/download-settings.php`
- Update check: `scripts/lib_git.py`
- Exit: `scripts/setup.php` lines 54-69

### Update Process
- Check: `lib_git.py --write-available --update-available`
- Install: `cmd.php?CMD=update`
- Branch detection from constants

### Settings Upload Process
1. Validate ZIP file
2. Extract to temp directory
3. Copy config.cfg
4. Copy rclone config
5. Copy button config
6. Copy VPN configs (with security)
7. Copy background images
8. Rewrite config for version compatibility
9. Apply password if present
10. Cleanup temp directory

## Dependencies
- Git library (lib_git.py)
- ZIP file handling
- File system access
- Command execution (for updates and exit)

