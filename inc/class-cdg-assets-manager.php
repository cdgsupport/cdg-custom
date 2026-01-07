<?php
/**
 * Assets Manager Class
 *
 * Handles theme asset loading with Divi-specific optimizations.
 *
 * @package CDG_Custom
 * @since 2.0.0
 */

declare(strict_types=1);

/**
 * CDG_Assets_Manager handles theme assets.
 */
class CDG_Assets_Manager
{
  /**
   * Theme instance.
   *
   * @var CDG_Theme|null
   */
  private ?CDG_Theme $theme = null;

  /**
   * Constructor.
   *
   * @param CDG_Theme|null $theme Theme instance.
   */
  public function __construct(?CDG_Theme $theme = null)
  {
    $this->theme = $theme;
    $this->setup_hooks();
  }

  /**
   * Setup hooks.
   *
   * @return void
   */
  private function setup_hooks(): void
  {
    // Frontend assets.
    add_action("wp_enqueue_scripts", [$this, "enqueue_styles"], 20);
    add_action("wp_enqueue_scripts", [$this, "enqueue_scripts"], 20);

    // Add subfooter CSS.
    add_action("wp_enqueue_scripts", [$this, "add_subfooter_css"], 25);
  }

  /**
   * Enqueue styles.
   *
   * @return void
   */
  public function enqueue_styles(): void
  {
    // Parent theme style - always load for proper inheritance.
    wp_enqueue_style(
      "divi-parent-style",
      get_template_directory_uri() . "/style.css",
      [],
      $this->theme ? $this->theme->get_divi_version() : null
    );

    // Skip child theme styles in Visual Builder to prevent layout issues.
    if ($this->theme && $this->is_builder_active_safe()) {
      return;
    }

    // Child theme style.
    wp_enqueue_style(
      "cdg-child-style",
      get_stylesheet_uri(),
      ["divi-parent-style"],
      CDG_THEME_VERSION
    );

    // Custom styles (if file exists).
    $custom_css_path = get_stylesheet_directory() . "/assets/css/custom.css";
    if (file_exists($custom_css_path)) {
      wp_enqueue_style(
        "cdg-custom-style",
        get_stylesheet_directory_uri() . "/assets/css/custom.css",
        ["cdg-child-style"],
        (string) filemtime($custom_css_path)
      );
    }
  }

  /**
   * Enqueue scripts.
   *
   * @return void
   */
  public function enqueue_scripts(): void
  {
    // Don't load in builder.
    if ($this->theme && $this->is_builder_active_safe()) {
      return;
    }

    // Custom scripts (if file exists).
    $custom_js_path = get_stylesheet_directory() . "/assets/js/custom.js";
    if (file_exists($custom_js_path)) {
      wp_enqueue_script(
        "cdg-custom-script",
        get_stylesheet_directory_uri() . "/assets/js/custom.js",
        ["jquery"],
        (string) filemtime($custom_js_path),
        true
      );
    }
  }

  /**
   * Add subfooter CSS for frontend copyright styling.
   *
   * Adds CSS for .cdg-subfooter-info-light and .cdg-subfooter-info-dark classes
   * which automatically append site title and copyright text.
   *
   * @return void
   */
  public function add_subfooter_css(): void
  {
    // Skip in builder.
    if ($this->theme && $this->is_builder_active_safe()) {
      return;
    }

    $site_title = get_bloginfo("name");

    // Escape for CSS content property.
    $site_title_css = $this->escape_css_content($site_title);
    $rights_text = __("All Rights Reserved", "cdg-custom");
    $rights_text_css = $this->escape_css_content($rights_text);

    $css =
      ':root {
    --cdg-subfooter-color-light: #333;
    --cdg-subfooter-color-dark: #F4F4F4;
    --cdg-subfooter-font-size: 1em;
    --cdg-subfooter-font-weight: 600;
}

.cdg-subfooter-info-light,
.cdg-subfooter-info-dark {
    display: inline-block;
    text-transform: uppercase;
    font-weight: var(--cdg-subfooter-font-weight);
    font-size: var(--cdg-subfooter-font-size);
}

.cdg-subfooter-info-light .et_pb_code_inner,
.cdg-subfooter-info-dark .et_pb_code_inner {
    display: inline-block;
    text-transform: uppercase;
    font-weight: var(--cdg-subfooter-font-weight);
    font-size: var(--cdg-subfooter-font-size);
}

.cdg-subfooter-info-light::after,
.cdg-subfooter-info-dark::after {
    display: inline-block;
    vertical-align: baseline;
    content: " ' .
      $site_title_css .
      " | " .
      $rights_text_css .
      '";
    text-transform: uppercase;
    font-weight: var(--cdg-subfooter-font-weight);
    font-size: var(--cdg-subfooter-font-size);
}

.cdg-subfooter-info-dark,
.cdg-subfooter-info-dark .et_pb_code_inner,
.cdg-subfooter-info-dark::after {
    color: var(--cdg-subfooter-color-dark);
}

.cdg-subfooter-info-light,
.cdg-subfooter-info-light .et_pb_code_inner,
.cdg-subfooter-info-light::after {
    color: var(--cdg-subfooter-color-light);
}';

    /**
     * Filter the subfooter CSS.
     *
     * @param string $css        The subfooter CSS.
     * @param string $site_title The site title.
     */
    $css = apply_filters("cdg_subfooter_css", $css, $site_title);

    wp_add_inline_style("cdg-child-style", $css);
  }

  /**
   * Escape a string for use in CSS content property.
   *
   * @param string $string The string to escape.
   * @return string The escaped string.
   */
  private function escape_css_content(string $string): string
  {
    // Escape backslashes first.
    $string = str_replace("\\", "\\\\", $string);

    // Escape single quotes.
    $string = str_replace("'", "\\'", $string);

    // Escape newlines and tabs for CSS content property.
    $string = str_replace(
      ["\r\n", "\r", "\n", "\t"],
      ["\\A", "\\A", "\\A", "\\9"],
      $string
    );

    return $string;
  }

  /**
   * Safe wrapper for checking if builder is active.
   *
   * Provides multiple fallback checks for reliable detection.
   *
   * @return bool
   */
  private function is_builder_active_safe(): bool
  {
    // Primary check: URL parameter (most reliable early detection).
    if (
      isset($_GET["et_fb"]) &&
      sanitize_text_field(wp_unslash($_GET["et_fb"])) === "1"
    ) {
      return true;
    }

    // Secondary check: Theme method (if available).
    if ($this->theme && method_exists($this->theme, "is_builder_active")) {
      return $this->theme->is_builder_active();
    }

    // Tertiary check: Direct Divi function checks.
    if (
      function_exists("et_builder_is_frontend_editor") &&
      et_builder_is_frontend_editor()
    ) {
      return true;
    }

    if (function_exists("et_core_is_fb_enabled") && et_core_is_fb_enabled()) {
      return true;
    }

    return false;
  }
}
