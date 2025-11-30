# Advanced Backup Configuration - Implementation Plan

## Overview
Implement advanced backup configuration options including checksum, target size requirements, camera folder masks, default backup modes, and secondary backup configuration.

## Missing Features

### General Backup Settings
- [ ] Backup checksum toggle with warning message
- [ ] Target size minimum requirements selector (0, 100 MB, 512 MB, 1 GB, 512 GB, 1 TB)
- [ ] Camera folder mask textarea (with virtual keyboard support)

### Default Backup Mode Configuration
- [ ] Default backup mode selector (source → target combinations)
  - To USB: anyusb→usb, usb→usb, nvme→usb, internal→usb, camera→usb, ftp→usb
  - To NVMe: anyusb→nvme, usb→nvme, internal→nvme, camera→nvme, ftp→nvme
  - To Internal: anyusb→internal, usb→internal, nvme→internal, camera→internal, ftp→internal
  - To rsync: usb→cloud_rsync, nvme→cloud_rsync, internal→cloud_rsync
  - To Cloud: all source types → each configured cloud service
- [ ] Default backup move files toggle
- [ ] Default backup generate thumbnails toggle

### Secondary Backup Mode Configuration
- [ ] Secondary backup mode selector (source → target combinations, same as default)
- [ ] Secondary backup move files toggle
- [ ] Secondary backup integration in main backup form

### Home Page Backup Features
- [ ] Secondary backup selector in backup form
- [ ] Secondary backup arguments passed to backup.py

## Implementation Details

### Files to Create/Modify
- `webapp/src/pages/Setup.jsx` - Add Advanced Backup Configuration section
- `webapp/src/pages/Home.jsx` - Add secondary backup selector
- `webapp/src/components/BackupConfig.jsx` - New component for backup configuration
- `webapp/src/components/SecondaryBackupSelector.jsx` - New component for secondary backup
- `webapp/server/routes/setup.js` - Add API endpoints for backup settings
- `webapp/server/routes/backup.js` - Update to handle secondary backup arguments
- `webapp/public/lang/*.json` - Add translation keys

### API Endpoints Needed
- `GET /setup/backup` - Get backup configuration
- `POST /setup/backup` - Save backup configuration
- `GET /backup/secondary-options` - Get available secondary backup options
- `POST /backup/start` - Update to accept secondary backup parameters

### Configuration Keys (from PHP setup.php)
- `conf_BACKUP_CHECKSUM`
- `conf_BACKUP_TARGET_SIZE_MIN`
- `conf_BACKUP_CAMERA_FOLDER_MASK`
- `conf_BACKUP_DEFAULT_SOURCE`
- `conf_BACKUP_DEFAULT_TARGET`
- `conf_BACKUP_DEFAULT_MOVE_FILES`
- `conf_BACKUP_DEFAULT_GENERATE_THUMBNAILS`
- `conf_BACKUP_DEFAULT_SOURCE2`
- `conf_BACKUP_DEFAULT_TARGET2`
- `conf_BACKUP_DEFAULT2_MOVE_FILES`

### Reference Implementation
- PHP setup: `scripts/setup.php` lines 590-770
- PHP main page: `scripts/index.php` lines 379-391, 474-482
- PHP helper: `scripts/sub-common.php` - `get_secondary_backup_selector()` function
- Backup script: Uses `--SecSourceName` and `--SecTargetName` arguments

### Backup Command Structure
The PHP implementation passes secondary backup as:
```bash
--SecSourceName <source> --SecTargetName <target>
```

## Dependencies
- Cloud services must be configured to show in secondary backup options
- NVMe availability detection for conditional options

