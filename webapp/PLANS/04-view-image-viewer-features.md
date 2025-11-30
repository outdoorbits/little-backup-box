# View/Image Viewer Features - Implementation Plan

## Overview
Implement comprehensive image viewer functionality with filtering, rating, comments, social media publishing, slideshow, and advanced navigation features.

## Missing Features

### Filtering System
- [ ] Storage medium filter (target_usb, source_usb, target_nvme, source_nvme, internal)
- [ ] Images per page filter (multiple options based on grid columns)
- [ ] Date filter (by creation date, with file counts)
- [ ] Rating filter (all, 0-5 stars, reject/X)
- [ ] File type filter (with file counts)
- [ ] File type extension filter (with file counts)
- [ ] Camera model name filter (with file counts, including null option)
- [ ] Directory filter (with file counts, base64 encoded paths)
- [ ] Social publish filter (by service bit)
- [ ] Social published filter (by service bit)
- [ ] Variable field/value filter (custom EXIF fields)

### View Modes and Navigation
- [ ] Grid view with configurable columns (single/multi)
- [ ] Single image view
- [ ] Grid columns toggle button
- [ ] Order by selection (filename, creation date, ID)
- [ ] Order direction toggle (ASC/DESC)
- [ ] Navigation controls (first, previous, next, last)
- [ ] Page navigation display (current page / total pages)
- [ ] Image count display

### Image Operations
- [ ] Image rating system (0-5 stars, reject option)
- [ ] Save ratings button
- [ ] Image comments textarea with character counter
- [ ] Save comments functionality
- [ ] Delete rejected images button
- [ ] Batch save ratings/comments

### Social Media Integration
- [ ] Social media publish checkboxes (one per configured service)
- [ ] Social media published status indicators
- [ ] Social media icons with states (nothing, publish, published, combined)
- [ ] Filter by social publish status
- [ ] Filter by social published status

### Advanced Display Features
- [ ] Slideshow functionality with full-screen overlay
- [ ] Slideshow timer selection (1-5 seconds, or disabled)
- [ ] Slideshow navigation (auto-advance, manual controls)
- [ ] Magnifying glass zoom feature
- [ ] Maximize/fullscreen button
- [ ] Download image link
- [ ] Thumbnail display (from tims directory)
- [ ] Full-size image display
- [ ] Video playback support
- [ ] Audio playback support
- [ ] Text file display
- [ ] EXIF metadata table display (all fields except blocked ones)
- [ ] Date grouping in grid view
- [ ] Rating visual indicators (CSS classes)

### Image Display Logic
- [ ] Web images: display directly
- [ ] HEIC/RAW/TIF: display thumbnail (tims)
- [ ] Low resolution preview warning for RAW/TIF
- [ ] GoPro H.265 video: use LRV preview if available
- [ ] Low resolution video warning

## Implementation Details

### Files to Create/Modify
- `webapp/src/pages/View.jsx` - Complete rewrite with all features
- `webapp/src/components/ImageViewer.jsx` - New component for image display
- `webapp/src/components/ImageFilters.jsx` - New component for filtering
- `webapp/src/components/ImageRating.jsx` - New component for rating system
- `webapp/src/components/ImageComments.jsx` - New component for comments
- `webapp/src/components/Slideshow.jsx` - New component for slideshow
- `webapp/src/components/MagnifyingGlass.jsx` - New component for zoom
- `webapp/src/components/ImageNavigation.jsx` - New component for navigation
- `webapp/server/routes/view.js` - Add all view-related API endpoints
- `webapp/public/lang/*.json` - Add translation keys
- `webapp/src/css/view.css` - Styles for view page

### API Endpoints Needed
- `GET /view/images` - Get images with filtering, pagination, ordering
- `GET /view/directories` - Get directory list with counts
- `GET /view/dates` - Get date list with counts
- `GET /view/ratings` - Get rating distribution with counts
- `GET /view/file-types` - Get file type list with counts
- `GET /view/file-extensions` - Get file extension list with counts
- `GET /view/camera-models` - Get camera model list with counts
- `GET /view/social-publish` - Get social publish distribution
- `GET /view/social-published` - Get social published distribution
- `GET /view/variable-fields` - Get available EXIF fields for filtering
- `GET /view/variable-values` - Get values for a specific field
- `POST /view/update` - Update image (rating, comment, social publish)
- `POST /view/delete-rejected` - Delete all rejected images
- `GET /view/image/:id` - Get single image details
- `GET /view/image/:id/next` - Get next image ID
- `GET /view/image/:id/previous` - Get previous image ID
- `GET /view/image/:id/first` - Get first image ID
- `GET /view/image/:id/last` - Get last image ID

### Database Schema (SQLite)
- Table: `EXIF_DATA`
- Key fields: `ID`, `Directory`, `File_Name`, `Create_Date`, `LbbRating`, `Rating`, `Comment`, `social_publish`, `social_published`, `File_Type`, `File_Type_Extension`, `Camera_Model_Name`

### Reference Implementation
- PHP view: `scripts/view.php` (entire file, 1444 lines)
- JavaScript: `scripts/js/slideshow.js`, `scripts/js/mglass.js`
- CSS: `scripts/css/slideshow.css`, `scripts/css/mglass.css`

### Key Algorithms
1. **Filtering**: Build WHERE clauses dynamically based on selected filters
2. **Pagination**: Use LIMIT and OFFSET with +1/-1 for navigation
3. **Rating System**: Radio buttons with visual star indicators
4. **Social Media**: Bitwise operations for publish status
5. **Slideshow**: Auto-advance with timer, manual navigation
6. **Magnifying Glass**: Mouse/touch tracking with zoom calculation

## Dependencies
- SQLite database access
- Image thumbnail generation (tims directory)
- Social media integration (see plan 02)
- EXIF metadata reading
- Media file serving (images, videos, audio)

