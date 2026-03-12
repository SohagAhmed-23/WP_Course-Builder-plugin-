<?php
/**
 * Course Builder — Single Course Overview Page
 * Slug: /course/{course-slug}
 */
defined( 'ABSPATH' ) || exit;

$course_id     = get_the_ID();
$title         = get_the_title();
$subtitle      = get_post_meta( $course_id, '_cb_subtitle',          true );
$duration      = (int) get_post_meta( $course_id, '_cb_duration_months', true );
$age_min       = (int) get_post_meta( $course_id, '_cb_age_min',         true );
$live_classes  = (int) get_post_meta( $course_id, '_cb_live_classes',    true );
$teacher_id    = (int) get_post_meta( $course_id, '_cb_teacher_id',      true );
$wc_product_id = (int) get_post_meta( $course_id, '_cb_wc_product_id',   true );
$video_url     = get_post_meta( $course_id, '_cb_video_url',         true );
$thumb_url     = get_the_post_thumbnail_url( $course_id, 'large' ) ?: '';

$objectives  = json_decode( get_post_meta( $course_id, '_cb_learning_objectives', true ) ?: '[]', true );
$overview    = json_decode( get_post_meta( $course_id, '_cb_programme_overview',  true ) ?: '[]', true );
$units       = json_decode( get_post_meta( $course_id, '_cb_course_content',      true ) ?: '[]', true );
$support     = json_decode( get_post_meta( $course_id, '_cb_additional_support',  true ) ?: '[]', true );

// Dept
$terms     = wp_get_post_terms( $course_id, 'cb_category' );
$dept_id   = ( ! empty( $terms ) && ! is_wp_error( $terms ) ) ? $terms[0]->term_id  : 0;
$dept_name = ( ! empty( $terms ) && ! is_wp_error( $terms ) ) ? $terms[0]->name     : '';

// Teacher from this dept
$dept_teachers = [];
if ( $dept_id ) {
    $all_teachers = get_posts([ 'post_type'=>'cb_teacher','posts_per_page'=>-1,'post_status'=>'publish' ]);
    foreach ( $all_teachers as $t ) {
        $cats = json_decode( get_post_meta( $t->ID, '_cb_categories', true ) ?: '[]', true );
        if ( in_array( $dept_id, array_map('intval', $cats) ) ) {
            $dept_teachers[] = $t;
        }
    }
}

// WooCommerce
$product        = $wc_product_id && function_exists('wc_get_product') ? wc_get_product($wc_product_id) : null;
$is_free        = false;
$variations_data = [];
if ( $product ) {
    if ( $product->get_type() === 'variable' ) {
        foreach ( $product->get_available_variations() as $v ) {
            $var = wc_get_product( $v['variation_id'] );
            if ( ! $var ) continue;
            $attrs = [];
            foreach ( $v['attributes'] as $k => $val ) {
                $term_obj = get_term_by( 'slug', $val, str_replace('attribute_', '', $k) );
                $attrs[]  = $term_obj ? $term_obj->name : ucfirst($val);
            }
            $variations_data[] = [
                'id'         => $v['variation_id'],
                'label'      => implode( ' / ', $attrs ) ?: 'Option',
                'price'      => (float) $var->get_price(),
                'price_html' => wc_price( $var->get_price() ),
                'add_url'    => add_query_arg([ 'add-to-cart' => $v['variation_id'] ], wc_get_checkout_url() ),
            ];
        }
    } elseif ( (float) $product->get_price() == 0 ) {
        $is_free = true;
    }
}

// Similar courses
$similar = get_posts([
    'post_type'      => 'cb_course',
    'post_status'    => 'publish',
    'posts_per_page' => 4,
    'post__not_in'   => [ $course_id ],
    'tax_query'      => $dept_id ? [[
        'taxonomy' => 'cb_category',
        'field'    => 'term_id',
        'terms'    => $dept_id,
    ]] : [],
]);

// Unit count
$unit_count = count( array_filter( $units, fn($u) => !empty($u['title']) ) );

// Video embed
function cb_embed( string $url ): string {
    if ( preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\s]+)/', $url, $m) )
        return 'https://www.youtube.com/embed/' . $m[1] . '?rel=0&modestbranding=1';
    if ( preg_match('/vimeo\.com\/(\d+)/', $url, $m) )
        return 'https://player.vimeo.com/video/' . $m[1];
    return $url;
}

get_header();
?>
<div class="cbo-page">

    <!-- ── HERO ─────────────────────────────────────────────────────────────── -->
    <div class="cbo-hero">
        <div class="cbo-container">
            <?php if ( $dept_name ) : ?>
                <div class="cbo-hero__breadcrumb">
                    <a href="<?php echo home_url('/'); ?>">Home</a>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    <span><?php echo esc_html($dept_name); ?></span>
                </div>
            <?php endif; ?>

            <h1 class="cbo-hero__title"><?php echo esc_html($title); ?></h1>
            <?php if ($subtitle) : ?>
                <p class="cbo-hero__subtitle"><?php echo esc_html($subtitle); ?></p>
            <?php endif; ?>

            <!-- Stats bar -->
            <div class="cbo-stats-bar">
                <?php if ($unit_count) : ?>
                <div class="cbo-stat">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                    <strong><?php echo esc_html($unit_count); ?> Units</strong>
                </div>
                <?php endif; ?>
                <?php if ($duration) : ?>
                <div class="cbo-stat">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <strong><?php echo esc_html($duration); ?> Months</strong>
                </div>
                <?php endif; ?>
                <?php if ($age_min) : ?>
                <div class="cbo-stat">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    <strong>Age <?php echo esc_html($age_min); ?>+</strong>
                </div>
                <?php endif; ?>
                <div class="cbo-stat">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                    <strong>Online Live</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- ── MAIN CONTENT ─────────────────────────────────────────────────────── -->
    <div class="cbo-container cbo-layout">

        <!-- LEFT COLUMN -->
        <div class="cbo-main">

            <!-- Learning Objectives -->
            <?php if ( !empty($objectives) ) : ?>
            <div class="cbo-section">
                <div class="cbo-section__head">
                    <div class="cbo-section__icon cbo-section__icon--red">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    </div>
                    <h2>Learning Objectives</h2>
                </div>
                <ul class="cbo-points">
                    <?php foreach ($objectives as $obj) : if (!trim($obj)) continue; ?>
                        <li><?php echo esc_html($obj); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Programme Overview -->
            <?php if ( !empty($overview) ) : ?>
            <div class="cbo-section">
                <div class="cbo-section__head">
                    <div class="cbo-section__icon cbo-section__icon--blue">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 9h6M9 12h6M9 15h4"/></svg>
                    </div>
                    <h2>Programme Overview</h2>
                </div>
                <ul class="cbo-points cbo-points--flame">
                    <?php foreach ($overview as $pt) : if (!trim($pt)) continue; ?>
                        <li><?php echo esc_html($pt); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Course Contents -->
            <?php if ( !empty($units) ) : ?>
            <div class="cbo-section">
                <div class="cbo-section__head">
                    <div class="cbo-section__icon cbo-section__icon--teal">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                    </div>
                    <h2>Course Contents</h2>
                </div>
                <div class="cbo-units-grid">
                    <?php foreach ($units as $i => $unit) : if (empty($unit['title'])) continue; ?>
                    <div class="cbo-unit-card">
                        <div class="cbo-unit-card__num"><?php printf('%02d', $i + 1); ?></div>
                        <div class="cbo-unit-card__body">
                            <strong><?php echo esc_html($unit['title']); ?></strong>
                            <?php if (!empty($unit['lessons'])) : ?>
                                <p><?php echo esc_html($unit['lessons']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Additional Support -->
            <?php if ( !empty($support) ) : ?>
            <div class="cbo-section">
                <div class="cbo-section__head">
                    <div class="cbo-section__icon cbo-section__icon--yellow">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    </div>
                    <h2>Additional Support</h2>
                </div>
                <ul class="cbo-points">
                    <?php foreach ($support as $s) : if (!trim($s)) continue; ?>
                        <li><?php echo esc_html($s); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Unique Features (static) -->
            <div class="cbo-section">
                <div class="cbo-section__head">
                    <div class="cbo-section__icon cbo-section__icon--gold">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    </div>
                    <h2>Unique Features</h2>
                </div>
                <div class="cbo-features-grid">
                    <div class="cbo-feature-card">
                        <div class="cbo-feature-card__icon" style="background:#FFF0F0">
                            <svg viewBox="0 0 24 24" fill="none" stroke="#EF3E26" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                        </div>
                        <strong>Neuro-Friendly Design</strong>
                        <p>Multi-sensory tools for all learning styles</p>
                    </div>
                    <div class="cbo-feature-card">
                        <div class="cbo-feature-card__icon" style="background:#F0F4FF">
                            <svg viewBox="0 0 24 24" fill="none" stroke="#244092" stroke-width="2"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
                        </div>
                        <strong>Music-Led Learning</strong>
                        <p>Jolly songs make phonics memorable and fun</p>
                    </div>
                    <div class="cbo-feature-card">
                        <div class="cbo-feature-card__icon" style="background:#F0FFF8">
                            <svg viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>
                        </div>
                        <strong>Achievement Badges</strong>
                        <p>Digital rewards after every completed unit</p>
                    </div>
                    <div class="cbo-feature-card">
                        <div class="cbo-feature-card__icon" style="background:#FFF8F0">
                            <svg viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                        </div>
                        <strong>Mobile-Friendly Sessions</strong>
                        <p>Learn on any device, anywhere</p>
                    </div>
                    <div class="cbo-feature-card">
                        <div class="cbo-feature-card__icon" style="background:#F5F0FF">
                            <svg viewBox="0 0 24 24" fill="none" stroke="#8B5CF6" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        </div>
                        <strong>Native-Level Instructors</strong>
                        <p>Qualified, fully-certified teachers</p>
                    </div>
                    <div class="cbo-feature-card">
                        <div class="cbo-feature-card__icon" style="background:#F0FAFF">
                            <svg viewBox="0 0 24 24" fill="none" stroke="#0EA5E9" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                        </div>
                        <strong>Data-Driven Progress</strong>
                        <p>Real-time analytics for each child's growth</p>
                    </div>
                </div>
            </div>

            <!-- Course Instructors -->
            <?php if ( !empty($dept_teachers) ) : ?>
            <div class="cbo-section">
                <div class="cbo-section__head">
                    <div class="cbo-section__icon cbo-section__icon--purple">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <h2>Course Instructors</h2>
                </div>
                <div class="cbo-instructors">
                    <?php foreach ($dept_teachers as $t) :
                        $pid = (int) get_post_meta($t->ID, '_cb_photo_id', true);
                        $photo = $pid ? wp_get_attachment_image_url($pid, 'thumbnail') : '';
                        $desig = get_post_meta($t->ID, '_cb_designation', true);
                        $parts = explode(' ', trim($t->post_title));
                        $initials = strtoupper(($parts[0][0] ?? '') . (end($parts)[0] ?? ''));
                    ?>
                    <div class="cbo-instructor">
                        <?php if ($photo) : ?>
                            <img src="<?php echo esc_url($photo); ?>" alt="<?php echo esc_attr($t->post_title); ?>" class="cbo-instructor__photo">
                        <?php else : ?>
                            <div class="cbo-instructor__photo cbo-instructor__initials"><?php echo esc_html($initials); ?></div>
                        <?php endif; ?>
                        <strong class="cbo-instructor__name"><?php echo esc_html($t->post_title); ?></strong>
                        <?php if ($desig) : ?><span class="cbo-instructor__desig"><?php echo esc_html($desig); ?></span><?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- .cbo-main -->

        <!-- RIGHT SIDEBAR -->
        <div class="cbo-sidebar">

            <!-- Enroll Card -->
            <div class="cbo-enroll-card">
                <h3>Enrol in this Course</h3>

                <?php if ( $product && !$is_free && !empty($variations_data) ) : ?>
                <!-- Variation toggle (monthly/yearly) -->
                <div class="cbo-var-tabs" id="cbo-var-tabs">
                    <?php foreach ($variations_data as $i => $v) : ?>
                    <button class="cbo-var-tab <?php echo $i===0?'active':''; ?>"
                            data-id="<?php echo esc_attr($v['id']); ?>"
                            data-price="<?php echo esc_attr($v['price_html']); ?>"
                            data-url="<?php echo esc_url($v['add_url']); ?>">
                        <?php echo esc_html($v['label']); ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                <div class="cbo-price" id="cbo-price">
                    <?php echo wp_kses_post($variations_data[0]['price_html'] ?? ''); ?>
                    <span class="cbo-price__period" id="cbo-period">/ <?php echo esc_html( strtolower($variations_data[0]['label'] ?? '') ); ?></span>
                </div>
                <a href="<?php echo esc_url($variations_data[0]['add_url'] ?? '#'); ?>"
                   class="cbo-enroll-btn" id="cbo-enroll-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 13l4 4L19 7"/></svg>
                    Enrol Now
                </a>

                <?php elseif ( $is_free ) : ?>
                <div class="cbo-price cbo-price--free">FREE</div>
                <a href="<?php echo esc_url( get_permalink($wc_product_id) ); ?>"
                   class="cbo-enroll-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 13l4 4L19 7"/></svg>
                    Enrol Free
                </a>

                <?php elseif ( $product ) : ?>
                <div class="cbo-price"><?php echo wp_kses_post($product->get_price_html()); ?></div>
                <a href="<?php echo esc_url( add_query_arg('add-to-cart', $wc_product_id, wc_get_checkout_url()) ); ?>"
                   class="cbo-enroll-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 13l4 4L19 7"/></svg>
                    Enrol Now
                </a>

                <?php else : ?>
                <a href="#demo-form" class="cbo-enroll-btn cbo-enroll-btn--outline">
                    Register Your Interest
                </a>
                <?php endif; ?>

                <!-- Perks -->
                <ul class="cbo-enroll-perks">
                    <?php if ($unit_count) : ?><li><svg viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Full access to all <?php echo $unit_count; ?> units</li><?php endif; ?>
                    <li><svg viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Live sessions + recordings</li>
                    <li><svg viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Certificate on completion</li>
                </ul>
            </div>

            <!-- Course Explainer Video -->
            <?php if ( $video_url || $thumb_url ) : ?>
            <div class="cbo-sidebar-card">
                <div class="cbo-sidebar-card__head">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#244092" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><polygon points="10 8 16 11 10 14 10 8" fill="#244092" stroke="none"/></svg>
                    Course Explainer
                </div>
                <div class="cbo-video-wrap">
                    <?php if ($video_url) : ?>
                        <iframe src="<?php echo esc_url(cb_embed($video_url)); ?>"
                                frameborder="0" allowfullscreen loading="lazy"></iframe>
                    <?php elseif ($thumb_url) : ?>
                        <img src="<?php echo esc_url($thumb_url); ?>" alt="<?php echo esc_attr($title); ?>">
                    <?php endif; ?>
                </div>
                <?php if ($video_url) : ?><p class="cbo-video-caption">Watch 3-min Course Overview</p><?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Register Demo Class -->
            <div class="cbo-sidebar-card" id="demo-form">
                <div class="cbo-sidebar-card__head">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#244092" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    Register for Demo Class
                </div>
                <p style="font-size:13px;color:#64748B;margin:0 0 14px">Join a free 30-minute demo class — no commitment required.</p>
                <form class="cbo-demo-form" onsubmit="return false;">
                    <div class="cbo-form-field">
                        <label>Student Name</label>
                        <input type="text" placeholder="e.g. Ayaan Rahman" class="cbo-input">
                    </div>
                    <div class="cbo-form-field">
                        <label>Parent Phone Number</label>
                        <input type="tel" placeholder="+880 1x xx xx xxxx" class="cbo-input">
                    </div>
                    <button type="submit" class="cbo-enroll-btn" style="margin-top:4px">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        Book My Free Demo
                    </button>
                </form>
            </div>

            <!-- Batch Schedule -->
            <div class="cbo-sidebar-card">
                <div class="cbo-sidebar-card__head">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#244092" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Batch Schedule
                </div>
                <div class="cbo-schedule">
                    <div class="cbo-schedule__col">
                        <div class="cbo-schedule__label">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            Morning
                        </div>
                        <div class="cbo-schedule__time">8:00 AM – 9:30 AM</div>
                        <div class="cbo-schedule__time">9:00 AM – 9:30 AM</div>
                        <div class="cbo-schedule__time">10:00 AM – 11:30 AM</div>
                        <div class="cbo-schedule__time">11:00 AM – 11:30 AM</div>
                    </div>
                    <div class="cbo-schedule__col">
                        <div class="cbo-schedule__label">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                            Evening
                        </div>
                        <div class="cbo-schedule__time">4:00 PM – 4:30 PM</div>
                        <div class="cbo-schedule__time">5:00 PM – 5:30 PM</div>
                        <div class="cbo-schedule__time">6:00 PM – 8:30 PM</div>
                        <div class="cbo-schedule__time">7:00 PM – 7:30 PM</div>
                    </div>
                </div>
            </div>

            <!-- 24/7 Card -->
            <div class="cbo-247-card">
                <div class="cbo-247-card__top">
                    <span class="cbo-247-badge">24/7</span>
                    <strong>Always Here For You</strong>
                </div>
                <ul class="cbo-247-list">
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="#A5B4FC" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 1.18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.72a16 16 0 0 0 6 6l.92-.92a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7a2 2 0 0 1 1.72 2.03z"/></svg>
                        Parent helpline &amp; support
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="#A5B4FC" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        Secure access to recordings
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="#A5B4FC" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.1"/></svg>
                        Missed class recovery
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="#A5B4FC" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        Parent-teacher sessions
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="#A5B4FC" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                        Learning progress tracking
                    </li>
                </ul>
            </div>

        </div><!-- .cbo-sidebar -->
    </div><!-- .cbo-layout -->

    <!-- ── SIMILAR COURSES ───────────────────────────────────────────────────── -->
    <?php if ( !empty($similar) ) : ?>
    <div class="cbo-similar">
        <div class="cbo-container">
            <h2 class="cbo-similar__title">Similar Courses</h2>
            <p class="cbo-similar__sub">Continue your child's learning journey</p>
            <div class="cbo-similar-grid">
                <?php foreach ($similar as $sc) :
                    $sc_thumb    = get_the_post_thumbnail_url($sc->ID, 'medium') ?: '';
                    $sc_subtitle = get_post_meta($sc->ID, '_cb_subtitle', true);
                    $sc_duration = (int) get_post_meta($sc->ID, '_cb_duration_months', true);
                    $sc_live     = (int) get_post_meta($sc->ID, '_cb_live_classes', true);
                    $sc_units    = json_decode( get_post_meta($sc->ID, '_cb_course_content', true) ?: '[]', true );
                    $sc_uc       = count(array_filter($sc_units, fn($u)=>!empty($u['title'])));
                    $sc_terms    = wp_get_post_terms($sc->ID, 'cb_category');
                    $sc_dept     = (!empty($sc_terms)&&!is_wp_error($sc_terms)) ? $sc_terms[0]->name : '';
                    $sc_wc       = (int) get_post_meta($sc->ID, '_cb_wc_product_id', true);
                    $sc_prod     = $sc_wc && function_exists('wc_get_product') ? wc_get_product($sc_wc) : null;
                ?>
                <div class="cbo-similar-card">
                    <div class="cbo-similar-card__thumb">
                        <?php if ($sc_thumb) : ?><img src="<?php echo esc_url($sc_thumb); ?>" alt=""><?php endif; ?>
                        <?php if ($sc_dept) : ?><span class="cbo-similar-card__dept"><?php echo esc_html($sc_dept); ?></span><?php endif; ?>
                    </div>
                    <div class="cbo-similar-card__body">
                        <div class="cbo-similar-card__meta">
                            <?php if ($sc_live) : ?><span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg><?php echo $sc_live; ?> Live Class</span><?php endif; ?>
                            <?php if ($sc_duration) : ?><span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg><?php echo $sc_duration; ?> Months</span><?php endif; ?>
                        </div>
                        <h4><a href="<?php echo get_permalink($sc->ID); ?>"><?php echo esc_html($sc->post_title); ?></a></h4>
                        <?php if ($sc_subtitle) : ?><p><?php echo esc_html(wp_trim_words($sc_subtitle, 14)); ?></p><?php endif; ?>
                        <a href="<?php echo get_permalink($sc->ID); ?>" class="cbo-learn-more">Learn More →</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div><!-- .cbo-page -->

<script>
(function(){
    var tabs  = document.querySelectorAll('.cbo-var-tab');
    var price = document.getElementById('cbo-price');
    var btn   = document.getElementById('cbo-enroll-btn');
    if (!tabs.length) return;
    tabs.forEach(function(tab){
        tab.addEventListener('click', function(){
            tabs.forEach(function(t){ t.classList.remove('active'); });
            this.classList.add('active');
            if (price) {
                price.innerHTML = this.dataset.price +
                    '<span class="cbo-price__period" id="cbo-period"> / '+ this.dataset.label +'</span>';
            }
            if (btn) btn.href = this.dataset.url;
        });
    });
})();
</script>

<?php get_footer(); ?>
