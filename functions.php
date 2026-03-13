<?php
/**
 * CDG Custom Child Theme Functions
 *
 * A streamlined Divi child theme focused on Divi-specific functionality.
 * WordPress core optimizations are handled by the CDG Core mu-plugin.
 *
 * @package CDG_Custom
 * @since 2.0.0
 */

declare(strict_types=1);

// Prevent direct file access.
if (!defined("ABSPATH")) {
  exit();
}

// Define theme version constant.
define("CDG_THEME_VERSION", wp_get_theme()->get("Version"));

/**
 * Load theme classes explicitly.
 *
 * Replaces spl_autoload_register to avoid filesystem checks on every
 * class resolution. Divi 5 resolves significantly more classes than
 * Divi 4, making the autoloader a measurable overhead on every request.
 */
$cdg_inc_dir = get_stylesheet_directory() . "/inc/";
require_once $cdg_inc_dir . "class-cdg-theme.php";
require_once $cdg_inc_dir . "class-cdg-optimizations.php";
require_once $cdg_inc_dir . "class-cdg-assets-manager.php";

/**
 * Load theme text domain for internationalization.
 */
add_action(
  "after_setup_theme",
  function (): void {
    load_child_theme_textdomain(
      "cdg-custom",
      get_stylesheet_directory() . "/languages"
    );
  },
  5
);

/**
 * Initialize the theme with error handling.
 *
 * This single callback handles:
 * - Divi parent theme validation
 * - Theme initialization (CDG_Theme singleton)
 * - Gutenberg/block-editor theme support declarations
 *
 * Theme support for Divi-specific features (title-tag, post-thumbnails,
 * html5, nav menus, etc.) is handled in CDG_Theme::theme_setup() at
 * priority 15 to avoid duplication.
 */
add_action(
  "after_setup_theme",
  function (): void {
    try {
      // Check Divi parent theme.
      $parent_theme = wp_get_theme("Divi");
      if (!$parent_theme->exists()) {
        throw new RuntimeException(
          "Divi parent theme is required but not installed."
        );
      }

      // Check Divi version compatibility.
      $divi_version = $parent_theme->get("Version");
      if (version_compare($divi_version, "4.0", "<")) {
        throw new RuntimeException(
          "CDG Custom theme requires Divi 4.0 or higher."
        );
      }

      // Initialize theme.
      CDG_Theme::get_instance();
    } catch (Exception $e) {
      error_log("CDG Theme initialization failed: " . $e->getMessage());

      // Show admin notice if initialization fails.
      add_action("admin_notices", function () use ($e): void {
        if (current_user_can("manage_options")) {
          printf(
            '<div class="notice notice-error"><p><strong>%s</strong> %s</p></div>',
            esc_html__("CDG Theme Error:", "cdg-custom"),
            esc_html($e->getMessage())
          );
        }
      });
    }
  },
  10
);
