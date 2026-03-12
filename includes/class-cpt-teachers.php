<?php
namespace CB\Core;

defined( 'ABSPATH' ) || exit;

class CPT_Teachers {

    public static function register(): void {
        register_post_type( 'cb_teacher', [
            'label'           => __( 'Teachers', 'course-builder' ),
            'labels'          => self::labels(),
            'public'          => false,
            'show_ui'         => false,
            'show_in_menu'    => false,
            'show_in_rest'    => true,
            'supports'        => [ 'title', 'editor', 'thumbnail', 'custom-fields' ],
            'capability_type' => 'post',
            'map_meta_cap'    => true,
        ] );
    }

    private static function labels(): array {
        return [
            'name'          => _x( 'Teachers', 'post type general name', 'course-builder' ),
            'singular_name' => _x( 'Teacher', 'post type singular name', 'course-builder' ),
            'add_new_item'  => __( 'Add New Teacher', 'course-builder' ),
            'edit_item'     => __( 'Edit Teacher', 'course-builder' ),
            'menu_name'     => __( 'Teachers', 'course-builder' ),
        ];
    }

    public static function get_all(): array {
        return get_posts( [
            'post_type'      => 'cb_teacher',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ] );
    }

    public static function save( array $data ): int|\WP_Error {
        $post_data = [
            'post_type'    => 'cb_teacher',
            'post_status'  => 'publish',
            'post_title'   => sanitize_text_field( $data['name'] ?? '' ),
            'post_content' => wp_kses_post( $data['description'] ?? '' ),
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

        if ( isset( $data['designation'] ) ) {
            update_post_meta( $post_id, '_cb_designation', sanitize_text_field( $data['designation'] ) );
        }
        if ( isset( $data['photo_id'] ) ) {
            update_post_meta( $post_id, '_cb_photo_id', absint( $data['photo_id'] ) );
            set_post_thumbnail( $post_id, absint( $data['photo_id'] ) );
        }
        if ( isset( $data['categories'] ) ) {
            $cats = array_map( 'absint', (array) $data['categories'] );
            update_post_meta( $post_id, '_cb_categories', wp_json_encode( $cats ) );
        }

        return $post_id;
    }

    public static function get_formatted(): array {
        $teachers = self::get_all();
        $result   = [];
        foreach ( $teachers as $t ) {
            $photo_id   = (int) get_post_meta( $t->ID, '_cb_photo_id', true );
            $categories = json_decode( get_post_meta( $t->ID, '_cb_categories', true ) ?: '[]', true );
            $cat_names  = [];
            foreach ( $categories as $cat_id ) {
                $term = get_term( $cat_id, 'cb_category' );
                if ( $term && ! is_wp_error( $term ) ) {
                    $cat_names[] = $term->name;
                }
            }
            $result[] = [
                'id'          => $t->ID,
                'name'        => $t->post_title,
                'designation' => get_post_meta( $t->ID, '_cb_designation', true ),
                'description' => $t->post_content,
                'photo_url'   => $photo_id ? wp_get_attachment_image_url( $photo_id, 'thumbnail' ) : '',
                'photo_id'    => $photo_id,
                'categories'  => $categories,
                'cat_names'   => implode( ', ', $cat_names ),
            ];
        }
        return $result;
    }
}
