<?php
/**
 * Feature: WPML String Translation Bridge
 * الفايلات التانية (categories.php / taxonomy-course_category.php /
 * single-course.php / class-cs-listing.php / class-cs-countries.php) فيها
 * نصوص ثابتة مكتوبة إنجليزي جوه الكود نفسه (زي "Training category"،
 * "Course Details"، "Learn More"...)، مش محتوى بوستات، فمفيش طريقة يترجموا
 * غير عن طريق WPML String Translation.
 *
 * الكلاس ده بيسجّل كل نص من دول تلقائي أول ما الصفحة تتفتح (تحت دومين اسمه
 * "Courses System")، وبيرجّع نسخته المترجمة (لو الأدمن كتبها) حسب لغة
 * الصفحة الحالية. لو WPML مش شغّال، بيرجّع النص الإنجليزي زي ما هو من غير
 * أي تأثير.
 *
 * إزاي تترجم النصوص دي فعليًا (خطوة بالأدمن، مش كود):
 *   WPML -> Theme and plugin localization -> دومين "Courses System"
 *   (أو WPML -> String Translation لو النسخة قديمة)، هتلاقي كل نص من
 *   اللي جوه الفايلات دي متسجل هناك تلقائي أول ما حد يفتح أي صفحة كورسات،
 *   واكتب قدامه الترجمة العربية.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CS_I18N {

	/** اسم الدومين اللي هيظهر بيه كل نصوص البلجن دي في WPML String Translation. */
	const DOMAIN = 'Courses System';

	/**
	 * سجّل نص وارجعله مترجم حسب اللغة الحالية.
	 *
	 * @param string $string النص الأصلي (إنجليزي، ده اللي بيتخزن كمرجع).
	 * @param string $name   معرّف ثابت وفريد للنص ده (زي "label_duration").
	 * @return string
	 */
	public static function t( $string, $name ) {
		if ( '' === trim( (string) $string ) ) {
			return $string;
		}

		if ( ! class_exists( 'CS_WPML' ) || ! CS_WPML::active() ) {
			return $string;
		}

		// التسجيل: لو النص اتسجل قبل كده بنفس الاسم، WPML مش بيعمل حاجة تاني
		// غير إنه يحدّث القيمة الأصلية لو اتغيّرت -- آمن تتنادى كل مرة.
		do_action( 'wpml_register_string', $string, $name, self::DOMAIN, 'LINE', 'en' );

		return apply_filters( 'wpml_translate_single_string', $string, self::DOMAIN, $name );
	}
}

if ( ! function_exists( 'cs__' ) ) {
	/** رجّع النص مترجم (للاستخدام جوه echo/متغيرات). */
	function cs__( $string, $name ) {
		return CS_I18N::t( $string, $name );
	}
}

if ( ! function_exists( 'cs_e' ) ) {
	/** اطبع النص مترجم ومهرّب (escaped) مباشرة. */
	function cs_e( $string, $name ) {
		echo esc_html( CS_I18N::t( $string, $name ) );
	}
}
