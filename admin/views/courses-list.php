<?php
defined( 'ABSPATH' ) || exit;

$categories     = get_terms( [ 'taxonomy' => 'cb_category', 'hide_empty' => false ] );
$total_courses  = (int) wp_count_posts( 'cb_course' )->publish;
$total_teachers = (int) wp_count_posts( 'cb_teacher' )->publish;
$total_cats     = is_array( $categories ) ? count( $categories ) : 0;
?>
<div class="cb-wrap">

    <!-- Version / Changelog Banner -->
    <?php
    $last_seen_version = get_option( 'cb_last_seen_version', '0' );
    $is_new_version    = version_compare( CB_VERSION, $last_seen_version, '>' );
    ?>
    <?php if ( $is_new_version ) : ?>
    <div class="cb-version-banner" id="cbVersionBanner">
        <div class="cb-version-banner__inner">
            <div class="cb-version-banner__icon">🎉</div>
            <div class="cb-version-banner__content">
                <strong>Course Builder updated to v<?php echo CB_VERSION; ?></strong>
                <ul class="cb-version-banner__log">
                    <li>✅ <strong>Data now safe on update</strong> — courses, teachers &amp; departments are preserved when you reinstall or update the plugin</li>
                    <li>✅ <strong>Video field fixed</strong> — only one YouTube/Vimeo URL field on create/edit course (ghost field removed)</li>
                    <li>✅ <strong>Course Explainer position fixed</strong> — now always appears between Enrol card and Demo Registration in sidebar</li>
                    <li>✅ <strong>Preview button</strong> — appears instantly after saving a new course, no page reload needed</li>
                    <li>✅ <strong>New Settings page</strong> — control whether data is deleted on plugin uninstall (Course Builder → Settings)</li>
                    <li>✅ <strong>Design matched to reference</strong> — Poppins font, emoji icon headers, red ▸ bullets, inset shadow cards, scroll reveal</li>
                </ul>
            </div>
            <button class="cb-version-banner__close" onclick="cbDismissBanner()" title="Dismiss">✕</button>
        </div>
    </div>
    <script>
    function cbDismissBanner() {
        document.getElementById('cbVersionBanner').style.display = 'none';
        fetch(ajaxurl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=cb_dismiss_version_banner&nonce=<?php echo wp_create_nonce("cb_dismiss_banner"); ?>&version=<?php echo CB_VERSION; ?>'
        });
    }
    </script>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="cb-page-header cb-page-header--gradient">
        <div class="cb-page-header__left">
            <div class="cb-page-header__icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            </div>
            <div>
                <h1><?php _e( 'All Courses', 'course-builder' ); ?></h1>
                <p><?php _e( 'Manage, edit, and publish your course catalog.', 'course-builder' ); ?></p>
            </div>
        </div>
        <a href="<?php echo admin_url( 'admin.php?page=course-builder-add' ); ?>" class="cb-btn cb-btn--header">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            <?php _e( 'Add New Course', 'course-builder' ); ?>
        </a>
    </div>

    <!-- 3 Stat Cards only -->
    <div class="cb-stat-row">
        <div class="cb-stat-card">
            <div class="cb-stat-card__icon cb-stat-card__icon--primary">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            </div>
            <div>
                <div class="cb-stat-card__num"><?php echo esc_html( $total_courses ); ?></div>
                <div class="cb-stat-card__label"><?php _e( 'Total Courses', 'course-builder' ); ?></div>
            </div>
        </div>
        <div class="cb-stat-card">
            <div class="cb-stat-card__icon cb-stat-card__icon--secondary">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div>
                <div class="cb-stat-card__num"><?php echo esc_html( $total_teachers ); ?></div>
                <div class="cb-stat-card__label"><?php _e( 'Instructors', 'course-builder' ); ?></div>
            </div>
        </div>
        <div class="cb-stat-card">
            <div class="cb-stat-card__icon cb-stat-card__icon--neutral">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            </div>
            <div>
                <div class="cb-stat-card__num"><?php echo esc_html( $total_cats ); ?></div>
                <div class="cb-stat-card__label"><?php _e( 'Departments', 'course-builder' ); ?></div>
            </div>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="cb-filters">
        <span class="cb-filters__label"><?php _e( 'Filter:', 'course-builder' ); ?></span>
        <div class="cb-filters__search">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="cb-search" class="cb-input" placeholder="<?php esc_attr_e( 'Search courses…', 'course-builder' ); ?>">
        </div>
        <select id="cb-filter-category" class="cb-select">
            <option value="0"><?php _e( 'All Departments', 'course-builder' ); ?></option>
            <?php if ( ! is_wp_error( $categories ) ) foreach ( $categories as $cat ) : ?>
                <option value="<?php echo esc_attr( $cat->term_id ); ?>"><?php echo esc_html( $cat->name ); ?></option>
            <?php endforeach; ?>
        </select>
        <select id="cb-per-page" class="cb-select cb-select--narrow">
            <option value="10">10 / page</option>
            <option value="25">25 / page</option>
            <option value="50">50 / page</option>
        </select>
    </div>

    <!-- Stats text -->
    <div class="cb-stats">
        <span class="cb-stats__text" id="cb-stats-text"><?php _e( 'Loading…', 'course-builder' ); ?></span>
    </div>

    <!-- Table -->
    <div class="cb-card">
        <div class="cb-table-wrapper">
            <table class="cb-table" id="cb-courses-table">
                <thead>
                    <tr>
                        <th class="cb-th--check"><input type="checkbox" id="cb-check-all" class="cb-checkbox"></th>
                        <th><?php _e( 'Course Title', 'course-builder' ); ?></th>
                        <th><?php _e( 'Department', 'course-builder' ); ?></th>
                        <th><?php _e( 'Teacher', 'course-builder' ); ?></th>
                        <th><?php _e( 'Min Age', 'course-builder' ); ?></th>
                        <th><?php _e( 'Duration', 'course-builder' ); ?></th>
                        <th><?php _e( 'Live Classes', 'course-builder' ); ?></th>
                        <th><?php _e( 'Date', 'course-builder' ); ?></th>
                        <th class="cb-th--actions"><?php _e( 'Actions', 'course-builder' ); ?></th>
                    </tr>
                </thead>
                <tbody id="cb-courses-tbody">
                    <tr class="cb-loading-row">
                        <td colspan="9">
                            <div class="cb-spinner-wrap">
                                <div class="cb-spinner"></div>
                                <span><?php _e( 'Loading courses…', 'course-builder' ); ?></span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="cb-pagination" id="cb-pagination">
            <span class="cb-pagination__info" id="cb-pagination-info"></span>
            <div class="cb-pagination__controls" id="cb-pagination-controls"></div>
        </div>
    </div>
        <div id="cb-debug-out" style="white-space:pre-wrap;color:#94a3b8">Click Run Test to fire cb_get_courses and see the raw response...</div>
    </div>

</div>

<!-- Delete Confirm Modal -->
<div class="cb-modal-backdrop" id="cb-delete-modal" style="display:none">
    <div class="cb-modal">
        <div class="cb-modal__icon cb-modal__icon--danger">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
        </div>
        <h3><?php _e( 'Delete This Course?', 'course-builder' ); ?></h3>
        <p><?php _e( 'This cannot be undone. All course data, objectives, and content will be permanently removed.', 'course-builder' ); ?></p>
        <div class="cb-modal__actions">
            <button class="cb-btn cb-btn--ghost" id="cb-delete-cancel"><?php _e( 'Cancel', 'course-builder' ); ?></button>
            <button class="cb-btn cb-btn--danger" id="cb-delete-confirm"><?php _e( 'Delete Course', 'course-builder' ); ?></button>
        </div>
    </div>
</div>
