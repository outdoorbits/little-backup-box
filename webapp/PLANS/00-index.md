# Missing Functionality Implementation Plans - Index

This directory contains implementation plans for all missing functionality from the PHP UI that needs to be added to the React webapp.

## Plan Organization

Plans are organized by functional area:

1. **[01-display-hardware-configuration.md](./01-display-hardware-configuration.md)**
   - Display behavior, colors, backlight, rotation
   - I2C/SPI configuration
   - Resolution, offset, color model settings

2. **[02-social-media-integration.md](./02-social-media-integration.md)**
   - Telegram, Mastodon, Bluesky, Matrix configuration
   - Social media publishing in View page
   - Chat/room ID helpers

3. **[03-backup-configuration-advanced.md](./03-backup-configuration-advanced.md)**
   - Checksum, target size, camera folder mask
   - Default backup modes
   - Secondary backup configuration

4. **[04-view-image-viewer-features.md](./04-view-image-viewer-features.md)**
   - Complete filtering system
   - Image rating and comments
   - Slideshow, magnifying glass
   - Social media publishing

5. **[05-tools-system-operations.md](./05-tools-system-operations.md)**
   - Mount/unmount operations
   - Filesystem repair (fsck)
   - Partition formatting
   - F3 probe operations

6. **[06-mail-notification-system.md](./06-mail-notification-system.md)**
   - SMTP configuration
   - Email notifications
   - HTML email support

7. **[07-cloud-services-configuration.md](./07-cloud-services-configuration.md)**
   - Cloud service configuration (per service)
   - rclone GUI access
   - Sync methods and target directories

8. **[08-vpn-configuration.md](./08-vpn-configuration.md)**
   - OpenVPN and WireGuard configuration
   - VPN config file upload/removal
   - VPN timeout settings

9. **[09-network-configuration.md](./09-network-configuration.md)**
   - WiFi country code
   - rsync server configuration

10. **[10-hardware-configuration.md](./10-hardware-configuration.md)**
    - Fan PWM configuration
    - Menu/button configuration

11. **[11-security-authentication.md](./11-security-authentication.md)**
    - Password management
    - Password validation and application

12. **[12-system-management.md](./12-system-management.md)**
    - ~~Update management~~ - Moved to Maintenance page
    - Settings import/export
    - Exit LBB functionality
    - Debug configuration

13. **[13-energy-management.md](./13-energy-management.md)**
    - Power-off idle time configuration

14. **[14-database-operations.md](./14-database-operations.md)**
    - ~~Thumbnails generation~~ - Implemented in Maintenance page
    - ~~Database synchronization~~ - Implemented in Maintenance page
    - ~~EXIF updates~~ - Implemented in Maintenance page
    - ~~File rename operations~~ - Implemented in Maintenance page

15. **[15-home-page-enhancements.md](./15-home-page-enhancements.md)**
    - Secondary backup selector
    - Social media quick config
    - Stop backup button
    - Help section

16. **[16-view-theme-configuration.md](./16-view-theme-configuration.md)**
    - Sepia theme
    - Background image selection
    - Popup messages toggle

17. **[17-timezone-language-configuration.md](./17-timezone-language-configuration.md)**
    - Timezone selection
    - Language configuration verification

18. **[18-sysinfo-device-states.md](./18-sysinfo-device-states.md)**
    - Device states/SMART data display

## Implementation Priority

### High Priority (Core Functionality)
- 03: Backup Configuration Advanced
- 04: View/Image Viewer Features
- 14: Database Operations
- 15: Home Page Enhancements

### Medium Priority (Important Features)
- 02: Social Media Integration
- 05: Tools & System Operations
- 06: Mail Notification System
- 12: System Management

### Lower Priority (Advanced Configuration)
- 01: Display Hardware Configuration
- 07: Cloud Services Configuration
- 08: VPN Configuration
- 09: Network Configuration
- 10: Hardware Configuration
- 11: Security & Authentication
- 13: Energy Management
- 16: View/Theme Configuration
- 17: Timezone & Language Configuration
- 18: SysInfo Device States

## Statistics

- **Total Plans**: 18
- **Total Missing Features**: ~130+
- **Estimated Implementation Time**: Varies by plan complexity

## New Pages

- **Maintenance** (`/maintenance`) - Consolidates update management, database operations, and file operations from plans 12 and 14

## Notes

- All plans reference the PHP implementation in `scripts/` directory
- API endpoints should follow RESTful conventions
- Components should be reusable where possible
- Translation keys should be added to all language files
- Backend API routes need to be implemented in `webapp/server/`

