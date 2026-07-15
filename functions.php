<?php
/**
 * CDG Custom Child Theme Functions
 *
 * A streamlined Divi child theme focused on Divi-specific functionality.
 * WordPress core optimizations are handled by the CDG Core plugin
 * (wp-content/plugins/cdg-core/).
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
 *
 * Theme support for Divi-specific features (title-tag, post-thumbnails,
 * html5, nav menus, etc.) is handled in CDG_Theme::theme_setup() at
 * priority 15 to avoid duplication.
 *
 * CDG standardizes on Divi 5 across all sites, so only parent theme
 * existence is validated here — no version comparison is required.
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

/**
 * Warn admins when the CDG Core plugin isn't active.
 *
 * CDG Core (installed as a standard plugin at wp-content/plugins/cdg-core/,
 * managed via ManageWP) provides WordPress core optimizations, security
 * hardening, and agency features. It's recommended, not required — this
 * theme continues to function without it, just in a reduced capacity.
 * CDG_CORE_VERSION is only defined when the plugin's main file has loaded,
 * so this doubles as an "is active" check consistent with the status row
 * on Tools -> CDG Theme Status.
 *
 * @return void
 */
add_action(
  "admin_notices",
  function (): void {
    if (defined("CDG_CORE_VERSION")) {
      return;
    }

    if (!current_user_can("manage_options")) {
      return;
    }

    printf(
      '<div class="notice notice-warning is-dismissible"><p><strong>%s</strong> %s</p></div>',
      esc_html__("CDG Core plugin not detected:", "cdg-custom"),
      esc_html__(
        "Install and activate the CDG Core plugin (wp-content/plugins/cdg-core/) for full functionality, including security hardening and performance optimizations.",
        "cdg-custom"
      )
    );
  }
);
