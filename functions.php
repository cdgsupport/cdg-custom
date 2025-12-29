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
 * Autoloader for theme classes.
 *
 * @param string $class The class name to load.
 * @return void
 */
spl_autoload_register(function (string $class): void {
  $prefix = "CDG_";

  if (strpos($class, $prefix) !== 0) {
    return;
  }

  $class_file = "class-" . str_replace("_", "-", strtolower($class)) . ".php";
  $file_path = get_stylesheet_directory() . "/inc/" . $class_file;

  if (file_exists($file_path)) {
    require_once $file_path;
  }
});

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

/**
 * Optimize queries for slide posts.
 *
 * When Posts are renamed to Slides via CDG Core, this optimizes
 * the default query for better performance.
 */
add_action("pre_get_posts", function (WP_Query $query): void {
  if (is_admin() || !$query->is_main_query()) {
    return;
  }

  // Optimize slide/post queries on archives.
  if ($query->is_home() || $query->is_post_type_archive("post")) {
    $query->set("orderby", "menu_order");
    $query->set("order", "ASC");
    $query->set("posts_per_page", 10);

    // Skip counting total rows if not paginated.
    if (!$query->is_paged()) {
      $query->set("no_found_rows", true);
    }

    // Skip meta/term caching for performance.
    $query->set("update_post_meta_cache", false);
    $query->set("update_post_term_cache", false);
  }
});

/**
 * Add theme support for modern features.
 */
add_action(
  "after_setup_theme",
  function (): void {
    // Responsive embeds.
    add_theme_support("responsive-embeds");

    // Editor styles.
    add_theme_support("editor-styles");

    // Wide alignment.
    add_theme_support("align-wide");

    // Custom line height.
    add_theme_support("custom-line-height");

    // Custom units.
    add_theme_support("custom-units", "rem", "em", "vh", "vw");
  },
  20
);
