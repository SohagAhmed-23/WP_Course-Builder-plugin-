<?php
namespace CB\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Registers and handles all wp_ajax_* hooks.
 * Every action is prefixed with "cb_".
 */
class Ajax_Handler {

    public function register_hooks(): void {
        $actions = [
            // Courses
            'cb_save_course',
            'cb_delete_course',
            'cb_get_course',
            'cb_get_courses',
            // Categories
            'cb_save_category',
            'cb_delete_category',
            // Teachers
            'cb_save_teacher',
            'cb_delete_teacher',
            // Misc
            'cb_get_wc_products',
        ];

        foreach ( $actions as $action ) {
            add_action( 'wp_ajax_' . $action, [ $this, 'dispatch' ] );
        }
    }

    public function dispatch(): void {
        $action = sanitize_key( $_REQUEST['action'] ?? '' );
        $method = 'handle_' . str_replace( 'cb_', '', $action );

        if ( ! method_exists( $this, $method ) ) {
            wp_send_json_error( [ 'message' => 'Unknown action.' ], 400 );
        }

        $this->$method();
    }

    // ── Security helper ───────────────────────────────────────────────────────

    private function verify( string $nonce_action = 'cb_admin_nonce' ): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Unauthorized.' ], 403 );
        }
        if ( ! check_ajax_referer( $nonce_action, 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => 'Nonce verification failed.' ], 403 );
        }
    }

    // ── Courses ───────────────────────────────────────────────────────────────

    private function handle_get_courses(): void {
        $this->verify();
        $page     = absint( $_POST['page'] ?? 1 );
        $per_page = absint( $_POST['per_page'] ?? 10 );
        $search   = sanitize_text_field( $_POST['search'] ?? '' );
        $cat_id   = absint( $_POST['category_id'] ?? 0 );

        $data = CPT_Courses::get_paginated( $page, $per_page, $search, $cat_id );

        $courses = [];
        foreach ( $data['posts'] as $post ) {
            $teacher_id = (int) get_post_meta( $post->ID, '_cb_teacher_id', true );
            $teacher    = $teacher_id ? get_post( $teacher_id ) : null;
            $terms      = wp_get_post_terms( $post->ID, 'cb_category' );
            $courses[]  = [
                'id'          => $post->ID,
                'title'       => $post->post_title,
                'subtitle'    => get_post_meta( $post->ID, '_cb_subtitle', true ),
                'category'    => ! empty( $terms ) ? $terms[0]->name : '—',
                'teacher'     => $teacher ? $teacher->post_title : '—',
                'date'        => get_the_date( 'M j, Y', $post ),
            'age_min'     => get_post_meta( $post->ID, '_cb_age_min', true ),
            'duration'    => get_post_meta( $post->ID, '_cb_duration_months', true ),
            'live_classes'=> get_post_meta( $post->ID, '_cb_live_classes', true ),
            ];
        }

        wp_send_json_success( [
            'courses'     => $courses,
            'total'       => $data['total'],
            'total_pages' => $data['total_pages'],
        ] );
    }

    private function handle_get_course(): void {
        $this->verify();
        $id   = absint( $_POST['id'] ?? 0 );
        $post = get_post( $id );
        if ( ! $post || $post->post_type !== 'cb_course' ) {
            wp_send_json_error( [ 'message' => 'Course not found.' ] );
        }
        $terms = wp_get_post_terms( $post->ID, 'cb_category' );
        wp_send_json_success( [
            'id'                  => $post->ID,
            'title'               => $post->post_title,
            'subtitle'            => get_post_meta( $post->ID, '_cb_subtitle', true ),
            'category_id'         => ! empty( $terms ) ? $terms[0]->term_id : 0,
            'teacher_id'          => get_post_meta( $post->ID, '_cb_teacher_id', true ),
            'wc_product_id'       => get_post_meta( $post->ID, '_cb_wc_product_id', true ),
            'learning_objectives' => json_decode( get_post_meta( $post->ID, '_cb_learning_objectives', true ) ?: '[]', true ),
            'programme_overview'  => json_decode( get_post_meta( $post->ID, '_cb_programme_overview', true ) ?: '[]', true ),
            'course_content'      => json_decode( get_post_meta( $post->ID, '_cb_course_content', true ) ?: '[]', true ),
            'additional_support'  => json_decode( get_post_meta( $post->ID, '_cb_additional_support', true ) ?: '[]', true ),
            'age_min'             => get_post_meta( $post->ID, '_cb_age_min', true ),
            'video_url'           => get_post_meta( $post->ID, '_cb_video_url', true ),
            'youtube_url'         => get_post_meta( $post->ID, '_cb_youtube_url', true ),
            'duration_months'     => get_post_meta( $post->ID, '_cb_duration_months', true ),
            'live_classes'        => get_post_meta( $post->ID, '_cb_live_classes', true ),
        ] );
    }

    private function handle_save_course(): void {
        $this->verify();
        $data = [
            'id'                  => absint( $_POST['id'] ?? 0 ),
            'title'               => sanitize_text_field( $_POST['title'] ?? '' ),
            'subtitle'            => sanitize_text_field( $_POST['subtitle'] ?? '' ),
            'category_id'         => absint( $_POST['category_id'] ?? 0 ),
            'teacher_id'          => absint( $_POST['teacher_id'] ?? 0 ),
            'wc_product_id'       => absint( $_POST['wc_product_id'] ?? 0 ),
            'age_min'             => absint( $_POST['age_min'] ?? 0 ),
            'video_url'           => esc_url_raw( $_POST['video_url'] ?? '' ),
            'youtube_url'         => esc_url_raw( $_POST['youtube_url'] ?? '' ),
            'duration_months'     => absint( $_POST['duration_months'] ?? 0 ),
            'live_classes'        => absint( $_POST['live_classes'] ?? 0 ),
            'learning_objectives' => $this->sanitize_array( $_POST['learning_objectives'] ?? [] ),
            'programme_overview'  => $this->sanitize_array( $_POST['programme_overview'] ?? [] ),
            'course_content'      => $this->sanitize_nested( $_POST['course_content'] ?? [] ),
            'additional_support'  => $this->sanitize_array( $_POST['additional_support'] ?? [] ),
        ];

        if ( empty( $data['title'] ) ) {
            wp_send_json_error( [ 'message' => 'Course title is required.' ] );
        }

        // Map field names for meta saving
        $data['subtitle']           = $data['subtitle'];
        $data['learning_objectives']= $data['learning_objectives'];
        $data['course_content']     = $data['course_content'];
        $data['additional_support'] = $data['additional_support'];

        $post_id = CPT_Courses::save( $data );

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( [ 'message' => $post_id->get_error_message() ] );
        }

        // Save teacher separately (uses different meta key)
        update_post_meta( $post_id, '_cb_teacher_id', $data['teacher_id'] );
        update_post_meta( $post_id, '_cb_wc_product_id', $data['wc_product_id'] );

        wp_send_json_success( [
            'message' => $data['id'] ? 'Course updated.' : 'Course created.',
            'id'      => $post_id,
        ] );
    }

    private function handle_delete_course(): void {
        $this->verify();
        $id = absint( $_POST['id'] ?? 0 );
        if ( ! $id ) {
            wp_send_json_error( [ 'message' => 'Invalid ID.' ] );
        }
        $result = wp_delete_post( $id, true );
        if ( $result ) {
            wp_send_json_success( [ 'message' => 'Course deleted.' ] );
        }
        wp_send_json_error( [ 'message' => 'Failed to delete course.' ] );
    }

    // ── Categories ────────────────────────────────────────────────────────────

    private function handle_save_category(): void {
        $this->verify();
        $data = [
            'id'            => absint( $_POST['id'] ?? 0 ),
            'name'          => sanitize_text_field( $_POST['name'] ?? '' ),
            'description'   => sanitize_textarea_field( $_POST['description'] ?? '' ),
            'slug'          => sanitize_title( $_POST['slug'] ?? '' ),
            'image_id'      => absint( $_POST['image_id'] ?? 0 ),
            'subcategories' => $this->sanitize_nested( $_POST['subcategories'] ?? [] ),
        ];

        if ( empty( $data['name'] ) ) {
            wp_send_json_error( [ 'message' => 'Category name is required.' ] );
        }

        $result = Taxonomy_Category::save( $data );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        }

        wp_send_json_success( [
            'message' => $data['id'] ? 'Category updated.' : 'Category created.',
            'id'      => $result,
        ] );
    }

    private function handle_delete_category(): void {
        $this->verify();
        $id = absint( $_POST['id'] ?? 0 );
        if ( ! $id ) {
            wp_send_json_error( [ 'message' => 'Invalid ID.' ] );
        }
        global $wpdb;
        $result = wp_delete_term( $id, 'cb_category' );
        $wpdb->delete( $wpdb->prefix . 'cb_subcategories', [ 'category_id' => $id ] );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        }
        wp_send_json_success( [ 'message' => 'Category deleted.' ] );
    }

    // ── Teachers ──────────────────────────────────────────────────────────────

    private function handle_save_teacher(): void {
        $this->verify();
        $data = [
            'id'          => absint( $_POST['id'] ?? 0 ),
            'name'        => sanitize_text_field( $_POST['name'] ?? '' ),
            'designation' => sanitize_text_field( $_POST['designation'] ?? '' ),
            'photo_id'    => absint( $_POST['photo_id'] ?? 0 ),
            'description' => sanitize_textarea_field( $_POST['description'] ?? '' ),
            'categories'  => array_map( 'absint', (array) ( $_POST['categories'] ?? [] ) ),
        ];

        if ( empty( $data['name'] ) ) {
            wp_send_json_error( [ 'message' => 'Teacher name is required.' ] );
        }

        $post_id = CPT_Teachers::save( $data );

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( [ 'message' => $post_id->get_error_message() ] );
        }

        wp_send_json_success( [
            'message' => $data['id'] ? 'Teacher updated.' : 'Teacher created.',
            'id'      => $post_id,
        ] );
    }

    private function handle_delete_teacher(): void {
        $this->verify();
        $id = absint( $_POST['id'] ?? 0 );
        if ( ! $id ) {
            wp_send_json_error( [ 'message' => 'Invalid ID.' ] );
        }
        $result = wp_delete_post( $id, true );
        if ( $result ) {
            wp_send_json_success( [ 'message' => 'Teacher deleted.' ] );
        }
        wp_send_json_error( [ 'message' => 'Failed to delete teacher.' ] );
    }

    // ── WooCommerce Products ──────────────────────────────────────────────────

    private function handle_get_wc_products(): void {
        $this->verify();
        $exclude_course = absint( $_POST['exclude_course'] ?? 0 );

        if ( ! function_exists( 'wc_get_products' ) ) {
            wp_send_json_success( [ 'products' => [], 'message' => 'WooCommerce not active.' ] );
        }

        // Find all WC product IDs already assigned to OTHER courses
        $assigned = get_posts( [
            'post_type'      => 'cb_course',
            'posts_per_page' => -1,
            'post__not_in'   => $exclude_course ? [ $exclude_course ] : [],
            'fields'         => 'ids',
        ] );

        $used_ids = [];
        foreach ( $assigned as $cid ) {
            $pid = get_post_meta( $cid, '_cb_wc_product_id', true );
            if ( $pid ) $used_ids[] = (int) $pid;
        }

        $products = wc_get_products( [
            'status' => 'publish',
            'limit'  => -1,
        ] );

        $result = [];
        foreach ( $products as $product ) {
            if ( in_array( $product->get_id(), $used_ids, true ) ) continue;
            $result[] = [
                'id'    => $product->get_id(),
                'name'  => $product->get_name(),
                'price' => $product->get_price_html(),
            ];
        }

        wp_send_json_success( [ 'products' => $result ] );
    }

    // ── Sanitization helpers ──────────────────────────────────────────────────

    private function sanitize_array( $arr ): array {
        if ( ! is_array( $arr ) ) return [];
        return array_filter( array_map( 'sanitize_text_field', $arr ) );
    }

    private function sanitize_nested( $arr ): array {
        if ( ! is_array( $arr ) ) return [];
        $result = [];
        foreach ( $arr as $item ) {
            if ( is_array( $item ) ) {
                $result[] = array_map( 'sanitize_text_field', $item );
            } else {
                $result[] = sanitize_text_field( $item );
            }
        }
        return $result;
    }
}
