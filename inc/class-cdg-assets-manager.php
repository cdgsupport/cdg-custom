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
 * CDG_Assets_Manager handles theme assets
 */
class CDG_Assets_Manager
{
    /**
     * Theme instance
     *
     * @var CDG_Theme|null
     */
    private ?CDG_Theme $theme = null;

    /**
     * Constructor
     *
     * @param CDG_Theme|null $theme Theme instance
     */
    public function __construct(?CDG_Theme $theme = null)
    {
        $this->theme = $theme;
        $this->setup_hooks();
    }

    /**
     * Setup hooks
     *
     * @return void
     */
    private function setup_hooks(): void
    {
        // Frontend assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles'], 20);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts'], 20);
        
        // Add subfooter CSS
        add_action('wp_enqueue_scripts', [$this, 'add_subfooter_css'], 25);

        // Optimize script loading
        if (CDG_DEFER_SCRIPTS) {
            add_filter('script_loader_tag', [$this, 'add_defer_attribute'], 10, 2);
        }

        // Divi-specific optimizations
        add_action('wp_enqueue_scripts', [$this, 'optimize_divi_assets'], 999);
    }

    /**
     * Enqueue styles
     *
     * @return void
     */
    public function enqueue_styles(): void
    {
        // Parent theme style
        wp_enqueue_style(
            'divi-parent-style',
            get_template_directory_uri() . '/style.css',
            [],
            $this->theme ? $this->theme->get_divi_version() : null
        );

        // Child theme style
        wp_enqueue_style(
            'cdg-child-style',
            get_stylesheet_uri(),
            ['divi-parent-style'],
            CDG_THEME_VERSION
        );

        // Custom styles (if file exists)
        $custom_css_path = get_stylesheet_directory() . '/assets/css/custom.css';
        if (file_exists($custom_css_path)) {
            wp_enqueue_style(
                'cdg-custom-style',
                get_stylesheet_directory_uri() . '/assets/css/custom.css',
                ['cdg-child-style'],
                filemtime($custom_css_path)
            );
        }
    }

    /**
     * Enqueue scripts
     *
     * @return void
     */
    public function enqueue_scripts(): void
    {
        // Don't load in builder
        if ($this->theme && $this->theme->is_builder_active()) {
            return;
        }

        // Custom scripts (if file exists)
        $custom_js_path = get_stylesheet_directory() . '/assets/js/custom.js';
        if (file_exists($custom_js_path)) {
            wp_enqueue_script(
                'cdg-custom-script',
                get_stylesheet_directory_uri() . '/assets/js/custom.js',
                ['jquery'],
                filemtime($custom_js_path),
                true
            );
        }
    }

    /**
     * Add defer attribute to scripts
     *
     * @param string $tag Script tag
     * @param string $handle Script handle
     * @return string
     */
    public function add_defer_attribute(string $tag, string $handle): string
    {
        // Scripts that should not be deferred
        $no_defer = [
            'jquery',
            'jquery-core',
            'jquery-migrate',
            'et-builder-modules-script',
            'divi-custom-script',
            'gform_gravityforms',
        ];

        if (in_array($handle, $no_defer, true)) {
            return $tag;
        }

        // Don't defer if already has defer or async
        if (strpos($tag, 'defer') !== false || strpos($tag, 'async') !== false) {
            return $tag;
        }

        return str_replace(' src', ' defer src', $tag);
    }

    /**
     * Optimize Divi assets
     *
     * @return void
     */
    public function optimize_divi_assets(): void
    {
        // Skip in builder
        if ($this->theme && $this->theme->is_builder_active()) {
            return;
        }

        // Dequeue unnecessary Divi scripts on pages that don't need them
        if (!is_singular() || !$this->page_uses_divi_builder()) {
            wp_dequeue_script('et-builder-modules-script');
            wp_dequeue_style('et-builder-modules-style');
        }
    }

    /**
     * Check if current page uses Divi Builder
     *
     * @return bool
     */
    private function page_uses_divi_builder(): bool
    {
        if (!is_singular()) {
            return false;
        }

        $post_id = get_the_ID();
        if (!$post_id) {
            return false;
        }

        // Check if Divi Builder is used
        $use_builder = get_post_meta($post_id, '_et_pb_use_builder', true);
        
        return $use_builder === 'on';
    }

    /**
     * Add subfooter CSS for frontend copyright styling
     *
     * Adds CSS for .cdg-subfooter-info-light and .cdg-subfooter-info-dark classes
     * which automatically append site title and copyright text.
     *
     * @return void
     */
    public function add_subfooter_css(): void
    {
        // Skip in builder
        if ($this->theme && $this->theme->is_builder_active()) {
            return;
        }

        $site_title = get_bloginfo('name');
        // Escape for CSS content property - escape quotes and backslashes
        $site_title_css = addslashes($site_title);
        $rights_text = __('All Rights Reserved', 'cdg-custom');
        $rights_text_css = addslashes($rights_text);

        $css = ':root {
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
    content: " ' . $site_title_css . ' | ' . $rights_text_css . '";
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
         * Filter the subfooter CSS
         *
         * @param string $css The subfooter CSS
         * @param string $site_title The site title
         */
        $css = apply_filters('cdg_subfooter_css', $css, $site_title);

        wp_add_inline_style('cdg-child-style', $css);
    }

    /**
     * Cleanup caches
     *
     * @return void
     */
    public function cleanup_caches(): void
    {
        // Clear any asset-related caches
        wp_cache_delete('cdg_asset_versions', 'cdg_assets');
    }
}
