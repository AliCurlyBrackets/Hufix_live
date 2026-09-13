<?php
/**
 * Feature: Course Post Type
 * بوست تايب واحد اسمه course بيخدم حاجتين:
 *   - Master  (قالب مخفي، فيه كل المحتوى) -> _cs_is_master = 1
 *   - Session (النسخة المتكررة، بتاريخ + دولة، بتقرأ محتواها من الماستر)
 *
 * الـ Sessions هي اللي بتتعرض قدام. الماستر مخفي عن الفرونت.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CS_CPT {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		// اخفاء الماستر من كل كويريز الفرونت.
		add_action( 'pre_get_posts', array( __CLASS__, 'hide_masters_from_front' ) );
		// في الأدمن: اعرض الماستر بس (اخفي السيشنز/الكوبيهات).
		add_action( 'pre_get_posts', array( __CLASS__, 'admin_only_masters' ) );
		// شرح بسيط تحت صندوق "Featured Image" في شاشة الكورس.
		add_filter( 'admin_post_thumbnail_html', array( __CLASS__, 'thumbnail_box_note' ), 10, 2 );
	}

	/**
	 * ملاحظة توضيحية تحت صورة الكورس (Featured Image) في شاشة تعديل الماستر:
	 * الصورة دي هي اللي بتظهر في كارت الكورس، وهتتكرر تلقائي في كل السيشنز
	 * (النسخ) المتولدة من الكورس ده. ولو ما اترفعتش، الكارت هيتعرض عادي من غيرها.
	 */
	public static function thumbnail_box_note( $content, $post_id ) {
		if ( get_post_type( $post_id ) !== CS_CPT ) {
			return $content;
		}
		$content .= '<p style="margin-top:10px;color:#646970;font-size:12px;line-height:1.6;">'
			. 'الصورة دي هي اللي هتظهر في كارت الكورس (وفي كل نسخه/سيشن بيتولّد منه أوتوماتيك). '
			. 'ممكن ترفعها من هنا يدوي، أو من عمود <code>image_url</code> في شيت الاستيراد. '
			. 'ولو مفيش صورة، الكارت هيتعرض عادي من غيرها.'
			. '</p>';
		return $content;
	}

	/**
	 * قايمة الكورسات في الأدمن تعرض الماسترز بس.
	 */
	public static function admin_only_masters( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		global $pagenow;
		if ( 'edit.php' !== $pagenow ) {
			return;
		}
		if ( $query->get( 'post_type' ) !== CS_CPT ) {
			return;
		}
		$meta_query   = (array) $query->get( 'meta_query' );
		$meta_query[] = array( 'key' => CS_META_MASTER, 'compare' => 'NOT EXISTS' ); // الماستر = مش سيشن
		$query->set( 'meta_query', $meta_query );
	}

	public static function register() {
		// لازم نضمن دعم "الصورة المميزة" (Featured Image) بشكل عام، لإن بعض
		// الثيمات مش بتفعّلها بنفسها -- ولو مش مفعّلة، صندوق الصورة مش بيظهر
		// خالص في شاشة تعديل الكورس حتى لو الـ CPT نفسه بيدعمها في supports.
		add_theme_support( 'post-thumbnails' );

		$labels = array(
			'name'          => 'Courses',
			'singular_name' => 'Course',
			'add_new_item'  => 'Add New Course',
			'edit_item'     => 'Edit Course',
			'search_items'  => 'Search Courses',
			'menu_name'     => 'Courses',
		);

		register_post_type( CS_CPT, array(
			'labels'       => $labels,
			'public'       => true,
			'has_archive'  => true,
			'show_in_rest' => true, // مهم لو هتستعمله في الموبايل API بعدين
			'menu_icon'    => 'dashicons-welcome-learn-more',
			'rewrite'      => array( 'slug' => 'courses' ),
			'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'taxonomies'   => array( CS_TAX ),
		) );
	}

	/**
	 * الماستر ما يظهرش في أي كويري قدام (بس السيشنز).
	 */
	public static function hide_masters_from_front( $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( ! self::is_course_query( $query ) ) {
			return;
		}

		$meta_query   = (array) $query->get( 'meta_query' );
		$meta_query[] = array(
			'key'     => CS_META_IS_MASTER,
			'compare' => 'NOT EXISTS',
		);
		$query->set( 'meta_query', $meta_query );
	}

	/**
	 * تشيك إن الكويري بتخص الكورسات (archive / taxonomy).
	 */
	protected static function is_course_query( $query ) {
		return $query->is_post_type_archive( CS_CPT ) || $query->is_tax( CS_TAX );
	}

	/* ============ Helpers مشتركة لباقي الفيتشرز ============ */

	/** هل البوست ده ماستر؟ */
	public static function is_master( $post_id ) {
		return (bool) get_post_meta( $post_id, CS_META_IS_MASTER, true );
	}

	/** يرجّع ID الماستر بتاع سيشن (أو نفس الـ ID لو هو ماستر). */
	public static function master_id( $post_id ) {
		$master = (int) get_post_meta( $post_id, CS_META_MASTER, true );
		return $master ? $master : (int) $post_id;
	}
}

CS_CPT::init();