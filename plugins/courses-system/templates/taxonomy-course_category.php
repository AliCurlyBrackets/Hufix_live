<?php
/**
 * Template: Single Category (الصفحة 2) — بفلترة أجاكس.
 * اللود الأول: كروت Summary (من غير مدينة/تاريخ). مع أي فلتر: Detailed.
 * الكلاسات كلها مطابقة للأصل.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

// علامة تشخيصية مؤقتة: لو شفتها في "View Page Source" في المتصفح، معناه
// إن قالب البلجن ده هو اللي فعلاً شغال (مش نسخة تانية من الثيم). ممكن
// تتشال بعد ما نتأكد من المشكلة. شوف الشرح في الرد اللي بعت فيه v22.
echo "\n<!-- CS_PLUGIN_TEMPLATE_MARKER_V22 -->\n";

$term      = get_queried_object();
$theme_img = get_stylesheet_directory_uri() . '/assets/images/';

// اللود الأول = كاتيجوري الصفحة، من غير فلاتر => Summary.
$cards        = CS_Listing::get_cards( array( 'term_id' => $term->term_id ) );
$locations    = CS_Listing::used_countries( $term->term_id );
$languages    = CS_Listing::used_languages( $term->term_id );
$modes        = CS_Listing::used_modes( $term->term_id );
$cat_courses  = CS_Listing::category_masters( $term->term_id );

$is_ar_months = ( strpos( get_locale(), 'ar' ) === 0 );

$months = $is_ar_months
	? array( 1=>'يناير',2=>'فبراير',3=>'مارس',4=>'أبريل',5=>'مايو',6=>'يونيو',7=>'يوليو',8=>'أغسطس',9=>'سبتمبر',10=>'أكتوبر',11=>'نوفمبر',12=>'ديسمبر' )
	: array( 1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December' );

$cs_slides = class_exists( 'CS_Slider' ) ? CS_Slider::get_slides( $term->term_id ) : array();

// تشخيص مؤقت: ?cs_debug=1 في الرابط (أدمن بس) بيوري ليه الكاتيجوري دي
// راجعة "0 كورسات". اتشال بعد ما تتحل المشكلة -- شوف
// CS_Listing::debug_term_diagnostics().
if ( isset( $_GET['cs_debug'] ) && current_user_can( 'manage_options' ) ) {
	$cs_debug_data = CS_Listing::debug_term_diagnostics( $term->term_id );
	echo '<pre dir="ltr" style="direction:ltr;text-align:left;background:#111;color:#0f0;padding:16px;margin:16px;font-size:12px;white-space:pre-wrap;overflow:auto;border-radius:6px;">';
	echo esc_html( print_r( $cs_debug_data, true ) );
	echo '</pre>';
}
?>

<section class="sl-section">
  <div class="sl-track" id="slTrack">
    <?php foreach ( $cs_slides as $slide ) :
      $bg = ! empty( $slide['image'] ) ? $slide['image'] : $theme_img . 'Slider_Traning/sliser-1.jpg';
    ?>
    <div class="sl-slide">
      <img class="sl-bg" src="<?php echo esc_url( $bg ); ?>" alt="slide">
      <div class="sl-overlay"></div>
      <div class="sl-content">
        <h2 class="sl-title"><?php echo esc_html( $slide['title'] ?? '' ); ?></h2>
        <p class="sl-desc"><?php echo esc_html( $slide['desc'] ?? '' ); ?></p>
        <a href="<?php echo esc_url( ! empty( $slide['link'] ) ? $slide['link'] : '#' ); ?>" class="sl-link">
          <span><?php echo esc_html( ! empty( $slide['btn_text'] ) ? $slide['btn_text'] : cs__( 'Booking Now', 'slider_booking_now' ) ); ?></span>
          <img src="<?php echo esc_url( $theme_img . 'booking_NOw.png' ); ?>" alt="">
        </a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="sl-arrow sl-arrow-prev" id="slPrev"><img src="<?php echo esc_url( $theme_img . 'right_arrow.png' ); ?>" alt="prev"></div>
  <div class="sl-arrow sl-arrow-next" id="slNext"><img src="<?php echo esc_url( $theme_img . 'left_arrow.png' ); ?>" alt="next"></div>
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


<form id="cs-filter-form" data-term-id="<?php echo (int) $term->term_id; ?>" onsubmit="return false;">
<div class="cs-filterbar">
  <!-- <div class="cs-search">
    <img src="<?php echo esc_url( $theme_img . 'search_black.png' ); ?>" alt="search">
    <input type="text" name="q" placeholder="Search for a training...">
  </div> -->

  <select class="cs-select" name="course">
    <option value="0"><?php cs_e( 'All Courses', 'filter_all_courses' ); ?></option>
    <?php foreach ( $cat_courses as $mid => $mtitle ) : ?>
      <option value="<?php echo (int) $mid; ?>"><?php echo esc_html( $mtitle ); ?></option>
    <?php endforeach; ?>
  </select>

  <select class="cs-select" name="mode">
    <option value=""><?php cs_e( 'All Modes', 'filter_all_modes' ); ?></option>
    <?php foreach ( $modes as $key => $label ) : ?>
      <option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
    <?php endforeach; ?>
  </select>

  <select class="cs-select" name="lang">
    <option value=""><?php cs_e( 'All Languages', 'filter_all_languages' ); ?></option>
    <?php foreach ( $languages as $key => $label ) : ?>
      <option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
    <?php endforeach; ?>
  </select>

  <select class="cs-select" name="loc">
    <option value=""><?php cs_e( 'All Locations', 'filter_all_locations' ); ?></option>
    <?php foreach ( $locations as $code => $name ) : ?>
      <option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $name ); ?></option>
    <?php endforeach; ?>
  </select>

  <select class="cs-select" name="month">
    <option value="0"><?php cs_e( 'All Months', 'filter_all_months' ); ?></option>
    <?php foreach ( $months as $n => $mname ) : ?>
      <option value="<?php echo $n; ?>"><?php echo esc_html( $mname ); ?></option>
    <?php endforeach; ?>
  </select>

  <!-- <button class="cs-search-btn" type="submit">
    <img src="<?php echo esc_url( $theme_img . 'search.png' ); ?>" alt="">
    Search
  </button> -->
</div>
</form>

<style>
/* ============================================================
   Custom dropdown skin لـ .cs-select
   الـ <select> الأصلي بيفضل موجود (مخفي) عشان أي كود فلترة/أجاكس
   شغال على الـ change event بتاعه يفضل شغال زي ما هو بالظبط.
   ============================================================ */

.cs-filterbar { display: flex; gap: 14px; flex-wrap: wrap; }

.cs-select-wrap {
	position: relative;
	display: inline-block;
	flex: 1 1 auto;    /* بيوزّع المساحة الفاضية بالتساوي، وبردو محترم أقل عرض يسع النص جواه */
}

/* نخفي الـ select الأصلي بصريًا بس نسيبه شغال (مش display:none)
   عشان لو في كود بيعتمد على قياساته أو accessibility. */
.cs-select-wrap select.cs-select {
	position: absolute;
	inset: 0;
	width: 100%;
	height: 100%;
	opacity: 0;
	pointer-events: none;
}

.cs-select-trigger {
	display: flex;
	align-items: center;
	gap: 8px;
	width: 100%;
	box-sizing: border-box;
	padding: 10px 14px;
	border: 1px solid #16a3a3;      /* لون البوردر — غيّره هنا */
	border-radius: 8px;             /* استدارة البوردر — غيّرها هنا */
	background: #fff;
	color: #17324d;
	font-weight: 600;
	font-size: 14px;
	cursor: pointer;
	user-select: none;
	transition: border-color .15s ease, border-radius .15s ease;
}

.cs-select-trigger:hover {
	border-color: #0d8181;
}

.cs-select-trigger.active {
	border-color: #0d8181;
	border-radius: 8px 8px 0 0;      /* لما تتفتح، الزوايا السفلية تتقفل */
}

.cs-select-trigger .cs-label {
	overflow: visible;
	white-space: nowrap;
}

.cs-select-trigger .cs-arrow {
	flex: 0 0 auto;
	margin-left: auto;
	display: inline-flex;
	color: #16a3a3;
	transition: transform .2s ease;
	font-size: 11px;
}
.cs-select-trigger.active .cs-arrow {
	transform: rotate(180deg);
}

.cs-select-list {
	position: absolute;
	top: 100%;
	left: 0;
	width: 100%;
	box-sizing: border-box;
	background: #fff;
	border: 1px solid #0d8181;
	border-top: none;
	border-radius: 0 0 8px 8px;      /* استدارة القايمة نفسها */
	max-height: 260px;
	overflow-y: auto;
	z-index: 60;
	box-shadow: 0 10px 20px rgba(0,0,0,.10);
	display: none;
}
.cs-select-list.open {
	display: block;
}

.cs-select-list .cs-opt {
	padding: 10px 14px;
	cursor: pointer;
	font-size: 14px;
	color: #17324d;
	line-height: 1.4;
	white-space: normal;
}
.cs-select-list .cs-opt:hover {
	background: #eef8f8;
}
.cs-select-list .cs-opt.selected {
	background: #1e73be;   /* لون التحديد */
	color: #fff;
}

@media (max-width: 782px) {
	.cs-select-wrap { min-width: 100%; flex: 1 1 100%; }
}
</style>

<script>
document.addEventListener( 'DOMContentLoaded', function () {

	function buildCustomSelect( select ) {
		var wrap = document.createElement( 'div' );
		wrap.className = 'cs-select-wrap';

		var trigger = document.createElement( 'div' );
		trigger.className = 'cs-select-trigger';
		trigger.setAttribute( 'tabindex', '0' );

		var labelSpan = document.createElement( 'span' );
		labelSpan.className = 'cs-label';
		labelSpan.textContent = select.options[ select.selectedIndex ] ? select.options[ select.selectedIndex ].text : '';
		trigger.title = labelSpan.textContent;

		var arrow = document.createElement( 'span' );
		arrow.className = 'cs-arrow';
		arrow.innerHTML = '&#9662;';

		trigger.appendChild( labelSpan );
		trigger.appendChild( arrow );

		var list = document.createElement( 'div' );
		list.className = 'cs-select-list';

		function closeList() {
			list.classList.remove( 'open' );
			trigger.classList.remove( 'active' );
		}
		function openList() {
			document.querySelectorAll( '.cs-select-list.open' ).forEach( function ( openList ) {
				if ( openList !== list ) {
					openList.classList.remove( 'open' );
					if ( openList.previousElementSibling ) {
						openList.previousElementSibling.classList.remove( 'active' );
					}
				}
			} );
			list.classList.add( 'open' );
			trigger.classList.add( 'active' );
		}

		Array.prototype.forEach.call( select.options, function ( opt ) {
			var item = document.createElement( 'div' );
			item.className = 'cs-opt' + ( opt.selected ? ' selected' : '' );
			item.textContent = opt.text;
			item.title = opt.text;
			item.dataset.value = opt.value;

			item.addEventListener( 'click', function () {
				select.value = opt.value;
				labelSpan.textContent = opt.text;
				trigger.title = opt.text;

				list.querySelectorAll( '.cs-opt' ).forEach( function ( o ) {
					o.classList.remove( 'selected' );
				} );
				item.classList.add( 'selected' );

				closeList();
				select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			} );

			list.appendChild( item );
		} );

		trigger.addEventListener( 'click', function ( e ) {
			e.stopPropagation();
			if ( list.classList.contains( 'open' ) ) {
				closeList();
			} else {
				openList();
			}
		} );

		trigger.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Enter' || e.key === ' ' ) {
				e.preventDefault();
				trigger.click();
			} else if ( e.key === 'Escape' ) {
				closeList();
			}
		} );

		select.parentNode.insertBefore( wrap, select );
		wrap.appendChild( trigger );
		wrap.appendChild( list );
		wrap.appendChild( select );

		// نقيس أطول اختيار موجود في الليستة ونخلي عرض الصندوق يتحدد عليه،
		// مش على النص المختار حاليًا، عشان الشكل يبقى ثابت من أول ما الصفحة تفتح.
		fitWidthToLongestOption( wrap, trigger, select );

		// لو حد غيّر قيمة الـ select برمجيًا (من كود تاني)، نحدّث الشكل الظاهر.
		select.addEventListener( 'cs:sync', function () {
			var current = select.options[ select.selectedIndex ];
			labelSpan.textContent = current ? current.text : '';
			list.querySelectorAll( '.cs-opt' ).forEach( function ( o ) {
				o.classList.toggle( 'selected', o.dataset.value === select.value );
			} );
		} );
	}

	function fitWidthToLongestOption( wrap, trigger, select ) {
		var measurer = document.createElement( 'span' );
		measurer.style.position = 'absolute';
		measurer.style.visibility = 'hidden';
		measurer.style.whiteSpace = 'nowrap';
		measurer.style.pointerEvents = 'none';

		var triggerStyles = window.getComputedStyle( trigger );
		measurer.style.fontSize = triggerStyles.fontSize;
		measurer.style.fontWeight = triggerStyles.fontWeight;
		measurer.style.fontFamily = triggerStyles.fontFamily;

		document.body.appendChild( measurer );

		var maxTextWidth = 0;
		Array.prototype.forEach.call( select.options, function ( opt ) {
			measurer.textContent = opt.text;
			maxTextWidth = Math.max( maxTextWidth, measurer.getBoundingClientRect().width );
		} );

		document.body.removeChild( measurer );

		var horizontalPadding = 28; /* 14px يمين + 14px شمال زي padding الزرار */
		var arrowSpace = 26;        /* مساحة السهم + الفراغ بينه وبين النص */
		var buffer = 8;             /* هامش أمان بسيط */

		var neededWidth = Math.ceil( maxTextWidth + horizontalPadding + arrowSpace + buffer );

		wrap.style.minWidth = neededWidth + 'px';
	}

	document.querySelectorAll( 'select.cs-select' ).forEach( buildCustomSelect );

	document.addEventListener( 'click', function () {
		document.querySelectorAll( '.cs-select-list.open' ).forEach( function ( l ) {
			l.classList.remove( 'open' );
			if ( l.previousElementSibling ) {
				l.previousElementSibling.classList.remove( 'active' );
			}
		} );
	} );
} );
</script>

<div class="cs-main">
  <div class="cs-results"><?php cs_e( 'Showing', 'results_showing' ); ?> <span id="cs-count"><?php echo count( $cards ); ?></span> <?php cs_e( 'courses', 'results_courses' ); ?></div>
  <div class="cs-grid" id="cs-grid">
    <?php echo CS_Listing::cards_html( $cards, $theme_img ); // phpcs:ignore ?>
  </div>
</div>

<?php get_footer();