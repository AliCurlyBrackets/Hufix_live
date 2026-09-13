<?php
/**
 * Feature: Templates Loader
 * بيربط تمبليتات السيستم بمكانها الصح:
 *   - Page Template "Course Categories" (الصفحة 1: شبكة الكاتيجوري)
 *   - Taxonomy archive course_category (الصفحة 2: كورسات الكاتيجوري)
 *   - Single course (الصفحة 3: تفاصيل الكورس)
 *
 * لو حابب تحط التمبليتات في الثيم بدل البلجن، انسخ الفايلات لمجلد الثيم
 * وهو هياخدها من هناك أوتوماتيك.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CS_Templates {

	public static function init() {
		// تسجيل Page Template لشبكة الكاتيجوري.
		add_filter( 'theme_page_templates', array( __CLASS__, 'register_page_template' ) );
		add_filter( 'template_include', array( __CLASS__, 'load_template' ) );
	}

	public static function register_page_template( $templates ) {
		$templates['cs-categories'] = 'Course Categories';
		return $templates;
	}

	public static function load_template( $template ) {
		// 1) صفحة شبكة الكاتيجوري (Page Template).
		if ( is_page() ) {
			$page_tpl = get_page_template_slug( get_queried_object_id() );
			if ( 'cs-categories' === $page_tpl ) {
				return self::locate( 'categories.php', $template );
			}
		}

		// 2) أرشيف الكاتيجوري (الصفحة 2).
		if ( is_tax( CS_TAX ) ) {
			return self::locate( 'taxonomy-course_category.php', $template );
		}

		// 3) السنجل كورس (الصفحة 3).
		if ( is_singular( CS_CPT ) ) {
			return self::locate( 'single-course.php', $template );
		}

		return $template;
	}

	/**
	 * يدوّر على التمبليت في الثيم الأول، وبعدين في البلجن.
	 */
	protected static function locate( $file, $fallback ) {
		$theme = locate_template( array( 'courses-system/' . $file ) );
		if ( $theme ) {
			return $theme;
		}
		$plugin = CS_PATH . 'templates/' . $file;
		return file_exists( $plugin ) ? $plugin : $fallback;
	}
}

CS_Templates::init();