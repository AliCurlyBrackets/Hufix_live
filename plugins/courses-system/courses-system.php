<?php
/**
 * Plugin Name: Courses System
 * Description: نظام كورسات متكامل (Master + Sessions) مع تكرار تلقائي، دول العالم، استيراد اكسيل، ودعم WPML.
 * Version:     1.4.12
 * Author:      Tasweqa
 * Text Domain: courses-system
 *
 * ملاحظة: لو بتنشر عن طريق Code Snippets، انسخ محتوى كل فايل في سنيبت منفصل
 * بنفس الترتيب اللي تحت. لو بتنشر كـ plugin، سيب الملفات زي ما هي.
 *
 * التحديثات في 1.1.0:
 *  - صورة الكورس (Featured Image): ترفعها يدوي من شاشة الكورس، أو من عمود
 *    image_url في شيت الاستيراد. بتتكرر تلقائي في كل السيشنز، ولو مفيش
 *    صورة الكارت بيتعرض عادي من غيرها (من غير صورة بديلة).
 *  - السعر وعدد الأيام بقوا مخفيين في وضع الكارت العادي (قبل ما تستخدم
 *    الفلتر)، زي الدولة والتاريخ بالظبط.
 *  - محرك التكرار بقى بيحسب مدة الكورس بأيام شغل بس (الاتنين للجمعة)،
 *    والسبت والأحد ملهومش وجود خالص لا في العد ولا لو وقع عليهم تاريخ البداية.
 *
 * التحديثات في 1.2.0:
 *  - اتشال حقل "Duration (days)" اليدوي خالص. عدد أيام الكورس بقى أوتوماتيك
 *    100% من نوع التكرار: كل أسبوع = 5 أيام، كل أسبوعين = 10 أيام.
 *  - اتضاف add_theme_support('post-thumbnails') عشان صندوق "Featured Image"
 *    يظهر في شاشة تعديل الكورس (بعض الثيمات مش بتفعّلها بنفسها).
 *
 * التحديثات في 1.2.1:
 *  - إصلاح استيراد ترجمات WPML: source_course_id أصبح المرجع الأساسي.
 *  - إعادة رفع نفس شيت الترجمة تحدّث الترجمة الموجودة بدل إنشاء نسخة جديدة.
 *  - لو course_id في صف الترجمة يساوي ID الإنجليزي، يتم تجاهله بأمان.
 *
 * التحديثات في 1.3.0:
 *  - صفحة جديدة "ربط الترجمة" (Courses > ربط الترجمة): بترفع الشيت
 *    الإنجليزي لوحده والشيت العربي لوحده (وتقدر تكرر الرفع في كل ناحية)،
 *    وبتعرضلك عدد الكورسات في كل لغة وبتتأكد إن العدد متطابق، وبعدين
 *    زرار واحد بيعمل sync/ربط بين اللستتين في WPML -- الماستر بماستر
 *    والسيشن بالسيشن المقابل ليه (نفس التاريخ ونفس ترتيب المكان).
 *  - مبقاش لازم عمود source_course_id في الشيت العربي مع الطريقة الجديدة
 *    (الصفحة دي بتتجاهله أصلاً، والربط بيحصل من الواجهة بعد المراجعة).
 *  - صفحة Import CSV القديمة سايبينها زي ما هي بالظبط لمين متعوّد عليها.
 *
 * التحديثات في 1.3.1:
 *  - إصلاح: عدد السيشنز كان بيطلع مختلف بين اللغتين حتى لو الشيتين
 *    متطابقين تمامًا. السبب: كل كورس وقت الرفع بيتولّد بسقف "90 يوم من
 *    النهاردة" بس، والباقي بيكمّله كرون الصيانة في الخلفية على دفعات --
 *    فأي بصّة على الأرقام في نص العملية بتلاقي كل لغة واقفة عند رقم
 *    مختلف. زرار الربط بقى بيولّد السنة كاملة للكورسين (الأصل والترجمة)
 *    بنفس السنة الصريحة وبيستنى الطابور يخلص للاتنين قبل المطابقة.
 *  - زرار جديد "كمّل توليد السيشنز الناقصة دلوقتي" + تنبيه بعدد السيشنز
 *    اللي لسه في الطابور، عشان تعرف إن الأرقام المعروضة مش نهائية.
 *  - إصلاح: عناوين الكورسات كانت بتظهر فيها "&#8211;" بدل الشرطة الطويلة
 *    (wptexturize)، دلوقتي بنقرا العنوان الخام من الداتابيز.
 *
 * التحديثات في 1.3.2:
 *  - إصلاح جذري لأهم مشكلة: أي سيشن بيتولّد في الخلفية (WP-Cron) كان
 *    WPML بيديله لغة الموقع الافتراضية تلقائيًا -- حتى لو الماستر بتاعه
 *    عربي -- أو ميديلوش لغة خالص. لغة الماستر كانت بتتحدد مرة واحدة بس
 *    وقت الرفع، فأي سيشن بيتعمل بعد كده كان بيضيع. النتيجة كانت شاشة
 *    كورسات فيها English أكبر من Arabic بمئات، و"All" أكبر من
 *    "All languages" (بوستات من غير أي لغة).
 *  - حل دايم: هوك على cs_sessions_generation_done بيشتغل بعد *كل* عملية
 *    توليد (رفع أو كرون) وبيصلّح أي سيشن لغته مش زي لغة الماستر بتاعه،
 *    وبيربطه بالسيشن المقابل ليه لو الكورس ترجمة.
 *  - زرار "🩹 صلّح لغات السيشنز دلوقتي" في صفحة ربط الترجمة عشان تصلّح
 *    البيانات القديمة اللي اتظبطت غلط قبل التحديث ده.
 *
 * التحديثات في 1.4.0 -- "Sync Mode": الكرون اتشال خالص
 *  - مفيش أي جدولة WP-Cron لتوليد السيشنز ولا لربط الترجمة. اللي بينده
 *    التوليد هو اللي بيكمّله لحد الآخر في نفس العملية.
 *  - اتشال سقف الـ"90 يوم": كل سيشنز السنة بتتولّد وقتها بالعدد المضبوط
 *    (عدد المواعيد × عدد الأماكن)، مش على مراحل.
 *  - كل سيشن بياخد لغته الصح *لحظة ما بيتعمل* (CS_WPML::tag_session_on_create)
 *    وبيتربط بالسيشن المقابل ليه فورًا -- مفيش مرحلة تاجينج منفصلة يقدر
 *    أي سيشن يفوتها.
 *  - الحفظ اليدوي من شاشة الكورس بيكمّل كل السيشنز في نفس طلب الحفظ.
 *  - بديل الكرون اليومي: زرار "🧹 صيانة" في صفحة ربط الترجمة (بيمسح
 *    السيشنز اللي فاتت + بيكمّل أي سيشنز ناقصة لآخر السنة).
 *  - صفحة ربط الترجمة بقت توري "الفعلي / المفروض" لكل كورس، وتحذير
 *    واضح لو فيه أي سيشن ناقص.
 *  - لو عايز ترجّع سلوك الكرون القديم: define( 'CS_SYNC_MODE', false );
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// حارس: لو نسخة تانية من البلجن اتحمّلت قبل كده، بلاش نكرّر التحميل
// (بيمنع تكرار الـ constants وتكرار الكلاسات).
if ( defined( 'CS_VERSION' ) ) {
	return;
}

define( 'CS_VERSION', '1.4.12' );
define( 'CS_PATH', plugin_dir_path( __FILE__ ) );
define( 'CS_URL', plugin_dir_url( __FILE__ ) );

/**
 * ثوابت أساسية للسيستم كله عشان نبعد عن الـ magic strings.
 */
define( 'CS_CPT', 'course' );                 // البوست تايب
define( 'CS_TAX', 'course_category' );        // التاكسونومي (الكاتيجوري)
define( 'CS_META_IS_MASTER', '_cs_is_master' ); // 1 = ماستر كورس (قالب مخفي)
define( 'CS_META_MASTER', '_cs_master' );       // على السيشن: ID الماستر
define( 'CS_META_DATE', '_cs_session_date' );   // على السيشن: تاريخ البداية (Y-m-d)
define( 'CS_META_COUNTRY', '_cs_session_country' ); // على السيشن: كود الدولة
// ترتيب المكان ده (0، 1، 2...) جوه repeater الأسعار بتاع الماستر وقت
// التوليد. بنستخدمه (مش نص المكان نفسه) عشان نربط سيشن عربي بمقابله
// الإنجليزي وقت WPML linking -- لإن نص المكان ممكن يتترجم (Cairo ->
// القاهرة) فيبقى مش نفس النص في اللغتين حتى لو نفس المكان فعليًا، لكن
// الترتيب في الـ repeater بيفضل واحد. شوف CS_WPML::tag_sessions_language_batch().
define( 'CS_META_LOC_INDEX', '_cs_session_loc_index' );

/**
 * تحميل فايلات الفيتشرز.
 * كل فايل = فيتشر واحدة بمسؤولية واحدة.
 */
$cs_files = array(
	'includes/class-cs-wpml.php',        // ربط الترجمة مع WPML (لازم يتحمّل الأول، باقي الفايلات بتستخدمه)
	'includes/class-cs-i18n.php',        // ترجمة النصوص الثابتة (UI) عن طريق WPML String Translation
	'includes/class-cs-countries.php',   // بيانات دول العالم
	'includes/class-cs-cpt.php',         // تسجيل البوست تايب
	'includes/class-cs-taxonomy.php',    // تسجيل الكاتيجوري + صورة الكاتيجوري
	'includes/class-cs-acf.php',         // حقول ACF بالكود
	'includes/class-cs-recurrence.php',  // محرك التكرار + مزامنة + تجديد سنوي
	'includes/class-cs-slider.php',      // تحكم الإسلايدر
	'includes/class-cs-templates.php',   // تحميل تمبليتات الصفحات
	'includes/class-cs-assets.php',      // تحميل CSS/JS الثيم على صفحات الكورسات
	// الباقي هنضيفه في الخطوات الجاية:
	'includes/class-cs-listing.php',     // محرك عرض الكاتيجوري (عدم التكرار)
	'includes/class-cs-ajax.php',        // فلترة أجاكس
	'includes/class-cs-stripe.php',      // Stripe Checkout -- دفع مباشر من زرار "Initial Enrollment Request"
	'includes/class-cs-import.php',      // رفع الشيت (CSV Import)
	'includes/class-cs-translation-link.php', // رفع شيتين منفصلين (EN/AR) + زرار ربطهم كترجمة (لازم يتحمّل بعد class-cs-import لأنه بيورث منه)
	'includes/class-cs-category-cleanup.php', // أداة دمج الكاتيجوريز المكررة (شوف الفايل)
	'includes/class-cs-category-guard.php',   // بيمنع كاتيجوري لغة من إنها تمسح كاتيجوري اللغة التانية
	'includes/class-cs-pdf.php',         // توليد PDF أوتوماتيك من بيانات الكورس (بدون رفع يدوي)
);

foreach ( $cs_files as $cs_file ) {
	$path = CS_PATH . $cs_file;
	if ( file_exists( $path ) ) {
		require_once $path;
	}
}

/**
 * تفعيل / إلغاء التفعيل: نظّف الـ rewrite rules واجدول الكرون.
 */
register_activation_hook( __FILE__, function () {
	if ( class_exists( 'CS_CPT' ) ) {
		CS_CPT::register();
	}
	if ( class_exists( 'CS_Taxonomy' ) ) {
		CS_Taxonomy::register();
	}
	flush_rewrite_rules();

	// كرون يومي للتجديد السنوي وتنظيف السيشنز القديمة.
	if ( ! wp_next_scheduled( 'cs_daily_maintenance' ) ) {
		wp_schedule_event( time(), 'daily', 'cs_daily_maintenance' );
	}
} );

register_deactivation_hook( __FILE__, function () {
	flush_rewrite_rules();
	wp_clear_scheduled_hook( 'cs_daily_maintenance' );
} );


function cs_enqueue_assets() {

    wp_enqueue_script(
        'cs-filter',
        CS_URL . 'assets/js/cs-filter.js',
        array(),
        CS_VERSION,
        true
    );

    wp_localize_script(
        'cs-filter',
        'CS_FILTER',
        array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('cs_filter_nonce'),
        )
    );
}
add_action('wp_enqueue_scripts', 'cs_enqueue_assets');

// One-time repair after the v4 category logic is loaded.
add_action( 'admin_init', function () {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    $version = get_option( 'cs_category_logic_version', '' );
    if ( '4' === $version ) { return; }
    if ( class_exists( 'CS_Category_Guard' ) && class_exists( 'CS_WPML' ) && CS_WPML::active() ) {
        CS_Category_Guard::repair_all_translation_categories();
    }
    update_option( 'cs_category_logic_version', '4', false );
}, 99 );

