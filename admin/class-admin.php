<?php
namespace CB\Admin;

defined( 'ABSPATH' ) || exit;

class Admin {

    public function init(): void {
        add_action( 'admin_init',            [ $this, 'handle_purge' ] );
        add_action( 'admin_menu',            [ $this, 'register_menus' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    // ── Purge seed data (one-time cleanup) ───────────────────────────────────

    public function handle_purge(): void {
        if ( empty( $_GET['cb_purge_seed'] ) || ! current_user_can( 'manage_options' ) ) {
            return;
        }
        require_once CB_PLUGIN_DIR . 'includes/class-seed-data.php';
        \CB\Core\Seed_Data::purge();
        wp_redirect( admin_url( 'admin.php?page=course-builder&cb_purged=1' ) );
        exit;
    }

    // ── Admin menus ───────────────────────────────────────────────────────────

    public function register_menus(): void {
        add_menu_page(
            __( 'Course Builder', 'course-builder' ),
            __( 'Course Builder', 'course-builder' ),
            'manage_options',
            'course-builder',
            [ $this, 'render_courses_list' ],
            'dashicons-welcome-learn-more',
            26
        );

        add_submenu_page(
            'course-builder',
            __( 'All Courses', 'course-builder' ),
            __( 'All Courses', 'course-builder' ),
            'manage_options',
            'course-builder',
            [ $this, 'render_courses_list' ]
        );

        add_submenu_page(
            'course-builder',
            __( 'Add New Course', 'course-builder' ),
            __( 'Add New Course', 'course-builder' ),
            'manage_options',
            'course-builder-add',
            [ $this, 'render_course_add' ]
        );

        add_submenu_page(
            'course-builder',
            __( 'Departments', 'course-builder' ),
            __( 'Departments', 'course-builder' ),
            'manage_options',
            'course-builder-categories',
            [ $this, 'render_categories' ]
        );

        add_submenu_page(
            'course-builder',
            __( 'Teachers', 'course-builder' ),
            __( 'Teachers', 'course-builder' ),
            'manage_options',
            'course-builder-teachers',
            [ $this, 'render_teachers' ]
        );
    }

    // ── Page renderers ────────────────────────────────────────────────────────

    public function render_courses_list(): void {
        include CB_PLUGIN_DIR . 'admin/views/courses-list.php';
    }

    public function render_course_add(): void {
        include CB_PLUGIN_DIR . 'admin/views/course-add.php';
    }

    public function render_categories(): void {
        include CB_PLUGIN_DIR . 'admin/views/categories.php';
    }

    public function render_teachers(): void {
        include CB_PLUGIN_DIR . 'admin/views/teachers.php';
    }

    // ── Assets ────────────────────────────────────────────────────────────────

    public function enqueue_assets( string $hook ): void {
        $our_pages = [
            'toplevel_page_course-builder',
            'course-builder_page_course-builder-add',
            'course-builder_page_course-builder-categories',
            'course-builder_page_course-builder-teachers',
        ];

        if ( ! in_array( $hook, $our_pages, true ) ) {
            return;
        }

        // Bootstrap 5
        wp_enqueue_style(
            'bootstrap5',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
            [],
            '5.3.3'
        );

        // Select2
        wp_enqueue_style(
            'select2',
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
            [],
            '4.1.0'
        );

        // Our admin CSS
        wp_enqueue_style(
            'cb-admin',
            CB_PLUGIN_URL . 'assets/css/admin.css',
            [ 'bootstrap5', 'select2' ],
            CB_VERSION
        );

        // WP Media (for image uploads)
        wp_enqueue_media();

        // jQuery (built-in)
        wp_enqueue_script( 'jquery' );

        // Bootstrap 5 JS
        wp_enqueue_script(
            'bootstrap5',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
            [ 'jquery' ],
            '5.3.3',
            true
        );

        // Select2
        wp_enqueue_script(
            'select2',
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
            [ 'jquery' ],
            '4.1.0',
            true
        );

        // Our admin JS
        wp_enqueue_script(
            'cb-admin',
            CB_PLUGIN_URL . 'assets/js/admin.js',
            [ 'jquery', 'bootstrap5', 'select2' ],
            CB_VERSION,
            true
        );

        // Pass PHP data to JS
        wp_localize_script( 'cb-admin', 'CB_Admin', [
            'ajax_url'   => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'cb_admin_nonce' ),
            'plugin_url' => CB_PLUGIN_URL,
            'site_url'   => get_site_url(),
            'page'       => $hook,
        ] );
    }
}
