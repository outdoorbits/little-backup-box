# Display Hardware Configuration - Implementation Plan

## Overview
Implement comprehensive display hardware configuration section in the Setup page, allowing users to configure all aspects of the physical display connected to the Little Backup Box.

## Missing Features

### Display Behavior Settings
- [ ] Display enable/disable toggle
- [ ] Display font size selection (10, 12, 14, 16)
- [ ] Display frame time selection (0.1, 0.25, 0.5, 1, 1.5, 2, 2.5, 3 seconds)
- [ ] Display contrast slider (1-255)
- [ ] Display IP repeat toggle
- [ ] Display statusbar toggle
- [ ] Display frame time for IP selection (1, 1.5, 2, 2.5, 3, 4, 5 seconds)

### Display Colors
- [ ] Display color text selection (blue, green, red, white, yellow, orange, lightgrey, grey, black)
- [ ] Display color high selection
- [ ] Display color alert selection
- [ ] Display color background selection

### Display Backlight
- [ ] Display backlight enabled toggle
- [ ] Display backlight pin selection (0, GPIO 18, GPIO 24)

### Display Rotation
- [ ] Display rotation selection (0°, 180°)

### Display Hardware Configuration
- [ ] Display driver selection (none, SSD1306, SSD1309, SSD1322, SSD1331, SH1106, ST7735, ST7735 WAVESHARE LCD display HAT)
- [ ] Display connection type selection (I2C, SPI)

### I2C Configuration
- [ ] Display I2C address selection (0x3c, 0x3d) with device availability detection
- [ ] Auto-detect I2C devices using `i2cdetect -y 1`

### SPI Configuration
- [ ] Display SPI port selection (0, 1)

### Additional Display Settings
- [ ] Display resolution X selection (96, 128, 160, 250)
- [ ] Display resolution Y selection (32, 64, 80, 122, 128)
- [ ] Display offset X selection (-30 to 30)
- [ ] Display offset Y selection (-30 to 30)
- [ ] Display color model selection (1, RGB, RGBA)
- [ ] Display color BGR toggle
- [ ] Display color inverse toggle

## Implementation Details

### Files to Create/Modify
- `webapp/src/pages/Setup.jsx` - Add Display Hardware Configuration section
- `webapp/src/components/DisplayConfig.jsx` - New component for display configuration
- `webapp/server/routes/setup.js` - Add API endpoints for display settings
- `webapp/public/lang/*.json` - Add translation keys for display settings

### API Endpoints Needed
- `GET /setup/display` - Get current display configuration
- `POST /setup/display` - Save display configuration
- `GET /setup/display/i2c-detect` - Detect available I2C devices

### Configuration Keys (from PHP setup.php)
- `conf_DISP`
- `conf_DISP_FONT_SIZE`
- `conf_DISP_FRAME_TIME`
- `conf_DISP_CONTRAST`
- `conf_DISP_IP_REPEAT`
- `conf_DISP_SHOW_STATUSBAR`
- `conf_DISP_FRAME_TIME_IP`
- `conf_DISP_COLOR_TEXT`
- `conf_DISP_COLOR_HIGH`
- `conf_DISP_COLOR_ALERT`
- `conf_DISP_COLOR_BACKGROUND`
- `conf_DISP_BACKLIGHT_ENABLED`
- `conf_DISP_BACKLIGHT_PIN`
- `conf_DISP_ROTATE`
- `conf_DISP_DRIVER`
- `conf_DISP_CONNECTION`
- `conf_DISP_I2C_ADDRESS`
- `conf_DISP_SPI_PORT`
- `conf_DISP_RESOLUTION_X`
- `conf_DISP_RESOLUTION_Y`
- `conf_DISP_OFFSET_X`
- `conf_DISP_OFFSET_Y`
- `conf_DISP_COLOR_MODEL`
- `conf_DISP_COLOR_BGR`
- `conf_DISP_COLOR_INVERSE`

### Reference Implementation
- PHP file: `scripts/setup.php` lines 836-1130
- Uses `set_hardware.sh` script to apply hardware settings

## Dependencies
- Backend API must support I2C device detection
- Hardware settings require `sudo` access via backend

