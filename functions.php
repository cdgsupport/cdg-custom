<?php
/**
 * CDG Custom Child Theme Functions
 *
 * @package CDG_Custom
 * @since 1.0.0
 */

declare(strict_types=1);

// Prevent direct file access
if (!defined("ABSPATH")) {
  exit();
}

// Define theme version constant
define("CDG_THEME_VERSION", wp_get_theme()->get("Version"));

// Define configuration constants based on environment
$environment = wp_get_environment_type();
if (!defined("CDG_LOG_LEVEL")) {
  define("CDG_LOG_LEVEL", $environment === "production" ? "error" : "debug");
}
if (!defined("CDG_CUSTOM_LOG")) {
  define("CDG_CUSTOM_LOG", $environment !== "production");
}
if (!defined("CDG_CSP_REPORT_ONLY")) {
  define("CDG_CSP_REPORT_ONLY", $environment !== "production");
}
if (!defined("CDG_DEFER_SCRIPTS")) {
  define("CDG_DEFER_SCRIPTS", true);
}

// We define this as FALSE to prevent the error, but keep caching disabled
if (!defined("CDG_CACHE_ENABLED")) {
  define("CDG_CACHE_ENABLED", false);
}

// Autoloader for theme classes
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

// Load theme text domain for internationalization
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

// Initialize the theme with error handling
add_action(
  "after_setup_theme",
  function (): void {
    try {
      // Check Divi parent theme
      $parent_theme = wp_get_theme("Divi");
      if (!$parent_theme->exists()) {
        throw new RuntimeException(
          "Divi parent theme is required but not installed."
        );
      }

      // Check Divi version compatibility
      $divi_version = $parent_theme->get("Version");
      if (version_compare($divi_version, "4.0", "<")) {
        throw new RuntimeException(
          "CDG Custom theme requires Divi 4.0 or higher."
        );
      }

      // Initialize theme
      CDG_Theme::get_instance();
    } catch (Exception $e) {
      error_log("CDG Theme initialization failed: " . $e->getMessage());

      // Show admin notice if initialization fails
      add_action("admin_notices", function () use ($e): void {
        if (current_user_can("manage_options")) {
          printf(
            '<div class="notice notice-error"><p><strong>%s</strong> %s</p></div>',
            esc_html__("CDG Theme Error:", "cdg-custom"),
            esc_html($e->getMessage())
          );
        }
      });

      // Prevent theme from breaking the site
      add_action("wp_head", function (): void {
        echo "";
      });
    }
  },
  10
);

/**
 * Add page attributes support to posts (slides)
 */
add_action("admin_init", function (): void {
  add_post_type_support("post", "page-attributes");
});

/**
 * Remove unnecessary post type support
 */
add_action(
  "init",
  function (): void {
    $remove_support = [
      "editor",
      "author",
      "excerpt",
      "trackbacks",
      "comments",
      "revisions",
      "custom-fields",
    ];

    foreach ($remove_support as $feature) {
      remove_post_type_support("post", $feature);
    }
  },
  99
);

/**
 * Optimize queries for slide posts
 */
add_action("pre_get_posts", function ($query): void {
  if (is_admin() || !$query->is_main_query()) {
    return;
  }

  // Optimize slide queries
  if ($query->is_home() || $query->is_post_type_archive("post")) {
    $query->set("orderby", "menu_order");
    $query->set("order", "ASC");
    $query->set("posts_per_page", 10);

    // Skip counting total rows for pagination if not needed
    if (!$query->is_paged()) {
      $query->set("no_found_rows", true);
    }

    // Skip meta caching if not needed
    $query->set("update_post_meta_cache", false);
    $query->set("update_post_term_cache", false);
  }
});

/**
 * Add theme support for modern features
 */
add_action(
  "after_setup_theme",
  function (): void {
    // Add support for responsive embeds
    add_theme_support("responsive-embeds");

    // Add support for editor styles
    add_theme_support("editor-styles");

    // Add support for wide alignment
    add_theme_support("align-wide");

    // Add support for custom line height
    add_theme_support("custom-line-height");

    // Add support for custom units
    add_theme_support("custom-units", "rem", "em", "vh", "vw");
  },
  20
);
