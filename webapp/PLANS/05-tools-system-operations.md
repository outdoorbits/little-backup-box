# Tools & System Operations - Implementation Plan

## Overview
Implement comprehensive tools page with mount/unmount operations, filesystem repair, formatting, and fake flash detection (F3).

## Missing Features

### Mount/Unmount Operations
- [x] Mount/Unmount UI with proper button states
- [x] Storage type selection (USB, NVMe, Cloud services)
- [x] Role selection (source, target)
- [x] Device identifier preset selector (before mounting)
- [x] Mount status display for all storage types
- [x] Cloud storage mount/unmount support
- [x] Mount list display

### Filesystem Operations
- [x] fsck check operation
  - [x] Partition selector
  - [x] Check button
  - [x] Results display
- [x] fsck autorepair operation
  - [x] Partition selector
  - [x] Autorepair button (danger style)
  - [x] Results display
- [x] Format partition operation
  - [x] Partition selector (ignore filesystem)
  - [x] Filesystem type selector:
    - FAT32 (Windows)
    - exFAT (Windows)
    - NTFS (compression enabled) (Windows)
    - NTFS (no compression) (Windows)
    - Ext4 (Linux)
    - Ext3 (Linux)
    - HFS Plus (Mac)
    - HFS (Mac)
  - [x] Format button (danger style)
  - [x] Confirmation dialog

### F3 Probe Operations
- [x] F3 probe non-destructive
  - [x] Device selector (full device, not partition)
  - [x] Action selector
  - [x] Execute button (danger style)
- [x] F3 probe destructive
  - [x] Device selector
  - [x] Action selector
  - [x] Execute button (danger style)
  - [x] Warning message

### Help Section
- [x] Tools help text display (collapsible)

## Implementation Details

### Files to Create/Modify
- `webapp/src/pages/Tools.jsx` - Complete rewrite with all features
- `webapp/src/components/MountOperations.jsx` - New component for mount/unmount
- `webapp/src/components/FilesystemOperations.jsx` - New component for fsck/format
- `webapp/src/components/F3Operations.jsx` - New component for F3 probe
- `webapp/src/components/DeviceSelector.jsx` - Reusable device/partition selector
- `webapp/server/routes/tools.js` - Add all tools API endpoints
- `webapp/public/lang/*.json` - Add translation keys

### API Endpoints Needed
- `GET /tools/mounts` - Get current mount list
- `POST /tools/mount` - Mount storage (role, storage, deviceIdentifier)
- `POST /tools/umount` - Unmount storage (role, storage)
- `GET /tools/partitions` - Get available partitions
- `GET /tools/devices` - Get available devices (for F3)
- `POST /tools/fsck/check` - Run fsck check
- `POST /tools/fsck/repair` - Run fsck autorepair
- `POST /tools/format` - Format partition
- `POST /tools/f3` - Run F3 probe

### Command Execution
All operations execute via `cmd.php` or direct Python scripts:
- Mount: `lib_storage.py --Action mount`
- Unmount: `lib_storage.py --Action umount`
- fsck: `cmd.php?CMD=fsck&PARAM1=<partition>&PARAM2=<check|repair>`
- Format: `cmd.php?CMD=format&PARAM1=<partition>&PARAM2=<fstype>`
- F3: `cmd.php?CMD=f3&PARAM1=<device>&PARAM2=<action>`

### Reference Implementation
- PHP tools: `scripts/tools.php` (entire file, 321 lines)
- PHP cmd: `scripts/cmd.php` - Command execution handler
- Storage library: `scripts/lib_storage.py`

### Safety Features
- Format and F3 operations use danger styling
- Confirmation dialogs for destructive operations
- Proper error handling and user feedback
- Logging of all operations

## Dependencies
- Backend command execution with sudo access
- Storage detection and management
- Filesystem tools (fsck, mkfs.*)
- F3 tools (if available)

