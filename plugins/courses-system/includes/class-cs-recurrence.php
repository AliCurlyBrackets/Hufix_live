<?php
/**
 * Feature: Recurrence Engine
 * القلب بتاع السيستم. مسؤول عن:
 *   1) توليد الـ Sessions من الماستر: من تاريخ البداية -> آخر السنة،
 *      كل أسبوع/أسبوعين، مضروبة في عدد الدول المختارة.
 *   2) المزامنة: السيشن ما بيخزّنش محتوى، بيقرأ من الماستر ->
 *      فأي تعديل في الماستر بيظهر في كل السيشنز على طول (زي ما طلبت).
 *   3) التجديد السنوي: كرون يومي يولّد سيشنز السنة الجديدة وينضّف القديمة.
 *
 * مبدأ التصميم: السيشن = { master_id, date, country } بس. مفيش تكرار للمحتوى.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CS_Recurrence {

	/**
	 * أقصى عدد سيشنز بتتعمل (wp_insert_post) في نفس الطلب/الدفعة الواحدة.
	 * كورس فيه 50 دولة × تكرار أسبوعي ممكن يحتاج مئات السيشنز (900 زي ما
	 * ظهر فعليًا) -- لو حاولنا ننشئهم كلهم دفعة واحدة جوه نفس الريكوست
	 * (رفع شيت CSV أو حفظ الكورس يدوي)، ده اللي كان بيسبب الصفحة البيضا/
	 * الـ 500 (مهلة PHP/Nginx/PHP-FPM بتقفل الطلب بالقوة). فبقينا: أول
	 * دفعة (لحد GEN_BATCH_SIZE سيشن) بتتعمل فورًا في نفس الطلب (عشان
	 * الكورس يظهر بسيشن واحد على الأقل فورًا بعد الرفع)، والباقي -- مهما
	 * كان عدده -- بيتعمل في الخلفية عبر WP-Cron على دفعات صغيرة، بالظبط
	 * زي نفس الفكرة المستخدمة في CS_WPML لربط ترجمة السيشنز.
	 */
	const GEN_BATCH_SIZE = 40;

	/** الوقت الأساسي بالثواني بين كل دفعة والتانية (+ jitter عشوائي بسيط). */
	const GEN_BATCH_INTERVAL = 6;

	/**
	 * وضع "من غير كرون" (Sync Mode) -- مفعّل افتراضيًا.
	 *
	 * لما يكون مفعّل:
	 *   - مفيش أي جدولة WP-Cron لتوليد السيشنز ولا لربط الترجمة خالص.
	 *   - مفيش سقف "90 يوم": كل سيشنز السنة بتتولّد وقتها بالعدد المضبوط.
	 *   - اللي بينده التوليد هو اللي بيكمّله لحد الآخر في نفس العملية
	 *     (صفحات الرفع بتعمل كده على دفعات أجاكس مع بروجريس بار، والحفظ
	 *     اليدوي من الأدمن بيكمّله كله في نفس الطلب).
	 *
	 * لو عايز ترجّع سلوك الكرون القديم لأي سبب، حط في wp-config.php:
	 *   define( 'CS_SYNC_MODE', false );
	 */
	public static function sync_mode() {
		return defined( 'CS_SYNC_MODE' ) ? (bool) CS_SYNC_MODE : true;
	}

	/**
	 * بتفضّي طابور التوليد بتاع ماستر معيّن بالكامل في نفس الطلب ده.
	 * دي بديل الكرون في Sync Mode: اللي بيطلب التوليد هو اللي بيستناه
	 * يخلص، فالعدد بيطلع مضبوط من أول مرة من غير ما حد يستنى دورة كرون.
	 *
	 * @param  int $master_id
	 * @param  int $max_seconds  حد أقصى للأمان (0 = من غير حد).
	 * @return int عدد السيشنز اللي اتعملت في اللفة دي.
	 */
	public static function drain_queue( $master_id, $max_seconds = 0 ) {
		$created  = 0;
		$deadline = $max_seconds > 0 ? ( microtime( true ) + $max_seconds ) : 0;

		while ( self::has_pending_queue( $master_id ) ) {
			if ( $deadline && microtime( true ) > $deadline ) {
				break;
			}

			$before = self::pending_queue_count( $master_id );
			self::process_generate_sessions_batch( $master_id );
			$after  = self::pending_queue_count( $master_id );

			if ( $after >= $before ) {
				break; // مفيش تقدّم -- بلاش لفة لا نهائية.
			}

			$created += ( $before - $after );
		}

		return $created;
	}

	public static function init() {
		// كل ما الماستر يتحفظ -> نعيد بناء السيشنز بتاعته.
		add_action( 'acf/save_post', array( __CLASS__, 'on_master_save' ), 20 );

		// دفعات توليد السيشنز في الخلفية (شوف queue_remaining_sessions()).
		add_action( 'cs_generate_sessions_batch', array( __CLASS__, 'process_generate_sessions_batch' ) );

		// كل ما الكاتيجوري (أو أي تاكسونومي) تتغيّر على بوست -> لو ده ماستر،
		// نفس التغيير ينزل على كل السيشنز بتاعته. ده بيغطي: تعديل عادي،
		// Quick Edit، وكمان Bulk Edit (اللي مش بيعدّي على acf/save_post أصلاً).
		add_action( 'set_object_terms', array( __CLASS__, 'sync_terms_to_sessions' ), 10, 6 );

		// فيكس: نفس "المزامنة النهائية" اللي بتحصل في on_master_save()
		// (تفرض كاتيجوري الماستر الحالية على كل سيشناته) -- بس دلوقتي
		// مربوطة بحدث "خلصنا توليد كل سيشنز الماستر ده" (cs_sessions_generation_done)
		// بدل ما تكون مقفولة جوه on_master_save() اللي معلّق على acf/save_post
		// بس. المشكلة: الاستيراد من الشيت (CS_Import::import_row()) بيحط
		// الكاتيجوري ويولّد السيشنز بـ update_field()/استدعاء مباشر، من غير
		// ما يعدّي على acf/save_post خالص -- فالمزامنة النهائية دي كانت
		// بتتفوّت تمامًا وقت رفع الشيت، وكان الكورس مايظهرش في صفحة
		// الكاتيجوري إلا لو حد فتحه يدوي وضغط Update. دلوقتي بتشتغل تلقائي
		// في كل الحالات (استيراد، حفظ يدوي، كرون التجديد السنوي) لأنها كلها
		// بتطلق cs_sessions_generation_done لما السيشنز تخلص فعلاً.
		add_action( 'cs_sessions_generation_done', array( __CLASS__, 'sync_terms_after_generation' ) );

		// الكرون اليومي: تجديد سنوي + تنظيف.
		add_action( 'cs_daily_maintenance', array( __CLASS__, 'daily_maintenance' ) );

		// لما الماستر يتمسح نهائي (Delete Permanently) -> امسح كل سيشناته معاه.
		add_action( 'before_delete_post', array( __CLASS__, 'on_master_delete' ) );
		// لما الماستر يتنقل للسلة (Trash) -> انقل سيشناته للسلة معاه.
		add_action( 'wp_trash_post', array( __CLASS__, 'on_master_trash' ) );
		// لو استرجعت الماستر من السلة -> استرجع سيشناته معاه.
		add_action( 'untrash_post', array( __CLASS__, 'on_master_untrash' ) );

		// إشعار تشخيصي في صفحة تعديل الكورس بعد الحفظ (مؤقت للتصحيح).
		add_action( 'admin_notices', array( __CLASS__, 'render_debug_notice' ) );
	}

	/**
	 * شبكة أمان: بتتنادى تلقائي لما كل سيشنز ماستر معيّن تخلص توليد
	 * بالكامل (سواء دفعة واحدة فورية أو على مراحل عبر الكرون -- شوف
	 * do_action('cs_sessions_generation_done', ...) في الدالة دي وفي
	 * queue_remaining_sessions()/process_generate_sessions_batch()).
	 *
	 * بتاخد كاتيجوري الماستر *الحالية فعليًا* (مهما كان مصدر تحديثها --
	 * حفظ يدوي، استيراد شيت، تعديل تاكسونومي منفصل) وتفرضها على كل
	 * سيشنز الماستر ده، بالظبط زي المزامنة النهائية اللي في on_master_save()
	 * تحت. الفرق إنها هنا مش محتاجة hook بتاع acf/save_post عشان تشتغل --
	 * فبتغطي مسار الاستيراد (CS_Import::import_row()) اللي بيحط الكاتيجوري
	 * ويولّد السيشنز من غير ما يعدّي على acf/save_post خالص.
	 *
	 * @param int $master_id
	 */
	public static function sync_terms_after_generation( $master_id ) {
		self::sync_terms_to_sessions( $master_id, array(), array(), CS_TAX );
	}

	/**
	 * لما تاكسونومي (زي الكاتيجوري) تتغيّر على بوست كورس، وده ماستر (مش سيشن) ->
	 * نحط نفس الكاتيجوري على كل سيشنز الماستر ده. لو اللي اتغيّر سيشن (عنده
	 * CS_META_MASTER) منعملش حاجة، عشان منلفّش في حلقة لا نهائية.
	 *
	 * ملحوظة مهمة: مبنعتمدش على $tt_ids الجايين من الـ hook نفسه، لأنهم
	 * term_taxonomy_id (مش term_id) وممكن ييجوا كـ string مش int. لو اتبعتوا
	 * زي ما هما لـ wp_set_object_terms()، ووردبريس بيفتكرهم اسم كاتيجوري جديد
	 * وبينشئ كاتيجوري باسم الرقم نفسه (المشكلة اللي حصلت). فبدل كده، بنعيد جيب
	 * الـ term ids الحقيقية من الماستر بنفس الطريقة المضمونة المستخدمة في
	 * create_session().
	 *
	 * @param int    $object_id  ID البوست.
	 * @param string $taxonomy   اسم التاكسونومي.
	 */
	public static function sync_terms_to_sessions( $object_id, $terms, $tt_ids, $taxonomy ) {
		// Translation linking performs its own language-scoped taxonomy restore.
		// Do not let this generic WordPress hook fan one language's category out
		// to both translation trees while WPML is changing the translation group.
		if ( ! empty( $GLOBALS['cs_wpml_suppress_session_term_sync'] ) ) {
			return;
		}
		if ( CS_TAX !== $taxonomy || get_post_type( $object_id ) !== CS_CPT ) {
			return;
		}

		// ده سيشن مش ماستر -> بلاش نعيد النشر تاني.
		if ( get_post_meta( $object_id, CS_META_MASTER, true ) ) {
			return;
		}

		// term ids حقيقية للماستر (نفس الطريقة المستخدمة وقت إنشاء السيشن).
		// suppress_filters هنا لنفس سبب استخدامها في create_session().
		$term_ids = wp_get_object_terms( $object_id, CS_TAX, array(
			'fields'           => 'ids',
			'suppress_filters' => true,
		) );
		if ( ! $term_ids || is_wp_error( $term_ids ) ) {
			$term_ids = array();
		}

		$sessions = get_posts( array(
			'post_type'      => CS_CPT,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'suppress_filters' => true, // WPML بيفلتر WP_Query باللغة الحالية تلقائيًا لأي CPT مسجل Translatable -- من غيرها الاستعلام بيشوف سيشنز لغة واحدة بس فيفتكر إن سيشنز اللغة التانية مش موجودة ويعيد توليدها/يتجاهلها بالغلط.
			'meta_query'     => array(
				array( 'key' => CS_META_MASTER, 'value' => $object_id ),
			),
		) );

		foreach ( $sessions as $session_id ) {
			if ( class_exists( 'CS_WPML' ) && CS_WPML::active() ) {
				$session_lang = CS_WPML::post_language( $session_id );
				$master_lang  = CS_WPML::post_language( $object_id );
				if ( ! $session_lang && $master_lang ) {
					CS_WPML::set_language( $session_id, $master_lang );
					$session_lang = CS_WPML::post_language( $session_id );
				}
				if ( $session_lang && $master_lang && $session_lang === $master_lang ) {
					$language_terms = array();
					foreach ( $term_ids as $term_id ) {
						$term_lang = CS_WPML::term_language( (int) $term_id );
						if ( ! $term_lang || $term_lang === $session_lang ) {
							$language_terms[] = (int) $term_id;
						}
					}
					CS_WPML::set_terms_for_language( $session_id, $language_terms, $session_lang );
				}
			} else {
				wp_set_object_terms( $session_id, $term_ids, CS_TAX, false );
			}
		}	}

	/**
	 * لما الماستر يتحفظ من لوحة التحكم.
	 */
	public static function on_master_save( $post_id ) {
		if ( get_post_type( $post_id ) !== CS_CPT ) {
			return;
		}
		// السيشنز ملهاش داعي تعيد توليد سيشنز.
		if ( CS_CPT::is_master( $post_id ) === false && get_post_meta( $post_id, CS_META_MASTER, true ) ) {
			return;
		}

		// نسخة شبح اتعملت أوتوماتيك بواسطة WPML (مش استيراد/حفظ حقيقي).
		// لو سبناها تكمل، هتتعامل كماستر مستقل وتتولدلها سيشنز كاملة
		// تانية -- بتضاعف عدد الكورسات/السيشنز الحقيقي (شوف الشرح في
		// CS_WPML::is_auto_duplicate()).
		if ( class_exists( 'CS_WPML' ) && CS_WPML::is_auto_duplicate( $post_id ) ) {
			return;
		}

		// أول ما نحفظ كورس من الأدمن، نعتبره ماستر.
		update_post_meta( $post_id, CS_META_IS_MASTER, 1 );

		// ⚠️ كان هنا قبل كده بيمسح *كل* سيشنز الماستر ده (ممكن تبقى مئات
		// دلوقتي مع أكتر من مدينة) وبعدين يعيد توليدهم من الصفر في كل مرة
		// تتحفظ فيها -- حتى لو اللي اتغيّر مجرد حرف في الوصف. ده كان السبب
		// الرئيسي في "اللود الكتير" وقت الحفظ/التحديث. دلوقتي بنعمل
		// "reconcile" بدلها: نصحّح/نمسح بس اللي فعلاً محتاج تغيير (دولة
		// اتشالت، تاريخ بداية اتغيّر، أو مدة الكورس اتغيّرت)، ونسيب أي
		// سيشن لسه صالح زي ما هو من غير ما نلمسه خالص.
		$result = self::reconcile_sessions( $post_id );

		// Sync Mode: مفيش كرون هيكمّل الباقي، فبنكمّله إحنا هنا في نفس طلب
		// الحفظ. الحفظ ممكن ياخد وقت أطول شوية مع كورس فيه أماكن كتير، بس
		// بتخرج من الشاشة والعدد مضبوط 100% فورًا.
		if ( self::sync_mode() && self::has_pending_queue( $post_id ) ) {
			if ( function_exists( 'set_time_limit' ) ) {
				@set_time_limit( 0 );
			}
			@ignore_user_abort( true );
			$result['drained_now'] = self::drain_queue( $post_id );
		}

		// مهم: حفظ الـ taxonomy والـ ACF مش بالضرورة بيحصل في نفس ترتيب
		// الـ hooks. في بعض الحالات الـ set_object_terms hook يشتغل قبل ما
		// السيشنز الجديدة تتولد، أو قبل ما آخر نسخة من كاتيجوري الماستر تستقر.
		// لذلك بعد انتهاء حفظ الماستر/توليد السيشنز، نعمل مزامنة نهائية من
		// الكاتيجوري الفعلية الموجودة على الماستر إلى كل السيشنز الموجودة.
		// ده يمنع الحالة اللي فيها الـ checkbox متعلم، لكن الكورس لا يظهر
		// في الـ category إلا بعد Update ثاني.
		self::sync_terms_to_sessions( $post_id, array(), array(), CS_TAX );

		set_transient( 'cs_gen_debug_' . $post_id, $result, 60 );
	}

	/**
	 * "تسوية" سيشنز الماستر مع إعداداته الحالية، من غير ما نمسح ونعيد بناء
	 * كل حاجة زي الأول (بطيء جدًا خصوصًا مع أكتر من مدينة). اللي بيحصل:
	 *   1) أي سيشن دولته بقت مش موجودة في Locations & Prices، أو تاريخه
	 *      قبل تاريخ البداية الجديد -> يتمسح (بقى غير صالح فعلاً).
	 *   2) أي سيشن لسه صالح، بس تاريخ نهايته مش متوافق مع التكرار/المدة
	 *      الحالية (لو غيّرت من أسبوعي لأسبوعين مثلاً) -> بس نحدّث تاريخ
	 *      النهاية بتاعه (meta واحدة، من غير حذف/إعادة إنشاء).
	 *   3) بعد كده generate_sessions() (idempotent أصلاً) بتضيف أي تاريخ/
	 *      دولة لسه ناقصة بس.
	 *
	 * @param int $master_id
	 * @return array نفس شكل return بتاع generate_sessions() + مفاتيح إضافية.
	 */
	public static function reconcile_sessions( $master_id ) {
		// نفس منطق تحديد الدول المستخدم في generate_sessions() بالظبط.
		$countries = array();
		$rows      = get_field( 'cs_country_prices', $master_id );
		if ( ! empty( $rows ) && is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				if ( ! empty( $row['country'] ) ) {
					$countries[] = $row['country'];
				}
			}
		}
		if ( empty( $countries ) ) {
			$countries = (array) get_field( 'cs_countries', $master_id );
		}
		$countries       = CS_Countries::sanitize_codes( $countries );
		$countries_lc    = array_map( 'mb_strtolower', $countries );
		$no_country_mode = empty( $countries );

		$base       = get_field( 'cs_base_start_date', $master_id );
		$start_date = $base ? gmdate( 'Y-m-d', self::next_business_day( strtotime( $base ) ) ) : '';

		$interval = get_field( 'cs_recurrence', $master_id ) ?: 'weekly';
		$duration = ( 'biweekly' === $interval ) ? 10 : 5;

		// كل سيشنز الماستر ده (كل السنين).
		$sessions = get_posts( array(
			'post_type'      => CS_CPT,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'suppress_filters' => true, // WPML بيفلتر WP_Query باللغة الحالية تلقائيًا لأي CPT مسجل Translatable -- من غيرها الاستعلام بيشوف سيشنز لغة واحدة بس فيفتكر إن سيشنز اللغة التانية مش موجودة ويعيد توليدها/يتجاهلها بالغلط.
			'meta_query'     => array(
				array( 'key' => CS_META_MASTER, 'value' => $master_id ),
			),
		) );

		$deleted_stale  = 0;
		$end_dates_fixed = 0;

		foreach ( $sessions as $sid ) {
			$s_country = get_post_meta( $sid, CS_META_COUNTRY, true );
			$s_date    = get_post_meta( $sid, CS_META_DATE, true );

			$country_still_valid = $no_country_mode
				? ( '' === $s_country )
				: in_array( mb_strtolower( (string) $s_country ), $countries_lc, true );

			$date_still_valid = ( ! $start_date || ! $s_date ) ? true : ( $s_date >= $start_date );

			if ( ! $country_still_valid || ! $date_still_valid ) {
				wp_delete_post( $sid, true );
				$deleted_stale++;
				continue;
			}

			// صحّح تاريخ النهاية بس لو فعلاً مختلف (تحديث meta رخيص، من غير
			// حذف/إعادة إنشاء البوست بالكامل).
			$new_end = gmdate( 'Y-m-d', self::business_day_end( strtotime( $s_date ), $duration ) );
			if ( get_post_meta( $sid, '_cs_session_end', true ) !== $new_end ) {
				update_post_meta( $sid, '_cs_session_end', $new_end );
				$end_dates_fixed++;
			}
		}

		// أي تاريخ/دولة لسه ناقصة -- generate_sessions() idempotent أصلاً
		// وهتتخطى أي حاجة موجودة، فمش هتكرر حاجة.
		$result                    = self::generate_sessions( $master_id );
		$result['deleted_stale']   = $deleted_stale;
		$result['end_dates_fixed'] = $end_dates_fixed;

		return $result;
	}

	/**
	 * يمسح كل سيشنز ماستر معيّن (كل السنين)، نهائي. تُستخدم في حالتين:
	 *  1) قبل إعادة التوليد بعد أي حفظ للماستر (عشان مفيش سيشنز قديمة غلط فاضلة).
	 *  2) لما الماستر نفسه يتمسح نهائي -> نمسح سيشناته معاه.
	 *
	 * @param int $master_id
	 * @return int عدد السيشنز اللي اتمسحوا.
	 */
	public static function delete_sessions( $master_id ) {
		$sessions = get_posts( array(
			'post_type'      => CS_CPT,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'suppress_filters' => true, // WPML بيفلتر WP_Query باللغة الحالية تلقائيًا لأي CPT مسجل Translatable -- من غيرها الاستعلام بيشوف سيشنز لغة واحدة بس فيفتكر إن سيشنز اللغة التانية مش موجودة ويعيد توليدها/يتجاهلها بالغلط.
			'meta_query'     => array(
				array( 'key' => CS_META_MASTER, 'value' => $master_id ),
			),
		) );
		foreach ( $sessions as $sid ) {
			wp_delete_post( $sid, true );
		}
		return count( $sessions );
	}

	/**
	 * لما بوست كورس يتمسح نهائي (Delete Permanently / force delete): لو ده ماستر،
	 * امسح كل سيشناته معاه نهائي. لو ده سيشن (عنده CS_META_MASTER) منعملش حاجة
	 * عشان منلفّش في حلقة، ولإن مسحه لوحده أصلاً حاجة عادية.
	 *
	 * @param int $post_id
	 */
	public static function on_master_delete( $post_id ) {
		if ( get_post_type( $post_id ) !== CS_CPT ) {
			return;
		}
		if ( get_post_meta( $post_id, CS_META_MASTER, true ) ) {
			return; // ده سيشن مش ماستر.
		}
		self::delete_sessions( $post_id );
	}

	/**
	 * لما الماستر ينقل للسلة (Trash)، ننقل كل سيشناته للسلة معاه.
	 *
	 * @param int $post_id
	 */
	public static function on_master_trash( $post_id ) {
		if ( get_post_type( $post_id ) !== CS_CPT ) {
			return;
		}
		if ( get_post_meta( $post_id, CS_META_MASTER, true ) ) {
			return; // ده سيشن مش ماستر.
		}
		$sessions = get_posts( array(
			'post_type'      => CS_CPT,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'suppress_filters' => true, // WPML بيفلتر WP_Query باللغة الحالية تلقائيًا لأي CPT مسجل Translatable -- من غيرها الاستعلام بيشوف سيشنز لغة واحدة بس فيفتكر إن سيشنز اللغة التانية مش موجودة ويعيد توليدها/يتجاهلها بالغلط.
			'meta_query'     => array(
				array( 'key' => CS_META_MASTER, 'value' => $post_id ),
			),
		) );
		foreach ( $sessions as $sid ) {
			wp_trash_post( $sid );
		}
	}

	/**
	 * لو الماستر اتسترجع من السلة، نسترجع كل سيشناته اللي كانت اتنقلت معاه.
	 *
	 * @param int $post_id
	 */
	public static function on_master_untrash( $post_id ) {
		if ( get_post_type( $post_id ) !== CS_CPT ) {
			return;
		}
		if ( get_post_meta( $post_id, CS_META_MASTER, true ) ) {
			return; // ده سيشن مش ماستر.
		}
		$sessions = get_posts( array(
			'post_type'      => CS_CPT,
			'post_status'    => 'trash',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'suppress_filters' => true, // WPML بيفلتر WP_Query باللغة الحالية تلقائيًا لأي CPT مسجل Translatable -- من غيرها الاستعلام بيشوف سيشنز لغة واحدة بس فيفتكر إن سيشنز اللغة التانية مش موجودة ويعيد توليدها/يتجاهلها بالغلط.
			'meta_query'     => array(
				array( 'key' => CS_META_MASTER, 'value' => $post_id ),
			),
		) );
		foreach ( $sessions as $sid ) {
			wp_untrash_post( $sid );
		}
	}

	/**
	 * يعرض نتيجة التوليد كإشعار بعد الحفظ مباشرة (بيتشال لوحده بعد ما يتقرا مرة).
	 */
	public static function render_debug_notice() {
		global $pagenow;
		if ( 'post.php' !== $pagenow || empty( $_GET['post'] ) || empty( $_GET['message'] ) ) {
			return;
		}
		$post_id = (int) $_GET['post'];
		if ( get_post_type( $post_id ) !== CS_CPT ) {
			return;
		}
		$r = get_transient( 'cs_gen_debug_' . $post_id );
		if ( ! $r ) {
			return;
		}
		delete_transient( 'cs_gen_debug_' . $post_id );

		$class = $r['created'] > 0 ? 'notice-success' : 'notice-warning';
		echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p><strong>CS Sessions Debug:</strong> ';
		printf(
			'تم إنشاء <strong>%d</strong> سيشن جديد، وتخطّي %d موجودين مسبقًا. ',
			(int) $r['created'],
			(int) $r['skipped_existing']
		);
		if ( $r['used_fallback_no_country'] ) {
			echo '⚠️ مفيش دول اتقرت صح من حقل Countries &amp; Prices (اتحول لسيشن بدون دولة). ';
		}
		printf(
			'الدول اللي اتقرت: %s. من %s لحد %s، تكرار: %s.',
			esc_html( implode( ', ', array_filter( $r['countries_used'] ) ) ?: '(بدون دولة)' ),
			esc_html( $r['start_date'] ),
			esc_html( $r['year_end'] ),
			esc_html( $r['interval'] )
		);
		echo '</p></div>';
	}

	/**
	 * توليد/تحديث سيشنز ماستر معيّن للسنة الحالية.
	 * idempotent: لو السيشن موجود بنفس (تاريخ+دولة) مبيتعملش تاني.
	 *
	 * @param int      $master_id
	 * @param int|null $year      السنة (افتراضي: السنة الحالية).
	 */
	public static function generate_sessions( $master_id, $year = null ) {
		// حماية إضافية: نسخة شبح اتعملت أوتوماتيك بواسطة WPML ماينفعش
		// تتولدلها سيشنز خالص، حتى لو حد نادى الدالة دي عليها مباشرة من
		// مكان تاني (شوف الشرح الكامل في CS_WPML::is_auto_duplicate()).
		if ( class_exists( 'CS_WPML' ) && CS_WPML::is_auto_duplicate( $master_id ) ) {
			return array( 'created' => 0, 'skipped' => 0 );
		}

		// تاريخ البداية الأول -- قبل ما نحدد السنة، عشان لو الكورس هيبدأ
		// في سنة جاية (زي 2027) نولّدله سيشنز فعلاً بدل ما نقف على "السنة
		// الحالية" ونطلع صفر نتايج.
		$base  = get_field( 'cs_base_start_date', $master_id );
		$start = $base ? strtotime( $base ) : current_time( 'timestamp' );

		// لو الاستدعاء ده مبعوتله سنة صريحة من برا (الكرون اليومي بس هو اللي
		// بيعمل كده)، نحفظ ده عشان نعرف تحت إننا مانطبقش سقف الـ 90 يوم على
		// الكرون -- هو أصلاً بيشتغل في الخلفية من غير ما حد يستنى رده.
		$explicit_year = null !== $year;

		// لو مفيش سنة اتفرضت من برا (زي الكرون)، خد سنة تاريخ البداية نفسه.
		$year = $year ? (int) $year : (int) gmdate( 'Y', $start );

		// الدول: من repeater الأسعار الأول، ولو فاضي من حقل cs_countries القديم.
		$countries = array();
		$rows = get_field( 'cs_country_prices', $master_id );
		if ( ! empty( $rows ) && is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				if ( ! empty( $row['country'] ) ) {
					$countries[] = $row['country'];
				}
			}
		}
		$raw_countries_count = count( $countries );
		if ( empty( $countries ) ) {
			$countries = (array) get_field( 'cs_countries', $master_id );
		}
		$countries_before_sanitize = $countries;
		$countries = CS_Countries::sanitize_codes( $countries );
		$used_fallback = false;
		if ( empty( $countries ) ) {
			// مفيش دول متسجلة/متعرّفة -> برضو نولّد سيشنز (بدون دولة محددة) عشان
			// الكورس يتكرر أسبوعي/كل أسبوعين زي المفروض ويظهر في الفرونت.
			$countries     = array( '' );
			$used_fallback = true;
		}

		$interval   = get_field( 'cs_recurrence', $master_id ) ?: 'weekly';
		$step_days  = ( 'biweekly' === $interval ) ? 14 : 7;
		// مدة الكورس بقت أوتوماتيك بالكامل من نوع التكرار -- مفيش حقل يدوي.
		// أسبوع = 5 أيام شغل (الاتنين للجمعة)، أسبوعين = 10 أيام شغل.
		$duration   = ( 'biweekly' === $interval ) ? 10 : 5;

		$year_start = strtotime( "$year-01-01" );
		$year_end   = strtotime( "$year-12-31" );
		if ( $start < $year_start ) {
			$start = $year_start;
		}

		// لو تاريخ بداية الكورس فات فعلاً (زي شيت فيه base_start_date من شهور
		// فاتت، أو كورس قديم بيتراجع)، منولّدش سيشنز لتواريخ فاتت أصلاً --
		// محدش هيحجز فيها، وهي هتتمسح تاني يوم من كرون التنظيف اليومي، فتوليدها
		// أصلاً مجرد وقت وطلبات قاعدة بيانات ضايعة (وده كان بيبطّئ الرفع أكتر
		// لو تاريخ البداية بعيد في الماضي). بنقفز بنفس خطوة التكرار (أسبوع/
		// أسبوعين) لحد أول ميعاد النهارده أو بعده.
		$today_ts = current_time( 'timestamp' );
		if ( $start < $today_ts ) {
			while ( $start < $today_ts ) {
				$start = strtotime( "+{$step_days} days", $start );
			}
		}

		// ⚠️ الأفق الفعلي للتوليد: قبل كده كان بيولّد كل أسابيع السنة كلها
		// (ممكن توصل 52 أسبوع × كل الدول = مئات البوستات) في نفس الطلب اللي
		// اليوزر مستني رده وهو رافع الشيت أو حافظ الكورس -- وده كان السبب
		// الرئيسي في "اللود اللي بياخد وقت طويل جدًا". دلوقتي، لو الاستدعاء
		// ده جاي من رفع شيت أو حفظ يدوي (يعني مفيش $year صريح)، بنحدد سقف
		// قريب (افتراضي 90 يوم قدام بس) بدل آخر السنة كاملة. الكرون اليومي
		// (اللي بينده الدالة دي بسنة صريحة) هو اللي بيكمّل توليد باقي السنة
		// تدريجي، يوم بيوم، في الخلفية من غير ما حد يستنى رده. أول ميعاد
		// للكورس (start) بيتولّد دايمًا حتى لو بعد الـ 90 يوم دول، عشان
		// الكورس ميفضلش من غير أي سيشن ظاهر فورًا بعد الرفع.
		// Sync Mode بيلغي سقف الـ 90 يوم تمامًا: بنولّد السنة كاملة وقتها
		// بالعدد المضبوط، بدل ما نسيب الباقي لكرون بيكمّله على مهله (وده
		// اللي كان بيخلي عدد السيشنز يطلع مختلف بين لغة والتانية على حسب
		// اللحظة اللي بتبص فيها).
		if ( ! $explicit_year && ! self::sync_mode() ) {
			$window_days = defined( 'CS_SESSION_WINDOW_DAYS' ) ? (int) CS_SESSION_WINDOW_DAYS : 90;
			$horizon     = strtotime( "+{$window_days} days", current_time( 'timestamp' ) );
			$horizon     = max( $horizon, $start ); // ضمان أول ميعاد دايمًا داخل النطاق.
			$year_end    = min( $year_end, $horizon );
		}

		// السبت والأحد مش أيام كورس خالص. لو تاريخ البداية وقع في الويكند
		// (يدوي أو بعد ما اتحدد بأول يناير)، حوّله لأول يوم شغل بعده (السبت).
		$start = self::next_business_day( $start );

		// نجيب السيشنز الموجودة عشان نعرف نبني اللي ناقص بس. لازم نستخدم سنة
		// $start الفعلية بعد أي "قفز للنهارده" فوق، مش $year الأصلية -- لو
		// الكورس كان بيبدأ في سنة فاتت (زي شيت قديم بـ base_start_date من سنة
		// اللي فاتت) والقفزة نقلته لسنة تانية دلوقتي، والبحث عن "الموجود
		// بالفعل" فضل شايف السنة القديمة، كان ممكن يفتكر إن مفيش حاجة موجودة
		// ويكرر سيشنز موجودة فعلاً بالغلط.
		$existing_lookup_year = (int) gmdate( 'Y', $start );
		$existing = self::existing_session_keys( $master_id, $existing_lookup_year );

		// بدل ما ننشئ السيشنز مباشرة هنا (زي الأول)، بنجمّع الأول قايمة بس
		// بـ (تاريخ + دولة) اللي لسه ناقصة فعلاً -- الجمع ده رخيص (مفيش
		// كتابة في الداتابيز خالص)، حتى لو طلع فيها مئات العناصر.
		$pending = array();
		$skipped_existing = 0;

		for ( $ts = $start; $ts <= $year_end; $ts = strtotime( "+{$step_days} days", $ts ) ) {
			$date = gmdate( 'Y-m-d', $ts );

			foreach ( $countries as $loc_index => $country ) {
				$key = $date . '|' . $country;
				if ( isset( $existing[ $key ] ) ) {
					$skipped_existing++;
					continue; // موجود بالفعل
				}
				$pending[] = array(
					'date'      => $date,
					'country'   => $country,
					'duration'  => $duration,
					// ترتيب المكان ده في الـ repeater -- شوف تعريف
					// CS_META_LOC_INDEX في courses-system.php ليه محتاجينه.
					'loc_index' => $loc_index,
				);
			}
		}

		// دفعة أولى بتتعمل فورًا (لحد GEN_BATCH_SIZE) عشان الكورس يظهر بسيشن
		// على الأقل على طول من غير ما نستنى الكرون. الباقي -- مهما كان
		// عدده (10 ولا 900) -- بيتحط في طابور وبيتعمل في الخلفية.
		$first_batch = array_slice( $pending, 0, self::GEN_BATCH_SIZE );
		$rest        = array_slice( $pending, self::GEN_BATCH_SIZE );

		$created = 0;
		foreach ( $first_batch as $item ) {
			if ( self::create_session( $master_id, $item['date'], $item['country'], $item['duration'], $item['loc_index'] ) ) {
				$created++;
			}
		}

		if ( $rest ) {
			self::queue_remaining_sessions( $master_id, $rest );
		} else {
			// خلصنا كل حاجة في نفس الطلب -- مفيش داعي ننتظر أي دفعة تانية.
			do_action( 'cs_sessions_generation_done', $master_id );
		}

		return array(
			'created'                    => $created,
			'skipped_existing'           => $skipped_existing,
			'queued_for_background'      => count( $rest ),
			'countries_raw_repeater'     => $raw_countries_count,
			'countries_before_sanitize'  => $countries_before_sanitize,
			'countries_used'             => $countries,
			'used_fallback_no_country'   => $used_fallback,
			'start_date'                 => gmdate( 'Y-m-d', $start ),
			'year_end'                   => gmdate( 'Y-m-d', $year_end ),
			'interval'                   => $interval,
		);
	}

	/**
	 * يحط باقي السيشنز (اللي متعملتش فورًا) في transient خاص بالماستر ده،
	 * وبيجدول دفعة أولى تتنفذ عبر WP-Cron. لو فيه طابور شغال بالفعل لنفس
	 * الماستر (زي لو اتحفظ الكورس مرتين قريب من بعض)، بندمج العناصر الجديدة
	 * فيه (من غير تكرار) بدل ما نبدأ طابور جديد يلغي القديم.
	 *
	 * @param int   $master_id
	 * @param array $items      كل عنصر: [ 'date' => ..., 'country' => ..., 'duration' => ... ]
	 */
	protected static function queue_remaining_sessions( $master_id, $items ) {
		$key            = 'cs_gen_queue_' . $master_id;
		$existing_queue = get_transient( $key );

		if ( is_array( $existing_queue ) && $existing_queue ) {
			$seen = array();
			foreach ( $existing_queue as $it ) {
				$seen[ $it['date'] . '|' . $it['country'] ] = true;
			}
			foreach ( $items as $it ) {
				$k = $it['date'] . '|' . $it['country'];
				if ( ! isset( $seen[ $k ] ) ) {
					$existing_queue[] = $it;
					$seen[ $k ]       = true;
				}
			}
			$items = $existing_queue;
		}

		// ساعتين كفاية جدًا لأي طابور -- حتى بأبطأ سيناريو (900 سيشن /
		// GEN_BATCH_SIZE) هيخلص في دقايق معدودة.
		set_transient( $key, $items, 2 * HOUR_IN_SECONDS );

		self::maybe_schedule_batch( $master_id );
	}

	/**
	 * بيجدول تنفيذ دفعة توليد سيشنز واحدة، لو مفيش دفعة مجدولة بالفعل
	 * لنفس الماستر (تفادي تكرار جدولة لنفس الشغلانة). فيه jitter عشوائي
	 * بسيط (0-20 ثانية إضافية) عشان لو كذا ماستر بيتجدولوا في نفس
	 * اللحظة (زي كرون التجديد السنوي اليومي اللي بيمر على كل الماسترز
	 * مرة واحدة) ميتجمعوش كلهم في نفس تنفيذة الـ wp-cron.php وتضرب
	 * مهلة السيرفر تاني بس على مستوى الكرون بدل الأدمن.
	 */
	protected static function maybe_schedule_batch( $master_id ) {
		// Sync Mode: مفيش كرون خالص. اللي نده التوليد هو اللي هيفضّي
		// الطابور بنفسه (شوف drain_queue() وصفحات الرفع).
		if ( self::sync_mode() ) {
			return;
		}

		if ( ! wp_next_scheduled( 'cs_generate_sessions_batch', array( $master_id ) ) ) {
			$delay = self::GEN_BATCH_INTERVAL + wp_rand( 0, 20 );
			wp_schedule_single_event( time() + $delay, 'cs_generate_sessions_batch', array( $master_id ) );
		}

		if ( function_exists( 'spawn_cron' ) ) {
			spawn_cron();
		}
	}

	/**
	 * بتشتغل من WP-Cron. بتاخد دفعة واحدة بس (GEN_BATCH_SIZE سيشن) من
	 * طابور ماستر معيّن، وبعدين -- لو لسه فاضل -- بتجدول الدفعة اللي
	 * بعدها. لما الطابور يخلص، بتطلق cs_sessions_generation_done عشان
	 * أي حاجة مستنية كل السيشنز تخلص (زي ربط ترجمة WPML) تكمل شغلها.
	 *
	 * @param int $master_id
	 */
	/**
	 * هل لسه فيه سيشنز متبقّية جوه طابور التوليد بتاع الماستر ده؟ (شوف
	 * queue_remaining_sessions() فوق). بيتستخدم من CS_Import عشان يستنى
	 * لحد ما كل السيشنز تخلص فعليًا قبل ما يقول "تم الانتهاء" -- بدل ما
	 * يسيب الباقي على WP-Cron لوحده (اللي ممكن يتعطل في بعض الاستضافات).
	 */
	public static function has_pending_queue( $master_id ) {
		$queue = get_transient( 'cs_gen_queue_' . $master_id );
		return is_array( $queue ) && ! empty( $queue );
	}

	/**
	 * عدد العناصر المتبقّية جوه طابور ماستر معيّن (0 لو مفيش طابور خالص).
	 */
	public static function pending_queue_count( $master_id ) {
		$queue = get_transient( 'cs_gen_queue_' . $master_id );
		return is_array( $queue ) ? count( $queue ) : 0;
	}

	public static function process_generate_sessions_batch( $master_id ) {
		$key   = 'cs_gen_queue_' . $master_id;
		$queue = get_transient( $key );

		if ( empty( $queue ) || ! is_array( $queue ) ) {
			delete_transient( $key );
			do_action( 'cs_sessions_generation_done', $master_id );
			return;
		}

		$batch = array_slice( $queue, 0, self::GEN_BATCH_SIZE );
		$rest  = array_slice( $queue, self::GEN_BATCH_SIZE );

		foreach ( $batch as $item ) {
			$loc_index = isset( $item['loc_index'] ) ? $item['loc_index'] : 0;
			self::create_session( $master_id, $item['date'], $item['country'], $item['duration'], $loc_index );
		}

		if ( $rest ) {
			set_transient( $key, $rest, 2 * HOUR_IN_SECONDS );
			self::maybe_schedule_batch( $master_id );
		} else {
			delete_transient( $key );
			do_action( 'cs_sessions_generation_done', $master_id );
		}
	}

	/**
	 * إنشاء سيشن واحد (بوست خفيف بيشاور على الماستر).
	 */
	protected static function create_session( $master_id, $date, $country, $duration_days, $loc_index = 0 ) {
		$master = get_post( $master_id );
		if ( ! $master ) {
			return 0;
		}

		$session_id = wp_insert_post( array(
			'post_type'   => CS_CPT,
			'post_status' => 'publish',
			'post_title'  => $master->post_title, // للعرض في الأدمن بس
			'post_parent' => $master_id,
		) );

		if ( is_wp_error( $session_id ) || ! $session_id ) {
			return 0;
		}

		update_post_meta( $session_id, CS_META_MASTER, $master_id );
		update_post_meta( $session_id, CS_META_DATE, $date );
		update_post_meta( $session_id, CS_META_COUNTRY, $country );
		// شوف تعريف CS_META_LOC_INDEX في courses-system.php.
		update_post_meta( $session_id, CS_META_LOC_INDEX, (int) $loc_index );

		// تاريخ النهاية = المدة بأيام شغل بس (السبت والأحد ملهومش وجود في العد).
		// يعني أسبوع (5 أيام) = الاتنين لحد الجمعة، وأسبوعين (10 أيام) = بترجع
		// جمعة الأسبوع اللي بعده، من غير ما تتحسب أي سبت أو حد في نص الطريق.
		$end = gmdate( 'Y-m-d', self::business_day_end( strtotime( $date ), $duration_days ) );
		update_post_meta( $session_id, '_cs_session_end', $end );

		// مهم جدًا: ماينفعش نضع Category الماستر على السيشن قبل ما نحدد لغة السيشن.
		// WPML ممكن يعمل Taxonomy adjustment لحظة set_language()، وبالتالي لو
		// السيشن عربي والـ Category عربية واتحطت قبل تحديد اللغة، ممكن تتشال فورًا
		// أو تتبدل بالـ term الإنجليزي. ده كان سبب إن شيت العربي يخلص بنجاح لكن
		// الكورس لا يظهر داخل Category العربي على الـ Frontend.
		$master_terms = wp_get_object_terms( $master_id, CS_TAX, array(
			'fields'           => 'ids',
			'suppress_filters' => true,
		) );
		$master_terms = ( $master_terms && ! is_wp_error( $master_terms ) ) ? array_map( 'intval', $master_terms ) : array();

		// ⚠️ لغة السيشن بتتحدد *دلوقتي حالًا* وهو بيتعمل، قبل أي Category.
		//
		// قبل كده الربط كان بيحصل في "جولة تاجينج" منفصلة بتشتغل بعد ما كل
		// السيشنز تخلص. المشكلة إن أي سيشن بيتعمل بعد الجولة دي (كرون
		// التجديد، حفظ يدوي، إلخ) كان WPML بيديله لغة الموقع الافتراضية
		// تلقائيًا -- حتى لو الماستر بتاعه عربي -- أو ميديلوش لغة خالص.
		// ده اللي كان بيظهر كـ English (942) مقابل Arabic (506).
		// دلوقتي مفيش أي فجوة زمنية: السيشن بيتولد وهو عارف لغته.
		if ( class_exists( 'CS_WPML' ) ) {
			CS_WPML::tag_session_on_create( $session_id, $master_id, $date, (int) $loc_index, $country );

			// بعد ما لغة السيشن اتثبتت، عيّن Category الخاصة بنفس لغة الماستر
			// صراحةً. ده آخر خطوة في الإنشاء، لذلك أي Taxonomy Sync حصل أثناء
			// set_language/link_translation مش هيقدر يسيب السيشن من غير Category.
			$session_lang = CS_WPML::post_language( $session_id );
			if ( $session_lang && $master_terms ) {
				$language_terms = array();
				foreach ( $master_terms as $term_id ) {
					$term_lang = CS_WPML::term_language( $term_id );
					if ( ! $term_lang || $term_lang === $session_lang ) {
						$language_terms[] = $term_id;
					}
				}
				if ( $language_terms ) {
					CS_WPML::set_terms_for_language( $session_id, $language_terms, $session_lang );
				}
			}
		} elseif ( $master_terms ) {
			wp_set_object_terms( $session_id, $master_terms, CS_TAX, false );
		}

		// ⚠️ إجباري: نضمن يدويًا إن كل سيشن ياخد رابط فريد فعلاً، من غير ما
		// نعتمد على الرقم -2/-3 اللي ووردبريس المفروض يضيفه لوحده.
		//
		// اللي بيحصل من غيرها: wp_insert_post() فوق بيتنفذ *قبل* ما لغة
		// السيشن تتحدد (لغته بتتحدد أسفل من خلال tag_session_on_create()).
		// في اللحظة دي، فحص تكرار الـ slug بتاع ووردبريس (wp_unique_post_slug)
		// بيتفلتر أحيانًا من خلال WPML بطريقة بتفترض إن أي بوست لسه من غير
		// لغة متحددة هو "لغة مختلفة" عن أي بوست تاني موجود بالفعل، فبيتجاوز
		// فحص التكرار تمامًا ومبيضيفش أي رقم -- ده اللي أثبتناه فعليًا:
		// 700 سيشن عربي تحت نفس الكورس، كلهم بالظبط نفس الـ post_name
		// (العناوين الطويلة بتتقطع عند حد الـ 200 حرف اللي ووردبريس بيفرضه،
		// فبما إن العنوان واحد لكل سيشنز الكورس، الجزء المقطوع بيطلع
		// متطابق حرف بحرف كل مرة). النتيجة: كل الروابط طلعت متطابقة، وأي
		// حد يدوس عليها كلهم بيوصلوه لنفس البوست (غالبًا أقدم واحد ووردبريس
		// يلاقيه بالصدفة عند البحث بالـ slug -- عادة الماستر نفسه أو أقدم
		// سيشن)، مش السيشن اللي فعلاً دوس عليه. ده سبب ظهور صفحة الكورس
		// المفرد فاضية من السعر/المكان/التاريخ في العربي بالتحديد.
		//
		// الحل الأضمن اللي مش هيتأثر بأي سلوك WPML تاني: منعتمدش على أي
		// رقم تلقائي خالص، ونضيف ID السيشن نفسه (فريد دايمًا بالتعريف، مفيش
		// احتمال تكرار خالص) في آخر الـ slug إحنا بأيدينا بعد ما السيشن
		// خلص إنشاء ولغته اتثبتت.
		$id_suffix = '-' . $session_id;
		$base_slug = sanitize_title( $master->post_title );
		$base_slug = function_exists( '_truncate_post_slug' )
			? _truncate_post_slug( $base_slug, 200 - strlen( $id_suffix ) )
			: substr( $base_slug, 0, max( 0, 200 - strlen( $id_suffix ) ) );
		wp_update_post( array(
			'ID'        => $session_id,
			'post_name' => $base_slug . $id_suffix,
		) );

		return $session_id;
	}

	/**
	 * السبت (6) والأحد (7) مش أيام كورس. لو التاريخ وقع في واحد منهم،
	 * رجّع أول يوم شغل جاي (السبت -> الاتنين اللي بعده، الأحد -> الاتنين).
	 *
	 * @param int $ts Unix timestamp.
	 * @return int
	 */
	protected static function next_business_day( $ts ) {
		$dow = (int) gmdate( 'N', $ts ); // 1=Mon ... 6=Sat, 7=Sun
		if ( 6 === $dow ) {
			$ts = strtotime( '+2 days', $ts );
		} elseif ( 7 === $dow ) {
			$ts = strtotime( '+1 day', $ts );
		}
		return $ts;
	}

	/**
	 * يحسب آخر يوم كورس بعد ما يعدّ $duration_days من أيام الشغل بس، بادئًا
	 * بيوم البداية نفسه كـ "يوم 1". أي سبت/أحد بيتخطّاه تمامًا ومش بيتحسب
	 * ضمن الـ duration ولا بيوقّف عنده الكورس.
	 * مثال: بداية الاتنين + duration=5  -> الجمعة (نفس الأسبوع).
	 *       بداية الاتنين + duration=10 -> الجمعة اللي بعد أسبوع (مش بينها سبت/حد).
	 *
	 * @param int $start_ts      Unix timestamp ليوم البداية (المفروض يوم شغل بالفعل).
	 * @param int $duration_days عدد أيام الكورس الفعلية (شغل بس).
	 * @return int Unix timestamp لآخر يوم.
	 */
	protected static function business_day_end( $start_ts, $duration_days ) {
		$duration_days = max( 1, (int) $duration_days );
		$ts    = self::next_business_day( $start_ts ); // احتياطًا لو جالنا تاريخ ويكند.
		$count = 1; // يوم البداية = يوم شغل رقم 1.

		while ( $count < $duration_days ) {
			$ts  = strtotime( '+1 day', $ts );
			$dow = (int) gmdate( 'N', $ts );
			if ( $dow < 6 ) { // مش سبت ولا حد.
				$count++;
			}
		}

		return $ts;
	}

	/**
	 * مفاتيح السيشنز الموجودة (date|country) لماستر في سنة.
	 */
	protected static function existing_session_keys( $master_id, $year ) {
		$q = new WP_Query( array(
			'post_type'      => CS_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'suppress_filters' => true, // WPML بيفلتر WP_Query باللغة الحالية تلقائيًا لأي CPT مسجل Translatable -- من غيرها الاستعلام بيشوف سيشنز لغة واحدة بس فيفتكر إن سيشنز اللغة التانية مش موجودة ويعيد توليدها/يتجاهلها بالغلط.
			'meta_query'     => array(
				array( 'key' => CS_META_MASTER, 'value' => $master_id ),
				array(
					'key'     => CS_META_DATE,
					'value'   => array( "$year-01-01", "$year-12-31" ),
					'compare' => 'BETWEEN',
					'type'    => 'DATE',
				),
			),
		) );

		$keys = array();
		foreach ( $q->posts as $sid ) {
			$date    = get_post_meta( $sid, CS_META_DATE, true );
			$country = get_post_meta( $sid, CS_META_COUNTRY, true );
			$keys[ $date . '|' . $country ] = $sid;
		}
		return $keys;
	}

	/* ===================== Sync ===================== */
	// ملاحظة: مفيش داعي لكود مزامنة معقّد. لإن السيشن بيقرأ المحتوى من الماستر
	// وقت العرض (شوف cs_field() تحت)، فأي تعديل في الماستر بيبان في كل السيشنز
	// فورًا. الحاجة الوحيدة اللي بتتغير لما تعدّل الدول/التكرار هي عدد السيشنز،
	// وده on_master_save بيتكفّل بيه.

	/**
	 * Helper عام: هات قيمة حقل بغض النظر إنت واقف على سيشن ولا ماستر.
	 * أي template بتستخدمه عشان "التعديل يبان في الكوبي".
	 */
	public static function field( $name, $post_id = null ) {
		$post_id   = $post_id ?: get_the_ID();
		$master_id = CS_CPT::master_id( $post_id );

		// A Session is only allowed to read fields from the Master in the
		// Session's own WPML language. This is a hard runtime guarantee for
		// old sessions whose _cs_master was left pointing at the other language.
		if ( class_exists( 'CS_WPML' ) && CS_WPML::active() && get_post_meta( $post_id, CS_META_MASTER, true ) ) {
			$session_lang = CS_WPML::post_language( $post_id );
			if ( $session_lang ) {
				$resolved = CS_WPML::localized_post_id( $master_id, $session_lang );
				if ( $resolved && get_post_type( $resolved ) === CS_CPT ) {
					$master_id = $resolved;
				}
			}
		}

		return get_field( $name, $master_id );
	}

	/**
	 * عدد أيام الكورس -- أوتوماتيك بالكامل من نوع التكرار، مفيش حقل يدوي:
	 * كل أسبوع = 5 أيام شغل (الاتنين للجمعة)، كل أسبوعين = 10 أيام شغل.
	 * بتشتغل صح سواء انت واقف على ماستر أو سيشن.
	 */
	public static function duration_days( $post_id = null ) {
		$post_id   = $post_id ?: get_the_ID();
		$master_id = CS_CPT::master_id( $post_id );
		$interval  = get_field( 'cs_recurrence', $master_id ) ?: 'weekly';
		return ( 'biweekly' === $interval ) ? 10 : 5;
	}

	/* ================= Yearly Renewal ================= */

	/**
	 * كرون يومي:
	 *  - يحذف السيشنز اللي عدّى تاريخها (تنظيف).
	 *  - في بداية كل سنة (أو لو ناقص) يولّد سيشنز السنة الحالية لكل الماسترز.
	 */
	public static function daily_maintenance() {
		$today = current_time( 'Y-m-d' );
		$year  = (int) current_time( 'Y' );

		// 1) تنظيف السيشنز القديمة.
		$old = new WP_Query( array(
			'post_type'      => CS_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'suppress_filters' => true, // WPML بيفلتر WP_Query باللغة الحالية تلقائيًا لأي CPT مسجل Translatable -- من غيرها الاستعلام بيشوف سيشنز لغة واحدة بس فيفتكر إن سيشنز اللغة التانية مش موجودة ويعيد توليدها/يتجاهلها بالغلط.
			'meta_query'     => array(
				array( 'key' => CS_META_MASTER, 'compare' => 'EXISTS' ),
				array( 'key' => '_cs_session_end', 'value' => $today, 'compare' => '<', 'type' => 'DATE' ),
			),
		) );
		foreach ( $old->posts as $sid ) {
			wp_delete_post( $sid, true );
		}

		// 2) ضمان سيشنز السنة الحالية لكل الماسترز.
		$masters = new WP_Query( array(
			'post_type'      => CS_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'suppress_filters' => true, // WPML بيفلتر WP_Query باللغة الحالية تلقائيًا لأي CPT مسجل Translatable -- من غيرها الاستعلام بيشوف سيشنز لغة واحدة بس فيفتكر إن سيشنز اللغة التانية مش موجودة ويعيد توليدها/يتجاهلها بالغلط.
			'meta_query'     => array(
				array( 'key' => CS_META_IS_MASTER, 'value' => 1 ),
			),
		) );
		foreach ( $masters->posts as $master_id ) {
			self::generate_sessions( $master_id, $year );
		}
	}
}

CS_Recurrence::init();

/**
 * Wrapper قصير تستخدمه في الـ templates بدل get_field.
 * بيضمن إن السيشن بيقرأ محتوى الماستر.
 */
function cs_field( $name, $post_id = null ) {
	return CS_Recurrence::field( $name, $post_id );
}

/**
 * عدد أيام الكورس (أوتوماتيك من نوع التكرار -- weekly=5 / biweekly=10).
 * استخدمها بدل get_field('cs_duration_days', ...) في أي مكان.
 */
function cs_duration_days( $post_id = null ) {
	return CS_Recurrence::duration_days( $post_id );
}