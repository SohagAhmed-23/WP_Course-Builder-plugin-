/**
 * Course Builder — Admin JavaScript
 * Handles all AJAX CRUD, repeater fields, media uploads, pagination, and modals.
 */
/* global CB_Admin, wp */
(function ($) {
    'use strict';

    const { ajax_url } = CB_Admin;
    let nonce = CB_Admin.nonce; // mutable — refreshed on 403

    // Auto-refresh nonce if it expires (e.g. after reinstall / long session)
    function refreshNonce() {
        return $.post(ajax_url, { action: 'cb_refresh_nonce' }).done(res => {
            if (res.success && res.data.nonce) { nonce = res.data.nonce; }
        });
    }

    // ── Toast Notification System ─────────────────────────────────────────
    const Toast = {
        container: null,

        init() {
            this.container = $('<div id="cb-toast-container"></div>').appendTo('body');
        },

        show(message, type = 'success', duration = 3500) {
            const icons = {
                success: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>`,
                error:   `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
            };
            const $toast = $(`
                <div class="cb-toast cb-toast--${type}">
                    ${icons[type] || ''}
                    <span>${message}</span>
                </div>
            `).appendTo(this.container);

            setTimeout(() => {
                $toast.css('animation', 'cb-toast-out .2s ease forwards');
                setTimeout(() => $toast.remove(), 200);
            }, duration);
        }
    };

    // ── AJAX Helper ───────────────────────────────────────────────────────
    function ajax(action, data = {}) {
        const deferred = $.Deferred();
        $.post(ajax_url, { action, nonce, ...data })
            .done(res => {
                // Nonce expired — refresh and retry once
                if (!res.success && res.data && res.data.message && res.data.message.indexOf('Nonce') !== -1) {
                    refreshNonce().done(() => {
                        $.post(ajax_url, { action, nonce, ...data })
                            .done(r => deferred.resolve(r))
                            .fail(e => deferred.reject(e));
                    });
                } else {
                    deferred.resolve(res);
                }
            })
            .fail(e => deferred.reject(e));
        return deferred.promise();
    }

    // ── Repeater Factory ──────────────────────────────────────────────────
    const Repeater = {
        /**
         * Add a simple single-input row.
         * @param {jQuery} $list  - Container element
         * @param {string} name   - Input name (array syntax)
         * @param {string} value  - Pre-filled value
         * @param {string} placeholder
         */
        addSimple($list, name, value = '', placeholder = 'Enter text…') {
            const $item = $(`
                <div class="cb-repeater-item">
                    <div class="cb-drag-handle">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/>
                            <line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/>
                            <line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
                        </svg>
                    </div>
                    <div class="cb-repeater-item__content">
                        <input type="text" name="${name}" value="${escHtml(value)}" placeholder="${placeholder}">
                    </div>
                    <button type="button" class="cb-repeater-item__remove" title="Remove">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
            `);
            $list.append($item);
            $item.find('input').focus();
        },

        /**
         * Add a Course Unit row (unit label + title + lessons summary).
         */
        addUnit($list, data = {}) {
            const idx   = $list.children().length;
            const label = data.unit  || `Unit ${idx + 1}`;
            const title = data.title || '';
            const less  = data.lessons || '';

            const $item = $(`
                <div class="cb-repeater-item">
                    <div class="cb-repeater-item__content cb-unit-block">
                        <div class="cb-unit-row">
                            <div>
                                <label class="cb-label" style="margin-bottom:4px;font-size:11px">Unit Label</label>
                                <input type="text" name="course_content[${idx}][unit]"
                                    value="${escHtml(label)}" placeholder="Unit ${idx + 1}" class="cb-input cb-input--sm">
                            </div>
                            <div>
                                <label class="cb-label" style="margin-bottom:4px;font-size:11px">Unit Title</label>
                                <input type="text" name="course_content[${idx}][title]"
                                    value="${escHtml(title)}" placeholder="e.g. Listening & Speaking" class="cb-input cb-input--sm">
                            </div>
                        </div>
                        <div style="margin-top:8px">
                            <label class="cb-label" style="margin-bottom:4px;font-size:11px">Lessons / Topics</label>
                            <input type="text" name="course_content[${idx}][lessons]"
                                value="${escHtml(less)}" placeholder="e.g. Introduction, Core concepts, Practice" class="cb-input cb-input--sm">
                        </div>
                    </div>
                    <button type="button" class="cb-repeater-item__remove" title="Remove Unit">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
            `);
            $list.append($item);
        },
    };

    function escHtml(str) {
        return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // Delegate remove
    $(document).on('click', '.cb-repeater-item__remove', function () {
        $(this).closest('.cb-repeater-item').fadeOut(150, function () {
            $(this).remove();
        });
    });

    // ── Button Loading State ──────────────────────────────────────────────
    function btnLoading($btn, loading) {
        $btn.find('.cb-btn__text').toggle(!loading);
        $btn.find('.cb-btn__loader').toggle(loading);
        $btn.prop('disabled', loading);
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  PAGE: All Courses
    // ═══════════════════════════════════════════════════════════════════════
    if ($('#cb-courses-table').length) {

        let currentPage = 1;
        let perPage     = 10;
        let searchVal   = '';
        let catId       = 0;
        let deleteId    = null;

        function renderRow(c) {
            const age      = c.age_min      ? c.age_min + '+'      : '—';
            const duration = c.duration     ? c.duration + ' mo'   : '—';
            const live     = c.live_classes ? c.live_classes        : '—';
            return `
                <tr data-id="${c.id}">
                    <td><input type="checkbox" class="cb-checkbox cb-row-check" value="${c.id}"></td>
                    <td>
                        <div class="cb-course-title">
                            <strong>${escHtml(c.title)}</strong>
                            ${c.subtitle ? `<span class="cb-course-subtitle">${escHtml(c.subtitle)}</span>` : ''}
                        </div>
                    </td>
                    <td>${c.category ? `<span class="cb-tag">${escHtml(c.category)}</span>` : '<span class="cb-muted">—</span>'}</td>
                    <td>${escHtml(c.teacher || '—')}</td>
                    <td class="cb-muted">${age}</td>
                    <td class="cb-muted">${duration}</td>
                    <td class="cb-muted">${live}</td>
                    <td class="cb-muted">${escHtml(c.date)}</td>
                    <td>
                        <div class="cb-action-btns">
                            <a href="${c.permalink || '#'}" target="_blank" class="cb-action-btn cb-action-btn--preview" title="Preview">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                            </a>
                            <a href="admin.php?page=course-builder-add&id=${c.id}" class="cb-action-btn cb-action-btn--edit" title="Edit">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </a>
                            <button type="button" class="cb-action-btn cb-action-btn--delete" data-id="${c.id}" title="Delete">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>`;
        }

        function renderPagination(total, totalPages) {
            const from = total === 0 ? 0 : (currentPage - 1) * perPage + 1;
            const to   = Math.min(currentPage * perPage, total);
            $('#cb-pagination-info').text(`Showing ${from}–${to} of ${total} courses`);
            $('#cb-stats-text').text(`${total} course${total !== 1 ? 's' : ''} found`);

            const $controls = $('#cb-pagination-controls').empty();
            if (totalPages <= 1) return;

            const prevBtn = $(`<button class="cb-page-btn" ${currentPage === 1 ? 'disabled' : ''}>‹</button>`)
                .on('click', () => { currentPage--; loadCourses(); });
            $controls.append(prevBtn);

            for (let p = 1; p <= totalPages; p++) {
                if (totalPages > 7 && Math.abs(p - currentPage) > 2 && p !== 1 && p !== totalPages) {
                    if (p === currentPage - 3 || p === currentPage + 3) {
                        $controls.append('<span style="padding:0 4px;color:var(--cb-text-faint)">…</span>');
                    }
                    continue;
                }
                const $btn = $(`<button class="cb-page-btn ${p === currentPage ? 'active' : ''}">${p}</button>`)
                    .on('click', () => { currentPage = p; loadCourses(); });
                $controls.append($btn);
            }

            const nextBtn = $(`<button class="cb-page-btn" ${currentPage >= totalPages ? 'disabled' : ''}>›</button>`)
                .on('click', () => { currentPage++; loadCourses(); });
            $controls.append(nextBtn);
        }

        function loadCourses() {
            const $tbody = $('#cb-courses-tbody');
            $tbody.html('<tr class="cb-loading-row"><td colspan="9"><div class="cb-spinner-wrap"><div class="cb-spinner"></div><span>Loading…</span></div></td></tr>');

            ajax('cb_get_courses', {
                page: currentPage, per_page: perPage,
                search: searchVal, category_id: catId,
            }).done(res => {
                if (!res.success) {
                    const msg = res.data?.message || 'Failed to load courses.';
                    $tbody.html('<tr><td colspan="9" class="cb-empty" style="color:#ef3e26">⚠ ' + msg + ' — <a href="javascript:location.reload()">Reload page</a></td></tr>');
                    Toast.show(msg, 'error');
                    return;
                }
                const { courses, total, total_pages } = res.data;

                if (courses.length === 0) {
                    $tbody.html('<tr><td colspan="9" class="cb-empty">No courses found. <a href="admin.php?page=course-builder-add">Add your first course →</a></td></tr>');
                    renderPagination(0, 0);
                    return;
                }
                $tbody.html(courses.map(renderRow).join(''));
                renderPagination(total, total_pages);
            }).fail(function(xhr) {
                const errText = xhr.responseText ? xhr.responseText.substring(0, 200) : 'Unknown error';
                $tbody.html('<tr><td colspan="9" class="cb-empty" style="color:#ef3e26">⚠ AJAX error — <a href="javascript:location.reload()">Reload the page</a> to try again.<br><small style="color:#94a3b8">' + errText + '</small></td></tr>');
            });
        }

        // Debounced search
        let searchTimer;
        $('#cb-search').on('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                searchVal   = $(this).val();
                currentPage = 1;
                loadCourses();
            }, 400);
        });

        $('#cb-filter-category').on('change', function () {
            catId = parseInt($(this).val());
            currentPage = 1;
            loadCourses();
        });

        $('#cb-per-page').on('change', function () {
            perPage = parseInt($(this).val());
            currentPage = 1;
            loadCourses();
        });

        // Select all
        $(document).on('change', '#cb-check-all', function () {
            $('.cb-row-check').prop('checked', $(this).prop('checked'));
        });

        // Delete
        $(document).on('click', '.cb-action-btn--delete', function () {
            deleteId = $(this).data('id');
            $('#cb-delete-modal').fadeIn(150);
        });

        $('#cb-delete-cancel').on('click', () => $('#cb-delete-modal').fadeOut(150));
        $('#cb-delete-modal').on('click', function (e) {
            if ($(e.target).is(this)) $(this).fadeOut(150);
        });

        $('#cb-delete-confirm').on('click', function () {
            const $btn = $(this);
            $btn.text('Deleting…').prop('disabled', true);

            ajax('cb_delete_course', { id: deleteId }).done(res => {
                $('#cb-delete-modal').fadeOut(150);
                $btn.text('Yes, Delete').prop('disabled', false);
                if (res.success) {
                    Toast.show('Course deleted successfully.', 'success');
                    loadCourses();
                } else {
                    Toast.show(res.data.message || 'Delete failed.', 'error');
                }
            });
        });

        loadCourses();

    }

    // ═══════════════════════════════════════════════════════════════════════
    //  PAGE: Add / Edit Course
    // ═══════════════════════════════════════════════════════════════════════
    if ($('#cb-course-form').length) {

        const editId = parseInt($('#cb-course-form').data('edit-id')) || 0;

        // Load WC products
        function loadProducts(currentProductId = 0) {
            ajax('cb_get_wc_products', { exclude_course: editId }).done(res => {
                const $sel = $('#cb-wc-product').empty();
                $sel.append('<option value="0">— No Product —</option>');
                if (res.success && res.data.products.length) {
                    res.data.products.forEach(p => {
                        const opt = $('<option>').val(p.id).text(p.name);
                        if (p.id === currentProductId) opt.prop('selected', true);
                        $sel.append(opt);
                    });
                } else if (res.success) {
                    $sel.append('<option value="0" disabled>No available products</option>');
                }
            });
        }

        // If editing, load course data
        if (editId > 0) {
            ajax('cb_get_course', { id: editId }).done(res => {
                if (!res.success) { Toast.show('Failed to load course data.', 'error'); return; }
                const d = res.data;
                $('#cb-title').val(d.title || '');
                $('#cb-subtitle').val(d.subtitle || '');
                $('#cb-category').val(d.category_id || 0);
                $('#cb-teacher').val(d.teacher_id || 0);

                // Repeaters
                (d.programme_overview || []).forEach(v => {
                    Repeater.addSimple($('#cb-overview-list'), 'programme_overview[]', v, 'e.g. Students will explore…');
                });
                (d.learning_objectives || []).forEach(v => {
                    Repeater.addSimple($('#cb-objectives-list'), 'learning_objectives[]', v, 'e.g. Understand core concepts');
                });
                (d.course_content || []).forEach(unit => Repeater.addUnit($('#cb-units-list'), unit));
                (d.additional_support || []).forEach(v => {
                    Repeater.addSimple($('#cb-support-list'), 'additional_support[]', v, 'e.g. Certificate on completion');
                });

                loadProducts(parseInt(d.wc_product_id) || 0);
                if (d.age_min) $('#cb-age-min').val(d.age_min);
                if (d.duration_months) $('#cb-duration').val(d.duration_months);
                if (d.live_classes)    $('#cb-live-classes').val(d.live_classes);
                // Featured image
                if (d.featured_image_id) {
                    $('#cb-featured-image-id').val(d.featured_image_id);
                    $('#cb-featured-image-img').attr('src', d.featured_image_url);
                    $('#cb-featured-image-preview').show();
                    $('#cb-featured-image-remove').show();
                }
            });
        } else {
            loadProducts();
            // Default: one empty objective and unit
            Repeater.addSimple($('#cb-objectives-list'), 'learning_objectives[]', '', 'e.g. Understand the fundamentals');
            Repeater.addSimple($('#cb-overview-list'),    'programme_overview[]',  '', 'e.g. Students will explore…');
            Repeater.addUnit($('#cb-units-list'));
        }



        // Add buttons
        $('#cb-add-overview').on('click', () => Repeater.addSimple($('#cb-overview-list'), 'programme_overview[]', '', 'e.g. Students will explore…'));
        $('#cb-add-objective').on('click', () =>
            Repeater.addSimple($('#cb-objectives-list'), 'learning_objectives[]', '', 'e.g. Master advanced techniques')
        );
        $('#cb-add-unit').on('click', () => Repeater.addUnit($('#cb-units-list')));
        $('#cb-add-support').on('click', () =>
            Repeater.addSimple($('#cb-support-list'), 'additional_support[]', '', 'e.g. Access to course community')
        );

        // Collect unit data properly
        function collectUnits() {
            const units = [];
            $('#cb-units-list .cb-repeater-item').each(function () {
                units.push({
                    unit:    $(this).find('[name*="[unit]"]').val(),
                    title:   $(this).find('[name*="[title]"]').val(),
                    lessons: $(this).find('[name*="[lessons]"]').val(),
                });
            });
            return units;
        }

        // Form submit
        $('#cb-course-form').on('submit', function (e) {
            e.preventDefault();
            const $btn    = $('#cb-save-btn');
            const $status = $('#cb-form-status');

            btnLoading($btn, true);
            $status.hide();

            const objectives = [];
            $('[name="learning_objectives[]"]').each(function () {
                const v = $(this).val().trim();
                if (v) objectives.push(v);
            });

            const support = [];
            $('[name="additional_support[]"]').each(function () {
                const v = $(this).val().trim();
                if (v) support.push(v);
            });

            const postData = {
                id:                  editId,
                title:               $('#cb-title').val(),
                subtitle:            $('#cb-subtitle').val(),
                category_id:         $('#cb-category').val(),
                teacher_id:          $('#cb-teacher').val(),
                wc_product_id:       $('#cb-wc-product').val(),
                age_min:             $('#cb-age-min').val(),
                video_url:           $('#cb-video-url').val(),
                duration_months:     $('#cb-duration').val(),
                live_classes:        $('#cb-live-classes').val(),
                featured_image_id:   parseInt($('#cb-featured-image-id').val()) || 0,
            };

            // Append as array data
            const overview = [];
            $('[name="programme_overview[]"]').each(function () {
                const v = $(this).val().trim();
                if (v) overview.push(v);
            });

            const params = $.param(postData)
                + '&' + $.param({ learning_objectives: objectives })
                + '&' + $.param({ programme_overview: overview })
                + '&' + $.param({ additional_support: support })
                + '&' + $.param({ course_content: collectUnits() });

            $.post(ajax_url, 'action=cb_save_course&nonce=' + nonce + '&' + params)
              .done(res => {
                btnLoading($btn, false);
                if (res.success) {
                    $status.removeClass('cb-form-status--error').addClass('cb-form-status--success')
                        .html('✓ ' + res.data.message).show();
                    Toast.show(res.data.message, 'success');
                    // Show / update preview button immediately after save
                    if (res.data.id && res.data.permalink) {
                        // Sidebar preview wrap
                        const $pw = $('#cb-preview-wrap');
                        const $pb = $('#cb-preview-btn');
                        if ($pb.length) { $pb.attr('href', res.data.permalink); $pw.show(); }
                        // Header preview button — inject for new courses, update for edits
                        if (!$('#cb-preview-btn-header').length) {
                            const $back = $('.cb-page-header .cb-btn--ghost').first();
                            $('<a target="_blank" id="cb-preview-btn-header" class="cb-preview-btn"></a>')
                                .attr('href', res.data.permalink)
                                .html('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg> Preview')
                                .insertBefore($back);
                        } else {
                            $('#cb-preview-btn-header').attr('href', res.data.permalink);
                        }
                    }
                    if (!editId) {
                        setTimeout(() => {
                            window.location.href = 'admin.php?page=course-builder-add&id=' + res.data.id;
                        }, 1200);
                    }
                } else {
                    $status.removeClass('cb-form-status--success').addClass('cb-form-status--error')
                        .html('✗ ' + (res.data.message || 'Save failed.')).show();
                    Toast.show(res.data.message || 'Save failed.', 'error');
                }
            });
        });
    }

    // ── Featured Image Media Uploader ────────────────────────────────────────
    if ($('#cb-featured-image-btn').length) {
        let featuredFrame;

        $('#cb-featured-image-btn').on('click', function () {
            if (featuredFrame) { featuredFrame.open(); return; }
            featuredFrame = wp.media({
                title: 'Select Featured Image',
                button: { text: 'Set Featured Image' },
                multiple: false,
                library: { type: 'image' }
            });
            featuredFrame.on('select', function () {
                const attachment = featuredFrame.state().get('selection').first().toJSON();
                $('#cb-featured-image-id').val(attachment.id);
                $('#cb-featured-image-img').attr('src', attachment.url);
                $('#cb-featured-image-preview').show();
                $('#cb-featured-image-remove').show();
            });
            featuredFrame.open();
        });

        $('#cb-featured-image-remove').on('click', function () {
            $('#cb-featured-image-id').val('-1'); // -1 = explicitly removed
            $('#cb-featured-image-img').attr('src', '');
            $('#cb-featured-image-preview').hide();
            $(this).hide();
        });
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  PAGE: Categories
    // ═══════════════════════════════════════════════════════════════════════
    if ($('#cb-category-form').length) {

        // Auto-generate slug from name
        $('#cb-cat-name').on('input', function () {
            const slug = $(this).val().toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
            $('#cb-cat-slug').val(slug);
        });

        // Add subcategory row
        $('#cb-add-subcat').on('click', () =>
            Repeater.addSimple($('#cb-subcats-list'), 'subcategories[][name]', '', 'Subcategory name…')
        );

        // Featured Image via WP Media
        let catMediaFrame;
        $('#cb-cat-image-wrap').on('click', function (e) {
            if ($(e.target).closest('.cb-image-upload__actions').length) return;
            openMediaFrame('category');
        });
        $('#cb-cat-change-image').on('click', () => openMediaFrame('category'));
        $('#cb-cat-remove-image').on('click', () => {
            $('#cb-cat-image-id').val('0');
            $('#cb-cat-image-preview').html(`
                <div class="cb-image-upload__placeholder">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <span>Click to upload image</span>
                </div>`);
            $('#cb-cat-image-actions').hide();
        });

        function openMediaFrame(context) {
            if (catMediaFrame) { catMediaFrame.open(); return; }
            catMediaFrame = wp.media({ title: 'Select Featured Image', button: { text: 'Use this image' }, multiple: false });
            catMediaFrame.on('select', function () {
                const attach = catMediaFrame.state().get('selection').first().toJSON();
                const url    = attach.sizes?.medium?.url || attach.url;
                $('#cb-cat-image-id').val(attach.id);
                $('#cb-cat-image-preview').html(`<img src="${url}" alt="" style="width:100%;height:100%;object-fit:cover">`);
                $('#cb-cat-image-actions').show();
            });
            catMediaFrame.open();
        }

        // Edit category — populate form
        $(document).on('click', '#cb-categories-tbody .cb-action-btn--edit', function () {
            const id  = $(this).data('id');
            const cat = (window.CB_Categories || []).find(c => c.id == id);
            if (!cat) return;

            $('#cb-cat-id').val(cat.id);
            $('#cb-cat-name').val(cat.name);
            $('#cb-cat-slug').val(cat.slug);
            $('#cb-cat-description').val(cat.description || '');
            $('#cb-cat-image-id').val(cat.image_id || 0);
            $('#cb-cat-form-title').text('Edit Category');

            if (cat.image_url) {
                $('#cb-cat-image-preview').html(`<img src="${cat.image_url}" alt="" style="width:100%;height:100%;object-fit:cover">`);
                $('#cb-cat-image-actions').show();
            }

            // Subcategories
            const $sublist = $('#cb-subcats-list').empty();
            (cat.subcategories || []).forEach(sub => {
                Repeater.addSimple($sublist, 'subcategories[][name]', sub.name, 'Subcategory name…');
            });

            $('html, body').animate({ scrollTop: $('#cb-category-form').offset().top - 80 }, 300);
        });

        // Reset form
        $('#cb-cat-reset-btn').on('click', () => {
            $('#cb-category-form')[0].reset();
            $('#cb-cat-id').val('0');
            $('#cb-cat-form-title').text('Add New Category');
            $('#cb-subcats-list').empty();
            $('#cb-cat-image-id').val('0');
            $('#cb-cat-image-preview').html(`
                <div class="cb-image-upload__placeholder">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <span>Click to upload image</span>
                </div>`);
            $('#cb-cat-image-actions').hide();
            $('#cb-cat-status').hide();
            catMediaFrame = null;
        });

        // Save category
        $('#cb-category-form').on('submit', function (e) {
            e.preventDefault();
            const $btn    = $('#cb-cat-save-btn');
            const $status = $('#cb-cat-status');

            btnLoading($btn, true);
            $status.hide();

            const subcats = [];
            $('[name="subcategories[][name]"]').each(function () {
                const v = $(this).val().trim();
                if (v) subcats.push({ name: v });
            });

            const params = {
                id:          $('#cb-cat-id').val(),
                name:        $('#cb-cat-name').val(),
                slug:        $('#cb-cat-slug').val(),
                description: $('#cb-cat-description').val(),
                image_id:    $('#cb-cat-image-id').val(),
            };

            $.post(ajax_url, 'action=cb_save_category&nonce=' + nonce
                + '&' + $.param(params)
                + '&' + $.param({ subcategories: subcats })
            ).done(res => {
                btnLoading($btn, false);
                if (res.success) {
                    $status.removeClass('cb-form-status--error').addClass('cb-form-status--success')
                        .html('✓ ' + res.data.message).show();
                    Toast.show(res.data.message, 'success');
                    setTimeout(() => location.reload(), 900);
                } else {
                    $status.removeClass('cb-form-status--success').addClass('cb-form-status--error')
                        .html('✗ ' + (res.data.message || 'Save failed.')).show();
                }
            });
        });

        // Delete category
        let deleteCatId = null;
        $(document).on('click', '#cb-categories-tbody .cb-action-btn--delete', function () {
            deleteCatId = $(this).data('id');
            $('#cb-cat-delete-name').text($(this).data('name') || '');
            $('#cb-cat-delete-modal').fadeIn(150);
        });

        $('#cb-cat-delete-cancel').on('click', () => $('#cb-cat-delete-modal').fadeOut(150));
        $('#cb-cat-delete-modal').on('click', function (e) {
            if ($(e.target).is(this)) $(this).fadeOut(150);
        });

        $('#cb-cat-delete-confirm').on('click', function () {
            const $btn = $(this).text('Deleting…').prop('disabled', true);
            ajax('cb_delete_category', { id: deleteCatId }).done(res => {
                $('#cb-cat-delete-modal').fadeOut(150);
                $btn.text('Delete Category').prop('disabled', false);
                if (res.success) {
                    Toast.show('Category deleted.', 'success');
                    $(`#cb-categories-tbody tr[data-id="${deleteCatId}"]`).fadeOut(250, function () { $(this).remove(); });
                    window.CB_Categories = (window.CB_Categories || []).filter(c => c.id != deleteCatId);
                } else {
                    Toast.show(res.data.message || 'Delete failed.', 'error');
                }
            });
        });
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  PAGE: Teachers
    // ═══════════════════════════════════════════════════════════════════════
    if ($('#cb-teacher-form').length) {

        // Init Select2 for categories
        if ($.fn.select2) {
            $('#cb-teacher-categories').select2({
                placeholder: 'Select categories…',
                allowClear: true,
            });
        }

        // Photo upload
        let teacherMediaFrame;
        $('#cb-teacher-upload-btn').on('click', () => openTeacherPhoto());

        function openTeacherPhoto() {
            if (teacherMediaFrame) { teacherMediaFrame.open(); return; }
            teacherMediaFrame = wp.media({ title: 'Select Teacher Photo', button: { text: 'Use this photo' }, multiple: false, library: { type: 'image' } });
            teacherMediaFrame.on('select', function () {
                const attach = teacherMediaFrame.state().get('selection').first().toJSON();
                const url    = attach.sizes?.thumbnail?.url || attach.url;
                $('#cb-teacher-photo-id').val(attach.id);
                $('#cb-teacher-photo-preview').html(`<img src="${url}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%">`);
                $('#cb-teacher-remove-photo').show();
            });
            teacherMediaFrame.open();
        }

        $('#cb-teacher-remove-photo').on('click', () => {
            $('#cb-teacher-photo-id').val('0');
            $('#cb-teacher-photo-preview').html(`
                <div class="cb-photo-upload__placeholder">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>`);
            $('#cb-teacher-remove-photo').hide();
        });

        // Edit teacher
        $(document).on('click', '#cb-teachers-tbody .cb-action-btn--edit', function () {
            const id      = $(this).data('id');
            const teacher = (window.CB_Teachers || []).find(t => t.id == id);
            if (!teacher) return;

            $('#cb-teacher-id').val(teacher.id);
            $('#cb-teacher-name').val(teacher.name);
            $('#cb-teacher-designation').val(teacher.designation || '');
            $('#cb-teacher-description').val(teacher.description || '');
            $('#cb-teacher-form-title').text('Edit Teacher');

            if (teacher.photo_url) {
                $('#cb-teacher-photo-id').val(teacher.photo_id || 0);
                $('#cb-teacher-photo-preview').html(`<img src="${teacher.photo_url}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%">`);
                $('#cb-teacher-remove-photo').show();
            }

            if ($.fn.select2) {
                $('#cb-teacher-categories').val(teacher.categories || []).trigger('change');
            } else {
                $('#cb-teacher-categories').val(teacher.categories || []);
            }

            $('html, body').animate({ scrollTop: $('#cb-teacher-form').offset().top - 80 }, 300);
        });

        // Reset
        $('#cb-teacher-reset-btn').on('click', () => {
            $('#cb-teacher-form')[0].reset();
            $('#cb-teacher-id').val('0');
            $('#cb-teacher-form-title').text('Add New Teacher');
            $('#cb-teacher-description').val('');
            $('#cb-teacher-photo-id').val('0');
            $('#cb-teacher-photo-preview').html(`
                <div class="cb-photo-upload__placeholder">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>`);
            $('#cb-teacher-remove-photo').hide();
            if ($.fn.select2) $('#cb-teacher-categories').val([]).trigger('change');
            $('#cb-teacher-status').hide();
            teacherMediaFrame = null;
        });

        // Save teacher
        $('#cb-teacher-form').on('submit', function (e) {
            e.preventDefault();
            const $btn    = $('#cb-teacher-save-btn');
            const $status = $('#cb-teacher-status');

            btnLoading($btn, true);
            $status.hide();

            const cats = $('#cb-teacher-categories').val() || [];

            const params = {
                id:          $('#cb-teacher-id').val(),
                name:        $('#cb-teacher-name').val(),
                designation: $('#cb-teacher-designation').val(),
                description: $('#cb-teacher-description').val(),
                photo_id:    $('#cb-teacher-photo-id').val(),
            };

            $.post(ajax_url, 'action=cb_save_teacher&nonce=' + nonce
                + '&' + $.param(params)
                + '&' + $.param({ categories: cats })
            ).done(res => {
                btnLoading($btn, false);
                if (res.success) {
                    $status.removeClass('cb-form-status--error').addClass('cb-form-status--success')
                        .html('✓ ' + res.data.message).show();
                    Toast.show(res.data.message, 'success');
                    setTimeout(() => location.reload(), 900);
                } else {
                    $status.removeClass('cb-form-status--success').addClass('cb-form-status--error')
                        .html('✗ ' + (res.data.message || 'Save failed.')).show();
                }
            });
        });

        // Delete teacher
        let deleteTeacherId = null;
        $(document).on('click', '#cb-teachers-tbody .cb-action-btn--delete', function () {
            deleteTeacherId = $(this).data('id');
            $('#cb-teacher-delete-name').text($(this).data('name') || '');
            $('#cb-teacher-delete-modal').fadeIn(150);
        });

        $('#cb-teacher-delete-cancel').on('click', () => $('#cb-teacher-delete-modal').fadeOut(150));
        $('#cb-teacher-delete-modal').on('click', function (e) {
            if ($(e.target).is(this)) $(this).fadeOut(150);
        });

        $('#cb-teacher-delete-confirm').on('click', function () {
            const $btn = $(this).text('Deleting…').prop('disabled', true);
            ajax('cb_delete_teacher', { id: deleteTeacherId }).done(res => {
                $('#cb-teacher-delete-modal').fadeOut(150);
                $btn.text('Delete Teacher').prop('disabled', false);
                if (res.success) {
                    Toast.show('Teacher deleted.', 'success');
                    $(`#cb-teachers-tbody tr[data-id="${deleteTeacherId}"]`).fadeOut(250, function () { $(this).remove(); });
                    window.CB_Teachers = (window.CB_Teachers || []).filter(t => t.id != deleteTeacherId);
                } else {
                    Toast.show(res.data.message || 'Delete failed.', 'error');
                }
            });
        });
    }

    // ── Init ──────────────────────────────────────────────────────────────
    $(document).ready(() => {
        Toast.init();
    });

})(jQuery);
