<?php
/**
 * Divi Optimizations Class
 *
 * Handles Divi-specific functionality including ACF Local JSON configuration
 * and Divi 5 theme support.
 *
 * Note: CSS/HTML minification and caching are handled by SpinupWP at the server level.
 *
 * @package CDG_Custom
 * @since 2.0.0
 */

declare(strict_types=1);

/**
 * CDG_Optimizations handles Divi-specific optimizations.
 */
class CDG_Optimizations
{
  /**
   * Theme instance.
   *
   * @var CDG_Theme|null
   */
  private ?CDG_Theme $theme = null;

  /**
   * ACF JSON directory path.
   *
   * @var string
   */
  private string $acf_json_dir;

  /**
   * Constructor.
   *
   * @param CDG_Theme|null $theme Theme instance.
   */
  public function __construct(?CDG_Theme $theme = null)
  {
    $this->theme = $theme;
    $this->acf_json_dir = get_stylesheet_directory() . "/acf-json";

    $this->setup_hooks();
    $this->create_acf_json_directory();
  }

  /**
   * Setup hooks.
   *
   * @return void
   */
  private function setup_hooks(): void
  {
    // ACF Local JSON configuration.
    add_action("acf/init", [$this, "configure_acf"]);

    // Divi 5 specific support.
    if ($this->theme && $this->theme->is_divi_5()) {
      add_filter("et_builder_module_performance", [
        $this,
        "enhance_module_performance",
      ]);
    }
  }

  /**
   * Create ACF JSON directory if it doesn't exist.
   *
   * @return void
   */
  private function create_acf_json_directory(): void
  {
    try {
      if (!file_exists($this->acf_json_dir)) {
        $parent_dir = get_stylesheet_directory();

        if (!is_writable($parent_dir)) {
          return;
        }

        if (!wp_mkdir_p($this->acf_json_dir)) {
          return;
        }

        // Create .gitkeep file.
        $gitkeep_file = $this->acf_json_dir . "/.gitkeep";
        if (!file_exists($gitkeep_file) && is_writable($this->acf_json_dir)) {
          file_put_contents($gitkeep_file, ""); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        }

        // Create index.php for security.
        $index_file = $this->acf_json_dir . "/index.php";
        if (!file_exists($index_file) && is_writable($this->acf_json_dir)) {
          file_put_contents($index_file, "<?php\n// Silence is golden.\n"); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        }
      }
    } catch (Exception $e) {
      error_log(
        "CDG Theme: Error creating ACF JSON directory - " . $e->getMessage()
      );
    }
  }

  /**
   * Configure ACF Local JSON and admin visibility.
   *
   * @return void
   */
  public function configure_acf(): void
  {
    if (!class_exists("ACF")) {
      return;
    }

    // Set Local JSON save and load points.
    add_filter("acf/settings/save_json", [$this, "acf_json_save_point"]);
    add_filter("acf/settings/load_json", [$this, "acf_json_load_point"]);

    // Hide ACF admin menu for non-administrators.
    if (is_admin() && !current_user_can("manage_options")) {
      add_filter("acf/settings/show_admin", "__return_false");
    }
  }

  /**
   * ACF JSON save point.
   *
   * @return string
   */
  public function acf_json_save_point(): string
  {
    return $this->acf_json_dir;
  }

  /**
   * ACF JSON load point.
   *
   * @param array<string> $paths Load paths.
   * @return array<string>
   */
  public function acf_json_load_point(array $paths): array
  {
    $paths[] = $this->acf_json_dir;

    return $paths;
  }

  /**
   * Enhance Divi 5 module performance settings.
   *
   * @param array<string, mixed> $performance Performance settings.
   * @return array<string, mixed>
   */
  public function enhance_module_performance(array $performance): array
  {
    $performance["lazy_load"] = true;

    return $performance;
  }
}
