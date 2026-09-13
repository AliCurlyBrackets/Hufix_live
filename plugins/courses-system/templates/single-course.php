<?php
/**
 * Template: Single Course (الصفحة 3)
 * ماركب مطابق للأصل (sc-hero / sc-body / sc-left / sc-right).
 * البوست ده = سيشن. المحتوى بيتقرأ من الماستر، والتاريخ/الدولة من السيشن.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$theme_img = get_stylesheet_directory_uri() . '/assets/images/';

while ( have_posts() ) : the_post();

$session_id = get_the_ID();

// IMPORTANT: a translated session is a separate Course post. Do not trust
// the current session ID if its WPML language is wrong/stale. Resolve the
// sibling session from the language-specific master + date + location index
// before rendering anything. This makes Arabic cards open Arabic sessions.
$current_lang = '';
if ( class_exists( 'CS_WPML' ) && CS_WPML::active() ) {
  $current_lang = (string) apply_filters( 'wpml_current_language', null );
  $resolved_session = CS_WPML::localized_session_id( $session_id, $current_lang );
  if ( $resolved_session && $resolved_session !== $session_id && get_post_type( $resolved_session ) === CS_CPT ) {
    $target_url = get_permalink( $resolved_session );
    if ( $target_url && ! headers_sent() ) {
      wp_safe_redirect( $target_url, 302 );
      exit;
    }
  }
  $session_id = $resolved_session ?: $session_id;
}

$master_id  = CS_CPT::master_id( $session_id );

// Frontend language is the source of truth for the single-course page.
// Old sessions can carry a stale _cs_master pointer or even a wrong WPML
// language after historical linking. Never let that stale pointer send an
// Arabic URL to the English Master (or read its price/location fields).
if ( class_exists( 'CS_WPML' ) && CS_WPML::active() && $master_id && $current_lang ) {
  $master_id = CS_WPML::localized_post_id( $master_id, $current_lang );
}

// SAFETY NET: the URL is supposed to always point to a real session post
// (which always carries a session date). If it doesn't -- e.g. it's
// actually the Master post itself (older courses whose Master never got a
// clean WPML language, so no proper session could be resolved above), or
// an orphaned/broken session -- price, location and date would silently
// render blank because those come from the session's own postmeta, not
// the Master. Instead of showing a blank page, redirect (ONCE -- see the
// cs_snet guard below, never loop) to a real session belonging to this
// same (already language-resolved) Master: the next upcoming one, or the
// most recent past one if none are upcoming.
//
// ⚠️ لازم نضمن إن ده يحصل *مرة واحدة بس* لكل زيارة. لو فيه أكتر من بوست
// تالف بيحوّلوا لبعض (زي نسخ WPML الشبح -- شوف CS_WPML::is_auto_duplicate())
// من غير الحارس ده كنا هندخل في لوب لا نهائي (redirect loop) لحد ما
// المتصفح يوقف بنفسه من غير ما يعرض حاجة خالص -- وده بالظبط اللي حصل.
if ( ! isset( $_GET['cs_snet'] ) && '' === (string) get_post_meta( $session_id, CS_META_DATE, true ) && $master_id ) {
  $today = current_time( 'Y-m-d' );

  // suppress_filters + without_language_filter: same reasoning as
  // CS_Listing::get_cards(). Without this, WPML's own pre_get_posts hook
  // can silently drop sessions that ended up tagged with the wrong/default
  // language during creation, before this query even runs -- which would
  // leave THIS safety net (whose whole job is to rescue a blank page) with
  // no candidate to redirect to, on the language where it matters most.
  $fallback_query = function () use ( $master_id, $today ) {
    return get_posts( array(
      'post_type'        => CS_CPT,
      'post_status'      => 'publish',
      'posts_per_page'   => 1,
      'fields'           => 'ids',
      'orderby'          => 'meta_value',
      'order'            => 'ASC',
      'meta_key'         => CS_META_DATE,
      'suppress_filters' => true,
      'meta_query'       => array(
        'relation' => 'AND',
        array( 'key' => CS_META_MASTER, 'value' => (int) $master_id ),
        array( 'key' => CS_META_DATE, 'value' => $today, 'compare' => '>=', 'type' => 'DATE' ),
      ),
    ) );
  };
  $fallback_session = ( class_exists( 'CS_WPML' ) && CS_WPML::active() )
    ? CS_WPML::without_language_filter( $fallback_query )
    : $fallback_query();

  if ( empty( $fallback_session ) ) {
    // مفيش سيشن جاي -- خد أحدث سيشن فات بدل ما تسيب الصفحة فاضية.
    $fallback_query_past = function () use ( $master_id ) {
      return get_posts( array(
        'post_type'        => CS_CPT,
        'post_status'      => 'publish',
        'posts_per_page'   => 1,
        'fields'           => 'ids',
        'orderby'          => 'meta_value',
        'order'            => 'DESC',
        'meta_key'         => CS_META_DATE,
        'suppress_filters' => true,
        'meta_query'       => array(
          array( 'key' => CS_META_MASTER, 'value' => (int) $master_id ),
          array( 'key' => CS_META_DATE, 'compare' => 'EXISTS' ),
        ),
      ) );
    };
    $fallback_session = ( class_exists( 'CS_WPML' ) && CS_WPML::active() )
      ? CS_WPML::without_language_filter( $fallback_query_past )
      : $fallback_query_past();
  }

  // نتأكد فعليًا (مش بس نثق في نتيجة الكويري) إن المرشح ده عنده تاريخ
  // حقيقي مش فاضي، وإنه مش نفس البوست الحالي، قبل ما نحوّل عليه.
  $candidate = ! empty( $fallback_session ) ? (int) $fallback_session[0] : 0;
  if ( $candidate && $candidate !== (int) $session_id && '' !== (string) get_post_meta( $candidate, CS_META_DATE, true ) ) {
    $target_url = get_permalink( $candidate );
    if ( $target_url && ! headers_sent() ) {
      // cs_snet=1 يمنع أي محاولة تحويل تانية لو السيشن اللي حوّلنا عليه
      // ده نفسه طلع تالف -- بدل اللوب، هنعرض الصفحة زي ما هي (سعر/تاريخ
      // فاضي بدل ما نفضل نلف من غير ما توصل لحاجة خالص).
      $target_url = add_query_arg( 'cs_snet', '1', $target_url );
      wp_safe_redirect( $target_url, 302 );
      exit;
    }
  }
}

get_header();

// بيانات السيشن.
$country = get_post_meta( $session_id, CS_META_COUNTRY, true );
$s_date  = get_post_meta( $session_id, CS_META_DATE, true );
$s_end   = get_post_meta( $session_id, '_cs_session_end', true );
$loc     = $country ? CS_Countries::name( $country ) : '';

// بيانات الماستر.
$summary  = get_field( 'cs_summary', $master_id );
$mode     = CS_Listing::label_mode( get_field( 'cs_delivery_mode', $master_id ) );
$lang     = CS_Listing::label_lang( get_field( 'cs_language', $master_id ) );
$days     = cs_duration_days( $master_id );
$hours    = (int) get_field( 'cs_duration_hours', $master_id );
$loc_index = get_post_meta( $session_id, CS_META_LOC_INDEX, true );
$pc       = CS_Listing::country_price( $master_id, $country, $loc_index ); // سعر + عملة مكان السيشن
$price    = $pc['price'];
$curr     = $pc['currency'];
$objs     = get_field( 'cs_objectives', $master_id );
$schedule = get_field( 'cs_schedule', $master_id );
$pdf      = get_field( 'cs_outline_pdf', $master_id );

// رابط الرجوع لأرشيف كاتيجوري الكورس.
$terms     = get_the_terms( $master_id, CS_TAX );
$back_link = ( $terms && ! is_wp_error( $terms ) ) ? get_term_link( $terms[0] ) : home_url( '/' );

$date_long = $s_date ? date_i18n( 'F j, Y', strtotime( $s_date ) ) : '';
$end_long  = $s_end ? date_i18n( 'F j, Y', strtotime( $s_end ) ) : '';

// الدول (المدن) اللي الكورس ده متاح فيها فعليًا — لملء سيلكت "Preferred City" في بوب أب "Request Another Date/City".
$available_countries = CS_Listing::master_countries( $master_id );

$course_title   = get_the_title( $master_id );
$requests_nonce = wp_create_nonce( 'cs_course_requests' );

// لو جاي من كارت عام (قبل أي فلتر -- شوف build_card() في class-cs-listing.php)
// معناه المستخدم لسه ما اختارش سيشن (مدينة/تاريخ) بعينه، فمينفعش نوريه
// سعر/لوكيشن/تاريخ سيشن معين وكأنه هو الوحيد المتاح. لازم يفلتر (أو يفتح
// من كارت فيه دولة/تاريخ ظاهرين) عشان التفاصيل دي تظهر.
$cs_general_view = isset( $_GET['cs_general'] ) && '1' === $_GET['cs_general'];
?>

<!-- HERO -->
<section class="sc-hero">
  <a href="<?php echo esc_url( $back_link ); ?>" class="sc-back">
    <img src="<?php echo esc_url( $theme_img . 'ARROW_LEFT.png' ); ?>" alt=""> <?php cs_e( 'Back Courses', 'sc_back_courses' ); ?>
  </a>
  <div class="sc-badges">
    <span class="sc-badge sc-badge-teal"><?php echo esc_html( $mode ); ?></span>
    <span class="sc-badge sc-badge-dark"><?php echo esc_html( $lang ); ?></span>
  </div>
  <div class="sc-hero-inner">
    <div>
      <h1 class="sc-hero-title"><?php echo esc_html( get_the_title( $master_id ) ); ?></h1>
      <p class="sc-hero-desc"><?php echo esc_html( $summary ); ?></p>
      <div class="sc-hero-meta">
        <?php if ( ! $cs_general_view ) : ?>
        <div class="sc-hero-meta-item"><img src="<?php echo esc_url( $theme_img . 'PIN_ICON.png' ); ?>" alt=""><?php echo esc_html( $loc ); ?></div>
        <?php endif; ?>
        <div class="sc-hero-meta-item"><img src="<?php echo esc_url( $theme_img . 'CLOCK_ICON.png' ); ?>" alt=""><?php echo esc_html( $days ); ?> <?php cs_e( 'days', 'unit_days' ); ?></div>
        <?php if ( ! $cs_general_view ) : ?>
        <div class="sc-hero-meta-item"><img src="<?php echo esc_url( $theme_img . 'CAL_ICON.png' ); ?>" alt=""><?php echo esc_html( trim( $date_long . ( $end_long ? ' – ' . $end_long : '' ) ) ); ?></div>
        <?php endif; ?>
      </div>
    </div>
    <div class="sc-price-card">
      <?php if ( ! $cs_general_view ) : ?>
      <div class="sc-price-label"><?php cs_e( 'Price', 'sc_price' ); ?></div>
      <div class="sc-price-val"><?php echo esc_html( $curr . ' ' . ( $price ? number_format( (float) $price ) : '' ) ); ?></div>
      <?php endif; ?>
      <?php if ( $cs_general_view ) : ?>
      <a href="<?php echo esc_url( add_query_arg( 'course', $master_id, $back_link ) ); ?>" class="sc-enroll-btn"><?php cs_e( 'Choose a Date & Location', 'sc_choose_date_location' ); ?></a>
      <?php else : ?>
      <a href="#" class="sc-enroll-btn js-stripe-checkout" data-session="<?php echo esc_attr( $session_id ); ?>"><?php cs_e( 'Initial Enrollment Request', 'sc_initial_enrollment' ); ?></a>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- BODY -->
<div class="sc-body">

  <!-- LEFT -->
  <div class="sc-left">

    <!-- Learning Objectives -->
    <?php if ( ! empty( $objs ) ) : ?>
    <div class="sc-box">
      <div class="sc-box-header">
        <img src="<?php echo esc_url( $theme_img . 'right2.png' ); ?>" alt="">
        <h3 class="sc-box-title"><?php cs_e( 'Learning Objectives', 'sc_learning_objectives' ); ?></h3>
      </div>
      <ul class="sc-obj-list">
        <?php foreach ( $objs as $o ) : ?>
        <li><img src="<?php echo esc_url( $theme_img . 'right.png' ); ?>" alt=""><?php echo esc_html( $o['text'] ?? '' ); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <!-- Daily Schedule -->
    <?php if ( ! empty( $schedule ) ) : ?>
    <div class="sc-box">
      <div class="sc-box-header">
        <img src="<?php echo esc_url( $theme_img . 'right2.png' ); ?>" alt="">
        <h3 class="sc-box-title"><?php cs_e( 'Detailed Daily Schedule', 'sc_daily_schedule' ); ?></h3>
      </div>
      <div class="sc-schedule">
        <?php $dn = 0; foreach ( $schedule as $day ) : $dn++; ?>
        <div class="sc-day">
          <div class="sc-day-num"><?php echo $dn; ?></div>
          <div class="sc-day-content">
            <div class="sc-day-title"><?php echo esc_html( $day['day_title'] ?? ( cs__( 'Day', 'sc_day_word' ) . ' ' . $dn ) ); ?></div>
            <?php if ( ! empty( $day['points'] ) ) : ?>
            <ul class="sc-day-points">
              <?php foreach ( $day['points'] as $p ) : ?>
              <li><img src="<?php echo esc_url( $theme_img . 'right.png' ); ?>" alt=""><?php echo esc_html( $p['text'] ?? '' ); ?></li>
              <?php endforeach; ?>
            </ul>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- RIGHT SIDEBAR -->
  <div class="sc-right">

    <!-- Course Details -->
    <div class="sc-sidebar-box">
      <h3 class="sc-sidebar-title"><?php cs_e( 'Course Details', 'sc_course_details' ); ?></h3>
      <div class="sc-detail-row">
        <img src="<?php echo esc_url( $theme_img . 'time.png' ); ?>" alt="">
        <div class="sc-detail-info"><small><?php cs_e( 'Duration', 'sc_duration' ); ?></small><strong><?php echo esc_html( $days ); ?> <?php cs_e( 'days', 'unit_days' ); ?> (<?php echo esc_html( $hours ); ?> <?php cs_e( 'hours', 'unit_hours' ); ?>)</strong></div>
      </div>
      <div class="sc-detail-row">
        <img src="<?php echo esc_url( $theme_img . 'pc.png' ); ?>" alt="">
        <div class="sc-detail-info"><small><?php cs_e( 'Delivery', 'sc_delivery' ); ?></small><strong><?php echo esc_html( $mode ); ?></strong></div>
      </div>
      <div class="sc-detail-row">
        <img src="<?php echo esc_url( $theme_img . 'lang.png' ); ?>" alt="">
        <div class="sc-detail-info"><small><?php cs_e( 'Language', 'sc_language' ); ?></small><strong><?php echo esc_html( $lang ); ?></strong></div>
      </div>
      <?php if ( ! $cs_general_view ) : ?>
      <div class="sc-detail-row">
        <img src="<?php echo esc_url( $theme_img . 'location_i.png' ); ?>" alt="">
        <div class="sc-detail-info"><small><?php cs_e( 'Location', 'sc_location' ); ?></small><strong><?php echo esc_html( $loc ); ?></strong></div>
      </div>
      <div class="sc-detail-row">
        <img src="<?php echo esc_url( $theme_img . 'date.png' ); ?>" alt="">
        <div class="sc-detail-info"><small><?php cs_e( 'Date', 'sc_date' ); ?></small><strong><?php echo esc_html( $date_long ); ?><br><?php cs_e( 'to', 'sc_date_to' ); ?> <?php echo esc_html( $end_long ); ?></strong></div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Need a Different Date -->
    <div class="sc-sidebar-box" style="text-align:center">
      <img src="<?php echo esc_url( $theme_img . 'dates.png' ); ?>" alt="" style="width:36px;height:36px;object-fit:contain;margin-bottom:.7rem;opacity:.6">
      <h4 style="font-size:.98rem;font-weight:700;margin-bottom:.4rem"><?php cs_e( 'Need a Different Date or City?', 'sc_need_diff_date_title' ); ?></h4>
      <p style="font-size:.8rem;color:#777;margin-bottom:1rem"><?php cs_e( 'We can arrange this course in another city or on different dates', 'sc_need_diff_date_desc' ); ?></p>
      <a href="#" class="sc-enroll-btn js-open-alt-date" style="background:#50c0af">
        <img src="<?php echo esc_url( $theme_img . 'USER_ICON.png' ); ?>" alt="" style="width:16px;height:16px;filter:brightness(10);vertical-align:middle;margin-left:6px">
        <?php cs_e( 'Request Another Date/City', 'sc_request_alt_date_btn' ); ?>
      </a>
    </div>

    <!-- Ready to Enroll -->
    <div class="sc-cta-box">
      <h4><?php cs_e( 'Ready to enroll?', 'sc_ready_enroll_title' ); ?></h4>
      <p><?php cs_e( 'Reserve your spot in this course!', 'sc_ready_enroll_desc' ); ?></p>
      <?php if ( $cs_general_view ) : ?>
      <a href="<?php echo esc_url( add_query_arg( 'course', $master_id, $back_link ) ); ?>" class="sc-cta-btn-white"><?php cs_e( 'Choose a Date & Location', 'sc_choose_date_location' ); ?></a>
      <?php else : ?>
      <a href="#" class="sc-cta-btn-white js-stripe-checkout" data-session="<?php echo esc_attr( $session_id ); ?>"><?php cs_e( 'Initial Enrollment Request', 'sc_initial_enrollment' ); ?></a>
      <?php endif; ?>
    </div>

    <!-- Course Outline -->
    <?php
    // لو حد رفع PDF يدوي هناخده، غير كده بنولّد PDF أوتوماتيك من بيانات الكورس نفسها.
    $pdf_url = $pdf ? $pdf : add_query_arg( 'cs_pdf', $session_id, home_url( '/' ) );
    ?>
    <div class="sc-sidebar-box sc-outline-box">
      <img class="sc-outline-icon" src="<?php echo esc_url( $theme_img . 'document.png' ); ?>" alt="">
      <h4 class="sc-sidebar-title"><?php cs_e( 'Course Outline', 'sc_course_outline_title' ); ?></h4>
      <p><?php cs_e( 'Download the complete course brochure as PDF', 'sc_course_outline_desc' ); ?></p>
      <a href="<?php echo esc_url( $pdf_url ); ?>" class="sc-dl-btn" download>
        <img src="<?php echo esc_url( $theme_img . 'download.png' ); ?>" alt="">
        <?php cs_e( 'Download Outline PDF', 'sc_download_outline_btn' ); ?>
      </a>
    </div>

    <!-- Need Customized Training -->
    <div class="sc-sidebar-box sc-custom-box">
      <h4 class="sc-sidebar-title"><?php cs_e( 'Need Customized Training?', 'sc_custom_training_title' ); ?></h4>
      <p><?php cs_e( 'We design and deliver tailored training programs for organizations.', 'sc_custom_training_desc' ); ?></p>
      <a href="#" class="sc-custom-link"><?php cs_e( 'Request Customized Course', 'sc_custom_training_btn' ); ?> <span>&rarr;</span></a>
    </div>

  </div>
</div>

<!-- ============ Popup: Initial Enrollment Request ============ -->
<div class="cs-modal-overlay" id="cs-modal-enroll">
  <div class="cs-modal">
    <div class="cs-modal-head">
      <h3><?php cs_e( 'Initial Enrollment Request', 'sc_initial_enrollment' ); ?></h3>
      <button type="button" class="cs-modal-close" aria-label="Close">&times;</button>
    </div>
    <div class="cs-modal-body">
      <div class="cs-modal-summary">
        <div class="cs-modal-summary-title"><?php echo esc_html( $course_title ); ?></div>
        <?php if ( $cs_general_view ) : ?>
        <div class="cs-modal-summary-row">
          <span><img src="<?php echo esc_url( $theme_img . 'pc.png' ); ?>" alt=""><?php echo esc_html( $mode ); ?></span>
        </div>
        <?php else : ?>
        <div class="cs-modal-summary-row">
          <span><img src="<?php echo esc_url( $theme_img . 'location_i.png' ); ?>" alt=""><?php echo esc_html( $loc ); ?></span>
          <span><img src="<?php echo esc_url( $theme_img . 'pc.png' ); ?>" alt=""><?php echo esc_html( $mode ); ?></span>
        </div>
        <div class="cs-modal-summary-row">
          <span><img src="<?php echo esc_url( $theme_img . 'date.png' ); ?>" alt=""><?php echo esc_html( trim( $date_long . ( $end_long ? ' – ' . $end_long : '' ) ) ); ?></span>
        </div>
        <div class="cs-modal-summary-row">
          <span class="cs-modal-price">$&nbsp;<?php echo esc_html( $curr . ' ' . ( $price ? number_format( (float) $price ) : '' ) ); ?></span>
        </div>
        <?php endif; ?>
      </div>

    <?php $is_ar = (strpos(get_locale(), 'ar') === 0); ?>

<form class="cs-modal-form" id="cs-form-enroll">
    <input type="hidden" name="session_id" value="<?php echo esc_attr( $session_id ); ?>">
    <div class="cs-form-grid">
        <div class="cs-field">
            <label><?php echo $is_ar ? 'الاسم الكامل' : 'Full Name'; ?> <span>*</span></label>
            <input type="text" name="name" required>
        </div>
        <div class="cs-field">
            <label><?php echo $is_ar ? 'البريد الإلكتروني' : 'Email'; ?> <span>*</span></label>
            <input type="email" name="email" required>
        </div>
        <div class="cs-field">
            <label><?php echo $is_ar ? 'رقم الهاتف' : 'Phone'; ?> <span>*</span></label>
            <input type="tel" name="phone" required>
        </div>
        <div class="cs-field">
            <label><?php echo $is_ar ? 'الشركة' : 'Company'; ?></label>
            <input type="text" name="company">
        </div>
    </div>
    <div class="cs-field">
        <label><?php echo $is_ar ? 'عدد المشاركين' : 'Number of Participants'; ?></label>
        <input type="number" name="participants" min="1" value="1">
    </div>
    <div class="cs-field">
        <label><?php echo $is_ar ? 'ملاحظات إضافية' : 'Additional Notes'; ?></label>
        <textarea name="notes" rows="4" placeholder="<?php echo $is_ar ? 'أي متطلبات خاصة أو أسئلة...' : 'Any special requirements or questions...'; ?>"></textarea>
    </div>
    <p class="cs-form-msg"></p>
    <div class="cs-form-actions">
        <button type="button" class="cs-btn-cancel js-modal-cancel"><?php echo $is_ar ? 'إلغاء' : 'Cancel'; ?></button>
        <button type="submit" class="cs-btn-submit"><?php echo $is_ar ? 'إرسال طلب التسجيل' : 'Submit Enrollment Request'; ?></button>
    </div>
</form>
    </div>
  </div>
</div>

<?php $is_ar = (strpos(get_locale(), 'ar') === 0); ?>

<!-- ============ Popup: Request Another Date or City ============ -->
<div class="cs-modal-overlay" id="cs-modal-alt-date">
  <div class="cs-modal">
    <div class="cs-modal-head">
      <h3><?php echo $is_ar ? 'طلب تاريخ أو مدينة أخرى' : 'Request Another Date or City'; ?></h3>
      <button type="button" class="cs-modal-close" aria-label="Close">&times;</button>
    </div>
    <div class="cs-modal-body">
      <div class="cs-modal-course-box">
        <small><?php echo $is_ar ? 'الكورس' : 'Course'; ?></small>
        <strong><?php echo esc_html( $course_title ); ?></strong>
      </div>
      <form class="cs-modal-form" id="cs-form-alt-date">
        <input type="hidden" name="master_id" value="<?php echo esc_attr( $master_id ); ?>">
        <div class="cs-form-grid">
          <div class="cs-field">
            <label><?php echo $is_ar ? 'الاسم الكامل' : 'Full Name'; ?> <span>*</span></label>
            <input type="text" name="name" required>
          </div>
          <div class="cs-field">
            <label><?php echo $is_ar ? 'الشركة' : 'Company'; ?></label>
            <input type="text" name="company">
          </div>
          <div class="cs-field">
            <label><?php echo $is_ar ? 'البريد الإلكتروني' : 'Email'; ?> <span>*</span></label>
            <input type="email" name="email" required>
          </div>
          <div class="cs-field">
            <label><?php echo $is_ar ? 'رقم الهاتف' : 'Phone'; ?> <span>*</span></label>
            <input type="tel" name="phone" required>
          </div>
        </div>
        <div class="cs-field">
          <label><?php echo $is_ar ? 'المدينة المفضلة' : 'Preferred City'; ?></label>
          <select name="city">
            <option value=""><?php echo $is_ar ? 'اختر مدينة' : 'Select a city'; ?></option>
            <?php foreach ( $available_countries as $code => $name ) : ?>
            <option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $name ); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="cs-form-grid">
          <div class="cs-field">
            <label><?php echo $is_ar ? 'تاريخ البداية المفضل' : 'Preferred Start Date'; ?></label>
            <input type="date" name="start_date">
          </div>
          <div class="cs-field">
            <label><?php echo $is_ar ? 'تاريخ النهاية المفضل' : 'Preferred End Date'; ?></label>
            <input type="date" name="end_date">
          </div>
        </div>
        <div class="cs-field">
          <label><?php echo $is_ar ? 'ملاحظات إضافية' : 'Additional Notes'; ?></label>
          <textarea name="notes" rows="4" placeholder="<?php echo $is_ar ? 'أي متطلبات أو ملاحظات خاصة...' : 'Any special requirements or notes...'; ?>"></textarea>
        </div>
        <p class="cs-form-msg"></p>
        <div class="cs-form-actions">
          <button type="button" class="cs-btn-cancel js-modal-cancel"><?php echo $is_ar ? 'إلغاء' : 'Cancel'; ?></button>
          <button type="submit" class="cs-btn-submit"><?php echo $is_ar ? 'إرسال الطلب' : 'Submit Request'; ?></button>
        </div>
      </form>
    </div>
  </div>
</div>
<style>
/* ============ Popups: Initial Enrollment Request / Request Another Date or City ============ */
.cs-modal-overlay{display:none;position:fixed;inset:0;background:rgba(20,25,30,.55);z-index:99999;align-items:center;justify-content:center;padding:20px;}
.cs-modal-overlay.is-open{display:flex;}
.cs-modal{background:#fff;width:100%;max-width:560px;max-height:90vh;overflow-y:auto;border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,.25);font-family:var(--bold);}
.cs-modal-head{display:flex;align-items:center;justify-content:space-between;padding:22px 26px;border-bottom:1px solid #eee;}
.cs-modal-head h3{margin:0;font-size:1.25rem;font-weight:800;color:#1c2530;}
.cs-modal-close{background:none;border:none;font-size:1.6rem;line-height:1;cursor:pointer;color:#8a929b;padding:0;}
.cs-modal-close:hover{color:#333;}
.cs-modal-body{padding:22px 26px 26px;}
.cs-modal-summary{background:#eaf6fb;border:1px solid #d9edf5;border-radius:12px;padding:16px 18px;margin-bottom:20px;}
.cs-modal-summary-title{font-weight:800;font-size:1.05rem;color:#16324a;margin-bottom:10px;}
.cs-modal-summary-row{display:flex;gap:22px;flex-wrap:wrap;align-items:center;font-size:.88rem;color:#3c4a56;margin-bottom:6px;}
.cs-modal-summary-row:last-child{margin-bottom:0;}
.cs-modal-summary-row span{display:inline-flex;align-items:center;gap:6px;}
.cs-modal-summary-row img{width:14px;height:14px;object-fit:contain;opacity:.75;}
.cs-modal-price{font-weight:800;color:#1c2530;}
.cs-modal-course-box{background:#f5f6f7;border-radius:10px;padding:12px 16px;margin-bottom:20px;}
.cs-modal-course-box small{display:block;color:#8a929b;font-size:.75rem;margin-bottom:4px;}
.cs-modal-course-box strong{font-size:.98rem;color:#1c2530;}
.cs-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;}
.cs-field{margin-bottom:16px;}
.cs-form-grid .cs-field{margin-bottom:0;}
.cs-field label{display:block;font-size:.86rem;font-weight:600;color:#2a333c;margin-bottom:6px;}
.cs-field label span{color:#e0503c;}
.cs-field input,.cs-field select,.cs-field textarea{width:100%;box-sizing:border-box;border:1px solid #dfe3e7;border-radius:8px;padding:10px 12px;font-size:.9rem;font-family:inherit;color:#1c2530;background:#fff;}
.cs-field input:focus,.cs-field select:focus,.cs-field textarea:focus{outline:none;border-color:#2f655f;box-shadow:0 0 0 3px rgba(58,169,216,.15);}
.cs-field textarea{resize:vertical;}
.cs-form-msg{font-size:.85rem;margin:0 0 8px;min-height:1em;}
.cs-form-msg.is-error{color:#c0392b;}
.cs-form-msg.is-success{color:#1a8f4c;}
.cs-form-actions{display:flex;justify-content:flex-end;gap:12px;margin-top:6px;}
.cs-btn-cancel{background:#fff;border:1px solid #dfe3e7;color:#2a333c;border-radius:8px;padding:10px 20px;font-size:.9rem;font-weight:600;cursor:pointer;}
.cs-btn-cancel:hover{background:#f5f6f7;}
.cs-btn-submit{background:#2f655f;border:none;color:#fff;border-radius:8px;padding:10px 22px;font-size:.9rem;font-weight:700;cursor:pointer;}
.cs-btn-submit:hover{background:#2f97c3;}
.cs-btn-submit:disabled{opacity:.6;cursor:default;}
@media (max-width:560px){.cs-form-grid{grid-template-columns:1fr;}}
.js-stripe-checkout.is-loading{opacity:.65;pointer-events:none;}
</style>

<script>
(function () {
	var AJAX_URL = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
	var NONCE    = <?php echo wp_json_encode( $requests_nonce ); ?>;

	function openModal( el ) {
		if ( ! el ) return;
		el.classList.add( 'is-open' );
		document.body.style.overflow = 'hidden';
	}
	function closeModal( el ) {
		if ( ! el ) return;
		el.classList.remove( 'is-open' );
		document.body.style.overflow = '';
	}

	var enrollModal  = document.getElementById( 'cs-modal-enroll' );
	var altDateModal = document.getElementById( 'cs-modal-alt-date' );

	document.querySelectorAll( '.js-open-enroll' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			openModal( enrollModal );
		} );
	} );

	document.querySelectorAll( '.js-open-alt-date' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			openModal( altDateModal );
		} );
	} );

	[ enrollModal, altDateModal ].forEach( function ( modal ) {
		if ( ! modal ) return;
		modal.addEventListener( 'click', function ( e ) {
			if ( e.target === modal ) closeModal( modal );
		} );
		modal.querySelector( '.cs-modal-close' ).addEventListener( 'click', function () {
			closeModal( modal );
		} );
		var cancelBtn = modal.querySelector( '.js-modal-cancel' );
		if ( cancelBtn ) {
			cancelBtn.addEventListener( 'click', function () {
				closeModal( modal );
			} );
		}
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' ) {
			closeModal( enrollModal );
			closeModal( altDateModal );
		}
	} );

	function handleSubmit( form, action, modal ) {
		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			var msg = form.querySelector( '.cs-form-msg' );
			var submitBtn = form.querySelector( '.cs-btn-submit' );
			msg.textContent = '';
			msg.className = 'cs-form-msg';

			var data = new FormData( form );
			data.append( 'action', action );
			data.append( 'nonce', NONCE );

			submitBtn.disabled = true;

			fetch( AJAX_URL, { method: 'POST', credentials: 'same-origin', body: data } )
				.then( function ( r ) { return r.json(); } )
				.then( function ( res ) {
					submitBtn.disabled = false;
					if ( res.success ) {
						msg.textContent = res.data.message;
						msg.className = 'cs-form-msg is-success';
						form.reset();
						setTimeout( function () { closeModal( modal ); msg.textContent = ''; }, 1800 );
					} else {
						msg.textContent = ( res.data && res.data.message ) ? res.data.message : 'Something went wrong.';
						msg.className = 'cs-form-msg is-error';
					}
				} )
				.catch( function () {
					submitBtn.disabled = false;
					msg.textContent = 'Something went wrong. Please try again.';
					msg.className = 'cs-form-msg is-error';
				} );
		} );
	}

	handleSubmit( document.getElementById( 'cs-form-enroll' ), 'cs_enrollment_request', enrollModal );
	handleSubmit( document.getElementById( 'cs-form-alt-date' ), 'cs_another_date_request', altDateModal );

	// زرار "Initial Enrollment Request" (لما فيه سيشن محدد -- سعر/تاريخ/لوكيشن
	// ظاهرين) بيروح مباشرة على Stripe Checkout بدل الفورم، بنفس السعر
	// الظاهر في الصفحة.
	document.querySelectorAll( '.js-stripe-checkout' ).forEach( function ( btn ) {
		var originalText = btn.textContent;
		btn.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			if ( btn.classList.contains( 'is-loading' ) ) return;

			btn.classList.add( 'is-loading' );
			btn.textContent = <?php echo wp_json_encode( __( 'Redirecting to payment…', 'courses-system' ) ); ?>;

			var data = new FormData();
			data.append( 'action', 'cs_stripe_checkout' );
			data.append( 'nonce', NONCE );
			data.append( 'session_id', btn.getAttribute( 'data-session' ) );

			fetch( AJAX_URL, { method: 'POST', credentials: 'same-origin', body: data } )
				.then( function ( r ) { return r.json(); } )
				.then( function ( res ) {
					if ( res.success && res.data && res.data.url ) {
						window.location.href = res.data.url;
						return;
					}
					btn.classList.remove( 'is-loading' );
					btn.textContent = originalText;
					alert( ( res.data && res.data.message ) ? res.data.message : 'Something went wrong. Please try again.' );
				} )
				.catch( function () {
					btn.classList.remove( 'is-loading' );
					btn.textContent = originalText;
					alert( 'Something went wrong. Please try again.' );
				} );
		} );
	} );
})();
</script>

<?php endwhile; get_footer();
