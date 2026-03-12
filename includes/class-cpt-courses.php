<?php
namespace CB\Core;

defined( 'ABSPATH' ) || exit;

class CPT_Courses {

    public static function register(): void {
        register_post_type( 'cb_course', [
            'label'               => __( 'Courses', 'course-builder' ),
            'labels'              => self::labels(),
            'public'              => true,
            'show_ui'             => false, // We use our own admin UI
            'show_in_menu'        => false,
            'show_in_rest'        => true,
            'supports'            => [ 'title', 'editor', 'thumbnail', 'custom-fields' ],
            'has_archive'         => true,
            'rewrite'             => [ 'slug' => 'course' ],
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
        ] );
    }

    private static function labels(): array {
        return [
            'name'               => _x( 'Courses', 'post type general name', 'course-builder' ),
            'singular_name'      => _x( 'Course', 'post type singular name', 'course-builder' ),
            'add_new'            => __( 'Add New', 'course-builder' ),
            'add_new_item'       => __( 'Add New Course', 'course-builder' ),
            'edit_item'          => __( 'Edit Course', 'course-builder' ),
            'new_item'           => __( 'New Course', 'course-builder' ),
            'view_item'          => __( 'View Course', 'course-builder' ),
            'search_items'       => __( 'Search Courses', 'course-builder' ),
            'not_found'          => __( 'No courses found.', 'course-builder' ),
            'not_found_in_trash' => __( 'No courses found in Trash.', 'course-builder' ),
            'menu_name'          => __( 'Courses', 'course-builder' ),
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Returns all courses as WP_Post objects.
     */
    public static function get_all( array $args = [] ): array {
        $defaults = [
            'post_type'      => 'cb_course',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];
        return get_posts( array_merge( $defaults, $args ) );
    }

    /**
     * Get courses for paginated table view.
     */
    public static function get_paginated( int $page = 1, int $per_page = 10, string $search = '', int $category_id = 0 ): array {
        $args = [
            'post_type'      => 'cb_course',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        if ( $search ) {
            $args['s'] = sanitize_text_field( $search );
        }

        if ( $category_id > 0 ) {
            $args['tax_query'] = [ [
                'taxonomy' => 'cb_category',
                'field'    => 'term_id',
                'terms'    => $category_id,
            ] ];
        }

        $query = new \WP_Query( $args );
        return [
            'posts'       => $query->posts,
            'total'       => $query->found_posts,
            'total_pages' => $query->max_num_pages,
        ];
    }

    /**
     * Save/update a course with all meta fields.
     */
    public static function save( array $data ): int|\WP_Error {
        $post_data = [
            'post_type'   => 'cb_course',
            'post_status' => 'publish',
            'post_title'  => sanitize_text_field( $data['title'] ?? '' ),
            'post_content'=> wp_kses_post( $data['description'] ?? '' ),
        ];

        if ( ! empty( $data['id'] ) ) {
            $post_data['ID'] = absint( $data['id'] );
            $post_id = wp_update_post( $post_data, true );
        } else {
            $post_id = wp_insert_post( $post_data, true );
        }

        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        // Meta fields
        $meta_keys = [
            'subtitle', 'wc_product_id', 'teacher_id', 'age_min', 'duration_months', 'live_classes', 'video_url', 'youtube_url',
        ];
        foreach ( $meta_keys as $key ) {
            if ( isset( $data[ $key ] ) ) {
                update_post_meta( $post_id, '_cb_' . $key, sanitize_text_field( $data[ $key ] ) );
            }
        }

        // JSON-encoded repeater fields
        $json_keys = [ 'learning_objectives', 'programme_overview', 'course_content', 'additional_support' ];
        foreach ( $json_keys as $key ) {
            if ( isset( $data[ $key ] ) ) {
                update_post_meta( $post_id, '_cb_' . $key, wp_json_encode( $data[ $key ] ) );
            }
        }

        // Category taxonomy
        if ( ! empty( $data['category_id'] ) ) {
            wp_set_post_terms( $post_id, [ absint( $data['category_id'] ) ], 'cb_category' );
        }

        return $post_id;
    }
}
