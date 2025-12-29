# CDG Custom Child Theme

A streamlined Divi child theme focused exclusively on Divi-specific functionality. WordPress core optimizations, security hardening, and agency features are handled by the **CDG Core mu-plugin**.

## Version 2.0.0

### Requirements

- WordPress 6.0+
- PHP 8.0+
- Divi 4.0+ (Divi 5 supported)
- **CDG Core mu-plugin** (for full functionality)

### Architecture

This child theme follows a separation of concerns principle:

| Component | Location | Responsibility |
|-----------|----------|----------------|
| **CDG Child Theme** | `/wp-content/themes/cdg-custom/` | Divi-specific functionality |
| **CDG Core mu-plugin** | `/wp-content/mu-plugins/cdg-core/` | WordPress optimizations & agency features |

### What This Theme Handles

- ✅ Divi version detection (4.x and 5.x)
- ✅ Divi Builder optimizations
- ✅ ACF Local JSON configuration
- ✅ Divi-specific asset loading
- ✅ Dynamic CSS minification
- ✅ Builder layout caching
- ✅ Navigation menu registration
- ✅ Theme support declarations

### What CDG Core Handles

- WordPress head cleanup
- Emoji removal
- Security hardening (XML-RPC, uploads, etc.)
- Dashboard widget removal
- Heartbeat control
- Gutenberg optimization
- Query optimizations
- Image lazy loading
- Gravity Forms / Divi fixes
- Post type renaming (Posts → Slides)
- Documentation system
- CPT Dashboard widgets
- Admin branding

## File Structure

```
cdg-custom/
├── functions.php           # Main theme bootstrap
├── style.css               # Theme header & base styles
├── README.md               # This file
├── inc/
│   ├── class-cdg-theme.php           # Main theme controller
│   ├── class-cdg-optimizations.php   # Divi-specific optimizations
│   ├── class-cdg-assets-manager.php  # Asset enqueueing
│   └── class-cdg-logger.php          # Simple logging
├── acf-json/               # ACF Local JSON (auto-created)
├── assets/
│   ├── css/
│   │   └── custom.css      # Optional custom styles
│   └── js/
│       └── custom.js       # Optional custom scripts
└── languages/              # Translation files
```

## Configuration

### Environment Constants

Add to `wp-config.php` as needed:

```php
// Logging level: debug, info, warning, error
define('CDG_LOG_LEVEL', 'error');

// Enable/disable script deferring
define('CDG_DEFER_SCRIPTS', true);

// Enable/disable theme caching
define('CDG_CACHE_ENABLED', true);
```

### Environment Detection

The theme automatically detects the WordPress environment and adjusts:

| Environment | Log Level | Caching |
|-------------|-----------|---------|
| Production | error | Enabled |
| Staging | debug | Disabled |
| Development | debug | Disabled |
| Local | debug | Disabled |

## Integration with CDG Core

The theme checks for CDG Core presence and displays status in the admin:

**Tools → CDG Theme Status**

If CDG Core is not detected, a notice is displayed recommending installation.

## Divi 5 Support

The theme automatically detects Divi 5 and enables:

- `et-builder-5` theme support
- `et-builder-performance` theme support
- Enhanced module performance settings
- Builder layout caching

## Site-Specific Customizations

Some features in this theme are site-specific:

- **Directory CPT menu hiding** - Modify in `functions.php`
- **Custom navigation menus** - Modify in `class-cdg-theme.php`

## Changelog

### 2.0.0
- Separated WordPress optimizations into CDG Core mu-plugin
- Streamlined theme to Divi-specific functionality only
- Added CDG Core detection and status
- Improved Divi 5 support
- Fixed autoloader path handling
- Added sanitization for builder detection

### 1.0.0
- Initial release (all-in-one theme)
