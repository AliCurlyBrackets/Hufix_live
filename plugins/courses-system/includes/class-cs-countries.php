<?php
/**
 * Feature: Locations (كانت اسمها Countries)
 * ⚠️ اتغيّر منطقها بالكامل: مبقاش فيه قايمة دول ثابتة (ISO) نتقيّد بيها.
 * دلوقتي "المكان" (Location) نص حر بيتكتب زي ما هو -- ممكن يكون اسم دولة
 * ("Egypt")، أو مدينة ("Cairo")، أو أي حاجة تانية ("Alex", "Mansoura")،
 * وبيتعرض بالظبط زي ما اتكتب من غير أي ترجمة أو تحقق من قايمة معيّنة.
 *
 * سيبنا اسم الكلاس والدوال زي ما هي (name/exists/sanitize_codes) عشان
 * باقي الفيتشرز (الاستيراد، الفلتر، السنجل كورس...) تفضل شغالة من غير
 * ما نلمسها، بس الداخل اتبسّط بالكامل ليدعم أي نص حر.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CS_Countries {

	/**
	 * كل الدول: code => name إنجليزي.
	 * ده اللي بيتغذّى منه اختيارات حقل الدولة في الأدمن (ACF)، فسيبناه
	 * إنجليزي زي ما هو عشان الأدمن يفضل شغال بشكل ثابت.
	 *
	 * @return array
	 */
	public static function all() {
		$list = array(
			'EG' => 'Egypt', 'SA' => 'Saudi Arabia', 'AE' => 'United Arab Emirates',
			'QA' => 'Qatar', 'KW' => 'Kuwait', 'BH' => 'Bahrain', 'OM' => 'Oman',
			'JO' => 'Jordan', 'LB' => 'Lebanon', 'IQ' => 'Iraq', 'SY' => 'Syria',
			'YE' => 'Yemen', 'PS' => 'Palestine', 'SD' => 'Sudan', 'LY' => 'Libya',
			'TN' => 'Tunisia', 'DZ' => 'Algeria', 'MA' => 'Morocco', 'MR' => 'Mauritania',
			'US' => 'United States', 'CA' => 'Canada', 'MX' => 'Mexico',
			'GB' => 'United Kingdom', 'IE' => 'Ireland', 'FR' => 'France',
			'DE' => 'Germany', 'IT' => 'Italy', 'ES' => 'Spain', 'PT' => 'Portugal',
			'NL' => 'Netherlands', 'BE' => 'Belgium', 'LU' => 'Luxembourg',
			'CH' => 'Switzerland', 'AT' => 'Austria', 'SE' => 'Sweden',
			'NO' => 'Norway', 'DK' => 'Denmark', 'FI' => 'Finland', 'IS' => 'Iceland',
			'PL' => 'Poland', 'CZ' => 'Czechia', 'SK' => 'Slovakia', 'HU' => 'Hungary',
			'RO' => 'Romania', 'BG' => 'Bulgaria', 'GR' => 'Greece', 'HR' => 'Croatia',
			'RS' => 'Serbia', 'UA' => 'Ukraine', 'RU' => 'Russia', 'TR' => 'Turkey',
			'CY' => 'Cyprus', 'MT' => 'Malta',
			'CN' => 'China', 'JP' => 'Japan', 'KR' => 'South Korea', 'IN' => 'India',
			'PK' => 'Pakistan', 'BD' => 'Bangladesh', 'ID' => 'Indonesia',
			'MY' => 'Malaysia', 'SG' => 'Singapore', 'TH' => 'Thailand',
			'VN' => 'Vietnam', 'PH' => 'Philippines', 'HK' => 'Hong Kong',
			'AU' => 'Australia', 'NZ' => 'New Zealand',
			'ZA' => 'South Africa', 'NG' => 'Nigeria', 'KE' => 'Kenya',
			'ET' => 'Ethiopia', 'GH' => 'Ghana', 'TZ' => 'Tanzania',
			'BR' => 'Brazil', 'AR' => 'Argentina', 'CL' => 'Chile',
			'CO' => 'Colombia', 'PE' => 'Peru',
		);

		/**
		 * فلتر عشان تقدر تضيف/تعدل الدول من غير ما تلمس الفايل ده.
		 */
		return apply_filters( 'cs_countries_list', $list );
	}

	/**
	 * نفس الدول بالظبط، بس بالاسم العربي. code => name عربي.
	 *
	 * @return array
	 */
	protected static function all_ar() {
		$list = array(
			'EG' => 'مصر', 'SA' => 'المملكة العربية السعودية', 'AE' => 'الإمارات العربية المتحدة',
			'QA' => 'قطر', 'KW' => 'الكويت', 'BH' => 'البحرين', 'OM' => 'عُمان',
			'JO' => 'الأردن', 'LB' => 'لبنان', 'IQ' => 'العراق', 'SY' => 'سوريا',
			'YE' => 'اليمن', 'PS' => 'فلسطين', 'SD' => 'السودان', 'LY' => 'ليبيا',
			'TN' => 'تونس', 'DZ' => 'الجزائر', 'MA' => 'المغرب', 'MR' => 'موريتانيا',
			'US' => 'الولايات المتحدة', 'CA' => 'كندا', 'MX' => 'المكسيك',
			'GB' => 'المملكة المتحدة', 'IE' => 'أيرلندا', 'FR' => 'فرنسا',
			'DE' => 'ألمانيا', 'IT' => 'إيطاليا', 'ES' => 'إسبانيا', 'PT' => 'البرتغال',
			'NL' => 'هولندا', 'BE' => 'بلجيكا', 'LU' => 'لوكسمبورغ',
			'CH' => 'سويسرا', 'AT' => 'النمسا', 'SE' => 'السويد',
			'NO' => 'النرويج', 'DK' => 'الدنمارك', 'FI' => 'فنلندا', 'IS' => 'آيسلندا',
			'PL' => 'بولندا', 'CZ' => 'التشيك', 'SK' => 'سلوفاكيا', 'HU' => 'المجر',
			'RO' => 'رومانيا', 'BG' => 'بلغاريا', 'GR' => 'اليونان', 'HR' => 'كرواتيا',
			'RS' => 'صربيا', 'UA' => 'أوكرانيا', 'RU' => 'روسيا', 'TR' => 'تركيا',
			'CY' => 'قبرص', 'MT' => 'مالطا',
			'CN' => 'الصين', 'JP' => 'اليابان', 'KR' => 'كوريا الجنوبية', 'IN' => 'الهند',
			'PK' => 'باكستان', 'BD' => 'بنغلاديش', 'ID' => 'إندونيسيا',
			'MY' => 'ماليزيا', 'SG' => 'سنغافورة', 'TH' => 'تايلاند',
			'VN' => 'فيتنام', 'PH' => 'الفلبين', 'HK' => 'هونغ كونغ',
			'AU' => 'أستراليا', 'NZ' => 'نيوزيلندا',
			'ZA' => 'جنوب أفريقيا', 'NG' => 'نيجيريا', 'KE' => 'كينيا',
			'ET' => 'إثيوبيا', 'GH' => 'غانا', 'TZ' => 'تنزانيا',
			'BR' => 'البرازيل', 'AR' => 'الأرجنتين', 'CL' => 'تشيلي',
			'CO' => 'كولومبيا', 'PE' => 'بيرو',
		);

		/** نفس منطق الفلتر بتاع اللستة الإنجليزية، بس للأسماء العربية. */
		return apply_filters( 'cs_countries_list_ar', $list );
	}

	/**
	 * اسم المكان اللي هيتعرض. المكان دلوقتي نص حر (زي ما اتكتب بالظبط في
	 * الشيت أو حقل الأدمن)، فمفيش ترجمة أو lookup -- بس ننضّفه من مسافات
	 * زيادة في الأول والآخر.
	 */
	public static function name( $code ) {
		return trim( (string) $code );
	}

	/**
	 * تشيك إن فيه اسم مكان مكتوب فعلاً (مش فاضي). مبقاش بيتحقق من قايمة
	 * دول محددة -- أي نص مش فاضي مقبول.
	 */
	public static function exists( $code ) {
		return '' !== trim( (string) $code );
	}

	/**
	 * تنضيف قائمة أماكن (من الشيت مثلا "Cairo,Alex,Riyadh") -- بيسيب النص
	 * زي ما هو (من غير ما يحوّله لحروف كبيرة أو يتحقق من قايمة دول)، وبس
	 * بيشيل المسافات الزيادة والتكرار.
	 */
	public static function sanitize_codes( $raw ) {
		if ( is_string( $raw ) ) {
			$raw = array_map( 'trim', explode( ',', $raw ) );
		}
		$out = array();
		foreach ( (array) $raw as $code ) {
			$code = sanitize_text_field( trim( (string) $code ) );
			if ( '' !== $code ) {
				$out[] = $code;
			}
		}
		return array_values( array_unique( $out ) );
	}
}