<?php
namespace CB\Core;

defined( 'ABSPATH' ) || exit;

class Taxonomy_Category {

    public static function register(): void {
        register_taxonomy( 'cb_category', [ 'cb_course' ], [
            'label'             => __( 'Course Categories', 'course-builder' ),
            'labels'            => self::labels(),
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => false,
            'show_admin_column' => false,
            'show_in_rest'      => true,
            'rewrite'           => [ 'slug' => 'course-category' ],
        ] );
    }

    private static function labels(): array {
        return [
            'name'              => _x( 'Course Categories', 'taxonomy general name', 'course-builder' ),
            'singular_name'     => _x( 'Course Category', 'taxonomy singular name', 'course-builder' ),
            'add_new_item'      => __( 'Add New Category', 'course-builder' ),
            'edit_item'         => __( 'Edit Category', 'course-builder' ),
            'menu_name'         => __( 'Categories', 'course-builder' ),
        ];
    }

    /**
     * Get all categories with their meta (image, description, subcategories).
     */
    public static function get_all_formatted(): array {
        global $wpdb;

        $terms = get_terms( [
            'taxonomy'   => 'cb_category',
            'hide_empty' => false,
        ] );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return [];
        }

        $result = [];
        foreach ( $terms as $term ) {
            $image_id   = (int) get_term_meta( $term->term_id, 'cb_image_id', true );
            $subcats    = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}cb_subcategories WHERE category_id = %d ORDER BY sort_order ASC",
                    $term->term_id
                ),
                ARRAY_A
            );
            $result[] = [
                'id'           => $term->term_id,
                'name'         => $term->name,
                'slug'         => $term->slug,
                'description'  => $term->description,
                'image_url'    => $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '',
                'image_id'     => $image_id,
                'course_count' => $term->count,
                'subcategories'=> $subcats,
            ];
        }
        return $result;
    }

    public static function save( array $data ): int|\WP_Error {
        global $wpdb;

        $args = [
            'description' => wp_kses_post( $data['description'] ?? '' ),
            'slug'        => sanitize_title( $data['slug'] ?? $data['name'] ),
        ];

        if ( ! empty( $data['id'] ) ) {
            $result = wp_update_term( absint( $data['id'] ), 'cb_category', array_merge( $args, [
                'name' => sanitize_text_field( $data['name'] ),
            ] ) );
        } else {
            $result = wp_insert_term( sanitize_text_field( $data['name'] ), 'cb_category', $args );
        }

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        $term_id = $result['term_id'] ?? absint( $data['id'] );

        // Save featured image
        if ( ! empty( $data['image_id'] ) ) {
            update_term_meta( $term_id, 'cb_image_id', absint( $data['image_id'] ) );
        }

        // Save subcategories (replace all)
        $wpdb->delete( $wpdb->prefix . 'cb_subcategories', [ 'category_id' => $term_id ] );
        if ( ! empty( $data['subcategories'] ) && is_array( $data['subcategories'] ) ) {
            foreach ( $data['subcategories'] as $i => $subcat ) {
                $name = sanitize_text_field( $subcat['name'] ?? '' );
                if ( ! $name ) continue;
                $wpdb->insert( $wpdb->prefix . 'cb_subcategories', [
                    'category_id' => $term_id,
                    'name'        => $name,
                    'slug'        => sanitize_title( $name ),
                    'sort_order'  => (int) $i,
                ] );
            }
        }

        return $term_id;
    }
}
