# SysInfo Device States - Implementation Plan

## Overview
Add device states/SMART data display to the System Info page.

## Missing Features

### Device States Display
- [ ] Device states section
  - [ ] List all available partitions
  - [ ] For each partition, run `smartctl -a <device>`
  - [ ] Parse and display SMART data
  - [ ] Format as table with device name and SMART attributes
  - [ ] Handle devices without SMART support gracefully

## Implementation Details

### Files to Create/Modify
- `webapp/src/pages/SysInfo.jsx` - Add Device States section
- `webapp/src/components/DeviceStates.jsx` - New component for device states
- `webapp/server/routes/sysinfo.js` - Add API endpoint for device states
- `webapp/public/lang/*.json` - Add translation keys

### API Endpoints Needed
- `GET /sysinfo/device-states` - Get device states/SMART data
  - Returns array of devices with SMART data
  - Each device has: name, identifier, smartData (parsed)

### SMART Data Parsing
- Execute: `smartctl -a <device>`
- Parse lines containing `: ` (key: value format)
- Format as key-value pairs
- Display in table format

### Reference Implementation
- PHP sysinfo: `scripts/sysinfo.php` lines 121-165
- SMART command: `smartctl -a <device>`
- Parsing: Look for lines with `: ` separator

### Device Detection
- Use same partition list as other operations
- Include all partitions (skipMounted=False, ignore-fs=True)
- Handle devices without SMART gracefully (show "-" or empty)

### UI Layout
- Card section titled "Device States"
- Table format: Device name | SMART attributes
- Scrollable if many devices
- Loading state while fetching data

## Dependencies
- smartctl tool
- Device/partition detection
- Text parsing capability

