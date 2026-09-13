<?php
/**
 * Template: Course Categories (الصفحة 1)
 * مطابق للأصل حرف بحرف: السلايدر (sl-section) + الكاتيجوري (tg-section)
 * في فايل واحد. الكلاسات كلها زي ما هي بالظبط.
 *
 * الصور الثابتة (الأزرار/الأسهم/صور السلايد الافتراضية) بتطلع من الثيم:
 *   wp-content/themes/hufix-theme/assets/images/...
 * والداتا (الكاتيجوري + سلايدز لوحة التحكم) ديناميك.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
// جذر صور الثيم.
$theme_img = get_stylesheet_directory_uri() . '/assets/images/';
// سلايدز لوحة التحكم (لو موجودة).
$cs_slides = class_exists( 'CS_Slider' ) ? CS_Slider::get_slides() : array();
// كل الكاتيجوريز.
$terms = get_terms( array(
	'taxonomy'   => CS_TAX,
	'hide_empty' => false,
	'orderby'    => 'name',
) );
?>
<section class="sl-section">
  <div class="sl-track" id="slTrack">
    <?php if ( ! empty( $cs_slides ) ) : ?>
      <?php foreach ( $cs_slides as $slide ) :
        $bg   = ! empty( $slide['image'] ) ? $slide['image'] : $theme_img . 'Slider_Traning/sliser-1.jpg';
        $link = ! empty( $slide['link'] ) ? $slide['link'] : '#';
      ?>
    <div class="sl-slide">
      <img class="sl-bg" src="<?php echo esc_url( $bg ); ?>" alt="slide">
      <div class="sl-overlay"></div>
      <div class="sl-content">
        <h2 class="sl-title"><?php echo esc_html( $slide['title'] ?? '' ); ?></h2>
        <p class="sl-desc"><?php echo esc_html( $slide['desc'] ?? '' ); ?></p>
        <a href="<?php echo esc_url( $link ); ?>" class="sl-link">
          <span><?php echo esc_html( ! empty( $slide['btn_text'] ) ? $slide['btn_text'] : cs__( 'Booking Now', 'slider_booking_now' ) ); ?></span>
          <img src="<?php echo esc_url( $theme_img . 'booking_NOw.png' ); ?>" alt="">
        </a>
      </div>
    </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <!-- Arrows -->
  <div class="sl-arrow sl-arrow-prev" id="slPrev">
    <img src="<?php echo esc_url( $theme_img . 'right_arrow.png' ); ?>" alt="prev">
  </div>
  <div class="sl-arrow sl-arrow-next" id="slNext">
    <img src="<?php echo esc_url( $theme_img . 'left_arrow.png' ); ?>" alt="next">
  </div>
  <!-- Dots -->
  <div class="sl-dots" id="slDots"></div>
</section>

<?php if ( count( $cs_slides ) > 0 ) : ?>
<style>
/* ============ Slider engine (فانكشناليتي بس — التصميم زي ما هو) ============ */
.sl-section{position:relative;overflow:hidden;}
.sl-dots{display:flex;align-items:center;justify-content:center;gap:8px;}
.sl-dots .sl-dot{width:9px;height:9px;border-radius:50%;background:rgba(255,255,255,.55);border:none;padding:0;cursor:pointer;transition:background .2s,transform .2s;}
.sl-dots .sl-dot.is-active{background:#fff;transform:scale(1.25);}
.sl-arrow{cursor:pointer;}
</style>
<script>
(function () {
	var track = document.getElementById( 'slTrack' );
	var prev  = document.getElementById( 'slPrev' );
	var next  = document.getElementById( 'slNext' );
	var dotsEl = document.getElementById( 'slDots' );
	if ( ! track ) return;

	var slides = Array.prototype.slice.call( track.querySelectorAll( '.sl-slide' ) );
	var total  = slides.length;
	if ( total < 1 ) return;

	// لو سلايد واحد بس، اخفي الأسهم والدوتس ومفيش داعي نحرّك حاجة.
	if ( total <= 1 ) {
		if ( prev ) prev.style.display = 'none';
		if ( next ) next.style.display = 'none';
		if ( dotsEl ) dotsEl.style.display = 'none';
		return;
	}

	var index = 0;
	var AUTOPLAY_MS = 9000;
	var FADE_MS = 1000;
	var timer = null;

	/**
	 * بنتحكم في السلايدر بـ inline styles من الجافاسكريبت مباشرة (مش عن طريق كلاسات CSS خارجية)
	 * عشان نضمن إنها تكسب أي ستايل تاني من ثيم/بلجن تاني ممكن يكون بيعمل تعارض مع نفس الكلاسات دي
	 * (وده اللي كان بيسبب إن السلايد التاني يطلع فاضي/أبيض).
	 */

	// 1) نقيس ارتفاع أول سلايد وهو لسه في وضعه الطبيعي، قبل ما نحوّل الكل absolute.
	var naturalHeight = slides[0].getBoundingClientRect().height;

	track.style.position = 'relative';
	if ( naturalHeight ) {
		track.style.height = naturalHeight + 'px';
	}

	// 2) رصّ كل السلايدز فوق بعض (Cross-fade) بدل الاعتماد على transform/flex.
	slides.forEach( function ( slide, i ) {
		slide.style.position = 'absolute';
		slide.style.top = '0';
		slide.style.left = '0';
		slide.style.width = '100%';
		slide.style.height = '100%';
		slide.style.opacity = ( i === 0 ) ? '1' : '0';
		slide.style.zIndex = ( i === 0 ) ? '2' : '1';
		slide.style.transition = 'opacity ' + ( FADE_MS / 1000 ) + 's ease';
		slide.style.pointerEvents = ( i === 0 ) ? 'auto' : 'none';
	} );

	// ابني الدوتس (نقاط التنقل).
	if ( dotsEl ) {
		dotsEl.innerHTML = '';
		slides.forEach( function ( _, i ) {
			var dot = document.createElement( 'button' );
			dot.type = 'button';
			dot.className = 'sl-dot' + ( i === 0 ? ' is-active' : '' );
			dot.setAttribute( 'aria-label', 'Go to slide ' + ( i + 1 ) );
			dot.addEventListener( 'click', function () {
				goTo( i );
				restartAutoplay();
			} );
			dotsEl.appendChild( dot );
		} );
	}

	function render() {
		slides.forEach( function ( slide, i ) {
			var active = ( i === index );
			slide.style.opacity = active ? '1' : '0';
			slide.style.zIndex = active ? '2' : '1';
			slide.style.pointerEvents = active ? 'auto' : 'none';
		} );
		if ( dotsEl ) {
			Array.prototype.forEach.call( dotsEl.children, function ( dot, i ) {
				dot.classList.toggle( 'is-active', i === index );
			} );
		}
	}

	function goTo( i ) {
		index = ( i + total ) % total;
		render();
	}

	function goNext() { goTo( index + 1 ); }
	function goPrev() { goTo( index - 1 ); }

	function startAutoplay() {
		timer = setInterval( goNext, AUTOPLAY_MS );
	}
	function stopAutoplay() {
		if ( timer ) clearInterval( timer );
	}
	function restartAutoplay() {
		stopAutoplay();
		startAutoplay();
	}

	if ( next ) {
		next.addEventListener( 'click', function () {
			goNext();
			restartAutoplay();
		} );
	}
	if ( prev ) {
		prev.addEventListener( 'click', function () {
			goPrev();
			restartAutoplay();
		} );
	}

	var section = track.closest( '.sl-section' );
	if ( section ) {
		section.addEventListener( 'mouseenter', stopAutoplay );
		section.addEventListener( 'mouseleave', startAutoplay );
	}

	// لو المقاس اتغير (موبايل/تابلت) نعيد قياس ارتفاع السلايد النشط عشان الترابيزة متتكسرش.
	var resizeTimer;
	window.addEventListener( 'resize', function () {
		clearTimeout( resizeTimer );
		resizeTimer = setTimeout( function () {
			var active = slides[ index ];
			var prevPosition = active.style.position;
			active.style.position = 'static';
			var h = active.getBoundingClientRect().height;
			active.style.position = prevPosition;
			if ( h ) {
				track.style.height = h + 'px';
			}
		}, 200 );
	} );

	render();
	startAutoplay();
})();
</script>
<?php endif; ?>

<section class="tg-section">
  <div class="tg-container">
    <h2 class="tg-heading"><?php cs_e( 'Training category', 'categories_heading' ); ?></h2>
    <p class="tg-subheading"><?php cs_e( 'Our top-requested training themes include:', 'categories_subheading' ); ?></p>
    <div class="tg-grid">
      <?php if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) : ?>
        <?php foreach ( $terms as $term ) :
          $img  = CS_Taxonomy::image_url( $term->term_id, 'medium' );
          $link = get_term_link( $term );
        ?>
      <div class="tg-item">
        <a href="<?php echo esc_url( $link ); ?>">
          <div class="tg-card">
            <div class="tg-icon">
              <?php if ( $img ) : ?>
              <img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $term->name ); ?>">
              <?php endif; ?>
            </div>
            <div class="tg-title"><?php echo esc_html( $term->name ); ?></div>
          </div>
        </a>
      </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php get_footer();