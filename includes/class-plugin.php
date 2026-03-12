<?php
namespace CB\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Central plugin class — singleton.
 * Wires up all sub-systems in the correct order.
 */
final class Plugin {

    private static ?self $instance = null;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}
    private function __clone() {}

    public function init(): void {
        $this->load_dependencies();

        // CPTs and taxonomies MUST be registered on 'init' — not 'plugins_loaded'.
        add_action( 'init', [ $this, 'register_post_types' ] );

        // Textdomain must also load on 'init' or later (WP 6.7+).
        add_action( 'init', [ $this, 'load_textdomain' ] );

        $this->register_ajax();

        if ( is_admin() ) {
            $admin = new \CB\Admin\Admin();
            $admin->init();
        }

        // Frontend: template override + assets
        add_filter( 'single_template',       [ $this, 'load_course_template' ] );
        add_action( 'wp_enqueue_scripts',    [ $this, 'enqueue_frontend_assets' ] );
    }

    public function load_textdomain(): void {
        load_plugin_textdomain( 'course-builder', false, CB_PLUGIN_DIR . 'languages' );
    }

    private function load_dependencies(): void {
        require_once CB_PLUGIN_DIR . 'includes/class-cpt-courses.php';
        require_once CB_PLUGIN_DIR . 'includes/class-cpt-teachers.php';
        require_once CB_PLUGIN_DIR . 'includes/class-taxonomy-category.php';
        require_once CB_PLUGIN_DIR . 'includes/class-ajax-handler.php';
        require_once CB_PLUGIN_DIR . 'admin/class-admin.php';
    }

    public function register_post_types(): void {
        CPT_Courses::register();
        CPT_Teachers::register();
        Taxonomy_Category::register();
    }

    private function register_ajax(): void {
        $handler = new Ajax_Handler();
        $handler->register_hooks();
    }

    public function load_course_template( string $template ): string {
        global $post;
        if ( is_singular( 'cb_course' ) ) {
            $plugin_tpl = CB_PLUGIN_DIR . 'templates/single-cb_course.php';
            if ( file_exists( $plugin_tpl ) ) {
                return $plugin_tpl;
            }
        }
        return $template;
    }

    public function enqueue_frontend_assets(): void {
        if ( ! is_singular( 'cb_course' ) ) return;
        wp_enqueue_style(
            'cb-course',
            CB_PLUGIN_URL . 'assets/css/course.css',
            [],
            CB_VERSION
        );
        // Google Fonts
        wp_enqueue_style(
            'cb-google-fonts',
            'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap',
            [],
            null
        );
    }
}
