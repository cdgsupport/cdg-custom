<?php
/**
 * Main Theme Class
 *
 * Handles Divi-specific functionality including version detection,
 * builder optimizations, and theme setup.
 *
 * @package CDG_Custom
 * @since 2.0.0
 */

declare(strict_types=1);

/**
 * CDG_Theme class handles core Divi theme functionality.
 */
class CDG_Theme
{
  /**
   * Theme instance (Singleton).
   *
   * @var CDG_Theme|null
   */
  private static ?CDG_Theme $instance = null;

  /**
   * Theme components.
   *
   * @var array<string, object>
   */
  private array $components = [];

  /**
   * Initialization status.
   *
   * @var bool
   */
  private bool $initialized = false;

  /**
   * Divi version.
   *
   * @var string|null
   */
  private ?string $divi_version = null;

  /**
   * Is Divi 5.
   *
   * @var bool
   */
  private bool $is_divi_5 = false;

  /**
   * Get theme instance.
   *
   * @return CDG_Theme
   */
  public static function get_instance(): CDG_Theme
  {
    if (null === self::$instance) {
      self::$instance = new self();
    }

    return self::$instance;
  }

  /**
   * Constructor.
   */
  private function __construct()
  {
    $this->detect_divi_version();
    $this->init_components();
    $this->setup_hooks();
  }

  /**
   * Detect Divi version and capabilities.
   *
   * @return void
   */
  private function detect_divi_version(): void
  {
    // Check ET Core version first (most accurate).
    if (defined("ET_CORE_VERSION")) {
      $this->divi_version = ET_CORE_VERSION;
    } else {
      // Fallback to parent theme version.
      $parent_theme = wp_get_theme("Divi");
      if ($parent_theme->exists()) {
        $this->divi_version = $parent_theme->get("Version");
      }
    }

    // Check if it's Divi 5 or higher.
    if ($this->divi_version) {
      $this->is_divi_5 = version_compare($this->divi_version, "5.0", ">=");
    }
  }

  /**
   * Initialize theme components with error handling.
   *
   * @return void
   */
  private function init_components(): void
  {
    try {
      // Initialize components.
      $this->safe_init_component("assets", "CDG_Assets_Manager");
      $this->safe_init_component("optimizations", "CDG_Optimizations");

      // Initialize Divi 5 specific components if applicable.
      if ($this->is_divi_5) {
        $this->init_divi_5_features();
      }

      $this->initialized = true;
    } catch (Exception $e) {
      error_log("CDG Theme initialization error: " . $e->getMessage());
      $this->initialized = false;
    }
  }

  /**
   * Initialize Divi 5 specific features.
   *
   * @return void
   */
  private function init_divi_5_features(): void
  {
    add_action("et_builder_ready", [$this, "setup_divi_5_builder"]);
    add_filter("et_builder_load_requests", [
      $this,
      "optimize_builder_requests",
    ]);
    add_action("et_builder_modules_loaded", [$this, "register_custom_modules"]);
    add_filter("et_core_page_resource_hints", [
      $this,
      "add_divi_5_resource_hints",
    ]);
  }

  /**
   * Safely initialize a component.
   *
   * @param string $name  Component name.
   * @param string $class Component class name.
   * @return void
   */
  private function safe_init_component(string $name, string $class): void
  {
    try {
      if (class_exists($class)) {
        $this->components[$name] = new $class($this);
      }
    } catch (Exception $e) {
      error_log(
        sprintf(
          "CDG Theme: Failed to initialize %s - %s",
          $name,
          $e->getMessage()
        )
      );
    }
  }

  /**
   * Setup theme hooks.
   *
   * @return void
   */
  private function setup_hooks(): void
  {
    add_action("after_setup_theme", [$this, "theme_setup"], 15);
    add_filter("the_generator", "__return_empty_string");
    add_action("admin_init", [$this, "health_check"]);
    add_action("admin_notices", [$this, "check_compatibility"]);
    add_action("admin_menu", [$this, "add_admin_menu"]);
  }

  /**
   * Theme setup.
   *
   * @return void
   */
  public function theme_setup(): void
  {
    // Add theme support.
    add_theme_support("title-tag");
    add_theme_support("post-thumbnails");
    add_theme_support("html5", [
      "search-form",
      "comment-form",
      "comment-list",
      "gallery",
      "caption",
      "script",
      "style",
      "navigation-widgets",
    ]);

    // Modern theme support.
    add_theme_support("responsive-embeds");
    add_theme_support("automatic-feed-links");
    add_theme_support("customize-selective-refresh-widgets");

    // Divi 5 specific support.
    if ($this->is_divi_5) {
      add_theme_support("et-builder-5");
      add_theme_support("et-builder-performance");
    }

    // Register navigation menus.
    $this->register_nav_menus();
  }

  /**
   * Register navigation menus.
   *
   * @return void
   */
  private function register_nav_menus(): void
  {
    register_nav_menus([
      "primary" => esc_html__("Primary Menu", "cdg-custom"),
      "mobile" => esc_html__("Mobile Menu", "cdg-custom"),
      "footer" => esc_html__("Footer Menu", "cdg-custom"),
    ]);
  }

  /**
   * Add admin menu for CDG Theme Status.
   *
   * @return void
   */
  public function add_admin_menu(): void
  {
    add_management_page(
      esc_html__("CDG Theme Status", "cdg-custom"),
      esc_html__("CDG Theme Status", "cdg-custom"),
      "manage_options",
      "cdg-theme-status",
      [$this, "render_admin_page"]
    );
  }

  /**
   * Render admin page.
   *
   * @return void
   */
  public function render_admin_page(): void
  {
    if (!current_user_can("manage_options")) {
      return;
    } ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="card" style="max-width: 800px; padding: 20px; margin-top: 20px;">
                <h2><?php esc_html_e("Theme Status", "cdg-custom"); ?></h2>

                <?php
                $status_class = $this->initialized
                  ? "notice-success"
                  : "notice-warning";
                $status_text = $this->initialized
                  ? __("Active", "cdg-custom")
                  : __("Limited Mode", "cdg-custom");
                ?>

                <div class="notice inline <?php echo esc_attr(
                  $status_class
                ); ?>" style="margin: 15px 0;">
                    <p>
                        <strong><?php esc_html_e(
                          "Status:",
                          "cdg-custom"
                        ); ?></strong>
                        <?php echo esc_html($status_text); ?>
                    </p>
                </div>

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><?php esc_html_e(
                              "Theme Version",
                              "cdg-custom"
                            ); ?></th>
                            <td><?php echo esc_html(CDG_THEME_VERSION); ?></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e(
                              "Divi Version",
                              "cdg-custom"
                            ); ?></th>
                            <td><?php echo esc_html(
                              $this->divi_version ?? __("Unknown", "cdg-custom")
                            ); ?></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e(
                              "Divi 5 Support",
                              "cdg-custom"
                            ); ?></th>
                            <td>
                                <?php if ($this->is_divi_5): ?>
                                    <span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span>
                                    <?php esc_html_e(
                                      "Enabled",
                                      "cdg-custom"
                                    ); ?>
                                <?php else: ?>
                                    <span class="dashicons dashicons-minus" style="color: #dba617;"></span>
                                    <?php esc_html_e(
                                      "Divi 4 Mode",
                                      "cdg-custom"
                                    ); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e(
                              "PHP Version",
                              "cdg-custom"
                            ); ?></th>
                            <td><?php echo esc_html(PHP_VERSION); ?></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e(
                              "WordPress Version",
                              "cdg-custom"
                            ); ?></th>
                            <td><?php echo esc_html(
                              get_bloginfo("version")
                            ); ?></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e(
                              "CDG Core Plugin",
                              "cdg-custom"
                            ); ?></th>
                            <td>
                                <?php if (defined("CDG_CORE_VERSION")): ?>
                                    <span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span>
                                    <?php echo esc_html(
                                      sprintf(
                                        /* translators: %s: version number */
                                        __("Active (v%s)", "cdg-custom"),
                                        CDG_CORE_VERSION
                                      )
                                    ); ?>
                                <?php else: ?>
                                    <span class="dashicons dashicons-warning" style="color: #d63638;"></span>
                                    <?php esc_html_e(
                                      "Not Detected",
                                      "cdg-custom"
                                    ); ?>
                                    <br><small><?php esc_html_e(
                                      "Install the CDG Core mu-plugin for full functionality.",
                                      "cdg-custom"
                                    ); ?></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
  }

  /**
   * Perform health check.
   *
   * @return void
   */
  public function health_check(): void
  {
    if (!$this->initialized && current_user_can("manage_options")) {
      add_action("admin_notices", function (): void {
        printf(
          '<div class="notice notice-warning"><p>%s</p></div>',
          esc_html__(
            "CDG Theme is running with limited functionality. Some components failed to initialize. Please check the error logs.",
            "cdg-custom"
          )
        );
      });
    }
  }

  /**
   * Check compatibility.
   *
   * @return void
   */
  public function check_compatibility(): void
  {
    if (!current_user_can("manage_options")) {
      return;
    }

    // Check PHP version.
    if (version_compare(PHP_VERSION, "8.0", "<")) {
      printf(
        '<div class="notice notice-error"><p>%s</p></div>',
        sprintf(
          /* translators: %s: PHP version number */
          esc_html__(
            "CDG Theme requires PHP 8.0 or higher. Your server is running PHP %s.",
            "cdg-custom"
          ),
          PHP_VERSION
        )
      );
    }

    // Check Divi version.
    if (
      $this->divi_version &&
      version_compare($this->divi_version, "4.0", "<")
    ) {
      printf(
        '<div class="notice notice-warning"><p>%s</p></div>',
        esc_html__(
          "CDG Theme works best with Divi 4.0 or higher. Please update Divi for optimal performance.",
          "cdg-custom"
        )
      );
    }
  }

  /**
   * Setup Divi 5 builder.
   *
   * @return void
   */
  public function setup_divi_5_builder(): void
  {
    add_filter("et_builder_module_settings", [
      $this,
      "customize_module_settings",
    ]);
  }

  /**
   * Optimize builder requests.
   *
   * @param array<string, mixed> $requests Builder requests.
   * @return array<string, mixed>
   */
  public function optimize_builder_requests(array $requests): array
  {
    return $requests;
  }

  /**
   * Register custom modules.
   *
   * @return void
   */
  public function register_custom_modules(): void
  {
    // Register custom Divi modules here.
  }

  /**
   * Add Divi 5 resource hints.
   *
   * @param array<string> $hints Resource hints.
   * @return array<string>
   */
  public function add_divi_5_resource_hints(array $hints): array
  {
    $hints[] = "https://fonts.googleapis.com";
    $hints[] = "https://fonts.gstatic.com";

    return $hints;
  }

  /**
   * Customize module settings.
   *
   * @param array<string, mixed> $settings Module settings.
   * @return array<string, mixed>
   */
  public function customize_module_settings(array $settings): array
  {
    return $settings;
  }

  /**
   * Get theme component.
   *
   * @param string $name Component name.
   * @return object|null
   */
  public function get_component(string $name): ?object
  {
    return $this->components[$name] ?? null;
  }

  /**
   * Check if theme is fully initialized.
   *
   * @return bool
   */
  public function is_initialized(): bool
  {
    return $this->initialized;
  }

  /**
   * Get Divi version.
   *
   * @return string|null
   */
  public function get_divi_version(): ?string
  {
    return $this->divi_version;
  }

  /**
   * Check if Divi 5.
   *
   * @return bool
   */
  public function is_divi_5(): bool
  {
    return $this->is_divi_5;
  }

  /**
   * Check if builder is active (sanitized).
   *
   * @return bool
   */
  public function is_builder_active(): bool
  {
    // Divi 5 check.
    if (function_exists("et_builder_is_frontend_editor")) {
      return et_builder_is_frontend_editor();
    }

    // Divi 4 check.
    if (function_exists("et_core_is_fb_enabled")) {
      return et_core_is_fb_enabled();
    }

    // URL parameter check (sanitized).
    if (isset($_GET["et_fb"])) {
      // phpcs:ignore WordPress.Security.NonceVerification.Recommended
      return sanitize_text_field(wp_unslash($_GET["et_fb"])) === "1"; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    }

    return false;
  }
}
