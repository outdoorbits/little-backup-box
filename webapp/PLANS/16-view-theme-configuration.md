# View/Theme Configuration - Implementation Plan

## Overview
Implement view and theme configuration including theme selection, background images, and popup message settings.

## Missing Features

### Theme Configuration
- [ ] Theme selection (currently has light/dark/system)
  - [ ] Add sepia theme option
  - [ ] Theme preview or immediate application

### Background Image Configuration
- [ ] Background image selector
  - [ ] Load images from background images directory
  - [ ] "None" option
  - [ ] Image preview (optional)
  - [ ] Current selection display

### Popup Messages Configuration
- [ ] Popup messages toggle
- [ ] Enable/disable popup notifications

## Implementation Details

### Files to Create/Modify
- `webapp/src/pages/Setup.jsx` - Add View/Theme Configuration section
- `webapp/src/components/ThemeConfig.jsx` - New component for theme configuration
- `webapp/src/components/BackgroundImageSelector.jsx` - New component for background images
- `webapp/src/theme.js` - Update to support sepia theme
- `webapp/server/routes/setup.js` - Add API endpoints for view settings
- `webapp/public/lang/*.json` - Add translation keys

### API Endpoints Needed
- `GET /setup/view` - Get view/theme configuration
- `POST /setup/view` - Save view/theme configuration
- `GET /setup/view/background-images` - Get list of available background images

### Configuration Keys (from PHP setup.php)
- `conf_THEME` (light, dark, sepia, system)
- `conf_BACKGROUND_IMAGE` (filename or empty)
- `conf_POPUP_MESSAGES` (true/false)

### Background Images
- Location: `constants['const_MEDIA_DIR']/constants['const_BACKGROUND_IMAGES_DIR']`
- Detection: `find <dir> -type f -exec file --mime-type {} + | awk -F: '{if ($2 ~/image\//) print $1}'`
- Display: Applied as CSS background-image

### Reference Implementation
- PHP setup: `scripts/setup.php` lines 793-832
- Theme application: JavaScript in setup.php lines 802-811
- Background: Applied to body tag

### Popup Messages
- Controls whether popup notifications are shown
- Used throughout PHP UI for user feedback
- Should be integrated into webapp notification system

## Dependencies
- Background images directory
- Theme CSS/styling
- Image file detection
- Popup/notification system

