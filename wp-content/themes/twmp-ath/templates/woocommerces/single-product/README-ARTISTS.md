<!-- 
WordPress WooCommerce Artists Template
=====================================

This documentation provides an overview of the artists template implementation
for the TWMP ATH theme, including security, performance, and maintenance guidelines.

## File Structure
================

1. templates/woocommerces/single-product/artists.php
   - Main template file
   - Calls helper functions for data retrieval and rendering
   - Hooks: twmp_before_product_artists, twmp_after_product_artists

2. inc/helpers/artists.php
   - Modular helper functions
   - Data retrieval, sanitization, and rendering

3. templates/woocommerces/single-product/artists.css
   - Responsive grid styles
   - Mobile-first design
   - Dark mode support

4. inc/woocommerces/single.php
   - WooCommerce hooks integration
   - Displays template via woocommerce_after_single_product_summary

5. inc/classes/class-assets-theme.php
   - CSS enqueuing for single product pages

## Core Functions
=================

### twmp_get_product_artists( $product_id )
Retrieves artist data from ACF field 'field_ath_artists'
- Input: Product ID (required)
- Output: Array of sanitized artist data
- Handles missing ACF or invalid data gracefully

### twmp_sanitize_artist_data( $artist )
Sanitizes individual artist data
- Handles images, text, and URL fields
- Applies appropriate WordPress sanitization functions:
  - absint() for IDs
  - sanitize_text_field() for text
  - esc_url_raw() for URLs
  - wp_kses() for HTML content

### twmp_sanitize_image_data( $image )
Specialized image sanitization
- Handles ACF image arrays or image IDs
- Returns consistent image data structure
- Uses wp_get_attachment_url() and get_post_meta()

### twmp_render_artists_grid( $artists )
Renders responsive grid layout
- Applies proper escaping for all output
- Uses wp_get_attachment_image() for optimization
- Fallback to direct URL if attachment ID unavailable
- Generates unique IDs for accessibility

### twmp_render_no_artists_fallback()
Displays message when no artists available
- User-friendly fallback message
- Translatable text

## Security Features
====================

✓ File Access Protection
  - 'defined( ABSPATH ) || exit;' at top of each file
  - Prevents direct file access

✓ Data Sanitization
  - All inputs sanitized at retrieval
  - ACF data validated and type-checked
  - Arrays and objects validated with is_array()

✓ Output Escaping
  - esc_html() for plain text
  - esc_attr() for HTML attributes
  - esc_url() for links
  - wp_kses_post() for HTML content (pre-sanitized)
  - esc_url_raw() for database storage

✓ Capability Checks
  - Template honors WordPress standard capabilities
  - ACF field security handled by ACF itself

✓ CSRF Protection
  - No form submissions in template
  - Action/filter hooks provide extension points

## Performance Optimizations
=============================

✓ Minimal Database Queries
  - Single get_field() call per product
  - Uses wp_get_attachment_image() for lazy loading
  - Caches ACF data via WordPress object cache

✓ Image Optimization
  - Lazy loading via 'loading="lazy"' attribute
  - Async decoding with 'decoding="async"'
  - Uses WordPress native image functions
  - Supports responsive images via 'srcset'

✓ CSS Delivery
  - Conditional enqueuing (only on product pages)
  - Mobile-first design reduces CSS overhead
  - Modern CSS Grid layout
  - No JavaScript required for display

✓ Code Efficiency
  - Modular functions avoid code duplication
  - Early returns prevent unnecessary processing
  - Proper use of WordPress conditional functions

## Customization Guide
======================

### ACF Field Mapping
The template expects the following ACF field structure:

Field Key: field_ath_artists
Type: Repeater Field

Sub-fields:
  - ID (or id): Numeric identifier
  - image: Image field (returns array or ID)
  - name: Text field for artist name
  - position: Text field for artist position/title
  - description: WYSIWYG or Text area
  - url: URL field for artist link

To modify field keys:
1. Edit twmp_get_product_artists() in inc/helpers/artists.php
2. Update the 'field_ath_artists' parameter in get_field() call
3. Adjust sub-field names in twmp_sanitize_artist_data()

### Layout Customization
Edit templates/woocommerces/single-product/artists.css:
- Grid columns: Adjust grid-template-columns in media queries
- Gap/spacing: Modify 'gap' property
- Image sizes: Update width/height in .twmp-artist-image
- Colors: Modify brand colors and theme variables

### Template Hooks
Extend functionality via WordPress hooks:

/**
 * Before artists section renders
 * @since 1.0.0
 */
do_action( 'twmp_before_product_artists' );

/**
 * After artists section renders
 * @since 1.0.0
 */
do_action( 'twmp_after_product_artists' );

Example usage:
add_action( 'twmp_before_product_artists', function() {
    echo '<h2>Our Team</h2>';
});

## WordPress Coding Standards Compliance
=========================================

✓ Indentation: Tabs (4 spaces equivalent)
✓ Naming: snake_case for functions, camelCase for JS
✓ Comments: Detailed header documentation, inline comments for logic
✓ Security: All data sanitized and escaped
✓ Hooks: Action/filter hooks for extensibility
✓ Internationalization: esc_html__() for translatable strings
✓ Documentation: JSDoc-style function documentation

## Testing Checklist
====================

✓ Verify ACF field exists and contains artist data
✓ Test with no artists (fallback message appears)
✓ Test with 1, 2, 3+ artists (grid layout adjusts)
✓ Verify images load correctly
✓ Test responsive design (mobile, tablet, desktop)
✓ Check link functionality (if URL field populated)
✓ Verify HTML/CSS escaping (check source code)
✓ Test with special characters in artist names
✓ Verify dark mode styling
✓ Check accessibility (keyboard navigation, screen readers)

## Troubleshooting
==================

Issue: Artists not displaying
- Check: ACF field key 'field_ath_artists' exists
- Check: Field is assigned to product post type
- Check: Product has artists data saved
- Check: inc/helpers/artists.php is loaded in functions.php

Issue: Images not loading
- Check: Image field is properly configured in ACF
- Check: Image file exists in media library
- Check: WordPress has read permissions on upload directory

Issue: Styles not applied
- Check: CSS file is in templates/woocommerces/single-product/artists.css
- Check: is_product() returns true (not on product archive)
- Check: Theme is active and using class-assets-theme.php

Issue: Sanitization errors
- Check: Data types match expectations (array vs string)
- Check: ACF field returns expected format
- Check: WordPress debug logging for PHP errors

## Performance Metrics
======================

- Page Load Impact: ~2KB CSS, no JavaScript
- Database Queries: 1 get_field() call
- Image Optimization: Lazy loading, responsive srcset
- Rendering: CSS Grid (GPU accelerated)

## Browser Support
===================

✓ Chrome/Edge: 88+
✓ Firefox: 87+
✓ Safari: 14+
✓ Mobile browsers: iOS Safari 14+, Android Chrome
✓ Graceful degradation for older browsers

## Future Enhancements
======================

Potential improvements:
- Add artist filtering by category
- Implement lightbox for artist details
- Add artist search functionality
- Cache artists data in transient
- Add admin settings for artists per page
- Social media links for artists
- Artist portfolio links

## Support & Maintenance
========================

For issues or questions:
1. Check this documentation
2. Review the troubleshooting section
3. Enable WordPress debug logging
4. Check ACF field configuration
5. Verify file permissions and theme activation

Last Updated: April 30, 2026
Version: 1.0.0
-->
