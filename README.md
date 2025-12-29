# CDG Custom Child Theme

A streamlined Divi child theme focused exclusively on Divi-specific functionality. WordPress core optimizations, security hardening, and agency features are handled by the **CDG Core mu-plugin**.

## Version 2.1.0

### Requirements

- WordPress 6.0+
- PHP 8.0+
- Divi 4.0+ (Divi 5 supported)
- **CDG Core mu-plugin** (recommended for full functionality)

### Architecture

This child theme follows a separation of concerns principle:

| Component              | Location                           | Responsibility                            |
| ---------------------- | ---------------------------------- | ----------------------------------------- |
| **CDG Child Theme**    | `/wp-content/themes/cdg-custom/`   | Divi-specific functionality               |
| **CDG Core mu-plugin** | `/wp-content/mu-plugins/cdg-core/` | WordPress optimizations & agency features |
| **SpinupWP**           | Server level                       | Caching, performance, security headers    |

### What This Theme Handles

- ✅ Divi version detection (4.x and 5.x)
- ✅ Divi Builder optimizations
- ✅ ACF Local JSON configuration
- ✅ Divi-specific asset loading
- ✅ Dynamic CSS minification
- ✅ Navigation menu registration
- ✅ Theme support declarations
- ✅ Subfooter copyright styling

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

### What SpinupWP Handles

- Page caching (Nginx FastCGI)
- Object caching (Redis)
- Script/style optimization
- Security headers
- SSL/TLS

## File Structure

```
cdg-custom/
├── functions.php                     # Main theme bootstrap
├── style.css                         # Theme header & base styles
├── README.md                         # This file
├── inc/
│   ├── class-cdg-theme.php           # Main theme controller
│   ├── class-cdg-optimizations.php   # Divi-specific optimizations
│   └── class-cdg-assets-manager.php  # Asset enqueueing
├── acf-json/                         # ACF Local JSON (auto-created)
├── assets/
│   ├── css/
│   │   └── custom.css                # Optional custom styles
│   └── js/
│       └── custom.js                 # Optional custom scripts
└── languages/                        # Translation files
```

## Features

### ACF Local JSON

The theme automatically configures ACF Pro to save and load field groups from the `acf-json/` directory. This enables:

- Version control for field groups
- Faster field group loading
- Easy deployment across environments

### Subfooter CSS Classes

The theme provides two CSS classes for footer copyright text:

- `.cdg-subfooter-info-light` - Dark text for light backgrounds
- `.cdg-subfooter-info-dark` - Light text for dark backgrounds

These automatically append the site title and "All Rights Reserved" text using CSS `::after` pseudo-elements.

**Usage in Divi:**
Add a Code module with an empty `<span>` element:

```html
<span class="cdg-subfooter-info-light">© 2025</span>
```

**Customization via CSS variables:**

```css
:root {
  --cdg-subfooter-color-light: #333;
  --cdg-subfooter-color-dark: #f4f4f4;
  --cdg-subfooter-font-size: 1em;
  --cdg-subfooter-font-weight: 600;
}
```

### Divi 5 Support

The theme automatically detects Divi 5 and enables:

- `et-builder-5` theme support
- `et-builder-performance` theme support
- Enhanced module performance settings

## Admin Status Page

View theme status at **Tools → CDG Theme Status**, which displays:

- Theme version
- Divi version and mode (4.x or 5.x)
- PHP and WordPress versions
- CDG Core plugin status

## Changelog

### 2.1.0

- Removed script deferral (was breaking ACF admin interface)
- Removed caching code (SpinupWP handles this at server level)
- Removed logger class and environment detection
- Removed redundant post type support removal
- Simplified theme architecture
- Fixed ACF Pro field group editing issue

### 2.0.0

- Separated WordPress optimizations into CDG Core mu-plugin
- Streamlined theme to Divi-specific functionality only
- Added CDG Core detection and status
- Improved Divi 5 support

### 1.0.0

- Initial release
