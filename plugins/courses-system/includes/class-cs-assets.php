<?php
/**
 * Feature: Assets Loader
 * بيحمّل ملف/ملفات الـ CSS بتاعت الثيم (اللي فيها كلاسات tg- / sl- / cs-)
 * على صفحات الكورسات بس: شبكة الكاتيجوري + أرشيف الكاتيجوري + السنجل كورس.
 *
 * ليه؟ لإن الـ CSS ده في الثيم بيتحمّل على الصفحات الأصلية بس، ومش بيتحمّل
 * على تمبليت البلجن. فبنقوله يتحمّل هنا كمان.
 *
 * ======================= الجزء اللي بتعدّله إنت =======================
 * حط مسار/مسارات ملفات الـ CSS بتاعتك نسبةً لمجلد الثيم.
 * مثال: لو الملف موجود في:  wp-content/themes/YOUR-THEME/assets/css/courses.css
 * يبقى تكتب:  'assets/css/courses.css'
 *
 * إزاي تعرف اسم الملف بالظبط؟
 *   1) افتح صفحة عندك الاستايل شغال فيها صح (الصفحة الأصلية).
 *   2) View Page Source، ودوّر على <link ... .css>.
 *   3) خد الجزء اللي بعد اسم الثيم من الرابط وحطه تحت.
 * =====================================================================
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CS_Assets {

	/**
	 * ملفات CSS بتاعت الثيم (نسبة لمجلد الثيم).
	 * ضيف كل الملفات اللي محتاجها هنا.
	 */
	protected static function css_files() {
		$files = array(
			'assets/css/style.css', // مسار CSS بتاع الثيم (hufix-theme)
			// 'assets/css/slider.css',
			// 'style.css',
		);
		return apply_filters( 'cs_theme_css_files', $files );
	}

	/**
	 * ملفات JS (لو الإسلايدر محتاج جافاسكريبت). اختياري.
	 */
	protected static function js_files() {
		$files = array(
			// 'assets/js/slider.js',
		);
		return apply_filters( 'cs_theme_js_files', $files );
	}

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	public static function enqueue() {
		if ( ! self::is_course_page() ) {
			return;
		}

		$theme_uri = get_stylesheet_directory_uri();
		$theme_dir = get_stylesheet_directory();

		// CSS احتياطي بتاع البلجن نفسه (كارت من غير صورة... إلخ). بيتحمّل بعد
		// ستايل الثيم عشان يقدر يظبط الحالات الجديدة من غير ما يكسر حاجة.
		wp_enqueue_style(
			'cs-fallback-css',
			CS_URL . 'assets/css/cs-fallback.css',
			array(),
			CS_VERSION
		);

		// CSS
		$i = 0;
		foreach ( self::css_files() as $rel ) {
			$rel  = ltrim( $rel, '/' );
			$path = trailingslashit( $theme_dir ) . $rel;
			$ver  = file_exists( $path ) ? filemtime( $path ) : CS_VERSION;
			wp_enqueue_style(
				'cs-theme-css-' . $i,
				trailingslashit( $theme_uri ) . $rel,
				array(),
				$ver
			);
			$i++;
		}

		// JS (اختياري)
		$j = 0;
		foreach ( self::js_files() as $rel ) {
			$rel  = ltrim( $rel, '/' );
			$path = trailingslashit( $theme_dir ) . $rel;
			$ver  = file_exists( $path ) ? filemtime( $path ) : CS_VERSION;
			wp_enqueue_script(
				'cs-theme-js-' . $j,
				trailingslashit( $theme_uri ) . $rel,
				array( 'jquery' ),
				$ver,
				true
			);
			$j++;
		}
	}

	/**
	 * هل إحنا على صفحة من صفحات الكورسات؟
	 */
	protected static function is_course_page() {
		// شبكة الكاتيجوري (Page Template).
		if ( is_page() ) {
			$tpl = get_page_template_slug( get_queried_object_id() );
			if ( 'cs-categories' === $tpl ) {
				return true;
			}
		}
		// أرشيف الكاتيجوري + السنجل كورس + أرشيف الكورسات.
		if ( is_tax( CS_TAX ) || is_singular( CS_CPT ) || is_post_type_archive( CS_CPT ) ) {
			return true;
		}
		return false;
	}
}

CS_Assets::init();
