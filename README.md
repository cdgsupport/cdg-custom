# CDG Custom Child Theme

A streamlined Divi child theme focused exclusively on Divi-specific functionality. WordPress core optimizations, security hardening, and agency features are handled by the **CDG Core plugin**.

## Version 2.3.1

### Requirements

- WordPress 6.0+
- PHP 8.0+
- Divi 5 (standard across all CDG sites)
- **CDG Core plugin** (recommended for full functionality)

### Architecture

This child theme follows a separation of concerns principle:

| Component              | Location                           | Responsibility                            |
| ---------------------- | ---------------------------------- | ----------------------------------------- |
| **CDG Child Theme**    | `/wp-content/themes/cdg-custom/`   | Divi-specific functionality               |
| **CDG Core plugin**    | `/wp-content/plugins/cdg-core/`    | WordPress optimizations & agency features (managed via ManageWP) |
| **SpinupWP**           | Server level                       | Caching, performance, security headers    |

### What This Theme Handles

- ✅ Divi parent theme validation
- ✅ ACF Local JSON configuration
- ✅ Divi-specific asset loading
- ✅ Navigation menu registration
- ✅ Theme support declarations
- ✅ Subfooter copyright styling

### What CDG Core Handles

- WordPress head cleanup (including generator tag removal)
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
│   ├── class-cdg-optimizations.php   # ACF Local JSON & Divi optimizations
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

The theme automatically configures ACF Pro to save and load field groups from the `acf-json/` directory.

This enables:

- Version control for field groups
- Faster field group loading
- Easy deployment across environments
- Field group editing in wp-admin without requiring a developer

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

### Divi 5 Standard

CDG now builds exclusively on Divi 5. The theme validates that the Divi parent theme is installed, and displays the detected Divi version on the status page for reference — no version comparison or Divi 4/5 branching logic is performed.

## Admin Status Page

View theme status at **Tools → CDG Theme Status**, which displays:

- Theme version
- Divi version
- PHP and WordPress versions
- CDG Core plugin status

## Changelog

### 2.3.1

- **Updated CDG Core references from mu-plugin to standard plugin** — CDG Core is now installed at `/wp-content/plugins/cdg-core/` (managed via ManageWP) rather than as a must-use plugin. Updated `style.css`, `functions.php`, `class-cdg-theme.php`, and this README to reflect the new install path.
- **Added CDG Core detection notice** — `functions.php` now shows a dismissible admin notice (visible to administrators only) when the CDG Core plugin isn't active, reusing the existing `CDG_CORE_VERSION` check. Non-blocking — the theme still initializes normally, since CDG Core is recommended, not required.

### 2.3.0

- **Standardized on Divi 5** — CDG now builds exclusively on Divi 5, so all Divi-version-comparison logic has been removed as unnecessary:
  - Removed the `4.0` minimum-version check (and its `RuntimeException`) from the `after_setup_theme` initialization callback in `functions.php`. Only Divi parent theme existence is now validated.
  - Removed the `is_divi_5` property and `is_divi_5()` method from `CDG_Theme`.
  - Simplified `detect_divi_version()` to record the Divi version string for display purposes only — no comparison logic.
  - Removed the Divi-version warning block from `check_compatibility()`; it now checks PHP version only.
  - Removed the "Divi 5 Detected" row from the Tools → CDG Theme Status admin page.
- **Removed duplicate `phpcs:ignore` comment** in `CDG_Theme::is_builder_active()` — the nonce-verification suppression was declared on two adjacent lines; consolidated to one.

### 2.2.0

- **Replaced autoloader with explicit requires** — Removed `spl_autoload_register` that was triggering filesystem checks (`file_exists`) on every class PHP resolved. Divi 5's larger class footprint made this a measurable memory overhead on every request. The three theme classes are now loaded via direct `require_once` calls.
- **Removed unverified Divi 5 hooks** — Removed `init_divi_5_features()` and its four placeholder hook registrations (`et_builder_load_requests`, `et_builder_modules_loaded`, `et_core_page_resource_hints`, `et_builder_ready`) plus five empty/pass-through callback methods. These Divi 4 builder hooks may not exist or may behave differently in Divi 5's rewritten architecture. Also removed `et_builder_module_performance` filter from `CDG_Optimizations`.
- **Removed unverified Divi 5 theme support flags** — Removed `add_theme_support('et-builder-5')` and `add_theme_support('et-builder-performance')` which are not documented in Divi 5's official resources.
- **Consolidated `after_setup_theme` callbacks** — Reduced from four callbacks (priorities 5, 10, 15, 20) to three (5, 10, 15). Moved block editor theme support (`editor-styles`, `align-wide`, `custom-line-height`, `custom-units`) into `CDG_Theme::theme_setup()`. Eliminated duplicate `responsive-embeds` declaration.
- **Fixed Divi version detection** — Changed primary detection source from `ET_CORE_VERSION` to `wp_get_theme('Divi')->get('Version')`. In Divi 5, ET Core is architecturally separate and its version may not match the theme version. `ET_CORE_VERSION` is retained as a fallback only.
- **Removed duplicate generator filter** — Removed `the_generator` filter from theme (already handled by CDG Core's cleanup class when `remove_wp_version` is enabled).
- **Updated admin status page** — Changed "Divi 5 Support" label to "Divi 5 Detected" to accurately reflect informational status.

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
