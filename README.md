# ViewTracker for WooCommerce

A comprehensive WordPress plugin to track and analyze product views in your WooCommerce store, providing detailed analytics and reporting features.

## Features

### Core Tracking Features
- **Automated View Tracking** - Records each product page view with duplicate protection
- **View Count Storage** - Stores view counts as post meta for each product
- **Data Retention Settings** - Options to specify how long to keep view data (30 days, 90 days, 1 year, or unlimited)

### Admin Dashboard Features
- **Analytics Dashboard** - A dedicated dashboard showing the most viewed products with filtering options
- **Product Detail Integration** - Adds view count information to the individual product edit screens
- **Custom Date Range Reports** - Filter product view data by custom date ranges
- **Data Export** - Export view statistics as CSV files

### Shop & Customer Features
- **Popular Products Widget** - Displays most viewed products in widget areas
- **Shortcode Support** - `[viewtracker_popular_products]` shortcode to display popular products anywhere
- **Custom Thumbnail Sizing** - Configure thumbnail sizes globally or per-shortcode
- **External Plugin Compatibility** - Works with image replacement plugins via inline CSS
- **Product Sorting Option** - Adds "Sort by popularity (views)" option to product catalog
- **Custom CSS Classes** - ViewTracker-specific classes for easy styling

### Technical Features
- **Database Efficiency** - Optimized data storage to minimize database load
- **Caching Support** - Works with popular caching plugins via AJAX tracking
- **WooCommerce API Integration** - Custom API endpoint for retrieving view data
- **AJAX Support** - Records views via AJAX for better compatibility with cache plugins

### Advanced Options
- **Device Tracking** - Track views by device type (mobile, desktop, tablet)
- **Guest vs. Logged-in Tracking** - Differentiate between guest and logged-in user views
- **Admin View Exclusion** - Option to exclude admin views from the count
- **View Reset Tool** - Ability to reset view counts for specific products or all products

## Installation

1. Upload the `viewtracker-for-woocommerce` folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to WooCommerce → View Settings to configure the plugin

## Usage

### Displaying Most Viewed Products

#### Using the Widget
1. Go to Appearance → Widgets
2. Add the "Most Viewed Products" widget to your desired widget area
3. Configure the widget settings (title, number of products, etc.)
4. Save your changes

#### Using the Shortcode
Add the shortcode to any post or page:

```
[viewtracker_popular_products]
```

With parameters:
```
[viewtracker_popular_products limit="5" columns="4" days="30" category="clothing,accessories" tags="sale,new" thumbnail_size="120px"]
```

Parameters:
- `limit`: Number of products to show (default: 5)
- `columns`: Number of columns to display products in (default: 4)
- `days`: Time period in days to calculate popularity (default: 0, all time)
- `category`: Filter by category slug(s), comma-separated (default: empty)
- `tags`: Filter by tag slug(s), comma-separated (default: empty)
- `thumbnail_size`: Custom size for product thumbnails (e.g., "100px", "80%", "10rem") - overrides global setting

### Viewing Analytics

1. Go to WooCommerce → View Analytics
2. Use the date filters to select your desired date range
3. View overall statistics and most viewed products
4. Click on a product to see detailed analytics for that product

### Settings

Navigate to WooCommerce → View Settings to configure:

- **AJAX Tracking**: Enable/disable AJAX-based view tracking (recommended for cached sites)
- **Exclude Admin Views**: Prevent admin views from counting in stats
- **Duplicate Protection**: Prevent multiple views from the same user in a session
- **Data Retention**: Set how long to keep detailed view data
- **Dashboard Widget Count**: Number of products to show in the dashboard widget
- **Thumbnail Size**: Configure the default size for product thumbnails (e.g., "100px", "80%") that can be overridden per shortcode
- **Reset View Data**: Tools to reset view data for individual or all products

## Requirements

- WordPress 5.0 or higher
- WooCommerce 3.0.0 or higher
- PHP 7.2 or higher

## Privacy Considerations

This plugin collects and stores:
- Product view counts
- Anonymized IP addresses (last octet removed)
- User agent information (for device detection)
- Session IDs (for duplicate protection)
- User IDs (for logged-in users)

Please consider updating your site's privacy policy to reflect this data collection if required by your local privacy laws (like GDPR).

## Support

For support, feature requests, or bug reports, please contact us or open an issue in our repository.

## License

This plugin is licensed under GPL v2 or later.
