<?php
defined( 'ABSPATH' ) || exit;
$teachers   = \CB\Core\CPT_Teachers::get_formatted();
$categories = get_terms( [ 'taxonomy' => 'cb_category', 'hide_empty' => false ] );
?>
<div class="cb-wrap">

    <div class="cb-page-header cb-page-header--gradient cb-page-header--violet">
        <div class="cb-page-header__left">
            <div class="cb-page-header__icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div>
                <h1><?php _e( 'Teachers', 'course-builder' ); ?></h1>
                <p><?php _e( 'Manage your instructor roster and their course assignments.', 'course-builder' ); ?></p>
            </div>
        </div>
        <div class="cb-page-header__right" style="position:relative;z-index:1">
            <span class="cb-pill-count"><?php echo count( $teachers ); ?> <?php _e( 'instructors', 'course-builder' ); ?></span>
        </div>
    </div>

    <div class="cb-two-col">

        <!-- LEFT — Form -->
        <div class="cb-form-col">
            <div class="cb-card cb-card--accent-violet">
                <div class="cb-card__header">
                    <div class="cb-card__header-icon cb-icon-bg--violet">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <div><h2 id="cb-teacher-form-title"><?php _e( 'Add New Teacher', 'course-builder' ); ?></h2></div>
                </div>
                <div class="cb-card__body">
                    <form id="cb-teacher-form">
                        <?php wp_nonce_field( 'cb_admin_nonce', 'nonce' ); ?>
                        <input type="hidden" name="id" id="cb-teacher-id" value="0">

                        <!-- Photo + Name row -->
                        <div class="cb-teacher-top-row">
                            <div class="cb-photo-upload" id="cb-teacher-photo-wrap">
                                <input type="hidden" name="photo_id" id="cb-teacher-photo-id" value="0">
                                <div class="cb-photo-upload__preview" id="cb-teacher-photo-preview">
                                    <div class="cb-photo-upload__placeholder">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    </div>
                                </div>
                                <div class="cb-photo-upload__actions">
                                    <button type="button" class="cb-photo-upload__btn" id="cb-teacher-upload-btn" title="Upload photo">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                    </button>
                                    <button type="button" class="cb-photo-upload__btn cb-photo-upload__btn--remove" id="cb-teacher-remove-photo" style="display:none" title="Remove">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                    </button>
                                </div>
                            </div>

                            <div class="cb-teacher-top-fields">
                                <div class="cb-field">
                                    <label class="cb-label" for="cb-teacher-name"><?php _e( 'Full Name', 'course-builder' ); ?> <span class="cb-required">*</span></label>
                                    <input type="text" id="cb-teacher-name" name="name" class="cb-input" placeholder="e.g. Dr. Jane Smith" required>
                                </div>
                                <div class="cb-field" style="margin-bottom:0">
                                    <label class="cb-label" for="cb-teacher-designation"><?php _e( 'Designation', 'course-builder' ); ?></label>
                                    <input type="text" id="cb-teacher-designation" name="designation" class="cb-input" placeholder="e.g. Senior Language Instructor">
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="cb-field">
                            <label class="cb-label" for="cb-teacher-description">
                                <svg class="cb-label-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="17" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="17" y1="18" x2="3" y2="18"/></svg>
                                <?php _e( 'Biography / Description', 'course-builder' ); ?>
                            </label>
                            <textarea id="cb-teacher-description" name="description" class="cb-textarea" rows="4"
                                placeholder="A short bio about the teacher — their background, expertise, and teaching philosophy…"></textarea>
                            <p class="cb-hint"><?php _e( 'Displayed on the course and teacher profile pages.', 'course-builder' ); ?></p>
                        </div>

                        <!-- Assign Categories -->
                        <div class="cb-field">
                            <label class="cb-label" for="cb-teacher-categories">
                                <svg class="cb-label-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                                <?php _e( 'Assign Departments', 'course-builder' ); ?>
                            </label>
                            <select id="cb-teacher-categories" name="categories[]" class="cb-select cb-select--full cb-select2" multiple>
                                <?php if ( ! is_wp_error( $categories ) ) foreach ( $categories as $cat ) : ?>
                                    <option value="<?php echo esc_attr( $cat->term_id ); ?>"><?php echo esc_html( $cat->name ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="cb-teacher-status" class="cb-form-status" style="display:none"></div>

                        <div class="cb-field-actions">
                            <button type="submit" class="cb-btn cb-btn--primary" id="cb-teacher-save-btn">
                                <span class="cb-btn__text">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                    <?php _e( 'Save Teacher', 'course-builder' ); ?>
                                </span>
                                <span class="cb-btn__loader" style="display:none"><div class="cb-spinner cb-spinner--sm cb-spinner--white"></div></span>
                            </button>
                            <button type="button" class="cb-btn cb-btn--ghost" id="cb-teacher-reset-btn"><?php _e( 'Reset', 'course-builder' ); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- RIGHT — Teachers Table -->
        <div class="cb-table-col">
            <div class="cb-card">
                <div class="cb-card__header">
                    <div class="cb-card__header-icon cb-icon-bg--violet">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                    </div>
                    <div>
                        <h2><?php _e( 'Instructor Roster', 'course-builder' ); ?></h2>
                    </div>
                    <span class="cb-badge" style="margin-left:auto"><?php echo count( $teachers ); ?></span>
                </div>
                <div class="cb-table-wrapper">
                    <table class="cb-table" id="cb-teachers-table">
                        <thead>
                            <tr>
                                <th class="cb-th--avatar"></th>
                                <th><?php _e( 'Name & Bio', 'course-builder' ); ?></th>
                                <th><?php _e( 'Designation', 'course-builder' ); ?></th>
                                <th><?php _e( 'Departments', 'course-builder' ); ?></th>
                                <th class="cb-th--actions"><?php _e( 'Actions', 'course-builder' ); ?></th>
                            </tr>
                        </thead>
                        <tbody id="cb-teachers-tbody">
                            <?php if ( empty( $teachers ) ) : ?>
                                <tr><td colspan="5" class="cb-empty">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:40px;height:40px;margin-bottom:8px;display:block;margin-inline:auto;opacity:.3"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                                    <?php _e( 'No teachers yet. Add your first instructor!', 'course-builder' ); ?>
                                </td></tr>
                            <?php else : foreach ( $teachers as $teacher ) : ?>
                                <tr data-id="<?php echo esc_attr( $teacher['id'] ); ?>">
                                    <td>
                                        <?php if ( $teacher['photo_url'] ) : ?>
                                            <img src="<?php echo esc_url( $teacher['photo_url'] ); ?>" class="cb-avatar" alt="">
                                        <?php else : ?>
                                            <div class="cb-avatar cb-avatar--initials">
                                                <?php
                                                $parts = explode( ' ', trim( $teacher['name'] ) );
                                                echo strtoupper( ( $parts[0][0] ?? '' ) . ( end( $parts )[0] ?? '' ) );
                                                ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong class="cb-teacher-name"><?php echo esc_html( $teacher['name'] ); ?></strong>
                                        <?php if ( ! empty( $teacher['description'] ) ) : ?>
                                            <p class="cb-teacher-bio"><?php echo esc_html( wp_trim_words( $teacher['description'], 14, '…' ) ); ?></p>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ( $teacher['designation'] ) : ?>
                                            <span class="cb-pill cb-pill--violet"><?php echo esc_html( $teacher['designation'] ); ?></span>
                                        <?php else : ?>
                                            <span class="cb-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ( $teacher['cat_names'] ) : ?>
                                            <?php foreach ( explode( ', ', $teacher['cat_names'] ) as $cname ) : ?>
                                                <span class="cb-tag" style="margin:2px"><?php echo esc_html( $cname ); ?></span>
                                            <?php endforeach; ?>
                                        <?php else : ?>
                                            <span class="cb-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="cb-action-btns">
                                            <button type="button" class="cb-action-btn cb-action-btn--edit"
                                                data-id="<?php echo esc_attr( $teacher['id'] ); ?>" title="Edit">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                            </button>
                                            <button type="button" class="cb-action-btn cb-action-btn--delete"
                                                data-id="<?php echo esc_attr( $teacher['id'] ); ?>"
                                                data-name="<?php echo esc_attr( $teacher['name'] ); ?>" title="Delete">
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

    </div>
</div>

<!-- Delete Modal -->
<div class="cb-modal-backdrop" id="cb-teacher-delete-modal" style="display:none">
    <div class="cb-modal">
        <div class="cb-modal__icon cb-modal__icon--danger">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
        </div>
        <h3><?php _e( 'Delete Teacher?', 'course-builder' ); ?></h3>
        <p><?php _e( 'Remove <strong id="cb-teacher-delete-name"></strong> from the system? This cannot be undone.', 'course-builder' ); ?></p>
        <div class="cb-modal__actions">
            <button class="cb-btn cb-btn--ghost" id="cb-teacher-delete-cancel"><?php _e( 'Cancel', 'course-builder' ); ?></button>
            <button class="cb-btn cb-btn--danger" id="cb-teacher-delete-confirm"><?php _e( 'Delete Teacher', 'course-builder' ); ?></button>
        </div>
    </div>
</div>

<script>window.CB_Teachers = <?php echo wp_json_encode( $teachers ); ?>;</script>
