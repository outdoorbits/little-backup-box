# Database Operations - Implementation Plan

## Overview
Implement database management operations for thumbnails generation, database synchronization, and EXIF updates on the Home page.

## Missing Features

### Database Operations Section
- [x] ~~Generate thumbnails operation~~ - Implemented in Maintenance page
  - [x] ~~Button for USB target~~ - Implemented
  - [x] ~~Button for internal target~~ - Implemented
  - [x] ~~Button for NVMe target (if available)~~ - Implemented
  - [x] ~~Operation feedback~~ - Implemented
- [x] ~~Sync database operation~~ - Implemented in Maintenance page
  - [x] ~~Button for USB target~~ - Implemented
  - [x] ~~Button for internal target~~ - Implemented
  - [x] ~~Button for NVMe target (if available)~~ - Implemented
  - [x] ~~Operation feedback~~ - Implemented
- [x] ~~Update EXIF operation~~ - Implemented in Maintenance page
  - [x] ~~Button for USB target~~ - Implemented
  - [x] ~~Button for internal target~~ - Implemented
  - [x] ~~Button for NVMe target (if available)~~ - Implemented
  - [x] ~~Operation feedback~~ - Implemented

### File Operations Section
- [x] ~~Rename files operation~~ - Implemented in Maintenance page
  - [x] ~~Button for USB target~~ - Implemented
  - [x] ~~Button for internal target~~ - Implemented
  - [x] ~~Button for NVMe target (if available)~~ - Implemented
  - [x] ~~Warning message about rename operation~~ - Implemented
  - [x] ~~Operation feedback~~ - Implemented

## Implementation Details

### Files to Create/Modify
- ~~`webapp/src/pages/Home.jsx` - Add Database Operations and File Operations sections~~ - Implemented in Maintenance page instead
- [x] `webapp/src/components/DatabaseOperations.jsx` - New component for database operations - Created
- [x] `webapp/src/components/FileOperations.jsx` - New component for file operations - Created
- [x] `webapp/server/routes/backup.js` - Add API endpoints for database/file operations - Endpoint already exists
- [x] `webapp/public/lang/*.json` - Add translation keys - Added

### API Endpoints Needed
- `POST /backup/function/thumbnails` - Generate thumbnails
- `POST /backup/function/database` - Sync database
- `POST /backup/function/exif` - Update EXIF
- `POST /backup/function/rename` - Rename files

### Operation Parameters
- Target: usb, internal, nvme
- Function: thumbnails, database, exif, rename
- Device identifier presets (source and target)
- Power-off option

### Command Structure
Operations execute via `backup.py`:
```bash
backup.py --SourceName <function> --TargetName <target> \
  --rename-files <True/False> \
  --force-sync-database <True/False> \
  --generate-thumbnails <True/False> \
  --update-exif <True/False> \
  --device-identifier-preset-source <preset> \
  --device-identifier-preset-target <preset> \
  --power-off <True/False>
```

### Reference Implementation
- PHP main: `scripts/index.php` lines 395-429, 493-541
- Function mapping:
  - thumbnails: generate_thumbnails=True, update_exif=False
  - database: sync_database=True, generate_thumbnails=False, update_exif=False
  - exif: update_exif=True, generate_thumbnails=False, rename_files=False
  - rename: rename_files=True, generate_thumbnails=False, update_exif=False

### UI Layout
- Collapsible sections (details/summary)
- Section headers
- Buttons with arrow icons (→)
- Target-specific buttons (USB, Internal, NVMe)
- Warning messages for destructive operations

## Dependencies
- Backup script (backup.py)
- Database access
- EXIF tools
- Thumbnail generation tools

