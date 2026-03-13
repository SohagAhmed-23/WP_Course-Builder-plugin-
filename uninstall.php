<?php
/**
 * Course Builder — Uninstall Handler
 *
 * Triggered ONLY when admin clicks "Delete" on the Plugins screen.
 * NOT triggered on deactivation.
 *
 * ── Complete DB Audit ────────────────────────────────────────────────────────
 *
 * POSTS (post_type = cb_course, cb_teacher)
 *   wp_posts           → all cb_course + cb_teacher rows
 *   wp_postmeta        → ALL meta for those posts, including:
 *                         _cb_subtitle, _cb_teacher_id, _cb_wc_product_id,
 *                         _cb_age_min, _cb_age_max, _cb_duration_months,
 *                         _cb_live_classes, _cb_learning_objectives,
 *                         _cb_course_content, _cb_additional_support,
 *                         _cb_designation, _cb_photo_id, _cb_categories,
 *                         _thumbnail_id  (set by set_post_thumbnail)
 *   wp_term_relationships → post ↔ cb_category links
 *
 * TAXONOMY (taxonomy = cb_category)
 *   wp_terms           → category term name rows
 *   wp_term_taxonomy   → taxonomy registration rows
 *   wp_termmeta        → cb_image_id (featured image per category)
 *   wp_term_relationships → any remaining category ↔ post links
 *
 * CUSTOM TABLE
 *   {prefix}cb_subcategories → all subcategory rows (DROP TABLE)
 *
 * WP_OPTIONS
 *   cb_seeded, cb_version, cb_db_version
 *
 * NOTE: Media Library attachments (uploaded photos / category images) are
 * intentionally NOT deleted — they belong to the site's media library and
 * may be reused by other plugins or themes. Admins can clean these manually
 * via Media → Library if desired.
 * ────────────────────────────────────────────────────────────────────────────
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * SAFETY GUARD — Data is only deleted if the admin explicitly opted in
 * via Settings → Course Builder → "Delete all data on uninstall" checkbox.
 *
 * When updating the plugin (even via zip upload), WordPress calls uninstall.php.
 * Without this guard every update would wipe all courses, teachers and categories.
 *
 * To enable full cleanup on delete, set the option before deleting the plugin:
 *   update_option( 'cb_delete_data_on_uninstall', 1 );
 */
if ( ! get_option( 'cb_delete_data_on_uninstall' ) ) {
    // DATA PRESERVED — do not delete courses, teachers or categories.
    // Keep cb_version so the reinstall version-check triggers a rewrite flush.
    // This is the default safe mode used on every update / zip re-upload.
    exit;
}


global $wpdb;

/* ── 1. All cb_course + cb_teacher posts ─────────────────────────────────── */
$post_ids = $wpdb->get_col(
    "SELECT ID FROM {$wpdb->posts}
     WHERE post_type IN ('cb_course', 'cb_teacher')"
);

if ( ! empty( $post_ids ) ) {
    $id_list = implode( ',', array_map( 'intval', $post_ids ) );

    // All postmeta (covers every _cb_* key + _thumbnail_id)
    $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE post_id IN ($id_list)" );

    // Term–post relationships for these posts
    $wpdb->query( "DELETE FROM {$wpdb->term_relationships} WHERE object_id IN ($id_list)" );

    // The posts themselves
    $wpdb->query( "DELETE FROM {$wpdb->posts} WHERE ID IN ($id_list)" );
}

/* ── 2. cb_category taxonomy ─────────────────────────────────────────────── */
// Use direct SQL — taxonomy is NOT registered during uninstall so
// get_terms() / wp_delete_term() are unreliable here.
$term_ids = $wpdb->get_col(
    "SELECT tt.term_id
     FROM {$wpdb->term_taxonomy} AS tt
     WHERE tt.taxonomy = 'cb_category'"
);

if ( ! empty( $term_ids ) ) {
    $tid_list = implode( ',', array_map( 'intval', $term_ids ) );

    // Term meta (cb_image_id etc.)
    $wpdb->query( "DELETE FROM {$wpdb->termmeta} WHERE term_id IN ($tid_list)" );

    // term_taxonomy rows
    $wpdb->query(
        "DELETE FROM {$wpdb->term_taxonomy}
         WHERE term_id IN ($tid_list) AND taxonomy = 'cb_category'"
    );

    // Base term rows (only if they have no other taxonomy left)
    // Safe to delete directly since cb_category is plugin-only
    $wpdb->query( "DELETE FROM {$wpdb->terms} WHERE term_id IN ($tid_list)" );
}

/* ── 3. Orphaned term_relationships for cb_category ─────────────────────── */
// Belt-and-suspenders: catch any relationships not caught in step 1
// (e.g. if cb_category was ever attached to other post types)
$wpdb->query(
    "DELETE tr
     FROM {$wpdb->term_relationships} AS tr
     INNER JOIN {$wpdb->term_taxonomy} AS tt
        ON tr.term_taxonomy_id = tt.term_taxonomy_id
     WHERE tt.taxonomy = 'cb_category'"
);

/* ── 4. Drop custom subcategories table ──────────────────────────────────── */
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}cb_subcategories" );

/* ── 5. Plugin options ───────────────────────────────────────────────────── */
$options = [
    'cb_seeded',
    'cb_version',
    'cb_db_version',
];
foreach ( $options as $key ) {
    delete_option( $key );
    delete_site_option( $key ); // multisite safety
}

/* ── 6. Rewrite rules ────────────────────────────────────────────────────── */
// Remove any rewrite rules the CPTs registered so they
// don't linger in the DB after deletion.
delete_option( 'rewrite_rules' );

/* ── 7. Object cache ─────────────────────────────────────────────────────── */
wp_cache_flush();
