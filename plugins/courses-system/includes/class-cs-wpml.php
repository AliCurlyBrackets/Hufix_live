<?php
/**
 * Feature: WPML Linking
 * مسؤول عن حاجة واحدة بس: تحديد لغة بوست معيّن، وربطه كترجمة لبوست تاني
 * (بنفس trid) باستخدام الـ API الرسمي بتاع WPML. مبيعملش أي ترجمة نصوص
 * فعلية -- ده شغل الأدمن (بيكتب النص العربي في الشيت)، إحنا بس بنربط.
 *
 * محتاج WPML Multilingual CMS شغّال وميظبطش عليه.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CS_WPML {

	/**
	 * عدد السيشنز اللي بتتربط في كل دفعة (batch). كورسات فيها أماكن كتير
	 * (زي 50 مكان) بتولّد مئات السيشنز، ولو حاولنا نربطهم كلهم مرة واحدة
	 * جوه نفس الريكوست اللي بيرفع الشيت، بيحصل زحمة استعلامات ضخمة
	 * (خصوصًا إن WPML نفسه بيعمل استعلامات إضافية في shutdown hook لكل
	 * بوست اتلمس) وده بيسبب صفحة بيضا (timeout أو desync مع الداتابيز).
	 * عشان كده الربط بقى بيحصل بالخلفية عبر WP-Cron على دفعات صغيرة
	 * بدل ما يحصل كله فورًا في نفس الريكوست.
	 */
	const SESSIONS_BATCH_SIZE = 25;

	/** الوقت بالثواني بين كل دفعة والتانية. */
	const BATCH_INTERVAL = 10;

	public static function init() {
		add_action( 'cs_tag_sessions_language_batch', array( __CLASS__, 'tag_sessions_language_batch' ), 10, 4 );

		// لازم نستنى لحد ما كل سيشنز الماستر تخلص توليد (ممكن يحصل في
		// الخلفية على دفعات دلوقتي -- شوف CS_Recurrence::queue_remaining_sessions())
		// قبل ما نبدأ نربطهم كترجمات. لو ربطنا بدري (زي الأول لما كان
		// التاجينج بينادى فورًا بعد generate_sessions())، أي سيشن لسه
		// هيتعمل بعد كده في دفعة تالية كان بيفوت الربط تمامًا (تاجينج
		// batch بتوقف أول ما تلاقي صفحة فيها أقل من SESSIONS_BATCH_SIZE،
		// وممكن تكون دي "آخر صفحة موجودة دلوقتي" مش آخر صفحة فعلاً).
		add_action( 'cs_sessions_generation_done', array( __CLASS__, 'maybe_tag_after_generation' ) );

		// لما نكون واقفين على صفحة كورس بوضع "عام" (cs_general=1 -- يعني
		// الكارت جاي من ليستة من غير فلتر، فالسعر/التاريخ/اللوكيشن متخفيين
		// عمدًا -- شوف single-course.php)، الـ Language Switcher الافتراضي
		// بتاع WPML بيبني لينك اللغة التانية من غير ما يعرف بالعلامة دي
		// خالص، فبيوديك لصفحة الكورس المقابل باللغة التانية لكن كاملة
		// التفاصيل (سعر/تاريخ/لوكيشن ظاهرين) بدل ما تفضل في نفس الوضع
		// العام زي الصفحة اللي جاي منها. الفلتر ده بيثبّت نفس العلامة على
		// لينكات السويتشر عشان الوضع (عام/تفصيلي) يفضل متطابق بين اللغتين.
		add_filter( 'wpml_ls_language_url', array( __CLASS__, 'preserve_general_view_on_switch' ), 10, 2 );
	}

	/**
	 * حافظ على علامة cs_general=1 وإحنا بنولّد لينكات الـ Language Switcher،
	 * عشان صفحة الكورس تفضل في نفس وضع العرض (عام/تفصيلي) بعد تبديل اللغة.
	 *
	 * @param string $url  اللينك اللي WPML بناه للغة دي.
	 * @param array  $args بيانات اللغة (فيها 'code' مثلاً).
	 * @return string
	 */
	public static function preserve_general_view_on_switch( $url, $args ) {
		if ( ! is_singular( CS_CPT ) ) {
			return $url;
		}
		if ( ! isset( $_GET['cs_general'] ) || '1' !== (string) $_GET['cs_general'] ) {
			return $url;
		}
		if ( ! $url ) {
			return $url;
		}
		return add_query_arg( 'cs_general', '1', $url );
	}

	/**
	 * بتشتغل تلقائي أول ما كل سيشنز ماستر معيّن تخلص توليد بالكامل. لو
	 * الماستر ده متعلّم من CS_Import إنه محتاج ربط لغة/ترجمة (عمود lang
	 * أو source_course_id في الشيت)، بيحصل الربط دلوقتي بس -- بعد ما نتأكد
	 * إن كل سيشناته موجودة فعلاً. لو الماستر ده مش جايه من استيراد
	 * (زي حفظ يدوي عادي أو كرون التجديد السنوي)، الدالة بترجع فورًا من
	 * غير أي تأثير.
	 *
	 * @param int $master_id
	 */
	public static function maybe_tag_after_generation( $master_id ) {
		if ( ! self::active() ) {
			return;
		}

		// الكورس ده بيتربط دلوقتي يدويًا من صفحة "ربط الترجمة"
		// (CS_Translation_Link). الربط اليدوي أدق -- بيربط كل سيشن
		// بالسيشن المقابل له فعليًا -- فمنسيبش الكرون يشتغل عليه في نفس
		// الوقت ويستبدل الربط بمجرد set_language. الحارس ده مؤقت (نص
		// ساعة) عشان الكرون العادي يرجع يشتغل طبيعي بعد ما الربط يخلص.
		if ( get_transient( 'cs_manual_link_' . $master_id ) ) {
			return;
		}

		$lang_code = get_post_meta( $master_id, '_cs_pending_wpml_lang', true );
		if ( ! $lang_code ) {
			return;
		}

		$source_id = (int) get_post_meta( $master_id, '_cs_pending_wpml_source', true );

		// لغة الماستر نفسه اتحددت فورًا وقت الاستيراد (شوف
		// CS_Import::import_row())، مش هنا -- هنا بس بنربط كل سيشن
		// اتولد فعلاً بالسيشن الإنجليزي المقابل له (نفس التاريخ + الدولة).
		self::tag_sessions_language( $master_id, $lang_code, $source_id );

		delete_post_meta( $master_id, '_cs_pending_wpml_lang' );
		delete_post_meta( $master_id, '_cs_pending_wpml_source' );
	}

	/**
	 * بتتنادى من CS_Recurrence::create_session() لحظة ما السيشن يتعمل --
	 * مش في مرحلة تاجينج منفصلة بعدين.
	 *
	 * بتحط على السيشن نفس لغة الماستر بتاعه، ولو الماستر ده أصلاً ترجمة
	 * لماستر تاني، بتربط السيشن كترجمة فعلية للسيشن المقابل ليه في الأصل
	 * (نفس التاريخ + نفس ترتيب المكان).
	 *
	 * ليه ده مهم: أي بوست بيتعمل جوه طلب مالوش سياق لغة واضح (كرون، أو
	 * أي عملية خلفية) WPML بيديله لغة الموقع الافتراضية تلقائيًا -- حتى لو
	 * الماستر بتاعه عربي. فلو سبنا التاجينج لمرحلة تانية، أي سيشن بيتعمل
	 * بره نافذة التاجينج بيضيع (يا إنجليزي غلط يا من غير لغة خالص).
	 *
	 * @param int    $session_id
	 * @param int    $master_id
	 * @param string $date      تاريخ السيشن (Y-m-d).
	 * @param int    $loc_index ترتيب المكان جوه repeater الأسعار.
	 * @param string $country   اسم المكان (احتياطي للمطابقة).
	 */
	public static function tag_session_on_create( $session_id, $master_id, $date, $loc_index = 0, $country = '' ) {
		if ( ! self::active() || ! $session_id ) {
			return;
		}

		$lang = self::post_language( $master_id );
		if ( ! $lang ) {
			return; // الماستر نفسه مالوش لغة -- مفيش حاجة نعملها.
		}

		$source_master = self::source_master_of( $master_id, $lang );

		if ( $source_master ) {
			$match = self::find_source_session( $source_master, $date, $loc_index, $country );
			if ( $match && $match !== (int) $session_id ) {
				self::link_translation( $session_id, $match, $lang );
				return;
			}
		}

		self::set_language( $session_id, $lang );
	}

	/**
	 * ID الماستر الأصلي لو الماستر ده ترجمة، أو 0 لو هو الأصل.
	 * بنكاشها في الذاكرة لأنها بتتنادى مرة لكل سيشن (مئات المرات).
	 */
	protected static function source_master_of( $master_id, $lang ) {
		static $cache = array();

		$key = $master_id . '|' . $lang;
		if ( isset( $cache[ $key ] ) ) {
			return $cache[ $key ];
		}

		$default = self::default_language();
		$src     = 0;

		if ( $lang !== $default ) {
			$maybe = (int) apply_filters( 'wpml_object_id', (int) $master_id, CS_CPT, false, $default );
			if ( $maybe && $maybe !== (int) $master_id && get_post_type( $maybe ) === CS_CPT ) {
				$src = $maybe;
			}
		}

		$cache[ $key ] = $src;

		return $src;
	}

	/**
	 * بتدوّر على سيشن الماستر الأصلي المقابل لتاريخ + ترتيب مكان معيّن.
	 * استعلام مباشر واحد (على أعمدة مفهرسة) بدل ما نبني فهرس كامل ونكاشه --
	 * الكاش كان ممكن يبقى قديم لو سيشنز الأصل نفسها بتتعمل في نفس العملية.
	 *
	 * @return int 0 لو مفيش مقابل.
	 */
	protected static function find_source_session( $source_master_id, $date, $loc_index, $country = '' ) {
		global $wpdb;

		$found = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT m.post_id
				 FROM {$wpdb->postmeta} m
				 INNER JOIN {$wpdb->postmeta} d ON d.post_id = m.post_id AND d.meta_key = %s AND d.meta_value = %s
				 INNER JOIN {$wpdb->postmeta} l ON l.post_id = m.post_id AND l.meta_key = %s AND l.meta_value = %d
				 INNER JOIN {$wpdb->posts} p ON p.ID = m.post_id AND p.post_status = 'publish'
				 WHERE m.meta_key = %s AND m.meta_value = %d
				 LIMIT 1",
				CS_META_DATE,
				$date,
				CS_META_LOC_INDEX,
				(int) $loc_index,
				CS_META_MASTER,
				(int) $source_master_id
			)
		);

		if ( $found ) {
			return $found;
		}

		// احتياطي: نفس التاريخ + نفس اسم المكان (لسيشنز قديمة مالهاش ترتيب).
		if ( '' !== $country ) {
			$found = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT m.post_id
					 FROM {$wpdb->postmeta} m
					 INNER JOIN {$wpdb->postmeta} d ON d.post_id = m.post_id AND d.meta_key = %s AND d.meta_value = %s
					 INNER JOIN {$wpdb->postmeta} c ON c.post_id = m.post_id AND c.meta_key = %s AND c.meta_value = %s
					 INNER JOIN {$wpdb->posts} p ON p.ID = m.post_id AND p.post_status = 'publish'
					 WHERE m.meta_key = %s AND m.meta_value = %d
					 LIMIT 1",
					CS_META_DATE,
					$date,
					CS_META_COUNTRY,
					$country,
					CS_META_MASTER,
					(int) $source_master_id
				)
			);
		}

		return $found;
	}

	/** هل WPML شغّال فعلاً على الموقع؟ */
	public static function active() {
		return defined( 'ICL_SITEPRESS_VERSION' ) || class_exists( 'SitePress' );
	}

	/**
	 * هل البوست ده "نسخة شبح" اتعملت أوتوماتيك بواسطة خاصية WPML
	 * "Automatically duplicate content not available in this language"؟
	 * WPML بيحط الـ meta ده (_icl_lang_duplicate_of = ID الأصل) على أي
	 * بوست بيتعمله duplicate تلقائي، مش استيراد/إنشاء يدوي حقيقي.
	 *
	 * لازم نستبعد النسخ دي من أي منطق بيعامل بوست كـ "ماستر كورس" --
	 * لو سبناها تتعامل عادي، هتتولدلها سيشنز كاملة تانية (نسخة كاملة من
	 * كل سيشنز الأصل) لأنها بتورث كل الـ postmeta بما فيها علامة "ماستر"،
	 * وده اللي كان بيضاعف عدد السيشنز (زي 701 بيبقوا 1400).
	 */
	public static function is_auto_duplicate( $post_id ) {
		return (bool) get_post_meta( (int) $post_id, '_icl_lang_duplicate_of', true );
	}

	/** كود اللغة الافتراضي للموقع (مثلاً "en"). */
	public static function default_language() {
		$lang = apply_filters( 'wpml_default_language', null );
		return $lang ? $lang : 'en';
	}

	/**
	 * رجّع ID الترجمة الموجودة لكورس أصل في لغة معيّنة.
	 * مهم في الاستيراد عشان source_course_id يبقى هو المفتاح الحقيقي
	 * للترجمة بدل الاعتماد على تطابق العنوان.
	 *
	 * @param int    $source_id
	 * @param string $lang_code
	 * @return int 0 لو مفيش ترجمة موجودة.
	 */
	/**
	 * Return the translation of a Course System post in an explicit language.
	 * This is the single resolver used by frontend links and single-course
	 * rendering. It never relies on the current post's (possibly stale)
	 * language assignment.
	 */

	/**
	 * Resolve a session to the session belonging to a specific frontend language.
	 *
	 * IMPORTANT: Sessions are independent CPT posts. WPML's wpml_object_id() is
	 * only reliable here when the two sessions already belong to the same
	 * translation group. During/after imports that is not guaranteed. Therefore
	 * we resolve by the authoritative master translation + session identity
	 * (date + location index, with country as fallback).
	 */
	public static function localized_session_id( $session_id, $lang_code = '' ) {
		$session_id = (int) $session_id;
		if ( ! $session_id ) {
			return 0;
		}
		if ( ! self::active() ) {
			return $session_id;
		}

		$lang_code = $lang_code ? sanitize_key( $lang_code ) : sanitize_key( (string) apply_filters( 'wpml_current_language', null ) );
		if ( ! $lang_code ) {
			return $session_id;
		}

		$current_lang = self::post_language( $session_id );

		$master_id = (int) get_post_meta( $session_id, CS_META_MASTER, true );
		if ( $current_lang === $lang_code && $master_id ) {
			// Same language is only acceptable when the session points to a
			// master in that same language. Historical links may have left an
			// Arabic session with an English _cs_master (or vice versa).
			$ml = self::post_language( $master_id );
			if ( ! $ml || $ml === $lang_code ) {
				return $session_id;
			}
		}

		if ( ! $master_id ) {
			return $session_id;
		}
		if ( ! $master_id ) {
			return $session_id;
		}

		// Resolve the master to the requested language first. This is the
		// authoritative identity of the course, unlike a stale session language.
		$localized_master = self::localized_post_id( $master_id, $lang_code );
		if ( ! $localized_master || get_post_type( $localized_master ) !== CS_CPT ) {
			return $session_id;
		}

		$date      = (string) get_post_meta( $session_id, CS_META_DATE, true );
		$loc_index = get_post_meta( $session_id, CS_META_LOC_INDEX, true );
		$country   = (string) get_post_meta( $session_id, CS_META_COUNTRY, true );

		// First try an already linked WPML translation, but accept it only when
		// its language is exactly the requested one.
		$translated = (int) apply_filters( 'wpml_object_id', $session_id, CS_CPT, false, $lang_code );
		if ( $translated && $translated !== $session_id && get_post_type( $translated ) === CS_CPT && self::post_language( $translated ) === $lang_code ) {
			$tm = (int) get_post_meta( $translated, CS_META_MASTER, true );
			$tm = $tm ? self::localized_post_id( $tm, $lang_code ) : 0;
			if ( $tm === $localized_master ) {
				return $translated;
			}
		}

		// If WPML session translation is missing or stale, find the sibling
		// session generated from the language-specific master.
		global $wpdb;
		$meta_master = $wpdb->postmeta;
		$posts       = $wpdb->posts;
		$table       = self::icl_table_name();

		$sql = "SELECT p.ID
			FROM {$posts} p
			INNER JOIN {$meta_master} mm ON mm.post_id = p.ID AND mm.meta_key = %s AND mm.meta_value = %d
			INNER JOIN {$meta_master} md ON md.post_id = p.ID AND md.meta_key = %s AND md.meta_value = %s
			INNER JOIN {$table} it ON it.element_id = p.ID AND it.element_type = %s AND it.language_code = %s
			WHERE p.post_type = %s AND p.post_status IN ('publish','private')";
		$params = array( CS_META_MASTER, $localized_master, CS_META_DATE, $date, 'post_' . CS_CPT, $lang_code, CS_CPT );

		if ( '' !== (string) $loc_index ) {
			$sql .= " INNER JOIN {$meta_master} ml ON ml.post_id = p.ID AND ml.meta_key = %s AND ml.meta_value = %d";
			$params[] = CS_META_LOC_INDEX;
			$params[] = (int) $loc_index;
		}
		$sql .= " ORDER BY p.ID ASC LIMIT 1";

		$found = (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
		if ( $found ) {
			return $found;
		}

		// Last fallback for legacy sessions without loc_index.
		if ( '' !== $country ) {
			$sql = "SELECT p.ID
				FROM {$posts} p
				INNER JOIN {$meta_master} mm ON mm.post_id = p.ID AND mm.meta_key = %s AND mm.meta_value = %d
				INNER JOIN {$meta_master} md ON md.post_id = p.ID AND md.meta_key = %s AND md.meta_value = %s
				INNER JOIN {$meta_master} mc ON mc.post_id = p.ID AND mc.meta_key = %s AND mc.meta_value = %s
				INNER JOIN {$table} it ON it.element_id = p.ID AND it.element_type = %s AND it.language_code = %s
				WHERE p.post_type = %s AND p.post_status IN ('publish','private')
				ORDER BY p.ID ASC LIMIT 1";
			$params = array( CS_META_MASTER, $localized_master, CS_META_DATE, $date, CS_META_COUNTRY, $country, 'post_' . CS_CPT, $lang_code, CS_CPT );
			$found = (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
			if ( $found ) {
				return $found;
			}
		}

		return $session_id;
	}

	/** Return WPML's translations table name, or empty when unavailable. */
	protected static function icl_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'icl_translations';
	}

	public static function localized_post_id( $post_id, $lang_code = '' ) {
		$post_id = (int) $post_id;
		if ( ! $post_id ) {
			return 0;
		}
		if ( ! self::active() ) {
			return $post_id;
		}
		$lang_code = $lang_code ? sanitize_key( $lang_code ) : sanitize_key( (string) apply_filters( 'wpml_current_language', null ) );
		if ( ! $lang_code ) {
			return $post_id;
		}
		$localized = (int) apply_filters( 'wpml_object_id', $post_id, CS_CPT, false, $lang_code );
		if ( $localized && get_post_type( $localized ) === CS_CPT ) {
			return $localized;
		}
		// If WPML has no translation yet, keep the supplied ID rather than
		// inventing an unrelated post.
		return $post_id;
	}

	public static function get_translation_id( $source_id, $lang_code ) {
		if ( ! self::active() || ! $source_id || ! $lang_code ) {
			return 0;
		}

		$translated_id = apply_filters( 'wpml_object_id', (int) $source_id, CS_CPT, false, sanitize_key( $lang_code ) );
		$translated_id = (int) $translated_id;

		// WPML ممكن يرجّع الأصل نفسه في بعض الحالات؛ ده مش ترجمة فعلية.
		if ( ! $translated_id || $translated_id === (int) $source_id || get_post_type( $translated_id ) !== CS_CPT ) {
			return 0;
		}

		// تأكيد إضافي إن البوست اللي لقيناه فعلًا باللغة المطلوبة.
		$found_lang = self::post_language( $translated_id );
		if ( $found_lang && $found_lang !== sanitize_key( $lang_code ) ) {
			return 0;
		}

		return $translated_id;
	}

	/**
	 * حدّد لغة بوست. لو مش متحدد له trid قبل كده، هيتعمله واحد جديد تلقائي.
	 *
	 * @param int    $post_id
	 * @param string $lang_code كود اللغة زي "en" أو "ar"
	 */
	public static function set_language( $post_id, $lang_code ) {
		if ( ! self::active() ) {
			return;
		}

		$element_type = 'post_' . CS_CPT;
		$trid         = apply_filters( 'wpml_element_trid', null, $post_id, $element_type );

		do_action( 'wpml_set_element_language_details', array(
			'element_id'           => $post_id,
			'element_type'         => $element_type,
			'trid'                 => $trid, // null = هيتعمله trid جديد.
			'language_code'        => $lang_code,
			'source_language_code' => null,
		) );
	}

	/**
	 * Resolve the master that belongs to the same WPML language as a session.
	 * The session stores a master post ID in _cs_master; that ID must always
	 * point to the master translation in the session's own language.
	 */
	public static function resolve_session_master( $session_id, $rewrite = false ) {
		$session_id = (int) $session_id;
		$master_id  = (int) get_post_meta( $session_id, CS_META_MASTER, true );
		if ( ! $session_id || ! $master_id ) {
			return $master_id;
		}
		if ( ! self::active() ) {
			return $master_id;
		}

		$session_lang = self::post_language( $session_id );
		$master_lang  = self::post_language( $master_id );
		if ( ! $session_lang || ! $master_lang || $session_lang === $master_lang ) {
			return $master_id;
		}

		$localized = (int) apply_filters( 'wpml_object_id', $master_id, CS_CPT, false, $session_lang );
		if ( $localized && get_post_type( $localized ) === CS_CPT && $localized !== $master_id ) {
			if ( $rewrite ) {
				update_post_meta( $session_id, CS_META_MASTER, $localized );
			}
			return $localized;
		}
		return $master_id;
	}

	/**
	 * Category source of truth. For a session, categories are inherited from
	 * its language-matched master; for a master, they are its own categories.
	 */
	public static function expected_terms_for_post( $post_id ) {
		$post_id = (int) $post_id;
		$lang    = self::post_language( $post_id );
		if ( ! $post_id || ! $lang ) {
			return array();
		}
		$master = (int) get_post_meta( $post_id, CS_META_MASTER, true );
		if ( $master ) {
			$master = self::resolve_session_master( $post_id, true );
			$terms = self::same_language_terms( $master, $lang );
			if ( ! empty( $terms ) ) {
				return $terms;
			}
		}
		return self::same_language_terms( $post_id, $lang );
	}

	/**
	 * Restore a post's language-specific taxonomy from its master. This is
	 * intentionally strict: a session may never inherit a category from the
	 * other language just because WPML synchronized terms during linking.
	 */
	public static function enforce_post_category_from_master( $post_id ) {
		$post_id = (int) $post_id;
		if ( ! $post_id || ! self::active() ) {
			return;
		}
		$lang  = self::post_language( $post_id );
		$terms = self::expected_terms_for_post( $post_id );
		if ( $lang && ! empty( $terms ) ) {
			self::set_terms_for_language( $post_id, $terms, $lang );
	}
	}

	/**
	 * اربط $post_id كترجمة لـ $source_id بلغة $lang_code.
	 * لو $source_id لسه مالوش لغة متحددة، هيتحط في اللغة الافتراضية للموقع الأول.
	 *
	 * @return bool نجح الربط ولا لأ
	 */
	public static function link_translation( $post_id, $source_id, $lang_code ) {
		if ( ! self::active() ) {
			return false;
		}
		if ( ! $post_id || ! $source_id || $post_id === $source_id ) {
			return false;
		}
		if ( get_post_type( $source_id ) !== CS_CPT ) {
			return false;
		}

		$element_type = 'post_' . CS_CPT;

		// Normalize session -> master pointers before taking any snapshot.
		// Old versions could leave an Arabic session pointing to the English
		// master (or vice versa), which made price/location resolve from the
		// wrong language after a translation link.
		self::resolve_session_master( $post_id, true );
		self::resolve_session_master( $source_id, true );

		// تأكد إن الأصل نفسه متحدد له لغة (مهم عشان الـ trid يتظبط صح).
		$source_lang = apply_filters( 'wpml_element_language_code', null, array(
			'element_id'   => $source_id,
			'element_type' => $element_type,
		) );

		if ( ! $source_lang ) {
			$source_lang = self::default_language();
			self::set_language( $source_id, $source_lang );
		}

		// احفظ Category + بيانات الكورس/السيشن الخاصة بالترجمة قبل الربط.
		// WPML قد يشغّل مزامنة تلقائية لحظة تغيير الـ trid، وقد تنسخ بعض
		// custom fields من المصدر للهدف. في نظامنا ده غير مطلوب لأن الشيت
		// العربي/الإنجليزي يحتوي بيانات مستقلة (خصوصًا المكان والسعر وجدول
		// المحتوى). لذلك الربط يجب أن يغيّر علاقة الترجمة فقط، وليس بيانات
		// الكورس.
		$source_terms_before = self::expected_terms_for_post( $source_id );
		$target_lang         = sanitize_key( $lang_code );
		$target_terms_before = self::expected_terms_for_post( $post_id );

		// IMPORTANT: WPML can synchronize custom fields/taxonomies on BOTH
		// sides when a translation relationship is created. The old code only
		// snapshotted the target, so a session's _cs_master / location / index
		// on the SOURCE side could be changed silently. That is what caused
		// sessions to move between language category archives (701 -> 1401)
		// and made the single-session page unable to resolve its price/location.
		// Snapshot BOTH sides and restore BOTH sides after WPML finishes.
		$source_snapshot = self::snapshot_course_data( $source_id );
		$target_snapshot = self::snapshot_course_data( $post_id );

		$trid = apply_filters( 'wpml_element_trid', null, $source_id, $element_type );

		do_action( 'wpml_set_element_language_details', array(
			'element_id'           => $post_id,
			'element_type'         => $element_type,
			'trid'                 => $trid,
			'language_code'        => $lang_code,
			'source_language_code' => $source_lang,
		) );

		// WPML انتهى من الربط؛ رجّع كل بيانات الترجمة المستهدفة أولًا،
		// ثم Category كل لغة بشكل صريح. كده link_translation لا يقدر يبدّل
		// السعر/المكان/المحتوى العربي إلى نسخة المصدر الإنجليزي.
		// IMPORTANT: never write ACF master fields back during a translation
		// link. ACF/WPML can treat update_field() as a translation-sync event
		// and overwrite the other language. The CSV/import is the source of
		// truth for master content. We only restore SESSION identity meta
		// (master/date/country/index/end) when needed.
		if ( ! empty( $source_snapshot['session_meta'] ) ) {
			self::restore_course_data( $source_id, array( 'session_meta' => $source_snapshot['session_meta'] ) );
		}
		if ( ! empty( $target_snapshot['session_meta'] ) ) {
			self::restore_course_data( $post_id, array( 'session_meta' => $target_snapshot['session_meta'] ) );
		}

		// Restore taxonomy on BOTH sides as well. During this operation we
		// suppress the generic master->session hook; otherwise WPML's own
		// taxonomy hooks can temporarily apply the source term to both language
		// session sets, producing 1400/700 category counts.
		self::without_session_term_sync( function () use ( $source_id, $source_terms_before, $source_lang, $post_id, $target_terms_before, $target_lang ) {
			self::set_terms_for_language( $source_id, $source_terms_before, $source_lang );
			self::set_terms_for_language( $post_id, $target_terms_before, $target_lang );
		} );

		// Final source-of-truth pass. A session gets the category of its
		// language-matched master, never the category WPML happened to sync.
		self::enforce_post_category_from_master( $source_id );
		self::enforce_post_category_from_master( $post_id );

		if ( class_exists( 'CS_Category_Guard' ) ) {
			CS_Category_Guard::remember( $source_id );
			CS_Category_Guard::remember( $post_id );
		}

		return true;
	}

	/**
	 * Snapshot لبيانات Course System الخاصة بالبوست قبل ربط WPML.
	 * لا نحفظ أي meta داخلي خاص بـ WPML؛ فقط بيانات النظام التي يجب أن
	 * تظل مستقلة لكل لغة.
	 */
	protected static function snapshot_course_data( $post_id ) {
		$out = array( 'fields' => array(), 'thumb' => 0, 'session_meta' => array() );
		$is_session = (bool) get_post_meta( $post_id, CS_META_MASTER, true );

		// ACF Course fields belong to the Master only. Session pages read them
		// through cs_field() from their master, so never create/restore empty
		// ACF values on a session while linking it.
		if ( ! $is_session ) {
			$fields = array(
				'cs_summary',
				'cs_delivery_mode',
				'cs_language',
				'cs_duration_hours',
				'cs_country_prices',
				'cs_base_start_date',
				'cs_recurrence',
				'cs_objectives',
				'cs_schedule',
				'cs_outline_pdf',
			);
			foreach ( $fields as $field ) {
				$value = get_field( $field, $post_id );
				if ( null !== $value ) {
					$out['fields'][ $field ] = $value;
				}
			}
			$out['thumb'] = (int) get_post_thumbnail_id( $post_id );
		}

		// Session-specific identity/location must never be overwritten by linking.
		if ( $is_session ) {
			foreach ( array( CS_META_MASTER, CS_META_DATE, CS_META_COUNTRY, CS_META_LOC_INDEX, '_cs_session_end' ) as $key ) {
				$out['session_meta'][ $key ] = get_post_meta( $post_id, $key, true );
			}
		}
		return $out;
	}

	/** استرجاع Snapshot بعد انتهاء ربط WPML. */
	protected static function restore_course_data( $post_id, $snapshot ) {
		if ( ! is_array( $snapshot ) ) {
			return;
		}
		if ( ! empty( $snapshot['fields'] ) ) {
			foreach ( $snapshot['fields'] as $field => $value ) {
				update_field( $field, $value, $post_id );
			}
		}
		if ( ! empty( $snapshot['thumb'] ) ) {
			set_post_thumbnail( $post_id, (int) $snapshot['thumb'] );
		}
		if ( ! empty( $snapshot['session_meta'] ) ) {
			foreach ( $snapshot['session_meta'] as $key => $value ) {
				update_post_meta( $post_id, $key, $value );
			}
		}
	}

	/**
	 * حدّد لغة term (كاتيجوري) في WPML. زي set_language بالظبط بس
	 * للتاكسونومي مش للبوست -- WPML بيتعامل مع عناصر التاكسونومي كـ
	 * element_type منفصل ("tax_{taxonomy}") ومفيهوش أي ربط تلقائي
	 * بلغة البوست اللي هيتحط عليه.
	 *
	 * ليه محتاجينها: لو سبنا WPML يحدد لغة الـ term لوحده وقت
	 * wp_insert_term()، بياخد "اللغة الحالية" بتاعة سياق الريكوست
	 * (غالبًا لغة لوحة التحكم الافتراضية)، مش لغة الكورس اللي بنحط
	 * عليه الكاتيجوري -- فكاتيجوري بنص عربي 100% ممكن تتحط "en" لو
	 * حصل الإنشاء ولوحة التحكم شغالة إنجليزي، وتفضل تظهر تحت تبويب
	 * اللغة الغلط للأبد. بننادي الدالة دي فورًا بعد إنشاء أي term
	 * جديدة (مش الموجودة) في find_or_create_category_term()، ونديها
	 * لغة الكورس نفسه.
	 *
	 * @param int    $term_id
	 * @param string $lang_code
	 */
	public static function set_term_language( $term_id, $lang_code ) {
		if ( ! self::active() || ! $term_id || ! $lang_code ) {
			return;
		}

		$element_type = 'tax_' . CS_TAX;
		$trid         = apply_filters( 'wpml_element_trid', null, $term_id, $element_type );

		do_action( 'wpml_set_element_language_details', array(
			'element_id'           => $term_id,
			'element_type'         => $element_type,
			'trid'                 => $trid, // null = هيتعمله trid جديد.
			'language_code'        => $lang_code,
			'source_language_code' => null,
		) );
	}

	/**
	 * نفّذ أي عملية (Closure) من غير ما فلترة اللغة بتاعة WPML تتدخل فيها.
	 *
	 * ليه محتاجينها: WP_Term_Query مع suppress_filters=true (زي اللي
	 * بنستخدمها في find_or_create_category_term() و assign_category()
	 * في class-cs-import.php) بتضمن بس إن *القراءة* اللي إحنا كاتبينها
	 * بنفسنا ميتفلترش. لكن wp_set_object_terms() القياسية بتاعة ووردبريس
	 * -- الدالة اللي بتعمل الإسناد الفعلي -- بتعمل جوّاها استدعاء *تاني*
	 * لـ term_exists()، وده بره سيطرتنا وبيتفلتر بواسطة WPML برضه. يعني
	 * حتى لو اتأكدنا إن الـ term_id موجود قبل النداء، wp_set_object_terms()
	 * لوحدها ممكن ترفض تحطه لو مش بلغة سياق الأدمن الحالي وقت التنفيذ --
	 * فكاتيجوري عربي بيترفض بصمت لو سياق الأدمن (أو الكرون) وقتها إنجليزي،
	 * حتى لو الكود اللي قبلها "تأكد" إنه موجود. ده اللي كان بيخلي ID
	 * الإنجليزي يشتغل بينما نفس المنطق بالظبط بيفشل مع ID عربي.
	 *
	 * الحل الرسمي بتاع WPML لده: switch_lang('all') بتوقف فلترة اللغة
	 * تمامًا لحد ما نرجعها تاني -- فأي get_term_by/term_exists جوه
	 * $callback() هيشوف كل الـ terms بغض النظر عن لغتها.
	 *
	 * @param callable $callback
	 * @return mixed نتيجة $callback()، أو null لو WPML مش شغّال (هينفذ
	 *               $callback() عادي من غير أي لف).
	 */
	/**
	 * Temporarily stop Course System's master->session taxonomy propagation.
	 * Translation linking must not use the generic set_object_terms hook as a
	 * side effect; categories are reconciled explicitly after the relationship
	 * is established.
	 */
	public static function without_session_term_sync( $callback ) {
		$GLOBALS['cs_wpml_suppress_session_term_sync'] = true;
		try {
			return call_user_func( $callback );
		} finally {
			unset( $GLOBALS['cs_wpml_suppress_session_term_sync'] );
		}
	}

	public static function without_language_filter( $callback ) {
		global $sitepress;

		if ( ! self::active() || ! isset( $sitepress ) || ! is_object( $sitepress ) || ! method_exists( $sitepress, 'switch_lang' ) ) {
			return call_user_func( $callback );
		}

		$current = method_exists( $sitepress, 'get_current_language' ) ? $sitepress->get_current_language() : null;

		$sitepress->switch_lang( 'all', true );

		try {
			return call_user_func( $callback );
		} finally {
			$sitepress->switch_lang( $current ? $current : '', true );
		}
	}

	/**
	 * لغة كاتيجوري (term) معيّن في CS_TAX. WPML بيخزّن لغة عناصر
	 * التاكسونومي بالـ term_taxonomy_id مش term_id (عكس البوستات) --
	 * شوف نفس الملحوظة في CS_Category_Cleanup::term_language().
	 *
	 * @param int $term_id
	 * @return string كود اللغة ('en', 'ar'..)، أو '' لو مش معروفة/WPML مش شغّال.
	 */
	public static function term_language( $term_id ) {
		if ( ! self::active() || ! $term_id ) {
			return '';
		}

		$lookup = function () use ( $term_id ) {
			$term = get_term( (int) $term_id, CS_TAX );
			if ( ! $term || is_wp_error( $term ) ) {
				return '';
			}

			$lang = apply_filters( 'wpml_element_language_code', null, array(
				'element_id'   => $term->term_taxonomy_id,
				'element_type' => 'tax_' . CS_TAX,
			) );

			return $lang ? (string) $lang : '';
		};

		// get_term() نفسه ممكن يتفلتر حسب لغة سياق WPML، لذلك لازم نقرأ
		// لغة الـ term من غير فلتر اللغة. وإلا term صحيحة عربيًا مثل 88
		// ممكن تظهر للكود كأنها غير موجودة أو بلغة مختلفة.
		return self::without_language_filter( $lookup );
	}

	/**
	 * حافظ على كاتيجوري كل ترجمة من غير ما WPML يبدّلها مع الترجمة الأخرى.
	 *
	 * المطلوب في نظام الكورسات:
	 *   EN post -> EN category
	 *   AR post -> AR category
	 *
	 * WPML قد يعمل taxonomy synchronization تلقائيًا بعد حفظ إحدى
	 * الترجمات، فيمسح term اللغة الأخرى. لذلك بعد تعيين term على أي ترجمة،
	 * نستخدم علاقة WPML نفسها لاستخراج post/category الترجمة المقابلة،
	 * ثم نثبت كل زوج على الـ Post الصحيح.
	 *
	 * @param int   $post_id
	 * @param int[] $term_ids
	 */
	/**
	 * احتفظ بكاتيجوري كل ترجمة أثناء ربط WPML.
	 *
	 * WPML قد يزامن taxonomy relationships لحظة ربط ترجمتين. في نظامنا
	 * ده غير مطلوب: كل Post له Category من نفس لغته فقط.
	 * لذلك نأخذ الحالة الصحيحة قبل الربط، نسمح لـ WPML بالربط، ثم نعيد
	 * نفس Category لكل Post ونحذف أي term من لغة أخرى.
	 */
	public static function repair_translation_categories( $source_id, $target_id, $target_lang = '' ) {
		$source_id = (int) $source_id;
		$target_id = (int) $target_id;
		if ( ! self::active() || ! $source_id || ! $target_id || $source_id === $target_id ) {
			return;
		}

		$source_lang = self::post_language( $source_id );
		$target_lang = $target_lang ? sanitize_key( $target_lang ) : self::post_language( $target_id );
		if ( ! $source_lang || ! $target_lang ) {
			return;
		}

		$source_terms = self::same_language_terms( $source_id, $source_lang );
		$target_terms = self::same_language_terms( $target_id, $target_lang );

		// لو ناحية ناقصة، حاول نجيب Category ترجمتها من الناحية الأخرى.
		if ( empty( $source_terms ) && ! empty( $target_terms ) ) {
			foreach ( $target_terms as $term_id ) {
				$translated = (int) apply_filters( 'wpml_object_id', $term_id, CS_TAX, false, $source_lang );
				if ( $translated && self::term_language( $translated ) === $source_lang ) {
					$source_terms[] = $translated;
				}
			}
		}
		if ( empty( $target_terms ) && ! empty( $source_terms ) ) {
			foreach ( $source_terms as $term_id ) {
				$translated = (int) apply_filters( 'wpml_object_id', $term_id, CS_TAX, false, $target_lang );
				if ( $translated && self::term_language( $translated ) === $target_lang ) {
					$target_terms[] = $translated;
				}
			}
		}

		self::set_terms_for_language( $source_id, $source_terms, $source_lang );
		self::set_terms_for_language( $target_id, $target_terms, $target_lang );

		if ( class_exists( 'CS_Category_Guard' ) ) {
			CS_Category_Guard::remember( $source_id );
			CS_Category_Guard::remember( $target_id );
		}
	}

	public static function same_language_terms( $post_id, $lang_code = '' ) {
		$post_id = (int) $post_id;
		$lang_code = $lang_code ? sanitize_key( $lang_code ) : self::post_language( $post_id );
		if ( ! $post_id || ! $lang_code ) {
			return array();
		}
		$terms = self::without_language_filter( function () use ( $post_id ) {
			$ids = wp_get_object_terms( $post_id, CS_TAX, array( 'fields' => 'ids', 'suppress_filters' => true ) );
			return is_wp_error( $ids ) ? array() : array_map( 'intval', (array) $ids );
		} );
		$out = array();
		foreach ( $terms as $term_id ) {
			$term_lang = self::term_language( $term_id );
			if ( ! $term_lang || $term_lang === $lang_code ) {
				$out[] = (int) $term_id;
			}
		}
		return array_values( array_unique( $out ) );
	}

	public static function set_terms_for_language( $post_id, $term_ids, $lang_code ) {
		$post_id = (int) $post_id;
		$lang_code = sanitize_key( $lang_code );
		$term_ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $term_ids ) ) ) );
		if ( ! $post_id || ! $lang_code ) {
			return;
		}
		$valid = array();
		foreach ( $term_ids as $term_id ) {
			$term_lang = self::term_language( $term_id );
			if ( ! $term_lang || $term_lang === $lang_code ) {
				$valid[] = $term_id;
			}
		}
		// WPML's term-adjustment filter can silently replace a translated
		// term with the default-language term while we are explicitly
		// restoring a language-specific category. Disable that adjustment
		// for this one write only. This is especially important immediately
		// after wpml_set_element_language_details(), where WPML has just
		// changed the post's translation context.
		$disable_filter = '__return_true';
		add_filter( 'wpml_disable_term_adjust_id', $disable_filter, 999 );
		try {
			self::without_language_filter( function () use ( $post_id, $valid ) {
				wp_set_object_terms( $post_id, $valid, CS_TAX, false );
			} );
		} finally {
			remove_filter( 'wpml_disable_term_adjust_id', $disable_filter, 999 );
		}
	}

	/**
	 * تعيين Category لبوست واحد فقط. ممنوع مزامنتها إلى ترجمة أخرى.
	 */
	public static function assign_category_term( $post_id, $term_id ) {
		$post_id = (int) $post_id;
		$term_id = (int) $term_id;
		if ( ! $post_id || ! $term_id ) {
			return false;
		}
		if ( ! self::active() ) {
			wp_set_object_terms( $post_id, array( $term_id ), CS_TAX, false );
			return true;
		}
		$post_lang = self::post_language( $post_id );
		$term_lang = self::term_language( $term_id );
		if ( $post_lang && $term_lang && $post_lang !== $term_lang ) {
			return false;
		}
		self::set_terms_for_language( $post_id, array( $term_id ), $post_lang ?: $term_lang );
		if ( class_exists( 'CS_Category_Guard' ) ) {
			CS_Category_Guard::remember( $post_id );
		}
		return true;
	}

	/**
	 * استنسخ كل حقول ACF البسيطة (المواعيد، الدول، الأسعار، المدة...) من
	 * كورس أصلي لكورس تاني. بنستخدمها قبل ما نطبّق أعمدة الشيت المترجمة،
	 * عشان الكورس العربي "يدابلكيت" هيكل الأصلي بالظبط (نفس التواريخ والدول)
	 * وبعدين بس نستبدل النصوص بالترجمة اللي في الصف.
	 */
	public static function clone_master_fields( $source_id, $target_id ) {
		$fields = array(
			'cs_summary',
			'cs_delivery_mode',
			'cs_language',
			'cs_duration_hours',
			'cs_country_prices',
			'cs_base_start_date',
			'cs_recurrence',
			'cs_objectives',
			'cs_schedule',
			'cs_outline_pdf',
		);

		foreach ( $fields as $field ) {
			$value = get_field( $field, $source_id );
			if ( null !== $value && '' !== $value ) {
				update_field( $field, $value, $target_id );
			}
		}

		// لا ننسخ الـ taxonomy من الأصل إلى الترجمة.
		// كل لغة لها Category term مستقلة في الشيت الخاص بها، وassign_category_term()
		// هي التي تعيّن Category الترجمة بعد تحديد لغة الـ Post.
	}

	/**
	 * حط نفس لغة الماستر على كل السيشنز بتاعته (عشان WPML يفلتر صح
	 * في الفرونت حسب لغة الموقع الحالية). لو الماستر ده أصلاً ترجمة لماستر
	 * تاني ($source_master_id)، كل سيشن عربي (مثلاً) بيتربط كـ "ترجمة" فعلية
	 * للسيشن الإنجليزي المقابل له (نفس التاريخ + نفس الدولة)، مش بس بيتحط
	 * له لغة لوحده من غير ربط. من غير الربط ده، WPML بيعتبر السيشنز
	 * المترجمة بوستات "يتيمة" (مالهاش أصل)، وغالبًا بيستبعدها من العرض/الفلاتر
	 * زي ما ظهر لما اتعمل import لشيت عربي (اتترجم الماستر بس، من غير سيشناته).
	 *
	 * @param int    $master_id        ID الماستر (المترجَم، مثلاً العربي).
	 * @param string $lang_code        كود لغة الماستر ده.
	 * @param int    $source_master_id (اختياري) ID الماستر الأصلي لو ده ترجمة له.
	 */
	public static function tag_sessions_language( $master_id, $lang_code, $source_master_id = 0 ) {
		if ( ! self::active() ) {
			return;
		}

		// كورسات فيها كذا مكان بيبقى عندها كذا سيشن (مكان × تكرار)، فمنعملش
		// الربط كله دلوقتي جوه نفس الريكوست -- بنجدول أول دفعة بس، والباقي
		// هيكمّل نفسه لوحده كل دفعة بتجدول اللي بعدها (شوف
		// tag_sessions_language_batch()). ده بيخلي رفع الشيت يرجع بسرعة
		// حتى لو الكورس عنده مئات السيشنز.
		self::schedule_sessions_batch( $master_id, $lang_code, $source_master_id, 0 );
	}

	/**
	 * جدوَل دفعة ربط سيشنز واحدة (عبر WP-Cron) تبدأ من $offset.
	 */
	protected static function schedule_sessions_batch( $master_id, $lang_code, $source_master_id, $offset ) {
		// Sync Mode: مفيش كرون. بننفّذ الدفعات ورا بعضها هنا على طول.
		// (عمليًا نادرًا ما بتلاقي شغل، لأن كل سيشن بياخد لغته لحظة ما
		// بيتعمل -- شوف tag_session_on_create(). دي بقت شبكة أمان بس.)
		if ( class_exists( 'CS_Recurrence' ) && CS_Recurrence::sync_mode() ) {
			$guard = 0;
			while ( $guard++ < 500 ) {
				$before = $offset;
				self::tag_sessions_language_batch( $master_id, $lang_code, $source_master_id, $offset );
				$offset += self::SESSIONS_BATCH_SIZE;
				if ( $offset <= $before ) {
					break;
				}
				if ( ! self::has_more_sessions( $master_id, $offset ) ) {
					break;
				}
			}
			return;
		}

		wp_schedule_single_event(
			time() + self::BATCH_INTERVAL,
			'cs_tag_sessions_language_batch',
			array( $master_id, $lang_code, $source_master_id, $offset )
		);

		// لو مفيش زوار على الموقع دلوقتي، الـ cron العادي ممكن ياخد وقت
		// لحد ما يتفعّل. الدالة دي (بتاعة ووردبريس نفسه) بتبعت طلب HTTP
		// غير مانع (non-blocking) لـ wp-cron.php فورًا عشان الدفعة تتنفذ
		// بسرعة من غير ما نستنى زيارة حقيقية للموقع.
		if ( function_exists( 'spawn_cron' ) ) {
			spawn_cron();
		}
	}

	/**
	 * بتشتغل من WP-Cron. بتربط دفعة واحدة بس (SESSIONS_BATCH_SIZE سيشن)
	 * من سيشنز $master_id، وبعدين -- لو لسه فاضل سيشنز -- بتجدول
	 * الدفعة اللي بعدها. كل دفعة ريكوست/عملية منفصلة تمامًا عن اللي
	 * رفع بيها الأدمن الشيت، فمستحيل تسبب صفحة بيضا للأدمن.
	 *
	 * @param int    $master_id
	 * @param string $lang_code
	 * @param int    $source_master_id
	 * @param int    $offset            كام سيشن اتربطوا لحد دلوقتي (بنكمل منه).
	 */
	public static function tag_sessions_language_batch( $master_id, $lang_code, $source_master_id, $offset ) {
		if ( ! self::active() ) {
			return;
		}

		// نفس الحارس اللي فوق: لو الربط اليدوي شغال دلوقتي على الكورس ده،
		// سيبه لحد ما يخلص (شوف maybe_tag_after_generation()).
		if ( get_transient( 'cs_manual_link_' . $master_id ) ) {
			return;
		}

		$sessions = get_posts( array(
			'post_type'      => CS_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => self::SESSIONS_BATCH_SIZE,
			'offset'         => (int) $offset,
			'fields'         => 'ids',
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'meta_key'       => CS_META_MASTER,
			'meta_value'     => $master_id,
		) );

		if ( empty( $sessions ) ) {
			// خلصنا كل السيشنز -- نضّف الكاش المؤقت بتاع فهرس السيشنز الأصلية.
			if ( $source_master_id ) {
				delete_transient( 'cs_src_sessions_' . $source_master_id );
			}
			return;
		}

		// فهرس سيشنز الماستر الأصلي، متخزّن مؤقتًا (transient) عشان منعيدش
		// نجيبه من الداتابيز في كل دفعة -- كورس فيه 250 سيشن يعني 10 دفعات،
		// مفيش داعي نكرر نفس الاستعلام الكبير 10 مرات.
		//
		// ⚠️ بنبني فهرسين مش واحد:
		// - $by_loc_index: المفتاح "date|loc_index" (رقم ترتيب المكان جوه
		//   repeater الأسعار، شوف CS_META_LOC_INDEX). ده المطابقة الصح
		//   والموثوقة، لإنه مش متأثر بترجمة اسم المكان.
		// - $by_country: المفتاح القديم "date|country" (نص المكان الحرفي)،
		//   احتياطي بس لسيشنز اتعملت قبل ما CS_META_LOC_INDEX يتضاف
		//   (مالهاش القيمة دي، فبيرجعوا بـ loc_index = 0 لكل السيشنز، وده
		//   مش موثوق -- فبنفضّل نطابق بيه بس لو المطابقة بالـ loc_index فشلت).
		//
		// ليه المطابقة بنص المكان (زي ما كانت قبل كده) مش كافية لوحدها: المكان
		// نص حر بالكامل (شوف CS_Countries)، فلو الأدمن كتب في شيت الترجمة
		// العربي "القاهرة" بدل "Cairo"، النص مبقاش متطابق حتى لو نفس المكان
		// فعليًا -- فكان السيشن العربي بيفضل من غير ترجمة مربوطة (بوست يتيم)،
		// وده اللي كان بيسبب: مفيش زرار سويتش لانجويدج خالص على سيشنز
		// العربي (مالهاش ترجمة معروفة)، وعلى سيشنز الإنجليزي زرار السويتش
		// كان بيظهر (بسبب إعداد "fallback to default language" على الـ CPT)
		// بس بيوديك لنفس المحتوى الإنجليزي وبس الاتجاه (RTL) بيتغيّر، مش
		// المحتوى الفعلي.
		$by_loc_index = array();
		$by_country   = array();
		if ( $source_master_id ) {
			$cache_key = 'cs_src_sessions_' . $source_master_id;
			$cached    = get_transient( $cache_key );

			if ( false === $cached || ! isset( $cached['by_loc_index'], $cached['by_country'] ) ) {
				$source_sessions = get_posts( array(
					'post_type'      => CS_CPT,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'meta_key'       => CS_META_MASTER,
					'meta_value'     => $source_master_id,
				) );
				foreach ( $source_sessions as $source_session_id ) {
					$date      = get_post_meta( $source_session_id, CS_META_DATE, true );
					$country   = get_post_meta( $source_session_id, CS_META_COUNTRY, true );
					$loc_index = get_post_meta( $source_session_id, CS_META_LOC_INDEX, true );

					if ( '' !== $loc_index ) {
						$by_loc_index[ $date . '|' . (int) $loc_index ] = $source_session_id;
					}
					// النص القديم بردو، احتياطي (بيسيب آخر واحد لو فيه تكرار،
					// زي ما كان بيحصل قبل كده).
					$by_country[ $date . '|' . $country ] = $source_session_id;
				}
				$cached = array(
					'by_loc_index' => $by_loc_index,
					'by_country'   => $by_country,
				);
				// نص ساعة كفاية لأي كورس هياخد وقت أطول من كده مش طبيعي.
				set_transient( $cache_key, $cached, 30 * MINUTE_IN_SECONDS );
			} else {
				$by_loc_index = $cached['by_loc_index'];
				$by_country   = $cached['by_country'];
			}
		}

		foreach ( $sessions as $session_id ) {
			$source_session = 0;

			if ( $by_loc_index || $by_country ) {
				$date      = get_post_meta( $session_id, CS_META_DATE, true );
				$loc_index = get_post_meta( $session_id, CS_META_LOC_INDEX, true );

				if ( '' !== $loc_index ) {
					$key = $date . '|' . (int) $loc_index;
					if ( isset( $by_loc_index[ $key ] ) ) {
						$source_session = $by_loc_index[ $key ];
					}
				}

				if ( ! $source_session ) {
					// احتياطي: طابق بنص المكان القديم (شوف الشرح فوق).
					$country = get_post_meta( $session_id, CS_META_COUNTRY, true );
					$key     = $date . '|' . $country;
					if ( isset( $by_country[ $key ] ) ) {
						$source_session = $by_country[ $key ];
					}
				}
			}

			if ( $source_session ) {
				// اربطه كترجمة فعلية للسيشن الإنجليزي المقابل (نفس التاريخ والمكان).
				self::link_translation( $session_id, $source_session, $lang_code );
				continue;
			}

			// مفيش سيشن أصلي مقابل (أو مش ترجمة أصلاً) -- حط له اللغة بس.
			self::set_language( $session_id, $lang_code );
		}

		// لو الدفعة دي طلعت بالظبط SESSIONS_BATCH_SIZE، يبقى غالبًا فيه
		// دفعة تانية بعدها. لو أقل، يبقى دي آخر دفعة ومفيش داعي نجدول تاني
		// (هيتأكد ويرجع فاضي من نفسه المرة الجاية على أي حال، بس بلاش
		// نستنى دورة cron إضافية من غير داعي).
		// في Sync Mode اللفة اللي في schedule_sessions_batch() هي اللي
		// بتقدّم الـ offset، فمنجدولش (ولا نستدعي) حاجة من هنا تاني.
		if ( class_exists( 'CS_Recurrence' ) && CS_Recurrence::sync_mode() ) {
			return;
		}

		if ( count( $sessions ) === self::SESSIONS_BATCH_SIZE ) {
			self::schedule_sessions_batch( $master_id, $lang_code, $source_master_id, $offset + self::SESSIONS_BATCH_SIZE );
		}
	}

	/** فيه سيشنز تانية بعد الـ offset ده ولا خلصنا؟ */
	protected static function has_more_sessions( $master_id, $offset ) {
		$more = get_posts( array(
			'post_type'      => CS_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'offset'         => (int) $offset,
			'fields'         => 'ids',
			'meta_key'       => CS_META_MASTER,
			'meta_value'     => (int) $master_id,
		) );

		return ! empty( $more );
	}

	/**
	 * كود لغة بوست معيّن (زي "ar" أو "en"). بيرجع null لو WPML مش شغال
	 * أو لو مفيش لغة متحددة له لسه.
	 */
	public static function post_language( $post_id ) {
		if ( ! self::active() ) {
			return null;
		}

		$lang = apply_filters( 'wpml_element_language_code', null, array(
			'element_id'   => $post_id,
			'element_type' => 'post_' . CS_CPT,
		) );

		return $lang ? $lang : null;
	}
}

CS_WPML::init();
