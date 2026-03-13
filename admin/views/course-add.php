<?php
defined( 'ABSPATH' ) || exit;

$edit_id    = absint( $_GET['id'] ?? 0 );
$is_edit    = $edit_id > 0;
$page_title = $is_edit ? __( 'Edit Course', 'course-builder' ) : __( 'Add New Course', 'course-builder' );

$categories = get_terms( [ 'taxonomy' => 'cb_category', 'hide_empty' => false ] );
$teachers   = \CB\Core\CPT_Teachers::get_formatted();


?>
<div class="cb-wrap">

    <!-- Page Header -->
    <div class="cb-page-header cb-page-header--gradient">
        <div class="cb-page-header__left">
            <div class="cb-page-header__icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            </div>
            <div>
                <h1><?php echo esc_html( $page_title ); ?></h1>
                <p><?php _e( 'Fill in the details below to create a rich course experience.', 'course-builder' ); ?></p>
            </div>
        </div>
        <a href="<?php echo admin_url( 'admin.php?page=course-builder' ); ?>" class="cb-btn cb-btn--ghost">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            <?php _e( 'Back to Courses', 'course-builder' ); ?>
        </a>
    </div>

    <form id="cb-course-form" data-edit-id="<?php echo esc_attr( $edit_id ); ?>">
        <?php wp_nonce_field( 'cb_admin_nonce', 'nonce' ); ?>
        <input type="hidden" name="id" id="cb-course-id" value="<?php echo esc_attr( $edit_id ); ?>">

        <div class="cb-form-layout">

            <!-- LEFT COLUMN -->
            <div class="cb-form-main">

                <!-- Basic Info -->
                <div class="cb-card cb-card--accent-blue">
                    <div class="cb-card__header">
                        <div class="cb-card__header-icon cb-icon-bg--blue">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="17" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="17" y1="18" x2="3" y2="18"/></svg>
                        </div>
                        <div>
                            <h2><?php _e( 'Basic Information', 'course-builder' ); ?></h2>
                            <p><?php _e( 'The core identity of your course.', 'course-builder' ); ?></p>
                        </div>
                    </div>
                    <div class="cb-card__body">
                        <div class="cb-field">
                            <label class="cb-label" for="cb-title">
                                <?php _e( 'Course Title', 'course-builder' ); ?> <span class="cb-required">*</span>
                            </label>
                            <input type="text" id="cb-title" name="title" class="cb-input cb-input--lg" placeholder="e.g. Jolly English: Listening & Speaking Mastery" required>
                        </div>
                        <div class="cb-field">
                            <label class="cb-label" for="cb-subtitle"><?php _e( 'Subtitle', 'course-builder' ); ?></label>
                            <input type="text" id="cb-subtitle" name="subtitle" class="cb-input" placeholder="A short tagline that sells the course">
                        </div>


                    </div>
                </div>

                <!-- Learning Objectives -->
                <div class="cb-card cb-card--accent-green">
                    <div class="cb-card__header">
                        <div class="cb-card__header-icon cb-icon-bg--green">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        </div>
                        <div>
                            <h2><?php _e( 'Learning Objectives', 'course-builder' ); ?></h2>
                            <p><?php _e( 'What will students achieve by the end of this course?', 'course-builder' ); ?></p>
                        </div>
                    </div>
                    <div class="cb-card__body">
                        <div id="cb-objectives-list" class="cb-repeater"></div>
                        <button type="button" class="cb-btn cb-btn--outline cb-btn--sm" id="cb-add-objective">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            <?php _e( 'Add Objective', 'course-builder' ); ?>
                        </button>
                    </div>
                </div>

                <!-- Programme Overview -->
                <div class="cb-card cb-card--accent-teal">
                    <div class="cb-card__header">
                        <div class="cb-card__header-icon cb-icon-bg--teal">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 9h6M9 12h6M9 15h4"/></svg>
                        </div>
                        <div>
                            <h2><?php _e( 'Programme Overview', 'course-builder' ); ?></h2>
                            <p><?php _e( 'Key highlights and overview points of this programme.', 'course-builder' ); ?></p>
                        </div>
                    </div>
                    <div class="cb-card__body">
                        <div id="cb-overview-list" class="cb-repeater"></div>
                        <button type="button" class="cb-btn cb-btn--outline cb-btn--sm" id="cb-add-overview">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            <?php _e( 'Add Overview Point', 'course-builder' ); ?>
                        </button>
                    </div>
                </div>

                <!-- Course Content Units -->
                <div class="cb-card cb-card--accent-violet">
                    <div class="cb-card__header">
                        <div class="cb-card__header-icon cb-icon-bg--violet">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                        </div>
                        <div>
                            <h2><?php _e( 'Course Content', 'course-builder' ); ?></h2>
                            <p><?php _e( 'Organise your course into units with lesson summaries.', 'course-builder' ); ?></p>
                        </div>
                    </div>
                    <div class="cb-card__body">
                        <div id="cb-units-list" class="cb-repeater cb-repeater--units"></div>
                        <button type="button" class="cb-btn cb-btn--outline-violet cb-btn--sm" id="cb-add-unit">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            <?php _e( 'Add Unit', 'course-builder' ); ?>
                        </button>
                    </div>
                </div>

                <!-- Additional Support -->
                <div class="cb-card cb-card--accent-orange">
                    <div class="cb-card__header">
                        <div class="cb-card__header-icon cb-icon-bg--orange">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        </div>
                        <div>
                            <h2><?php _e( 'Additional Support', 'course-builder' ); ?></h2>
                            <p><?php _e( 'Extra resources, perks, or support included with this course.', 'course-builder' ); ?></p>
                        </div>
                    </div>
                    <div class="cb-card__body">
                        <div id="cb-support-list" class="cb-repeater"></div>
                        <button type="button" class="cb-btn cb-btn--outline-orange cb-btn--sm" id="cb-add-support">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            <?php _e( 'Add Support Item', 'course-builder' ); ?>
                        </button>
                    </div>
                </div>

            </div>

            <!-- RIGHT COLUMN — Sidebar -->
            <div class="cb-form-sidebar">

                <!-- Publish -->
                <div class="cb-card cb-card--publish">
                    <div class="cb-card__body">
                        <div id="cb-form-status" class="cb-form-status" style="display:none"></div>
                        <button type="submit" class="cb-btn cb-btn--primary cb-btn--full cb-btn--lg" id="cb-save-btn">
                            <span class="cb-btn__text">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                <?php echo $is_edit ? esc_html__( 'Update Course', 'course-builder' ) : esc_html__( 'Publish Course', 'course-builder' ); ?>
                            </span>
                            <span class="cb-btn__loader" style="display:none">
                                <div class="cb-spinner cb-spinner--sm cb-spinner--white"></div>
                                <?php _e( 'Saving…', 'course-builder' ); ?>
                            </span>
                        </button>
                        <!-- Preview button — always rendered, URL set via JS after save -->
                        <div id="cb-preview-wrap" style="<?php echo $is_edit ? '' : 'display:none;'; ?>margin-top:10px">
                            <a href="<?php echo $is_edit ? esc_url( get_permalink( $edit_id ) ) : '#'; ?>"
                               target="_blank"
                               id="cb-preview-btn"
                               class="cb-preview-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                <?php _e( 'Preview Course Page', 'course-builder' ); ?>
                            </a>
                        </div>
                        <?php if ( $is_edit ) : ?>
                        <a href="<?php echo admin_url( 'admin.php?page=course-builder-add' ); ?>" class="cb-btn cb-btn--ghost cb-btn--full" style="margin-top:8px">
                            <?php _e( '+ Add Another Course', 'course-builder' ); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Course Details -->
                <div class="cb-card">
                    <div class="cb-card__header">
                        <div class="cb-card__header-icon cb-icon-bg--teal">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                        <div><h2><?php _e( 'Course Details', 'course-builder' ); ?></h2></div>
                    </div>
                    <div class="cb-card__body">

                        <!-- Minimum Age -->
                        <div class="cb-field">
                            <label class="cb-label" for="cb-age-min">
                                <svg class="cb-label-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                <?php _e( 'Minimum Age', 'course-builder' ); ?>
                            </label>
                            <div class="cb-input-with-unit">
                                <input type="number" id="cb-age-min" name="age_min" class="cb-input"
                                    min="1" max="99" placeholder="e.g. 4">
                                <span class="cb-input-unit">years</span>
                            </div>
                            <p class="cb-hint"><?php _e( 'Minimum age required to enroll in this course.', 'course-builder' ); ?></p>
                        </div>

                        <!-- Course Duration -->
                        <div class="cb-field">
                            <label class="cb-label" for="cb-duration">
                                <svg class="cb-label-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                <?php _e( 'Course Duration', 'course-builder' ); ?>
                            </label>
                            <div class="cb-input-with-unit">
                                <input type="number" id="cb-duration" name="duration_months" class="cb-input" min="1" max="120" placeholder="e.g. 3">
                                <span class="cb-input-unit">months</span>
                            </div>
                        </div>

                        <!-- Number of Live Classes -->
                        <div class="cb-field" style="margin-bottom:0">
                            <label class="cb-label" for="cb-live-classes">
                                <svg class="cb-label-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                                <?php _e( 'Number of Live Classes', 'course-builder' ); ?>
                            </label>
                            <div class="cb-input-with-unit">
                                <input type="number" id="cb-live-classes" name="live_classes" class="cb-input" min="0" max="999" placeholder="e.g. 12">
                                <span class="cb-input-unit">classes</span>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Organisation -->
                <div class="cb-card">
                    <div class="cb-card__header">
                        <div class="cb-card__header-icon cb-icon-bg--blue">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                        </div>
                        <div><h2><?php _e( 'Organisation', 'course-builder' ); ?></h2></div>
                    </div>
                    <div class="cb-card__body">
                        <div class="cb-field">
                            <label class="cb-label" for="cb-category"><?php _e( 'Department', 'course-builder' ); ?></label>
                            <select id="cb-category" name="category_id" class="cb-select cb-select--full">
                                <option value="0"><?php _e( '— Select Department —', 'course-builder' ); ?></option>
                                <?php if ( ! is_wp_error( $categories ) ) foreach ( $categories as $cat ) : ?>
                                    <option value="<?php echo esc_attr( $cat->term_id ); ?>"><?php echo esc_html( $cat->name ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="cb-field" style="margin-bottom:0">
                            <label class="cb-label" for="cb-teacher"><?php _e( 'Teacher', 'course-builder' ); ?></label>
                            <select id="cb-teacher" name="teacher_id" class="cb-select cb-select--full">
                                <option value="0"><?php _e( '— Select Teacher —', 'course-builder' ); ?></option>
                                <?php foreach ( $teachers as $teacher ) : ?>
                                    <option value="<?php echo esc_attr( $teacher['id'] ); ?>"><?php echo esc_html( $teacher['name'] ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Explainer Video -->
                <div class="cb-card">
                    <div class="cb-card__header">
                        <div class="cb-card__header-icon cb-icon-bg--teal">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                        </div>
                        <div><h2><?php _e( 'Explainer Video', 'course-builder' ); ?></h2></div>
                    </div>
                    <div class="cb-card__body">
                        <div class="cb-field" style="margin-bottom:0">
                            <label class="cb-label" for="cb-video-url"><?php _e( 'YouTube / Vimeo URL', 'course-builder' ); ?></label>
                            <input type="url" id="cb-video-url" name="video_url" class="cb-input" placeholder="https://youtube.com/watch?v=...">
                        </div>
                    </div>
                </div>

                <!-- WooCommerce Product -->
                <div class="cb-card">
                    <div class="cb-card__header">
                        <div class="cb-card__header-icon cb-icon-bg--indigo">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                        </div>
                        <div>
                            <h2><?php _e( 'WooCommerce Product', 'course-builder' ); ?></h2>
                        </div>
                    </div>
                    <div class="cb-card__body">
                        <div class="cb-field" style="margin-bottom:0">
                            <label class="cb-label" for="cb-wc-product"><?php _e( 'Select Product', 'course-builder' ); ?></label>
                            <select id="cb-wc-product" name="wc_product_id" class="cb-select cb-select--full">
                                <option value="0"><?php _e( '— Loading… —', 'course-builder' ); ?></option>
                            </select>
                            <p class="cb-hint"><?php _e( 'Only products not assigned to other courses are shown.', 'course-builder' ); ?></p>
                        </div>
                    </div>
                </div>

            </div><!-- .cb-form-sidebar -->
        </div><!-- .cb-form-layout -->
    </form>

</div>
