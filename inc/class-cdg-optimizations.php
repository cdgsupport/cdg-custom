<?php
/**
 * Divi Optimizations Class
 *
 * Handles Divi-specific performance optimizations including
 * ACF Local JSON, Divi builder caching, and dynamic CSS.
 *
 * @package CDG_Custom
 * @since 2.0.0
 */

declare(strict_types=1);

/**
 * CDG_Optimizations handles Divi-specific performance optimizations
 */
class CDG_Optimizations
{
    /**
     * Theme instance
     *
     * @var CDG_Theme|null
     */
    private ?CDG_Theme $theme = null;

    /**
     * ACF JSON directory path
     *
     * @var string
     */
    private string $acf_json_dir;

    /**
     * Constructor
     *
     * @param CDG_Theme|null $theme Theme instance
     */
    public function __construct(?CDG_Theme $theme = null)
    {
        $this->theme = $theme;
        $this->acf_json_dir = get_stylesheet_directory() . '/acf-json';

        $this->setup_hooks();
        $this->create_acf_json_directory();
    }

    /**
     * Setup hooks
     *
     * @return void
     */
    private function setup_hooks(): void
    {
        // ACF optimization
        add_action('acf/init', [$this, 'optimize_acf']);

        // Divi specific optimizations
        if ($this->theme && $this->theme->get_divi_version()) {
            $this->setup_divi_optimizations();
        }

        // Cache optimizations
        add_action('save_post', [$this, 'clear_cache_on_save']);
        add_action('switch_theme', [$this, 'clear_all_caches']);
    }

    /**
     * Setup Divi-specific optimizations
     *
     * @return void
     */
    private function setup_divi_optimizations(): void
    {
        // Optimize Divi Builder loading
        add_filter('et_builder_load_requests', [$this, 'optimize_builder_requests']);

        // Divi 5 specific optimizations
        if ($this->theme && $this->theme->is_divi_5()) {
            add_action('et_builder_ready', [$this, 'optimize_divi_5_builder']);
            add_filter('et_builder_module_performance', [$this, 'enhance_module_performance']);
        }

        // Optimize Divi dynamic CSS
        add_filter('et_use_dynamic_css', '__return_true');
        add_filter('et_dynamic_css_custom_css', [$this, 'minify_dynamic_css']);
    }

    /**
     * Create ACF JSON directory if it doesn't exist
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

                // Create .gitkeep file
                $gitkeep_file = $this->acf_json_dir . '/.gitkeep';
                if (!file_exists($gitkeep_file) && is_writable($this->acf_json_dir)) {
                    file_put_contents($gitkeep_file, '');
                }

                // Create index.php for security
                $index_file = $this->acf_json_dir . '/index.php';
                if (!file_exists($index_file) && is_writable($this->acf_json_dir)) {
                    file_put_contents($index_file, "<?php\n// Silence is golden.\n");
                }
            }
        } catch (Exception $e) {
            error_log('CDG Theme: Error creating ACF JSON directory - ' . $e->getMessage());
        }
    }

    /**
     * Optimize ACF
     *
     * @return void
     */
    public function optimize_acf(): void
    {
        if (!class_exists('ACF')) {
            return;
        }

        // Set Local JSON save and load points
        add_filter('acf/settings/save_json', [$this, 'acf_json_save_point']);
        add_filter('acf/settings/load_json', [$this, 'acf_json_load_point']);

        // Cache ACF field groups
        add_filter('acf/load_field_groups', [$this, 'cache_field_groups']);

        // Optimize ACF in admin
        if (is_admin()) {
            if (!current_user_can('manage_options')) {
                add_filter('acf/settings/show_admin', '__return_false');
            }
            add_filter('acf/settings/remove_wp_meta_box', '__return_true');
        }

        // Optimize ACF in Divi Builder
        if ($this->theme && $this->theme->is_builder_active()) {
            add_filter('acf/settings/enqueue_select2', '__return_false');
        }
    }

    /**
     * ACF JSON save point
     *
     * @return string
     */
    public function acf_json_save_point(): string
    {
        return $this->acf_json_dir;
    }

    /**
     * ACF JSON load point
     *
     * @param array<string> $paths Load paths
     * @return array<string>
     */
    public function acf_json_load_point(array $paths): array
    {
        $paths[] = $this->acf_json_dir;
        return $paths;
    }

    /**
     * Cache ACF field groups
     *
     * @param array<mixed> $field_groups Field groups
     * @return array<mixed>
     */
    public function cache_field_groups(array $field_groups): array
    {
        if (!CDG_CACHE_ENABLED) {
            return $field_groups;
        }

        $cache_key = 'cdg_acf_field_groups';
        $cache_group = 'cdg_acf';

        $cached = wp_cache_get($cache_key, $cache_group);
        if ($cached !== false) {
            return $cached;
        }

        wp_cache_set($cache_key, $field_groups, $cache_group, HOUR_IN_SECONDS);
        return $field_groups;
    }

    /**
     * Optimize Divi 5 builder
     *
     * @return void
     */
    public function optimize_divi_5_builder(): void
    {
        add_filter('et_builder_get_layouts', [$this, 'cache_builder_layouts']);
        add_filter('et_module_shortcode_output', [$this, 'optimize_module_output'], 10, 3);
    }

    /**
     * Enhance module performance
     *
     * @param array<string, mixed> $performance Performance settings
     * @return array<string, mixed>
     */
    public function enhance_module_performance(array $performance): array
    {
        $performance['lazy_load'] = true;
        $performance['cache_duration'] = HOUR_IN_SECONDS;
        $performance['minify_output'] = true;

        return $performance;
    }

    /**
     * Optimize builder requests
     *
     * @param array<string, mixed> $requests Builder requests
     * @return array<string, mixed>
     */
    public function optimize_builder_requests(array $requests): array
    {
        if (isset($requests['modules'])) {
            $requests['modules'] = array_unique($requests['modules']);
        }
        return $requests;
    }

    /**
     * Cache builder layouts
     *
     * @param array<mixed> $layouts Layouts
     * @return array<mixed>
     */
    public function cache_builder_layouts(array $layouts): array
    {
        if (!CDG_CACHE_ENABLED) {
            return $layouts;
        }

        $cache_key = 'cdg_builder_layouts';
        $cache_group = 'cdg_divi';

        $cached = wp_cache_get($cache_key, $cache_group);
        if ($cached !== false) {
            return $cached;
        }

        wp_cache_set($cache_key, $layouts, $cache_group, HOUR_IN_SECONDS);
        return $layouts;
    }

    /**
     * Optimize module output
     *
     * @param string $output Module output
     * @param string $render_slug Render slug
     * @param object $module Module object
     * @return string
     */
    public function optimize_module_output(string $output, string $render_slug, $module): string
    {
        if (!is_admin() && $this->theme && !$this->theme->is_builder_active()) {
            $output = $this->minify_html($output);
        }
        return $output;
    }

    /**
     * Minify dynamic CSS
     *
     * @param string $css CSS to minify
     * @return string
     */
    public function minify_dynamic_css(string $css): string
    {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        // Remove whitespace
        $css = str_replace(["\r\n", "\r", "\n", "\t"], '', $css);
        // Remove multiple spaces
        $css = preg_replace('/\s+/', ' ', $css);
        // Remove spaces around specific characters
        $css = preg_replace('/\s*([{}:;,])\s*/', '$1', $css);

        return trim($css);
    }

    /**
     * Minify HTML
     *
     * @param string $html HTML to minify
     * @return string
     */
    private function minify_html(string $html): string
    {
        // Preserve pre and textarea content
        $preserved = [];
        $html = preg_replace_callback(
            '/<(pre|textarea|script|style).*?<\/\1>/si',
            function ($matches) use (&$preserved) {
                $key = '<!--PRESERVED' . count($preserved) . '-->';
                $preserved[$key] = $matches[0];
                return $key;
            },
            $html
        );

        // Remove HTML comments (except preserved markers)
        $html = preg_replace('/<!--(?!PRESERVED)[^>]*-->/', '', $html);
        
        // Remove whitespace between tags
        $html = preg_replace('/>\s+</', '><', $html);
        
        // Remove multiple spaces
        $html = preg_replace('/\s+/', ' ', $html);

        // Restore preserved content
        foreach ($preserved as $key => $value) {
            $html = str_replace($key, $value, $html);
        }

        return trim($html);
    }

    /**
     * Clear cache on post save
     *
     * @param int $post_id Post ID
     * @return void
     */
    public function clear_cache_on_save(int $post_id): void
    {
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }

        wp_cache_delete('cdg_builder_layouts', 'cdg_divi');
        wp_cache_delete('cdg_acf_field_groups', 'cdg_acf');

        // Clear SpinupWP cache if available
        if (function_exists('spinupwp_purge_post')) {
            spinupwp_purge_post($post_id);
        }
    }

    /**
     * Clear all theme caches
     *
     * @return void
     */
    public function clear_all_caches(): void
    {
        wp_cache_delete('cdg_builder_layouts', 'cdg_divi');
        wp_cache_delete('cdg_acf_field_groups', 'cdg_acf');

        // Clear SpinupWP cache if available
        if (function_exists('spinupwp_purge_site')) {
            spinupwp_purge_site();
        }
    }
}
