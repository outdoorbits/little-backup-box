# Hardware Configuration - Implementation Plan

## Overview
Implement hardware configuration for fan control and menu button settings.

## Missing Features

### Fan Configuration
- [ ] Fan PWM temperature input (Celsius)
- [ ] Fan PWM GPIO pin selection

### Menu/Button Configuration
- [ ] Menu enable/disable toggle
- [ ] Menu button rotation selection (0°, 180°)
- [ ] Menu button combination selector
  - [ ] Load combinations from config files
  - [ ] Display combination table (variant, up, down, left, right, comment)
  - [ ] Show basic vs custom combinations
- [ ] Menu button bouncetime selection (5, 10, 15, 20, 25, 50, 100, 200, 300, 400, 500 ms)
- [ ] Menu button edge detection selection (RISING, FALLING)
- [ ] Menu button resistor pull selection (DOWN, UP)
- [ ] Link to edit custom button config file

## Implementation Details

### Files to Create/Modify
- `webapp/src/pages/Setup.jsx` - Add Hardware Configuration section
- `webapp/src/components/HardwareConfig.jsx` - New component for hardware configuration
- `webapp/src/components/MenuConfig.jsx` - New component for menu/button configuration
- `webapp/src/components/FanConfig.jsx` - New component for fan configuration
- `webapp/server/routes/setup.js` - Add API endpoints for hardware settings
- `webapp/public/lang/*.json` - Add translation keys

### API Endpoints Needed
- `GET /setup/hardware` - Get hardware configuration
- `POST /setup/hardware` - Save hardware configuration
- `GET /setup/hardware/menu-combinations` - Get button combination list
- `GET /setup/hardware/menu-config-files` - Get button config file contents

### Configuration Keys (from PHP setup.php)
- `conf_FAN_PWM_TEMP_C`
- `conf_FAN_PWM_GPIO`
- `conf_MENU_ENABLED`
- `conf_MENU_BUTTON_COMBINATION` (variant number or 'c' + custom number)
- `conf_MENU_BUTTON_ROTATE` (0 or 2)
- `conf_MENU_BUTTON_BOUNCETIME` (milliseconds)
- `conf_MENU_BUTTON_EDGE_DETECTION` (RISING, FALLING)
- `conf_MENU_BUTTON_RESISTOR_PULL` (DOWN, UP)

### Button Combination Format
Config files contain lines like:
```
PIN1=up,PIN2=down,PIN3=left,PIN4=right:Comment text
```

### Button Config Files
- Basic: `scripts/buttons.cfg`
- Custom: `constants['const_MEDIA_DIR']/constants['const_BUTTONS_PRIVATE_CONFIG_FILE']`
- Custom combinations prefixed with 'c' (c1, c2, etc.)

### Reference Implementation
- PHP setup: `scripts/setup.php` lines 1132-1315, 1712-1742
- Button config: `scripts/buttons.cfg`
- Hardware script: `scripts/set_hardware.sh`

### Hardware Application
- Settings saved to config.cfg
- `set_hardware.sh` script applies hardware settings
- Button combinations loaded from config files

## Dependencies
- Button configuration files
- Hardware script execution
- GPIO access (for fan and buttons)

