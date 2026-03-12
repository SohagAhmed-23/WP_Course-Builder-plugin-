<?php
/**
 * Plugin Name:       Course Builder
 * Plugin URI:        https://example.com/course-builder
 * Description:       A modern, modular course management system for WordPress with full admin UI, AJAX-powered CRUD, WooCommerce integration, and a clean dashboard.
 * Version:           1.0.0
 * Author:            Course Builder Team
 * Author URI:        https://example.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       course-builder
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      8.0
 */

defined( 'ABSPATH' ) || exit;

// ── Plugin constants ──────────────────────────────────────────────────────────
define( 'CB_VERSION',     '1.0.1' );
define( 'CB_PLUGIN_FILE', __FILE__ );
define( 'CB_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'CB_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'CB_PLUGIN_BASE', plugin_basename( __FILE__ ) );

// ── Autoloader ────────────────────────────────────────────────────────────────
spl_autoload_register( function ( string $class ): void {
    $prefix = 'CB\\';
    if ( strpos( $class, $prefix ) !== 0 ) {
        return;
    }
    $relative = str_replace( [ $prefix, '\\' ], [ '', '/' ], $class );
    $map      = [
        'Core/Plugin'            => CB_PLUGIN_DIR . 'includes/class-plugin.php',
        'Core/CPT_Courses'       => CB_PLUGIN_DIR . 'includes/class-cpt-courses.php',
        'Core/CPT_Teachers'      => CB_PLUGIN_DIR . 'includes/class-cpt-teachers.php',
        'Core/Taxonomy_Category' => CB_PLUGIN_DIR . 'includes/class-taxonomy-category.php',
        'Core/Ajax_Handler'      => CB_PLUGIN_DIR . 'includes/class-ajax-handler.php',
        'Admin/Admin'            => CB_PLUGIN_DIR . 'admin/class-admin.php',
    ];
    if ( isset( $map[ $relative ] ) ) {
        require_once $map[ $relative ];
    }
} );

// ── Activation / Deactivation ─────────────────────────────────────────────────
register_activation_hook( __FILE__, 'cb_activate' );
register_deactivation_hook( __FILE__, 'cb_deactivate' );

function cb_activate(): void {
    require_once CB_PLUGIN_DIR . 'includes/class-cpt-courses.php';
    require_once CB_PLUGIN_DIR . 'includes/class-cpt-teachers.php';
    require_once CB_PLUGIN_DIR . 'includes/class-taxonomy-category.php';
    CB\Core\CPT_Courses::register();
    CB\Core\CPT_Teachers::register();
    CB\Core\Taxonomy_Category::register();
    flush_rewrite_rules();
    cb_install_tables();
}

function cb_deactivate(): void {
    flush_rewrite_rules();
}

function cb_install_tables(): void {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cb_subcategories (
        id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        category_id BIGINT UNSIGNED NOT NULL,
        name        VARCHAR(255)    NOT NULL,
        slug        VARCHAR(255)    NOT NULL,
        sort_order  INT             NOT NULL DEFAULT 0,
        created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY category_id (category_id)
    ) $charset;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}

// ── Bootstrap ─────────────────────────────────────────────────────────────────
add_action( 'plugins_loaded', function (): void {
    require_once CB_PLUGIN_DIR . 'includes/class-plugin.php';
    CB\Core\Plugin::instance()->init();
} );
