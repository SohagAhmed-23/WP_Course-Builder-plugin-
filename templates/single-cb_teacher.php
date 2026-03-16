<?php
/**
 * Single Teacher Profile Page
 * URL: /teacher/{teacher-slug}
 */
defined( 'ABSPATH' ) || exit;

// ── Data ──────────────────────────────────────────────────────────────────────
$teacher     = get_post();
$photo_id    = (int) get_post_meta( $teacher->ID, '_cb_photo_id', true );
$photo_url   = $photo_id ? wp_get_attachment_image_url( $photo_id, 'large' ) : '';
$designation = get_post_meta( $teacher->ID, '_cb_designation', true );
$bio         = $teacher->post_content;

$cat_ids   = json_decode( get_post_meta( $teacher->ID, '_cb_categories', true ) ?: '[]', true );
$cat_names = [];
foreach ( (array) $cat_ids as $cid ) {
    $term = get_term( (int) $cid, 'cb_category' );
    if ( $term && ! is_wp_error( $term ) ) $cat_names[] = $term->name;
}

$courses = get_posts( [
    'post_type'      => 'cb_course',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'meta_query'     => [ [
        'key'     => '_cb_teacher_id',
        'value'   => $teacher->ID,
        'compare' => '=',
    ] ],
] );

get_header();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ── Reset & Base ── */
        .tp-wrap *, .tp-wrap *::before, .tp-wrap *::after { box-sizing: border-box; margin: 0; padding: 0; }
        .tp-wrap {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f0f4fb;
            min-height: 100vh;
            padding-bottom: 80px;
        }

        /* ══════════════════════════════════════
           KEYFRAME ANIMATIONS
        ══════════════════════════════════════ */
        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(28px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes photoReveal {
            from { opacity: 0; transform: translateY(20px) scale(.94); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        @keyframes tagPop {
            from { opacity: 0; transform: scale(.8) translateY(6px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }
        @keyframes orbFloat {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50%       { transform: translateY(-18px) rotate(6deg); }
        }
        @keyframes orbFloat2 {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50%       { transform: translateY(14px) rotate(-5deg); }
        }
        @keyframes borderGlow {
            0%, 100% { box-shadow: 0 0 0 0 rgba(239,62,38,.15), 0 8px 40px rgba(36,64,146,.22); }
            50%       { box-shadow: 0 0 0 6px rgba(239,62,38,.08), 0 12px 48px rgba(36,64,146,.30); }
        }
        @keyframes countUp {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ══════════════════════════════════════
           HERO
        ══════════════════════════════════════ */
        .tp-hero {
            background: linear-gradient(135deg, #0f1829 0%, #1a2f6e 55%, #2748b5 100%);
            padding: 64px 0 0;
            position: relative;
            overflow: hidden;
        }
        .tp-hero__orb1 {
            position: absolute; top: -100px; right: -100px;
            width: 420px; height: 420px;
            background: radial-gradient(circle, rgba(255,255,255,.06) 0%, transparent 70%);
            border-radius: 50%;
            animation: orbFloat 9s ease-in-out infinite;
        }
        .tp-hero__orb2 {
            position: absolute; bottom: -40px; left: -80px;
            width: 320px; height: 320px;
            background: radial-gradient(circle, rgba(239,62,38,.10) 0%, transparent 70%);
            border-radius: 50%;
            animation: orbFloat2 11s ease-in-out infinite;
        }
        .tp-hero__orb3 {
            position: absolute; top: 30%; left: 45%;
            width: 180px; height: 180px;
            background: radial-gradient(circle, rgba(255,255,255,.03) 0%, transparent 70%);
            border-radius: 50%;
            animation: orbFloat 14s ease-in-out infinite 2s;
        }
        .tp-hero::before {
            content: '';
            position: absolute; inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px);
            background-size: 48px 48px;
            pointer-events: none;
        }
        .tp-hero__inner {
            max-width: 1140px;
            margin: 0 auto;
            padding: 0 28px;
            display: flex;
            align-items: flex-end;
            gap: 44px;
            position: relative;
            z-index: 1;
        }

        /* Photo */
        .tp-hero__photo-wrap {
            flex-shrink: 0;
            animation: photoReveal .8s cubic-bezier(.22,1,.36,1) .1s both;
        }
        .tp-hero__photo,
        .tp-hero__photo-placeholder {
            width: 320px;
            height:400px;
            border-radius: 20px;
            border: 4px solid rgba(255,255,255,.22);
            object-fit: cover;
            display: block;
            animation: borderGlow 4s ease-in-out infinite 1s;
            box-shadow: 0 8px 40px rgba(36,64,146,.35);
        }
        .tp-hero__photo-placeholder {
            background: linear-gradient(135deg, #2748b5, #0f1829);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: rgba(255,255,255,.35);
        }

        /* Info */
        .tp-hero__info { padding-bottom: 36px; flex: 1; }
        .tp-hero__name {
            font-size: clamp(1.6rem, 4vw, 2.4rem);
            font-weight: 800;
            color: #fff;
            line-height: 1.15;
            margin-bottom: 8px;
            animation: fadeSlideUp .7s cubic-bezier(.22,1,.36,1) .25s both;
        }
        .tp-hero__designation {
            font-size: 1rem;
            color: rgba(255,255,255,.72);
            font-weight: 500;
            margin-bottom: 18px;
            animation: fadeSlideUp .7s cubic-bezier(.22,1,.36,1) .35s both;
        }
        .tp-hero__tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            animation: fadeSlideUp .7s cubic-bezier(.22,1,.36,1) .45s both;
        }
        .tp-hero__tag {
            background: rgba(255,255,255,.11);
            color: rgba(255,255,255,.92);
            font-size: .73rem;
            font-weight: 700;
            padding: 5px 16px;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,.2);
            letter-spacing: .03em;
            animation: tagPop .5s cubic-bezier(.34,1.56,.64,1) both;
            transition: background .2s, transform .2s;
        }
        .tp-hero__tag:hover { background: rgba(255,255,255,.2); transform: translateY(-2px); }
        .tp-hero__tag:nth-child(1) { animation-delay: .5s; }
        .tp-hero__tag:nth-child(2) { animation-delay: .6s; }
        .tp-hero__tag:nth-child(3) { animation-delay: .7s; }
        .tp-hero__tag:nth-child(4) { animation-delay: .8s; }

        .tp-hero__wave {
            height: 48px;
            background: #f0f4fb;
            border-radius: 50% 50% 0 0 / 100% 100% 0 0;
            margin-top: 36px;
            position: relative;
            z-index: 2;
        }

        /* ══════════════════════════════════════
           STATS BAR
        ══════════════════════════════════════ */
        .tp-stats {
            max-width: 1140px;
            margin: -16px auto 0;
            padding: 0 28px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            position: relative;
            z-index: 3;
        }
        .tp-stat {
            background: #fff;
            border-radius: 16px;
            padding: 22px 18px;
            text-align: center;
            box-shadow: 0 4px 28px rgba(36,64,146,.10);
            border: 1.5px solid rgba(36,64,146,.06);
            animation: countUp .6s cubic-bezier(.22,1,.36,1) both;
            transition: transform .25s, box-shadow .25s;
        }
        .tp-stat:hover { transform: translateY(-4px); box-shadow: 0 10px 36px rgba(36,64,146,.15); }
        .tp-stat:nth-child(1) { animation-delay: .5s; }
        .tp-stat:nth-child(2) { animation-delay: .65s; }
        .tp-stat:nth-child(3) { animation-delay: .8s; }
        .tp-stat__num { font-size: 2rem; font-weight: 800; color: #1a2f6e; line-height: 1; }
        .tp-stat__label {
            font-size: .68rem; color: #94a3b8;
            font-weight: 700; text-transform: uppercase;
            letter-spacing: .08em; margin-top: 5px;
        }

        /* ══════════════════════════════════════
           BODY LAYOUT
        ══════════════════════════════════════ */
        .tp-body {
            max-width: 1140px;
            margin: 32px auto 0;
            padding: 0 28px;
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 28px;
            align-items: start;
        }

        /* ══════════════════════════════════════
           CARDS
        ══════════════════════════════════════ */
        .tp-card {
            background: #fff;
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 4px 28px rgba(36,64,146,.08);
            border: 1.5px solid rgba(36,64,146,.05);
            margin-bottom: 24px;
            transition: box-shadow .3s, transform .3s;
        }
        .tp-card:hover { box-shadow: 0 8px 40px rgba(36,64,146,.13); }
        .tp-card__header {
            display: flex; align-items: center; gap: 14px;
            margin-bottom: 22px; padding-bottom: 18px;
            border-bottom: 2px solid #f1f5f9;
        }
        .tp-card__icon {
            width: 48px; height: 48px;
            background: linear-gradient(135deg, #eef2ff, #dbeafe);
            border-radius: 12px;
            display: flex; align-items: center;
            justify-content: center;
            font-size: 1.35rem; flex-shrink: 0;
        }
        .tp-card__title {
            font-size: 1.05rem; font-weight: 800;
            color: #1e293b; position: relative; padding-left: 14px;
        }
        .tp-card__title::before {
            content: '';
            position: absolute; left: 0; top: 50%;
            transform: translateY(-50%);
            width: 4px; height: 80%;
            background: linear-gradient(180deg, #ef3e26, #f97316);
            border-radius: 2px;
        }

        /* ── Bio ── */
        .tp-bio { font-size: .95rem; color: #475569; line-height: 1.9; }
        .tp-bio p { margin-bottom: 12px; }
        .tp-bio p:last-child { margin-bottom: 0; }

        /* ── Courses ── */
        .tp-courses { display: flex; flex-direction: column; gap: 12px; }
        .tp-course-card {
            display: flex; gap: 16px; align-items: center;
            padding: 14px 16px; border-radius: 14px;
            border: 1.5px solid #e2e8f0; text-decoration: none;
            background: #fff;
            transition: border-color .25s, box-shadow .25s, transform .25s;
        }
        .tp-course-card:hover {
            border-color: #2748b5;
            box-shadow: 0 6px 24px rgba(36,64,146,.14);
            transform: translateY(-3px);
        }
        .tp-course-card__thumb {
            width: 76px; height: 58px;
            border-radius: 10px; object-fit: cover;
            background: #eef2ff; flex-shrink: 0;
        }
        .tp-course-card__thumb-placeholder {
            width: 76px; height: 58px;
            border-radius: 10px;
            background: linear-gradient(135deg, #eef2ff, #c7d2fe);
            display: flex; align-items: center;
            justify-content: center;
            font-size: 1.5rem; flex-shrink: 0;
        }
        .tp-course-card__info { flex: 1; min-width: 0; }
        .tp-course-card__title {
            font-size: .9rem; font-weight: 700;
            color: #1e293b; white-space: nowrap;
            overflow: hidden; text-overflow: ellipsis;
        }
        .tp-course-card__meta { font-size: .74rem; color: #94a3b8; margin-top: 4px; font-weight: 500; }
        .tp-course-card__arrow {
            color: #cbd5e1; font-size: 1.2rem;
            flex-shrink: 0;
            transition: color .2s, transform .2s;
        }
        .tp-course-card:hover .tp-course-card__arrow { color: #2748b5; transform: translateX(4px); }

        /* ── Sidebar Info ── */
        .tp-info-list { display: flex; flex-direction: column; gap: 16px; }
        .tp-info-row {
            display: flex; align-items: flex-start; gap: 12px;
            padding: 10px; border-radius: 10px;
            transition: background .2s;
        }
        .tp-info-row:hover { background: #f8fafc; }
        .tp-info-icon {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
            border-radius: 10px;
            display: flex; align-items: center;
            justify-content: center;
            font-size: 1rem; flex-shrink: 0;
        }
        .tp-info-label {
            font-size: .68rem; color: #94a3b8;
            font-weight: 700; text-transform: uppercase; letter-spacing: .06em;
        }
        .tp-info-value { font-size: .9rem; color: #334155; font-weight: 600; margin-top: 2px; }

        /* ── Empty ── */
        .tp-empty { text-align: center; padding: 40px 24px; color: #94a3b8; font-size: .9rem; }
        .tp-empty-icon { font-size: 2.8rem; margin-bottom: 12px; }

        /* ══════════════════════════════════════
           SCROLL REVEAL
        ══════════════════════════════════════ */
        .tp-reveal {
            opacity: 0;
            transform: translateY(28px);
            transition: opacity .7s cubic-bezier(.22,1,.36,1), transform .7s cubic-bezier(.22,1,.36,1);
        }
        .tp-reveal.is-visible { opacity: 1; transform: translateY(0); }
        .tp-reveal-delay-1 { transition-delay: .1s; }
        .tp-reveal-delay-2 { transition-delay: .2s; }

        /* ══════════════════════════════════════
           RESPONSIVE — Tablet ≤900px
        ══════════════════════════════════════ */
        @media (max-width: 900px) {
            .tp-body { grid-template-columns: 1fr; }
            .tp-sidebar {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
            .tp-sidebar .tp-card { margin-bottom: 0; }
        }

        /* ══════════════════════════════════════
           RESPONSIVE — Mobile ≤600px
        ══════════════════════════════════════ */
        @media (max-width: 600px) {
            .tp-hero { padding: 40px 0 0; }
            .tp-hero__inner {
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 20px;
                padding: 0 20px;
            }
            .tp-hero__photo,
            .tp-hero__photo-placeholder { width: 160px; height: 160px; border-radius: 16px; }
            .tp-hero__info { padding-bottom: 16px; }
            .tp-hero__tags { justify-content: center; }

            .tp-stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
                padding: 0 20px;
                margin-top: -10px;
            }
            .tp-stat:last-child { grid-column: 1 / -1; }

            .tp-body { padding: 0 16px; margin-top: 24px; }
            .tp-card { padding: 20px 16px; }
            .tp-sidebar { grid-template-columns: 1fr; }

            .tp-course-card__thumb,
            .tp-course-card__thumb-placeholder { width: 60px; height: 46px; }

            .tp-hero__wave { height: 36px; }
        }

        /* ══════════════════════════════════════
           RESPONSIVE — Tiny ≤380px
        ══════════════════════════════════════ */
        @media (max-width: 380px) {
            .tp-stats { grid-template-columns: 1fr; }
            .tp-stat:last-child { grid-column: auto; }
            .tp-hero__photo,
            .tp-hero__photo-placeholder { width: 130px; height: 130px; }
            .tp-hero__inner { padding: 0 16px; }
            .tp-body { padding: 0 12px; }
        }
    </style>
</head>
<body <?php body_class('tp-page'); ?>>
<div class="tp-wrap">

    <!-- ── Hero ── -->
    <div class="tp-hero">
        <div class="tp-hero__orb1"></div>
        <div class="tp-hero__orb2"></div>
        <div class="tp-hero__orb3"></div>
        <div class="tp-hero__inner">
            <div class="tp-hero__photo-wrap">
                <?php if ( $photo_url ) : ?>
                    <img src="<?php echo esc_url( $photo_url ); ?>"
                         alt="<?php echo esc_attr( $teacher->post_title ); ?>"
                         class="tp-hero__photo">
                <?php else : ?>
                    <div class="tp-hero__photo-placeholder">👤</div>
                <?php endif; ?>
            </div>
            <div class="tp-hero__info">
                <h1 class="tp-hero__name"><?php echo esc_html( $teacher->post_title ); ?></h1>
                <?php if ( $designation ) : ?>
                    <p class="tp-hero__designation">🎓 <?php echo esc_html( $designation ); ?></p>
                <?php endif; ?>
                <?php if ( $cat_names ) : ?>
                <div class="tp-hero__tags">
                    <?php foreach ( $cat_names as $name ) : ?>
                        <span class="tp-hero__tag"><?php echo esc_html( $name ); ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="tp-hero__wave"></div>
    </div>

    <!-- ── Stats ── -->
    <div class="tp-stats">
        <div class="tp-stat">
            <div class="tp-stat__num"><?php echo count( $courses ); ?></div>
            <div class="tp-stat__label">Courses</div>
        </div>
        <div class="tp-stat">
            <div class="tp-stat__num"><?php echo count( $cat_names ) ?: '—'; ?></div>
            <div class="tp-stat__label">Departments</div>
        </div>
        <div class="tp-stat">
            <div class="tp-stat__num"><?php echo get_the_date( 'Y', $teacher ); ?></div>
            <div class="tp-stat__label">Joined</div>
        </div>
    </div>

    <!-- ── Body ── -->
    <div class="tp-body">

        <!-- Left Column -->
        <div class="tp-left">

            <?php if ( $bio ) : ?>
            <div class="tp-card tp-reveal">
                <div class="tp-card__header">
                    <div class="tp-card__icon">📖</div>
                    <h2 class="tp-card__title">About <?php echo esc_html( $teacher->post_title ); ?></h2>
                </div>
                <div class="tp-bio"><?php echo wp_kses_post( wpautop( $bio ) ); ?></div>
            </div>
            <?php endif; ?>

            <div class="tp-card tp-reveal tp-reveal-delay-1">
                <div class="tp-card__header">
                    <div class="tp-card__icon">📚</div>
                    <h2 class="tp-card__title">Courses by <?php echo esc_html( $teacher->post_title ); ?></h2>
                </div>
                <?php if ( $courses ) : ?>
                <div class="tp-courses">
                    <?php foreach ( $courses as $co ) :
                        $thumb      = get_the_post_thumbnail_url( $co->ID, 'thumbnail' );
                        $age        = get_post_meta( $co->ID, '_cb_age_min', true );
                        $dur        = get_post_meta( $co->ID, '_cb_duration_months', true );
                        $sub        = get_post_meta( $co->ID, '_cb_subtitle', true );
                        $meta_parts = array_filter( [
                            $age ? 'Age ' . $age . '+' : '',
                            $dur ? $dur . ' months'    : '',
                            $sub ?: '',
                        ] );
                    ?>
                    <a href="<?php echo esc_url( get_permalink( $co->ID ) ); ?>" class="tp-course-card">
                        <?php if ( $thumb ) : ?>
                            <img src="<?php echo esc_url( $thumb ); ?>" alt="" class="tp-course-card__thumb">
                        <?php else : ?>
                            <div class="tp-course-card__thumb-placeholder">📘</div>
                        <?php endif; ?>
                        <div class="tp-course-card__info">
                            <div class="tp-course-card__title"><?php echo esc_html( $co->post_title ); ?></div>
                            <?php if ( $meta_parts ) : ?>
                                <div class="tp-course-card__meta"><?php echo esc_html( implode( ' · ', $meta_parts ) ); ?></div>
                            <?php endif; ?>
                        </div>
                        <span class="tp-course-card__arrow">→</span>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php else : ?>
                <div class="tp-empty">
                    <div class="tp-empty-icon">📭</div>
                    No courses assigned yet.
                </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- Sidebar -->
        <div class="tp-sidebar">
            <div class="tp-card tp-reveal tp-reveal-delay-2">
                <div class="tp-card__header">
                    <div class="tp-card__icon">ℹ️</div>
                    <h3 class="tp-card__title">Teacher Info</h3>
                </div>
                <div class="tp-info-list">
                    <div class="tp-info-row">
                        <div class="tp-info-icon">👤</div>
                        <div>
                            <div class="tp-info-label">Full Name</div>
                            <div class="tp-info-value"><?php echo esc_html( $teacher->post_title ); ?></div>
                        </div>
                    </div>
                    <?php if ( $designation ) : ?>
                    <div class="tp-info-row">
                        <div class="tp-info-icon">🎓</div>
                        <div>
                            <div class="tp-info-label">Designation</div>
                            <div class="tp-info-value"><?php echo esc_html( $designation ); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ( $cat_names ) : ?>
                    <div class="tp-info-row">
                        <div class="tp-info-icon">🏫</div>
                        <div>
                            <div class="tp-info-label">Departments</div>
                            <div class="tp-info-value"><?php echo esc_html( implode( ', ', $cat_names ) ); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="tp-info-row">
                        <div class="tp-info-icon">📚</div>
                        <div>
                            <div class="tp-info-label">Total Courses</div>
                            <div class="tp-info-value"><?php echo count( $courses ); ?> Course<?php echo count( $courses ) !== 1 ? 's' : ''; ?></div>
                        </div>
                    </div>
                   
                </div>
            </div>
        </div>

    </div><!-- .tp-body -->
</div><!-- .tp-wrap -->

<script>
(function () {
    /* Scroll-triggered reveal */
    var reveals = document.querySelectorAll('.tp-reveal');
    if (!reveals.length) return;
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (e.isIntersecting) {
                e.target.classList.add('is-visible');
                io.unobserve(e.target);
            }
        });
    }, { threshold: 0.10 });
    reveals.forEach(function (el) { io.observe(el); });
})();
</script>

<?php wp_footer(); ?>
</body>
</html>