<?php
namespace CB\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Seed Data — kept for reference / manual use only.
 * No longer called automatically on activation.
 *
 * To wipe all plugin data inserted by the old seed, go to:
 *   WordPress Admin → Course Builder → (any page)
 * and add ?cb_purge_seed=1 to the URL while logged in as admin,
 * e.g. admin.php?page=course-builder&cb_purge_seed=1
 */
class Seed_Data {

    /**
     * Purge all cb_course and cb_teacher posts and cb_category terms.
     * Triggered manually via URL param for cleanup after accidental seeding.
     */
    public static function purge(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        global $wpdb;

        // Delete all cb_course posts
        $course_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'cb_course'" );
        foreach ( $course_ids as $id ) {
            wp_delete_post( (int) $id, true );
        }

        // Delete all cb_teacher posts
        $teacher_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'cb_teacher'" );
        foreach ( $teacher_ids as $id ) {
            wp_delete_post( (int) $id, true );
        }

        // Delete all cb_category terms
        $terms = get_terms( [ 'taxonomy' => 'cb_category', 'hide_empty' => false, 'fields' => 'ids' ] );
        if ( ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term_id ) {
                wp_delete_term( (int) $term_id, 'cb_category' );
            }
        }

        // Truncate subcategories table
        $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}cb_subcategories" );

        // Clear the seeded flag
        delete_option( 'cb_seeded' );
    }
}
