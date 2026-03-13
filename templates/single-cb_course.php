<?php
/**
 * Course Builder — Single Course Page
 * Design: Pedago Kids reference (Poppins, #244092 primary, #EF3E26 accent)
 */
defined( 'ABSPATH' ) || exit;

$cid       = get_the_ID();
$title     = get_the_title();
$subtitle  = get_post_meta( $cid, '_cb_subtitle',          true );
$duration  = (int) get_post_meta( $cid, '_cb_duration_months', true );
$age_min   = (int) get_post_meta( $cid, '_cb_age_min',         true );
$live      = (int) get_post_meta( $cid, '_cb_live_classes',    true );
$wc_id     = (int) get_post_meta( $cid, '_cb_wc_product_id',   true );
$video_url = get_post_meta( $cid, '_cb_video_url', true );
$thumb     = get_the_post_thumbnail_url( $cid, 'large' ) ?: '';

$objectives = array_values( array_filter( (array) json_decode( get_post_meta( $cid, '_cb_learning_objectives', true ) ?: '[]', true ), 'trim' ) );
$overview   = array_values( array_filter( (array) json_decode( get_post_meta( $cid, '_cb_programme_overview',  true ) ?: '[]', true ), 'trim' ) );
$units_raw  = (array) json_decode( get_post_meta( $cid, '_cb_course_content', true ) ?: '[]', true );
$units      = array_values( array_filter( $units_raw, fn( $u ) => ! empty( $u['title'] ) ) );
$support    = array_values( array_filter( (array) json_decode( get_post_meta( $cid, '_cb_additional_support', true ) ?: '[]', true ), 'trim' ) );

/* Dept / taxonomy */
$terms     = wp_get_post_terms( $cid, 'cb_category' );
$dept_id   = ( ! empty( $terms ) && ! is_wp_error( $terms ) ) ? (int) $terms[0]->term_id : 0;
$dept_name = ( ! empty( $terms ) && ! is_wp_error( $terms ) ) ? $terms[0]->name : '';

/* Teachers in same dept */
$dept_teachers = [];
if ( $dept_id ) {
	foreach ( get_posts( [ 'post_type' => 'cb_teacher', 'posts_per_page' => -1, 'post_status' => 'publish' ] ) as $t ) {
		$tcats = array_map( 'intval', (array) json_decode( get_post_meta( $t->ID, '_cb_categories', true ) ?: '[]', true ) );
		if ( in_array( $dept_id, $tcats, true ) ) $dept_teachers[] = $t;
	}
}

/* WooCommerce */
$product   = ( $wc_id && function_exists( 'wc_get_product' ) ) ? wc_get_product( $wc_id ) : null;
$is_free   = false;
$vars_data = [];
$s_price   = '';
$s_url     = '';

if ( $product ) {
	if ( $product->get_type() === 'variable' ) {
		foreach ( $product->get_available_variations() as $v ) {
			$var = wc_get_product( $v['variation_id'] );
			if ( ! $var ) continue;
			$lbls = [];
			foreach ( $v['attributes'] as $k => $val ) {
				$tx     = str_replace( 'attribute_', '', $k );
				$tobj   = get_term_by( 'slug', $val, $tx );
				$lbls[] = $tobj ? $tobj->name : ucfirst( str_replace( '-', ' ', $val ) );
			}
			$label = implode( ' / ', $lbls ) ?: 'Option';
			$vars_data[] = [
				'label'      => $label,
				'price_html' => wc_price( $var->get_price() ),
				'period'     => strtolower( $label ),
				'url'        => add_query_arg( [ 'add-to-cart' => $v['variation_id'] ], wc_get_checkout_url() ),
			];
		}
	} elseif ( (float) $product->get_price() == 0 ) {
		$is_free = true;
	} else {
		$s_price = $product->get_price_html();
		$s_url   = add_query_arg( [ 'add-to-cart' => $wc_id ], wc_get_checkout_url() );
	}
}

/* Similar courses */
$similar = $dept_id ? get_posts( [
	'post_type' => 'cb_course', 'post_status' => 'publish',
	'posts_per_page' => 4, 'post__not_in' => [ $cid ],
	'tax_query' => [ [ 'taxonomy' => 'cb_category', 'field' => 'term_id', 'terms' => $dept_id ] ],
] ) : [];

$unit_count = count( $units );

/* Video embed helper */
if ( ! function_exists( 'cb_embed_url' ) ) {
	function cb_embed_url( string $url ): string {
		if ( preg_match( '/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\s]+)/', $url, $m ) )
			return 'https://www.youtube.com/embed/' . $m[1] . '?rel=0&modestbranding=1';
		if ( preg_match( '/vimeo\.com\/(\d+)/', $url, $m ) )
			return 'https://player.vimeo.com/video/' . $m[1];
		return '';
	}
}
$embed_url = $video_url ? cb_embed_url( $video_url ) : '';

get_header();
?>
<div class="cbo">

<!-- ══════════════════════  HERO  ══════════════════════ -->
<section class="cbo__hero">
	<div class="cbo__container">

		<?php if ( $dept_name ) : ?>
		<nav class="cbo__breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
			<a href="#">Courses</a>
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
			<span><?php echo esc_html( $dept_name ); ?></span>
		</nav>
		<?php endif; ?>

		<h1 class="cbo__course-title"><?php echo esc_html( $title ); ?></h1>

		<?php if ( $subtitle ) : ?>
		<p class="cbo__course-tagline"><?php echo esc_html( $subtitle ); ?></p>
		<?php endif; ?>

		<div class="cbo__hero-meta">
			<?php if ( $unit_count ) : ?><span class="cbo__meta-pill"><?php echo $unit_count; ?> Units</span><?php endif; ?>
			<?php if ( $live )        : ?><span class="cbo__meta-pill"><?php echo $live; ?> Live Classes</span><?php endif; ?>
			<?php if ( $duration )    : ?><span class="cbo__meta-pill"><?php echo $duration; ?> Months</span><?php endif; ?>
			<?php if ( $age_min )     : ?><span class="cbo__meta-pill">Age <?php echo $age_min; ?>+</span><?php endif; ?>
			<span class="cbo__meta-pill">Online Live</span>
		</div>

	</div>
</section>

<!-- ══════════════════════  BODY  ══════════════════════ -->
<div class="cbo__container">
<div class="cbo__layout">

	<!-- ═════ LEFT COLUMN ═════ -->
	<div class="cbo__left-col">

		<!-- Learning Objectives -->
		<?php if ( ! empty( $objectives ) ) : ?>
		<div class="cbo__card">
			<div class="cbo__card-header">
				<div class="cbo__card-icon">🎯</div>
				<h2>Learning Objectives</h2>
			</div>
			<ul class="cbo__styled-list">
				<?php foreach ( $objectives as $obj ) : ?><li><?php echo esc_html( $obj ); ?></li><?php endforeach; ?>
			</ul>
		</div>
		<?php endif; ?>

		<!-- Programme Overview -->
		<?php if ( ! empty( $overview ) ) : ?>
		<div class="cbo__card">
			<div class="cbo__card-header">
				<div class="cbo__card-icon">📋</div>
				<h2>Programme Overview</h2>
			</div>
			<ul class="cbo__styled-list cbo__styled-list--icon">
				<?php foreach ( $overview as $pt ) : ?><li>📌 <?php echo esc_html( $pt ); ?></li><?php endforeach; ?>
			</ul>
		</div>
		<?php endif; ?>

		<!-- Course Contents -->
		<?php if ( ! empty( $units ) ) : ?>
		<div class="cbo__card">
			<div class="cbo__card-header">
				<div class="cbo__card-icon">📚</div>
				<h2>Course Contents</h2>
			</div>
			<div class="cbo__units-grid">
				<?php foreach ( $units as $i => $unit ) : ?>
				<div class="cbo__unit-item">
					<span class="cbo__unit-num"><?php printf( '%02d', $i + 1 ); ?></span>
					<div>
						<strong><?php echo esc_html( $unit['title'] ); ?></strong>
						<?php if ( ! empty( $unit['lessons'] ) ) : ?><p><?php echo esc_html( $unit['lessons'] ); ?></p><?php endif; ?>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>

		<!-- Additional Support -->
		<?php if ( ! empty( $support ) ) : ?>
		<div class="cbo__card">
			<div class="cbo__card-header">
				<div class="cbo__card-icon">🤝</div>
				<h2>Additional Support</h2>
			</div>
			<ul class="cbo__styled-list">
				<?php foreach ( $support as $s ) : ?><li><?php echo esc_html( $s ); ?></li><?php endforeach; ?>
			</ul>
		</div>
		<?php endif; ?>

		<!-- Unique Features -->
		<div class="cbo__card">
			<div class="cbo__card-header">
				<div class="cbo__card-icon">✨</div>
				<h2>Unique Features</h2>
			</div>
			<div class="cbo__features-grid">
				<?php foreach ( [
					[ '🧠', 'Neuro-Friendly Design',   'Multi-sensory tasks for all learning styles' ],
					[ '🎵', 'Music-Led Learning',       'Jolly Songs make phonics memorable and fun' ],
					[ '🏆', 'Achievement Badges',       'Digital rewards after every completed unit' ],
					[ '📱', 'Mobile-Friendly Sessions', 'Learn on any device, anywhere' ],
					[ '🌍', 'Native-Level Instructors', 'Qualified TEFL-certified teachers' ],
					[ '📊', 'Data-Driven Progress',     "Real-time analytics for each child's growth" ],
				] as $f ) : ?>
				<div class="cbo__feature-item">
					<span class="cbo__feat-icon"><?php echo $f[0]; ?></span>
					<div><strong><?php echo esc_html( $f[1] ); ?></strong><p><?php echo esc_html( $f[2] ); ?></p></div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Course Instructors -->
		<?php if ( ! empty( $dept_teachers ) ) : ?>
		<div class="cbo__card">
			<div class="cbo__card-header">
				<div class="cbo__card-icon">👩‍🏫</div>
				<h2>Course Instructors</h2>
			</div>
			<div class="cbo__instructors-grid">
				<?php foreach ( $dept_teachers as $t ) :
					$pid   = (int) get_post_meta( $t->ID, '_cb_photo_id', true );
					$photo = $pid ? wp_get_attachment_image_url( $pid, 'thumbnail' ) : '';
					$desig = get_post_meta( $t->ID, '_cb_designation', true );
					$parts = explode( ' ', trim( $t->post_title ) );
					$ini   = strtoupper( ( $parts[0][0] ?? '' ) . ( end( $parts )[0] ?? '' ) );
				?>
				<div class="cbo__instructor-card">
					<div class="cbo__instructor-img-wrap">
						<?php if ( $photo ) : ?>
						<img src="<?php echo esc_url( $photo ); ?>" alt="<?php echo esc_attr( $t->post_title ); ?>">
						<?php else : ?>
						<div class="cbo__instructor-ini" style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#244092,#3558c0);color:#fff;font-size:22px;font-weight:800;display:flex;align-items:center;justify-content:center;"><?php echo esc_html( $ini ); ?></div>
						<?php endif; ?>
					</div>
					<div class="cbo__instructor-info">
						<strong><?php echo esc_html( $t->post_title ); ?></strong>
						<?php if ( $desig ) : ?><span><?php echo esc_html( $desig ); ?></span><?php endif; ?>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>

	</div><!-- /left-col -->

	<!-- ═════ RIGHT SIDEBAR ═════ -->
	<div class="cbo__sidebar">

		<!-- ENROL CARD -->
		<div class="cbo__enrol-card">
			<h3 class="cbo__enrol-title">Enrol in this Course</h3>

			<?php if ( ! empty( $vars_data ) ) : ?>
			<div class="cbo__price-toggle">
				<?php foreach ( $vars_data as $i => $v ) : ?>
				<button class="cbo__toggle-btn<?php echo $i === 0 ? ' active' : ''; ?>"
					data-html="<?php echo esc_attr( $v['price_html'] ); ?>"
					data-period="<?php echo esc_attr( $v['period'] ); ?>"
					data-url="<?php echo esc_url( $v['url'] ); ?>">
					<?php echo esc_html( $v['label'] ); ?>
				</button>
				<?php endforeach; ?>
			</div>
			<div class="cbo__price-block">
				<span class="cbo__price-amount" id="cboPriceVal"><?php echo wp_kses_post( $vars_data[0]['price_html'] ); ?></span>
				<span class="cbo__price-period" id="cboPricePer">/ <?php echo esc_html( $vars_data[0]['period'] ); ?></span>
			</div>
			<p class="cbo__price-note" id="cboPriceNote">Pay one time – Save on yearly plan</p>
			<a class="cbo__enrol-btn" id="cboEnrolBtn" href="<?php echo esc_url( $vars_data[0]['url'] ); ?>">🚀 Enrol Now</a>

			<?php elseif ( $is_free ) : ?>
			<p class="cbo__price-free">✓ This course is FREE</p>
			<a class="cbo__enrol-btn" href="<?php echo esc_url( get_permalink( $wc_id ) ); ?>">🚀 Enrol Free</a>

			<?php elseif ( $s_price ) : ?>
			<div class="cbo__price-block">
				<span class="cbo__price-amount"><?php echo wp_kses_post( $s_price ); ?></span>
			</div>
			<a class="cbo__enrol-btn" href="<?php echo esc_url( $s_url ); ?>">🚀 Enrol Now</a>

			<?php else : ?>
			<a class="cbo__enrol-btn cbo__enrol-btn--ghost" href="#cbo-demo">Register Interest</a>
			<?php endif; ?>

			<ul class="cbo__enrol-perks">
				<?php if ( $unit_count ) : ?><li>✅ Full access to all <?php echo $unit_count; ?> units</li><?php endif; ?>
				<li>✅ Live sessions + recordings</li>
				<li>✅ Certificate on completion</li>
			</ul>
		</div>

		<!-- COURSE EXPLAINER VIDEO (always shown between Enrol and Demo) -->
		<div class="cbo__card">
			<div class="cbo__card-header">
				<div class="cbo__card-icon">🎬</div>
				<h3>Course Explainer</h3>
			</div>
			<div class="cbo__video-box">
				<?php if ( $embed_url ) : ?>
				<iframe src="<?php echo esc_url( $embed_url ); ?>" frameborder="0" allowfullscreen loading="lazy"></iframe>
				<?php elseif ( $thumb ) : ?>
				<img src="<?php echo esc_url( $thumb ); ?>" alt="" class="cbo__video-thumb">
				<div class="cbo__play-overlay">
					<div class="cbo__play-btn">
						<svg viewBox="0 0 24 24" fill="white" width="36" height="36"><path d="M8 5v14l11-7z"/></svg>
					</div>
				</div>
				<div class="cbo__video-label">Watch Course Overview</div>
				<?php else : ?>
				<div class="cbo__video-placeholder">
					<div class="cbo__play-btn"><svg viewBox="0 0 24 24" fill="white" width="36" height="36"><path d="M8 5v14l11-7z"/></svg></div>
					<p>Add a YouTube URL in the course editor to show the explainer video here.</p>
				</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- REGISTER DEMO CLASS -->
		<div class="cbo__card" id="cbo-demo">
			<div class="cbo__card-header">
				<div class="cbo__card-icon">📝</div>
				<h3>Register for Demo Class</h3>
			</div>
			<p class="cbo__demo-sub">Join a free 30-minute demo class — no commitment required.</p>
			<form onsubmit="cboDemoSubmit(event)">
				<div class="cbo__form-group">
					<label>Student Name</label>
					<input type="text" placeholder="e.g. Ayaan Rahman" required>
				</div>
				<div class="cbo__form-group">
					<label>Parent Phone Number</label>
					<input type="tel" placeholder="+880 1X XX XX XXXX" required>
				</div>
				<button type="submit" class="cbo__enrol-btn" style="margin-top:4px">📅 Book My Free Demo</button>
				<div class="cbo__form-ok" id="cboDemoOk">✓ We'll contact you shortly to confirm your demo!</div>
			</form>
		</div>

		<!-- BATCH SCHEDULE -->
		<div class="cbo__card">
			<div class="cbo__card-header">
				<div class="cbo__card-icon">🗓️</div>
				<h3>Batch Schedule</h3>
			</div>
			<div class="cbo__schedule-grid">
				<div>
					<div class="cbo__schedule-heading cbo__schedule-heading--morning">☀️ Morning</div>
					<div class="cbo__time-pill">8:00 AM – 8:30 AM</div>
					<div class="cbo__time-pill">9:00 AM – 9:30 AM</div>
					<div class="cbo__time-pill">10:00 AM – 10:30 AM</div>
					<div class="cbo__time-pill">11:00 AM – 11:30 AM</div>
				</div>
				<div>
					<div class="cbo__schedule-heading cbo__schedule-heading--evening">🌙 Evening</div>
					<div class="cbo__time-pill">4:00 PM – 4:30 PM</div>
					<div class="cbo__time-pill">5:00 PM – 5:30 PM</div>
					<div class="cbo__time-pill">6:00 PM – 6:30 PM</div>
					<div class="cbo__time-pill">7:00 PM – 7:30 PM</div>
				</div>
			</div>
		</div>

		<!-- 24/7 SUPPORT -->
		<div class="cbo__support-card">
			<div class="cbo__support-badge">24/7</div>
			<h3>Always Here For You</h3>
			<ul class="cbo__support-list">
				<li><span class="cbo__s-icon">📞</span><span>Parent helpline &amp; support</span></li>
				<li><span class="cbo__s-icon">🔐</span><span>Secure access to recordings</span></li>
				<li><span class="cbo__s-icon">🔄</span><span>Missed class recovery</span></li>
				<li><span class="cbo__s-icon">👨‍👩‍👧</span><span>Parent–teacher sessions</span></li>
				<li><span class="cbo__s-icon">📈</span><span>Learning progress tracking</span></li>
			</ul>
		</div>

	</div><!-- /sidebar -->

</div><!-- .cbo__layout -->
</div><!-- .cbo__container -->

<!-- ══════════════════════  SIMILAR COURSES  ══════════════════════ -->
<?php if ( ! empty( $similar ) ) :
	$grads = [
		'linear-gradient(175deg,#3a5bbf 0%,#244092 100%)',
		'linear-gradient(175deg,#16b89e 0%,#0e8a7a 100%)',
		'linear-gradient(175deg,#a865e0 0%,#7b3fbe 100%)',
		'linear-gradient(175deg,#e05a4b 0%,#c0392b 100%)',
	];
?>
<section class="cbo__similar">
	<div class="cbo__container">
		<div class="cbo__section-header">
			<h2>Similar Courses</h2>
			<p>Continue your child's learning journey</p>
		</div>
		<div class="cbo__similar-grid">
			<?php foreach ( $similar as $idx => $sc ) :
				$sc_thumb = get_the_post_thumbnail_url( $sc->ID, 'medium' ) ?: '';
				$sc_sub   = get_post_meta( $sc->ID, '_cb_subtitle', true );
				$sc_dur   = (int) get_post_meta( $sc->ID, '_cb_duration_months', true );
				$sc_live  = (int) get_post_meta( $sc->ID, '_cb_live_classes', true );
				$sc_terms = wp_get_post_terms( $sc->ID, 'cb_category' );
				$sc_dept  = ( ! empty( $sc_terms ) && ! is_wp_error( $sc_terms ) ) ? $sc_terms[0]->name : '';
			?>
			<div class="cbo__course-card">
				<div class="cbo__cc-hero" style="background:<?php echo esc_attr( $grads[ $idx % 4 ] ); ?>">
					<?php if ( $sc_dept ) : ?>
					<span class="cbo__cc-subject"><?php echo esc_html( $sc_dept ); ?></span>
					<?php endif; ?>
					<span class="cbo__cc-badge">Level <?php echo $idx + 2; ?></span>
					<?php if ( $sc_thumb ) : ?>
					<div class="cbo__cc-img"><img src="<?php echo esc_url( $sc_thumb ); ?>" alt=""></div>
					<?php endif; ?>
				</div>
				<div class="cbo__cc-body">
					<div class="cbo__cc-meta">
						<?php if ( $sc_live ) : ?>
						<span class="cbo__cc-meta-item">
							<span class="cbo__cc-meta-icon cbo__cc-meta-icon--play">▶</span>
							<span><strong><?php echo $sc_live; ?></strong> Live Class</span>
						</span>
						<?php endif; if ( $sc_dur ) : ?>
						<span class="cbo__cc-meta-item">
							<span class="cbo__cc-meta-icon cbo__cc-meta-icon--cal">📅</span>
							<span>Duration<br><strong><?php echo $sc_dur; ?> Months</strong></span>
						</span>
						<?php endif; ?>
					</div>
					<h4 class="cbo__cc-title">
						<a href="<?php echo get_permalink( $sc->ID ); ?>"><?php echo esc_html( $sc->post_title ); ?></a>
					</h4>
					<?php if ( $sc_sub ) : ?>
					<p class="cbo__cc-desc"><?php echo esc_html( wp_trim_words( $sc_sub, 16 ) ); ?></p>
					<?php endif; ?>
					<a class="cbo__cc-more" href="<?php echo get_permalink( $sc->ID ); ?>">Learn More ↗</a>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

</div><!-- .cbo -->

<script>
/* Price toggle */
(function(){
	var tabs = document.querySelectorAll('.cbo__toggle-btn');
	if (!tabs.length) return;
	tabs.forEach(function(tab){
		tab.addEventListener('click', function(){
			tabs.forEach(function(t){ t.classList.remove('active'); });
			this.classList.add('active');
			var pv  = document.getElementById('cboPriceVal');
			var pp  = document.getElementById('cboPricePer');
			var pn  = document.getElementById('cboPriceNote');
			var btn = document.getElementById('cboEnrolBtn');
			if (pv)  pv.innerHTML   = this.dataset.html;
			if (pp)  pp.textContent = '/ ' + this.dataset.period;
			if (btn) btn.href       = this.dataset.url;
			if (pn)  pn.style.visibility = (this.dataset.period.indexOf('year') !== -1) ? 'visible' : 'hidden';
		});
	});
})();

/* Demo form */
function cboDemoSubmit(e){
	e.preventDefault();
	var ok  = document.getElementById('cboDemoOk');
	var btn = e.target.querySelector('button[type=submit]');
	if (ok)  ok.style.display = 'block';
	if (btn) btn.disabled     = true;
}

/* Scroll reveal */
(function(){
	if (!('IntersectionObserver' in window)) return;
	var els = document.querySelectorAll('.cbo__card,.cbo__enrol-card,.cbo__support-card,.cbo__course-card');
	els.forEach(function(el){
		el.style.cssText += 'opacity:0;transform:translateY(24px);transition:opacity .5s ease,transform .5s ease;';
	});
	var obs = new IntersectionObserver(function(entries){
		entries.forEach(function(e){
			if(e.isIntersecting){
				e.target.style.opacity='1';
				e.target.style.transform='translateY(0)';
				obs.unobserve(e.target);
			}
		});
	},{threshold:0.08});
	els.forEach(function(el){ obs.observe(el); });
})();
</script>
<?php get_footer(); ?>
