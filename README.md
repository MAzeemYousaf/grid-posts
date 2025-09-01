# Generic Posts Elementor Widget

A powerful and flexible Elementor widget that provides comprehensive post filtering, search, and display functionality with AJAX-powered interactions.

## Features

### 🎯 **Post Type Selection**
- Choose from any registered post type (posts, pages, custom post types)
- Configurable posts per page (1-50)

### 🔍 **Advanced Search**
- Search in post titles
- Search in post content
- Search in ACF fields
- Debounced search with 500ms delay
- Customizable search placeholder text

### 🏷️ **ACF Field Filters**
- Support for multiple ACF field types:
  - Text input
  - Dropdown select
  - Checkboxes
  - Radio buttons
  - Date picker
- Dynamic field options for select/radio/checkbox fields
- Custom field labels

### 📅 **Date & Taxonomy Filters**
- Published date filter (after specific date)
- Taxonomy filters for any registered taxonomy
- Custom taxonomy labels

### 📄 **Template Rendering**
- Elementor template support for custom post layouts
- Fallback to default layout with title, excerpt, and meta
- Responsive design

### 🔢 **Pagination Options**
- **Page Numbers**: Traditional numbered pagination
- **Previous/Next**: Simple navigation with page info
- **Load More**: Button to load additional posts
- **Infinite Scroll**: Automatic loading as user scrolls

### 🎨 **Styling & Layout**
- Three filter layout options: Horizontal, Vertical, Grid
- Responsive design for all screen sizes
- Customizable spacing and styling
- Modern, clean UI with hover effects

## Installation

1. Upload the plugin files to `/wp-content/plugins/generic-posts-elementor-widget/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. The widget will automatically appear in Elementor's widget panel

## Usage

### Basic Setup

1. **Add the Widget**: Drag and drop the "Generic Posts Widget" from Elementor's widget panel
2. **Configure Post Type**: Select the post type you want to display
3. **Set Posts Per Page**: Choose how many posts to show per page
4. **Select Template**: Choose an Elementor template for custom post layouts (optional)

### Search Configuration

1. **Enable Search**: Toggle search functionality on/off
2. **Customize Placeholder**: Set custom search placeholder text
3. **Search Scope**: Choose what to search in:
   - Post titles
   - Post content
   - ACF fields

### ACF Field Filters

1. **Enable ACF Filters**: Toggle ACF field filtering on/off
2. **Add Fields**: Use the repeater to add ACF fields:
   - **Field Name**: Enter the ACF field name (e.g., `location`, `category`)
   - **Field Label**: Display label for the field
   - **Field Type**: Choose input type (text, select, checkbox, radio, date)
   - **Field Options**: For select/checkbox/radio, enter options one per line

### Additional Filters

1. **Date Filter**: Enable published date filtering
2. **Taxonomy Filters**: Add filters for any registered taxonomy
3. **Custom Labels**: Set custom labels for all filter elements

### Pagination Settings

1. **Enable Pagination**: Toggle pagination on/off
2. **Pagination Type**: Choose from four pagination styles
3. **Custom Text**: Set custom text for load more button

### Styling Options

1. **Filter Layout**: Choose between horizontal, vertical, or grid layouts
2. **Spacing**: Adjust spacing between filter elements
3. **Responsive**: Widget automatically adapts to different screen sizes

## ACF Field Configuration Examples

### Select Field
```
Field Name: location
Field Label: Location
Field Type: Select
Field Options:
New York
Los Angeles
Chicago
Miami
```

### Checkbox Group
```
Field Name: amenities
Field Label: Amenities
Field Type: Checkbox
Field Options:
Parking
WiFi
Pool
Gym
```

### Date Field
```
Field Name: event_date
Field Label: Event Date
Field Type: Date
Field Options: (leave empty for date fields)
```

## AJAX Functionality

The widget uses AJAX for all interactions:
- **Real-time Search**: Search as you type with debouncing
- **Instant Filtering**: Apply filters without page reload
- **Smooth Pagination**: Load new pages seamlessly
- **Performance**: Only loads necessary data

## Browser Support

- Modern browsers (Chrome, Firefox, Safari, Edge)
- Mobile responsive
- Touch-friendly interface
- Accessibility compliant

## Customization

### CSS Classes
The widget uses semantic CSS classes for easy customization:
- `.gpw-wrapper` - Main container
- `.gpw-filters` - Filter section
- `.gpw-filter-item` - Individual filter
- `.gpw-results` - Results container
- `.gpw-post` - Individual post
- `.gpw-pagination` - Pagination container

### JavaScript Events
Custom events are fired for integration:
- `gpw:postsLoaded` - When posts are loaded
- `gpw:filterChanged` - When filters are applied
- `gpw:pageChanged` - When pagination changes

## Troubleshooting

### Common Issues

1. **ACF Fields Not Showing**: Ensure ACF plugin is active and fields are properly configured
2. **Search Not Working**: Check if search is enabled and search scope is configured
3. **Pagination Not Working**: Verify pagination is enabled and type is selected
4. **Filters Not Applying**: Check field names match ACF field names exactly

### Debug Mode

Enable WordPress debug mode to see detailed error messages:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Requirements

- WordPress 5.0+
- Elementor 3.0+
- PHP 7.4+
- ACF Pro (recommended for advanced field types)

## Support

For support and feature requests, please contact the plugin author.

## Changelog

### Version 1.0
- Initial release
- Complete filtering system
- AJAX-powered interactions
- Responsive design
- Multiple pagination types
- ACF field support
- Search functionality
- Taxonomy filtering
- Date filtering

## License

This plugin is licensed under the GPL v2 or later.

---

**Note**: This widget requires the Advanced Custom Fields (ACF) plugin for full functionality. While it will work without ACF, the ACF field filtering features will not be available.
