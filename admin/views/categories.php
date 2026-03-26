<?php
defined( 'ABSPATH' ) || exit;
$categories = \CB\Core\Taxonomy_Category::get_all_formatted();
?>
<div class="cb-wrap">

    <!-- Page Header -->
    <div class="cb-page-header">
        <div class="cb-page-header__left">
            <div class="cb-page-header__icon cb-page-header__icon--teal">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            </div>
            <div>
                <h1><?php _e( 'Departments', 'course-builder' ); ?></h1>
                <p><?php _e( 'Organise your courses with categories and subcategories.', 'course-builder' ); ?></p>
            </div>
        </div>
    </div>

    <div class="cb-two-col">

        <!-- LEFT — Form -->
        <div class="cb-form-col">
            <div class="cb-card">
                <div class="cb-card__header">
                    <h2 id="cb-cat-form-title"><?php _e( 'Add New Department', 'course-builder' ); ?></h2>
                </div>
                <div class="cb-card__body">
                    <form id="cb-category-form">
                        <?php wp_nonce_field( 'cb_admin_nonce', 'nonce' ); ?>
                        <input type="hidden" name="id" id="cb-cat-id" value="0">

                        <div class="cb-field">
                            <label class="cb-label" for="cb-cat-name"><?php _e( 'Name', 'course-builder' ); ?> <span class="cb-required">*</span></label>
                            <input type="text" id="cb-cat-name" name="name" class="cb-input" placeholder="e.g. Web Development" required>
                        </div>

                        <div class="cb-field">
                            <label class="cb-label" for="cb-cat-slug"><?php _e( 'Slug', 'course-builder' ); ?></label>
                            <input type="text" id="cb-cat-slug" name="slug" class="cb-input" placeholder="auto-generated">
                            <p class="cb-hint"><?php _e( 'Leave blank to auto-generate from name.', 'course-builder' ); ?></p>
                        </div>

                        <div class="cb-field">
                            <label class="cb-label" for="cb-cat-description"><?php _e( 'Description', 'course-builder' ); ?></label>
                            <textarea id="cb-cat-description" name="description" class="cb-textarea" rows="3" placeholder="Brief description of this category…"></textarea>
                        </div>

                        <!-- Featured Image -->
                        <div class="cb-field">
                            <label class="cb-label"><?php _e( 'Featured Image', 'course-builder' ); ?></label>
                            <div class="cb-image-upload" id="cb-cat-image-wrap">
                                <input type="hidden" name="image_id" id="cb-cat-image-id" value="0">
                                <div class="cb-image-upload__preview" id="cb-cat-image-preview">
                                    <div class="cb-image-upload__placeholder">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                        <span><?php _e( 'Click to upload image', 'course-builder' ); ?></span>
                                    </div>
                                </div>
                                <div class="cb-image-upload__actions" style="display:none" id="cb-cat-image-actions">
                                    <button type="button" class="cb-btn cb-btn--ghost cb-btn--sm" id="cb-cat-change-image"><?php _e( 'Change Image', 'course-builder' ); ?></button>
                                    <button type="button" class="cb-btn cb-btn--danger-ghost cb-btn--sm" id="cb-cat-remove-image"><?php _e( 'Remove', 'course-builder' ); ?></button>
                                </div>
                            </div>
                        </div>

                        <!-- Subcategories -->
                        <div class="cb-field">
                            <label class="cb-label"><?php _e( 'Subcategories', 'course-builder' ); ?></label>
                            <div id="cb-subcats-list" class="cb-repeater cb-repeater--compact">
                                <!-- Dynamically populated -->
                            </div>
                            <button type="button" class="cb-btn cb-btn--outline cb-btn--sm" id="cb-add-subcat">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                <?php _e( 'Add Subcategory', 'course-builder' ); ?>
                            </button>
                        </div>

                        <div id="cb-cat-status" class="cb-form-status" style="display:none"></div>

                        <div class="cb-field-actions">
                            <button type="submit" class="cb-btn cb-btn--primary" id="cb-cat-save-btn">
                                <span class="cb-btn__text"><?php _e( 'Save Department', 'course-builder' ); ?></span>
                                <span class="cb-btn__loader" style="display:none"><div class="cb-spinner cb-spinner--sm cb-spinner--white"></div></span>
                            </button>
                            <button type="button" class="cb-btn cb-btn--ghost" id="cb-cat-reset-btn"><?php _e( 'Reset', 'course-builder' ); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- RIGHT — Categories Table -->
        <div class="cb-table-col">
            <div class="cb-card">
                <div class="cb-card__header">
                    <h2><?php _e( 'Existing Departments', 'course-builder' ); ?></h2>
                    <span class="cb-badge"><?php echo count( $categories ); ?></span>
                </div>
                <div class="cb-table-wrapper">
                    <table class="cb-table" id="cb-categories-table">
                        <thead>
                            <tr>
                                <th class="cb-th--img"></th>
                                <th><?php _e( 'Name', 'course-builder' ); ?></th>
                                <th><?php _e( 'Slug', 'course-builder' ); ?></th>
                                <th><?php _e( 'Courses', 'course-builder' ); ?></th>
                                <th class="cb-th--actions"><?php _e( 'Actions', 'course-builder' ); ?></th>
                            </tr>
                        </thead>
                        <tbody id="cb-categories-tbody">
                            <?php if ( empty( $categories ) ) : ?>
                                <tr><td colspan="5" class="cb-empty"><?php _e( 'No categories yet. Create one!', 'course-builder' ); ?></td></tr>
                            <?php else : foreach ( $categories as $cat ) : ?>
                                <tr data-id="<?php echo esc_attr( $cat['id'] ); ?>">
                                    <td>
                                        <?php if ( $cat['image_url'] ) : ?>
                                            <img src="<?php echo esc_url( $cat['image_url'] ); ?>" class="cb-table-thumb" alt="">
                                        <?php else : ?>
                                            <div class="cb-table-thumb cb-table-thumb--placeholder">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo esc_html( $cat['name'] ); ?></strong>
                                        <?php if ( ! empty( $cat['subcategories'] ) ) : ?>
                                            <div class="cb-subcats-preview">
                                                <?php foreach ( array_slice( $cat['subcategories'], 0, 3 ) as $sub ) : ?>
                                                    <span class="cb-tag"><?php echo esc_html( $sub['name'] ); ?></span>
                                                <?php endforeach; ?>
                                                <?php if ( count( $cat['subcategories'] ) > 3 ) : ?>
                                                    <span class="cb-tag cb-tag--more">+<?php echo count( $cat['subcategories'] ) - 3; ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><code class="cb-code"><?php echo esc_html( $cat['slug'] ); ?></code></td>
                                    <td><span class="cb-count"><?php echo esc_html( $cat['course_count'] ); ?></span></td>
                                    <td>
                                        <div class="cb-action-btns">
                                            <button type="button" class="cb-action-btn cb-action-btn--edit" data-id="<?php echo esc_attr( $cat['id'] ); ?>" title="Edit">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                            </button>
                                            <button type="button" class="cb-action-btn cb-action-btn--delete" data-id="<?php echo esc_attr( $cat['id'] ); ?>" data-name="<?php echo esc_attr( $cat['name'] ); ?>" title="Delete">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div><!-- .cb-two-col -->
</div>

<!-- Delete Confirm Modal -->
<div class="cb-modal-backdrop" id="cb-cat-delete-modal" style="display:none">
    <div class="cb-modal">
        <div class="cb-modal__icon cb-modal__icon--danger">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
        </div>
        <h3><?php _e( 'Delete Category?', 'course-builder' ); ?></h3>
        <p><?php _e( 'Deleting "<strong id="cb-cat-delete-name"></strong>" will remove it and all its subcategories. Courses assigned to this category will be uncategorised.', 'course-builder' ); ?></p>
        <div class="cb-modal__actions">
            <button class="cb-btn cb-btn--ghost" id="cb-cat-delete-cancel"><?php _e( 'Cancel', 'course-builder' ); ?></button>
            <button class="cb-btn cb-btn--danger" id="cb-cat-delete-confirm"><?php _e( 'Delete Department', 'course-builder' ); ?></button>
        </div>
    </div>
</div>

<!-- Full category data for JS editing -->
<script>
window.CB_Categories = <?php echo wp_json_encode( $categories ); ?>;
</script>
