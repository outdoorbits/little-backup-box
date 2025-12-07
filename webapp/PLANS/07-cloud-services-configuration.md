# Cloud Services Configuration - Implementation Plan

## Overview
Implement cloud services configuration including rclone-based cloud storage setup, sync methods, target directories, and rclone GUI access.

## Missing Features

### Cloud Service Configuration (per service)
- [x] Cloud target basedir input (per service)
- [x] Cloud sync method selection (per service):
  - copy
  - sync
  - move
- [x] Cloud files stay in place toggle (per service)
- [x] Configuration table showing all services with settings

### rclone GUI
- [x] rclone GUI restart button
- [x] rclone GUI access information display:
  - URL
  - Username: 'lbb'
  - Password reference (set in password section)

### Cloud Service Detection
- [x] Auto-detect configured cloud services from rclone config
- [x] Display only configured services in UI

## Implementation Details

### Files to Create/Modify
- `webapp/src/pages/Setup.jsx` - Add Cloud Services Configuration section
- `webapp/src/components/CloudConfig.jsx` - New component for cloud configuration
- `webapp/src/components/CloudServiceTable.jsx` - New component for service table
- `webapp/server/routes/setup.js` - Add API endpoints for cloud settings
- `webapp/server/routes/cloud.js` - New route file for cloud operations
- `webapp/public/lang/*.json` - Add translation keys

### API Endpoints Needed
- `GET /setup/cloud` - Get cloud services configuration
- `POST /setup/cloud` - Save cloud services configuration
- `GET /setup/cloud/services` - Get list of configured cloud services
- `POST /setup/cloud/rclone-gui/restart` - Restart rclone GUI
- `GET /setup/cloud/rclone-gui/info` - Get rclone GUI access information

### Configuration Keys (from PHP setup.php)
- `conf_BACKUP_CLOUDS_TARGET_BASEDIR` - Pipe-separated: `service1|=|path1|;|service2|=|path2`
- `conf_BACKUP_SYNC_METHOD_CLOUDS` - Pipe-separated: `service1|=|method1|;|service2|=|method2`
- `conf_BACKUP_CLOUDS_TARGET_FILES_STAY_IN_PLACE` - Pipe-separated: `service1|=|true/false|;|service2|=|true/false`

### Configuration Format
The PHP implementation uses a pipe-separated format:
- Multiple services: `service1|=|value1|;|service2|=|value2`
- Each service has: `servicename|=|value`

### Reference Implementation
- PHP setup: `scripts/setup.php` lines 1448-1558
- PHP common: `scripts/sub-common.php` - `get_cloudservices()` function
- rclone GUI: `scripts/start-rclone-gui.py`

### Cloud Services
Common services include:
- Google Drive
- Dropbox
- OneDrive
- Amazon S3
- And many others supported by rclone

## Dependencies
- rclone configuration file
- rclone GUI (if enabled)
- Python rclone integration
- Cloud service credentials (managed via rclone)

