<?php

/**
 * Feature: ربط الترجمة (EN ⇄ AR) بشيتين منفصلين
 * =============================================================
 * الفكرة (الطريقة الجديدة):
 *
 *   1) ترفع الشيت الإنجليزي لوحده  -> بيتعمل كورسات إنجليزي + سيشنزها.
 *      وتقدر ترفع أكتر من شيت إنجليزي ورا بعض، وكله بيتجمّع في نفس اللستة.
 *   2) ترفع الشيت العربي لوحده     -> بيتعمل كورسات عربي + سيشنزها.
 *      وبرضه تقدر تكرر الرفع أكتر من مرة، وكله بيتجمّع.
 *   3) الصفحة بتعرض العدد في كل لغة، وبتقولك: العدد متطابق ✅ ولا لأ ❌.
 *   4) تدوس زرار واحد "اربط الترجمة" -> بيعمل sync بين اللستتين:
 *      كل كورس عربي بيتربط كترجمة رسمية للكورس الإنجليزي المقابل له في
 *      WPML، وكمان كل سيشن عربي بيتربط بالسيشن الإنجليزي المقابل له
 *      (نفس التاريخ + نفس ترتيب المكان).
 *
 * يعني مبقاش لازم عمود source_course_id في الشيت العربي خالص -- بتتجاهله
 * الصفحة دي أصلاً، والربط بيحصل من الواجهة بعد ما تشوف وتتأكد بعينك.
 *
 * الترتيب الافتراضي للربط = ترتيب الصفوف في الشيت (أول عربي مع أول
 * إنجليزي وهكذا)، وتقدر تغيّر أي زوج يدوي من القايمة المنسدلة قبل ما
 * تدوس الزرار.
 *
 * ملاحظة معمارية: الكلاس ده بيورث من CS_Import عشان يستخدم نفس محرك
 * الاستيراد (import_row) ونفس مكان التخزين المؤقت (import_tmp_dir) من
 * غير أي تعديل على فايل الاستيراد الأصلي -- فصفحة "Import CSV" القديمة
 * فاضلة شغالة زي ما هي بالظبط.
 */

if (! defined('ABSPATH')) {
	exit;
}

class CS_Translation_Link extends CS_Import
{

	/** نونس مستقل تمامًا عن نونس صفحة الاستيراد العادية. */
	const LINK_NONCE = 'cs_trlink_nonce';

	/** الأوبشن اللي بتتخزن فيه لستة الكورسات المرشّحة للربط. */
	const OPT_STAGE = 'cs_tr_stage';

	/** عدد صفوف الشيت اللي بتتعالج في كل طلب أجاكس (زي الاستيراد الأصلي). */
	const ROWS_BATCH = 1;

	/** عدد السيشنز اللي بتتربط في كل لفة جوه طلب الربط. */
	const SESS_BATCH = 40;

	public static function init()
	{
		add_action('admin_menu', array(__CLASS__, 'add_menu'), 20);

		add_action('wp_ajax_cs_trlink_stage_get',     array(__CLASS__, 'ajax_stage_get'));
		add_action('wp_ajax_cs_trlink_stage_update',  array(__CLASS__, 'ajax_stage_update'));
		add_action('wp_ajax_cs_trlink_import_start',  array(__CLASS__, 'ajax_import_start'));
		add_action('wp_ajax_cs_trlink_import_batch',  array(__CLASS__, 'ajax_import_batch'));
		add_action('wp_ajax_cs_trlink_drain',         array(__CLASS__, 'ajax_drain'));
		add_action('wp_ajax_cs_trlink_link_start',    array(__CLASS__, 'ajax_link_start'));
		add_action('wp_ajax_cs_trlink_link_batch',    array(__CLASS__, 'ajax_link_batch'));
		add_action('wp_ajax_cs_trlink_maint_start',   array(__CLASS__, 'ajax_maint_start'));
		add_action('wp_ajax_cs_trlink_maint_batch',   array(__CLASS__, 'ajax_maint_batch'));
		add_action('wp_ajax_cs_trlink_repair_start',  array(__CLASS__, 'ajax_repair_start'));
		add_action('wp_ajax_cs_trlink_repair_batch',  array(__CLASS__, 'ajax_repair_batch'));

		// ⚠️ الحارس الدايم ضد أهم مشكلة في النظام كله:
		//
		// السيشنز مش بتتولّد كلها مرة واحدة -- جزء بيتعمل وقت الرفع
		// والباقي بيكمّله WP-Cron في الخلفية على دفعات (وكمان كرون
		// الصيانة اليومي بيوسّع لآخر السنة). المشكلة إن أي بوست بيتعمل
		// جوه طلب كرون، WPML بيديله *لغة الموقع الافتراضية* تلقائيًا
		// (الإنجليزي غالبًا) -- حتى لو الماستر بتاعه عربي! ولغة الماستر
		// بتتحدد مرة واحدة بس وقت الرفع (_cs_pending_wpml_lang بيتمسح
		// بعدها)، فأي سيشن بيتولّد بعد كده كان بيفضل: يا إما من غير لغة
		// خالص، يا إما متسجّل إنجليزي غلط.
		//
		// ده اللي كان بيظهر في شاشة الكورسات كـ: English (942) مقابل
		// Arabic (506) مع إن المفروض يكونوا متساويين، و185 بوست مش
		// ظاهرين تحت أي لغة أصلاً.
		//
		// الهوك ده بيشتغل بعد *كل* عملية توليد (وقت الرفع أو من الكرون)
		// وبيصلّح أي سيشن لغته مش زي لغة الماستر بتاعه.
		add_action('cs_sessions_generation_done', array(__CLASS__, 'auto_repair_after_generation'), 20);
		add_action('cs_trlink_auto_repair', array(__CLASS__, 'auto_repair_after_generation'));
	}

	/* ================= إصلاح لغات السيشنز ================= */

	/** جدول WPML موجود فعلاً؟ (بنستعلم منه مباشرة عشان السرعة). */
	protected static function icl_table()
	{
		static $table = null;

		if (null !== $table) {
			return $table;
		}

		global $wpdb;
		$name  = $wpdb->prefix . 'icl_translations';
		$table = ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $name)) === $name) ? $name : '';

		return $table;
	}

	/**
	 * سيشنز ماستر معيّن اللي لغتها مش زي لغته (أو مالهاش لغة خالص).
	 * بنستعلم من جدول WPML مباشرة لأن WP_Query مش بتعرف تفلتر بالشكل ده،
	 * وكمان لأننا عايزين نمسك اللي *مالهوش صف أصلاً* في الجدول (LEFT JOIN).
	 */
	protected static function mismatched_sessions($master_id, $lang, $limit = 50)
	{
		$table = self::icl_table();
		if (! $table) {
			return array();
		}

		global $wpdb;

		return array_map('intval', (array) $wpdb->get_col(
			$wpdb->prepare(
				"SELECT p.ID
				 FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->postmeta} pm
				   ON pm.post_id = p.ID AND pm.meta_key = %s AND pm.meta_value = %d
				 LEFT JOIN {$table} t
				   ON t.element_id = p.ID AND t.element_type = %s
				 WHERE p.post_type = %s
				   AND p.post_status = 'publish'
				   AND ( t.language_code IS NULL OR t.language_code <> %s )
				 ORDER BY p.ID ASC
				 LIMIT %d",
				CS_META_MASTER,
				(int) $master_id,
				'post_' . CS_CPT,
				CS_CPT,
				$lang,
				(int) $limit
			)
		));
	}

	/** ID الماستر الأصلي لو الماستر ده ترجمة، أو 0 لو هو الأصل. */
	protected static function source_master_of($master_id, $lang)
	{
		if (! class_exists('CS_WPML') || ! CS_WPML::active()) {
			return 0;
		}

		$default = self::source_lang();
		if ($lang === $default) {
			return 0;
		}

		$src = (int) apply_filters('wpml_object_id', (int) $master_id, CS_CPT, false, $default);

		return ($src && $src !== (int) $master_id && get_post_type($src) === CS_CPT) ? $src : 0;
	}

	/** بيدوّر على السيشن الأصلي المقابل لسيشن معيّن جوه فهرس مبني قبل كده. */
	protected static function match_source_session($session_id, $index)
	{
		$date      = get_post_meta($session_id, CS_META_DATE, true);
		$loc_index = get_post_meta($session_id, CS_META_LOC_INDEX, true);

		if ('' !== $loc_index && isset($index['loc'][$date . '|' . (int) $loc_index])) {
			return (int) $index['loc'][$date . '|' . (int) $loc_index];
		}

		$country = get_post_meta($session_id, CS_META_COUNTRY, true);
		if (isset($index['ctry'][$date . '|' . $country])) {
			return (int) $index['ctry'][$date . '|' . $country];
		}

		if (isset($index['date'][$date])) {
			return (int) $index['date'][$date];
		}

		return 0;
	}

	/**
	 * بتصلّح دفعة سيشنز لماستر واحد. بترجّع كام سيشن اتصلّح فعلاً.
	 */
	protected static function repair_master_chunk($master_id, $limit = 50)
	{
		if (! class_exists('CS_WPML') || ! CS_WPML::active()) {
			return array('fixed' => 0, 'linked' => 0, 'remaining' => 0);
		}

		$lang = CS_WPML::post_language($master_id);
		if (! $lang) {
			return array('fixed' => 0, 'linked' => 0, 'remaining' => 0);
		}

		$ids = self::mismatched_sessions($master_id, $lang, $limit);
		if (! $ids) {
			return array('fixed' => 0, 'linked' => 0, 'remaining' => 0);
		}

		$src_master = self::source_master_of($master_id, $lang);
		$index      = array();

		if ($src_master) {
			$cache_key = 'cs_src_sessions_' . $src_master;
			$cached    = get_transient($cache_key);
			if (is_array($cached) && isset($cached['loc'])) {
				$index = $cached;
			} else {
				$index = self::build_source_index($src_master);
				set_transient($cache_key, $index, 30 * MINUTE_IN_SECONDS);
			}
		}

		$fixed  = 0;
		$linked = 0;

		foreach ($ids as $sid) {
			$match = $index ? self::match_source_session($sid, $index) : 0;

			if ($match && $match !== (int) $sid) {
				CS_WPML::link_translation($sid, $match, $lang);
				$linked++;
			} else {
				CS_WPML::set_language($sid, $lang);
			}
			$fixed++;
		}

		return array(
			'fixed'     => $fixed,
			'linked'    => $linked,
			'remaining' => count(self::mismatched_sessions($master_id, $lang, 1)),
		);
	}

	/**
	 * بتشتغل تلقائي بعد كل عملية توليد سيشنز (سواء وقت الرفع أو من
	 * الكرون). بتصلّح دفعة صغيرة، ولو لسه فاضل بتجدول اللفة اللي بعدها.
	 */
	public static function auto_repair_after_generation($master_id)
	{
		$master_id = (int) $master_id;

		if (! $master_id || ! class_exists('CS_WPML') || ! CS_WPML::active()) {
			return;
		}

		// مهمة ربط يدوية شغالة دلوقتي على الكورس ده -- سيبها تخلص الأول.
		if (get_transient('cs_manual_link_' . $master_id)) {
			return;
		}

		$sync = class_exists('CS_Recurrence') && CS_Recurrence::sync_mode();

		// Sync Mode: مفيش كرون -- بنكمّل الإصلاح هنا على طول لحد ما يخلص.
		if ($sync) {
			$guard = 0;
			do {
				$result = self::repair_master_chunk($master_id, 50);
				$guard++;
			} while ($result['remaining'] > 0 && $result['fixed'] > 0 && $guard < 500);

			return;
		}

		$result = self::repair_master_chunk($master_id, 50);

		if ($result['remaining'] > 0) {
			if (! wp_next_scheduled('cs_trlink_auto_repair', array($master_id))) {
				wp_schedule_single_event(time() + 8, 'cs_trlink_auto_repair', array($master_id));
				if (function_exists('spawn_cron')) {
					spawn_cron();
				}
			}
		}
	}

	/* ================= أجاكس: الصيانة اليدوية (بديل الكرون اليومي) ================= */

	/**
	 * في Sync Mode مفيش كرون يومي، فالصيانة (مسح السيشنز اللي فاتت +
	 * ضمان إن كل كورس عنده العدد المضبوط من السيشنز لآخر السنة) بقت
	 * زرار بتدوسه انت وقت ما تحب، بتشتغل قدامك ببروجريس بار.
	 *
	 * شغّلها مرة كل كام يوم، أو أول ما تدخل سنة جديدة.
	 */
	public static function ajax_maint_start()
	{
		try {
			self::guard();

			if (function_exists('set_time_limit')) {
				@set_time_limit(0);
			}

			// 1) امسح السيشنز اللي تاريخ نهايتها فات (بقت مالهاش لازمة).
			$today   = current_time('Y-m-d');
			$expired = get_posts(array(
				'post_type'      => CS_CPT,
				'post_status'    => 'publish',
				'posts_per_page' => 400,
				'fields'         => 'ids',
				'meta_query'     => array(
					array('key' => CS_META_MASTER, 'compare' => 'EXISTS'),
					array('key' => '_cs_session_end', 'value' => $today, 'compare' => '<', 'type' => 'DATE'),
				),
			));

			foreach ($expired as $sid) {
				wp_delete_post($sid, true);
			}

			// 2) جهّز قايمة الماسترز عشان نضمن العدد المضبوط لكل واحد.
			$masters = get_posts(array(
				'post_type'      => CS_CPT,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array(array('key' => CS_META_IS_MASTER, 'value' => 1)),
			));

			if (! $masters) {
				wp_send_json_error(array('message' => 'مفيش كورسات على الموقع.'));
			}

			$token = 'cs_trlink_mnt_' . wp_generate_password(20, false, false);

			$ok = self::job_save($token, array(
				'kind'    => 'maint',
				'masters' => array_map('intval', $masters),
				'i'       => 0,
				'created' => 0,
				'deleted' => count($expired),
			));

			if (! $ok) {
				wp_send_json_error(array('message' => 'مقدرش أخزّن بيانات المهمة على السيرفر.'));
			}

			wp_send_json_success(array(
				'token'   => $token,
				'total'   => count($masters),
				'deleted' => count($expired),
			));
		} catch (\Throwable $e) {
			wp_send_json_error(array('message' => 'خطأ: ' . $e->getMessage()));
		}
	}

	public static function ajax_maint_batch()
	{
		try {
			self::guard();

			if (function_exists('set_time_limit')) {
				@set_time_limit(120);
			}

			$token = isset($_POST['token']) ? sanitize_text_field(wp_unslash($_POST['token'])) : '';
			$job   = $token ? self::job_load($token) : false;

			if (! $job || empty($job['masters'])) {
				wp_send_json_error(array('message' => 'انتهت صلاحية مهمة الصيانة. ابدأ من تاني.'));
			}

			$total    = count($job['masters']);
			$deadline = microtime(true) + 15;

			while (microtime(true) < $deadline && $job['i'] < $total) {
				$master_id = (int) $job['masters'][$job['i']];

				if (class_exists('CS_Recurrence')) {
					$year = self::generation_year($master_id);
					$gen  = CS_Recurrence::generate_sessions($master_id, $year);
					$job['created'] += isset($gen['created']) ? (int) $gen['created'] : 0;
					// كمّل الطابور كله دلوقتي -- مفيش كرون يكمّله.
					$job['created'] += CS_Recurrence::drain_queue($master_id, 10);

					if (CS_Recurrence::has_pending_queue($master_id)) {
						// لسه فاضل -- كمّل نفس الماستر في الطلب الجاي.
						break;
					}
				}

				$job['i']++;
			}

			$done    = ($job['i'] >= $total);
			$percent = $total > 0 ? (int) round(($job['i'] / $total) * 100) : 100;

			if ($done) {
				$created = (int) $job['created'];
				$deleted = (int) $job['deleted'];
				self::job_delete($token);

				wp_send_json_success(array(
					'done'    => true,
					'percent' => 100,
					'created' => $created,
					'deleted' => $deleted,
					'total'   => $total,
				));
			}

			self::job_save($token, $job);

			wp_send_json_success(array(
				'done'    => false,
				'percent' => $percent,
				'created' => (int) $job['created'],
				'deleted' => (int) $job['deleted'],
				'total'   => $total,
			));
		} catch (\Throwable $e) {
			wp_send_json_error(array('message' => 'خطأ أثناء الصيانة: ' . $e->getMessage()));
		}
	}

	/* ================= أجاكس: زرار الإصلاح الشامل ================= */

	public static function ajax_repair_start()
	{
		try {
			self::guard();

			if (! class_exists('CS_WPML') || ! CS_WPML::active()) {
				wp_send_json_error(array('message' => 'WPML مش مفعّل، مفيش لغات نصلّحها.'));
			}
			if (! self::icl_table()) {
				wp_send_json_error(array('message' => 'مقدرش ألاقي جدول WPML (icl_translations).'));
			}

			$masters = get_posts(array(
				'post_type'      => CS_CPT,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array(array('key' => CS_META_IS_MASTER, 'value' => 1)),
			));

			if (! $masters) {
				wp_send_json_error(array('message' => 'مفيش كورسات على الموقع.'));
			}

			$token = 'cs_trlink_fix_' . wp_generate_password(20, false, false);

			$ok = self::job_save($token, array(
				'kind'    => 'repair',
				'masters' => array_map('intval', $masters),
				'i'       => 0,
				'stuck'   => 0,
				'fixed'   => 0,
				'linked'  => 0,
			));

			if (! $ok) {
				wp_send_json_error(array('message' => 'مقدرش أخزّن بيانات المهمة على السيرفر.'));
			}

			wp_send_json_success(array('token' => $token, 'total' => count($masters)));
		} catch (\Throwable $e) {
			wp_send_json_error(array('message' => 'خطأ: ' . $e->getMessage()));
		}
	}

	public static function ajax_repair_batch()
	{
		try {
			self::guard();

			if (function_exists('set_time_limit')) {
				@set_time_limit(120);
			}

			$token = isset($_POST['token']) ? sanitize_text_field(wp_unslash($_POST['token'])) : '';
			$job   = $token ? self::job_load($token) : false;

			if (! $job || empty($job['masters'])) {
				wp_send_json_error(array('message' => 'انتهت صلاحية مهمة الإصلاح. ابدأ من تاني.'));
			}

			$total    = count($job['masters']);
			$deadline = microtime(true) + 15;

			while (microtime(true) < $deadline && $job['i'] < $total) {
				$master_id = (int) $job['masters'][$job['i']];
				$result    = self::repair_master_chunk($master_id, 50);

				$job['fixed']  += $result['fixed'];
				$job['linked'] += $result['linked'];

				if ($result['remaining'] > 0 && $result['fixed'] > 0) {
					$job['stuck'] = 0;
					continue; // نفس الماستر، دفعة تانية.
				}

				// إما خلص، أو مبيتصلّحش (احتياطي ضد لفة لا نهائية).
				$job['i']++;
				$job['stuck'] = 0;
			}

			$done    = ($job['i'] >= $total);
			$percent = $total > 0 ? (int) round(($job['i'] / $total) * 100) : 100;

			if ($done) {
				$fixed  = (int) $job['fixed'];
				$linked = (int) $job['linked'];
				self::job_delete($token);

				wp_send_json_success(array(
					'done'    => true,
					'percent' => 100,
					'fixed'   => $fixed,
					'linked'  => $linked,
					'total'   => $total,
				));
			}

			self::job_save($token, $job);

			wp_send_json_success(array(
				'done'    => false,
				'percent' => $percent,
				'fixed'   => (int) $job['fixed'],
				'linked'  => (int) $job['linked'],
				'total'   => $total,
				'i'       => (int) $job['i'],
			));
		} catch (\Throwable $e) {
			wp_send_json_error(array('message' => 'خطأ أثناء الإصلاح: ' . $e->getMessage()));
		}
	}

	public static function add_menu()
	{
		add_submenu_page(
			'edit.php?post_type=' . CS_CPT,
			'ربط الترجمة (EN ⇄ AR)',
			'ربط الترجمة',
			'edit_posts',
			'cs-translation-link',
			array(__CLASS__, 'render_page')
		);
	}

	/* ================= اللغات ================= */

	/** لغة المصدر = لغة الموقع الافتراضية في WPML (غالبًا en). */
	public static function source_lang()
	{
		if (class_exists('CS_WPML') && CS_WPML::active()) {
			return CS_WPML::default_language();
		}
		return 'en';
	}

	/**
	 * كل اللغات المفعّلة في WPML ما عدا لغة المصدر -- دي اللي بتتعرض في
	 * قايمة "لغة الشيت التاني". لو WPML مش شغال بنرجّع العربي بس.
	 */
	public static function target_langs()
	{
		$out = array();

		if (class_exists('CS_WPML') && CS_WPML::active()) {
			$langs = apply_filters('wpml_active_languages', null, array('skip_missing' => 0));
			if (is_array($langs)) {
				$src = self::source_lang();
				foreach ($langs as $code => $info) {
					$code = sanitize_key($code);
					if ($code === $src) {
						continue;
					}
					$out[$code] = isset($info['translated_name']) && $info['translated_name']
						? $info['translated_name']
						: (isset($info['native_name']) ? $info['native_name'] : strtoupper($code));
				}
			}
		}

		if (empty($out)) {
			$out = array('ar' => 'العربية');
		}

		return $out;
	}

	/* ================= لستة الكورسات المرشّحة (Stage) ================= */

	/**
	 * بترجّع اللستة المخزّنة بالشكل array( 'src' => [ids], 'trg' => [ids] )
	 * بعد ما تشيل أي ID اتمسح من ووردبريس أو مبقاش كورس.
	 */
	protected static function get_stage()
	{
		$raw = get_option(self::OPT_STAGE, array());
		$out = array('src' => array(), 'trg' => array());

		foreach (array('src', 'trg') as $side) {
			if (empty($raw[$side]) || ! is_array($raw[$side])) {
				continue;
			}
			foreach ($raw[$side] as $id) {
				$id = (int) $id;
				if ($id && get_post_type($id) === CS_CPT && ! in_array($id, $out[$side], true)) {
					$out[$side][] = $id;
				}
			}
		}

		return $out;
	}

	protected static function save_stage($stage)
	{
		update_option(self::OPT_STAGE, array(
			'src' => array_values(array_map('intval', $stage['src'])),
			'trg' => array_values(array_map('intval', $stage['trg'])),
		), false);
	}

	/** بيضيف IDs لناحية معيّنة، بيحافظ على الترتيب ومبيكررش. */
	protected static function stage_add($side, $ids)
	{
		$stage = self::get_stage();
		foreach ((array) $ids as $id) {
			$id = (int) $id;
			if (! $id || get_post_type($id) !== CS_CPT) {
				continue;
			}
			if (! in_array($id, $stage[$side], true)) {
				$stage[$side][] = $id;
			}
		}
		self::save_stage($stage);
		return $stage;
	}

	/** عدد سيشنز ماستر معيّن (استعلام واحد خفيف بدل WP_Query كاملة). */
	protected static function count_sessions($master_id)
	{
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} pm
				 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id AND p.post_status = 'publish'
				 WHERE pm.meta_key = %s AND pm.meta_value = %d",
				CS_META_MASTER,
				(int) $master_id
			)
		);
	}

	/**
	 * عنوان البوست زي ما هو متخزّن في الداتابيز بالظبط، من غير أي فلاتر
	 * عرض. get_the_title() بيمرّر العنوان على wptexturize بتاعة ووردبريس،
	 * واللي بتحوّل الشرطة الطويلة (–) لكيان HTML نصي "&#8211;" -- وده كان
	 * بيظهر حرفيًا في الجداول هنا لأننا بنعرض النص كنص (textContent) مش
	 * كـ HTML. الخام هو الصح في الحالة دي.
	 */
	protected static function raw_title($post_id)
	{
		$t = get_post_field('post_title', $post_id, 'raw');
		return is_string($t) ? $t : '';
	}

	/**
	 * العدد المظبوط المفروض يكون موجود لماستر معيّن = عدد المواعيد ×
	 * عدد الأماكن المختلفة. بنحسبه من نفس منطق محرك التكرار بالظبط
	 * (نفس الدوال، مش نسخة تانية من الحساب) عشان مايحصلش أي انحراف.
	 */
	protected static function expected_sessions($master_id)
	{
		if (! class_exists('CS_Recurrence')) {
			return 0;
		}

		$base = get_field('cs_base_start_date', $master_id);
		if (! $base) {
			return 0;
		}

		$rows = get_field('cs_country_prices', $master_id);
		$locs = array();
		if (is_array($rows)) {
			foreach ($rows as $row) {
				if (! empty($row['country'])) {
					$locs[] = $row['country'];
				}
			}
		}
		$locs  = CS_Countries::sanitize_codes($locs);
		$count = max(1, count($locs));

		$interval = get_field('cs_recurrence', $master_id) ?: 'weekly';
		$step     = ('biweekly' === $interval) ? 14 : 7;

		$start = strtotime($base);
		$today = current_time('timestamp');
		while ($start < $today) {
			$start = strtotime("+{$step} days", $start);
		}

		$year_end = strtotime(gmdate('Y', $start) . '-12-31');

		$dates = 0;
		for ($ts = $start; $ts <= $year_end; $ts = strtotime("+{$step} days", $ts)) {
			$dow = (int) gmdate('N', $ts);
			if ($dow >= 6) {
				continue; // السبت والأحد مش أيام كورس.
			}
			$dates++;
		}

		return $dates * $count;
	}

	/** عدد الأماكن (صفوف الأسعار) في ماستر -- بنعرضه عشان تقارن قبل الربط. */
	protected static function count_locations($master_id)
	{
		$rows = get_field('cs_country_prices', $master_id);
		return is_array($rows) ? count($rows) : 0;
	}

	/**
	 * البيانات اللي الجافاسكريبت بيرسم بيها الجداول. بنحسبها من الأول في
	 * كل مرة (مفيش كاش) عشان الأرقام تبقى صح دايمًا بعد أي رفع أو ربط.
	 */
	protected static function stage_payload()
	{
		$stage  = self::get_stage();
		$target = self::current_target_lang();
		$out    = array('src' => array(), 'trg' => array());

		foreach ($stage as $side => $ids) {
			foreach ($ids as $id) {
				$linked = '';
				if (class_exists('CS_WPML') && CS_WPML::active()) {
					if ('src' === $side) {
						$t = CS_WPML::get_translation_id($id, $target);
						$linked = $t ? (string) $t : '';
					} else {
						$src_id = CS_WPML::get_translation_id($id, self::source_lang());
						$linked = $src_id ? (string) $src_id : '';
					}
				}

				$out[$side][] = array(
					'id'        => (int) $id,
					'title'     => self::raw_title($id),
					'sessions'  => self::count_sessions($id),
					// العدد المفروض يطلع بالظبط من إعدادات الكورس (التاريخ
					// × التكرار × عدد الأماكن). لو الاتنين مش متساويين
					// يبقى فيه سيشنز ناقصة لسه.
					'expected'  => self::expected_sessions($id),
					// كام سيشن لسه في طابور التوليد الخلفي للكورس ده. طول
					// ما الرقم ده أكبر من صفر، عدد السيشنز المعروض *مؤقت*
					// ولسه بيزيد -- وده كان سبب اختلاف الأعداد بين اللغتين.
					'pending'   => class_exists('CS_Recurrence') ? CS_Recurrence::pending_queue_count($id) : 0,
					'locations' => self::count_locations($id),
					'date'      => (string) get_field('cs_base_start_date', $id),
					'lang'      => (class_exists('CS_WPML') && CS_WPML::active()) ? (string) CS_WPML::post_language($id) : '',
					'linked'    => $linked,
					'edit'      => (string) get_edit_post_link($id, 'raw'),
				);
			}
		}

		$out['counts'] = array(
			'src' => count($out['src']),
			'trg' => count($out['trg']),
		);

		$pending = 0;
		foreach ($out as $side => $list) {
			if (! is_array($list) || 'counts' === $side) {
				continue;
			}
			foreach ($list as $row) {
				$pending += isset($row['pending']) ? (int) $row['pending'] : 0;
			}
		}
		$out['pending'] = $pending;

		return $out;
	}

	/** لغة الهدف المختارة حاليًا (متخزنة في أوبشن عشان تفضل بين الجلسات). */
	public static function current_target_lang()
	{
		$saved = get_option('cs_tr_target_lang', '');
		$langs = self::target_langs();

		if ($saved && isset($langs[$saved])) {
			return $saved;
		}
		if (isset($langs['ar'])) {
			return 'ar';
		}

		$keys = array_keys($langs);
		return $keys ? $keys[0] : 'ar';
	}

	/* ================= صفحة الأدمن ================= */

	public static function render_page()
	{
		if (! current_user_can('edit_posts')) {
			return;
		}

		$src_lang     = self::source_lang();
		$target_langs = self::target_langs();
		$target       = self::current_target_lang();
		$wpml_on      = class_exists('CS_WPML') && CS_WPML::active();
		$nonce        = wp_create_nonce(self::LINK_NONCE);
		$sample_url   = wp_nonce_url(
			admin_url('admin-post.php?action=cs_download_sample'),
			'cs_download_sample'
		);
?>
		<div class="wrap cs-trlink" dir="rtl">
			<h1>ربط الترجمة بين شيتين (<?php echo esc_html(strtoupper($src_lang)); ?> ⇄ <?php echo esc_html(strtoupper($target)); ?>)</h1>

			<p style="max-width:900px;">
				ارفع الشيت الإنجليزي لوحده، وارفع الشيت العربي لوحده (تقدر تكرر الرفع أكتر من مرة
				في كل ناحية والكورسات هتتجمّع مع بعض). لما العدد في الناحيتين يبقى متطابق، دوس
				<strong>«اربط الترجمة»</strong> وهيتعمل الربط بين كل كورس عربي والإنجليزي المقابل له
				في WPML، وكمان بين كل سيشن وسيشنه المقابل.
			</p>

			<?php if (! $wpml_on) : ?>
				<div class="notice notice-error inline">
					<p>⚠️ <strong>WPML مش مفعّل.</strong> تقدر ترفع الشيتات عادي، لكن زرار الربط مش هيربط أي حاجة لحد ما تفعّل WPML Multilingual CMS.</p>
				</div>
			<?php endif; ?>

			<div class="notice notice-info inline" style="max-width:900px;">
				<p style="margin:6px 0;">
					<strong>مهم:</strong> عمود <code>source_course_id</code> بيتم تجاهله في الصفحة دي --
					الربط هنا بيحصل من الزرار مش من الشيت. وعمود <code>lang</code> برضه بيتم تجاهله
					وبيتحدد من ناحية الرفع (يمين ولا شمال).
				</p>
			</div>

			<p>
				<a href="<?php echo esc_url($sample_url); ?>" class="button">⬇ تحميل نموذج CSV فاضي</a>
				<span class="cs-mini">(نفس أعمدة صفحة Import CSV -- سيب <code>lang</code> و <code>source_course_id</code> فاضيين، الصفحة دي بتتجاهلهم)</span>
			</p>

			<table class="form-table" style="max-width:600px;">
				<tr>
					<th><label for="cs-trlink-target">لغة الشيت التاني (الترجمة)</label></th>
					<td>
						<select id="cs-trlink-target">
							<?php foreach ($target_langs as $code => $label) : ?>
								<option value="<?php echo esc_attr($code); ?>" <?php selected($code, $target); ?>>
									<?php echo esc_html($label . ' (' . $code . ')'); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description">لغة المصدر متاخدة تلقائي من إعدادات WPML: <code><?php echo esc_html($src_lang); ?></code></p>
					</td>
				</tr>
			</table>

			<!-- ============ ناحيتين الرفع ============ -->
			<div class="cs-trlink-cols">
				<?php
				self::render_upload_box('src', 'الشيت الأول — ' . strtoupper($src_lang), '#2271b1');
				self::render_upload_box('trg', 'الشيت التاني — ' . strtoupper($target), '#00733c');
				?>
			</div>

			<!-- ============ المقارنة والربط ============ -->
			<h2 style="margin-top:34px;">المقارنة والربط</h2>

			<div id="cs-trlink-summary" class="cs-trlink-summary"></div>

			<div id="cs-trlink-pending-note"></div>

			<p>
				<button type="button" class="button" id="cs-trlink-refresh">🔄 تحديث الأرقام</button>
				<button type="button" class="button" id="cs-trlink-finish">⏩ كمّل توليد السيشنز الناقصة دلوقتي</button>
				<span class="cs-mini" id="cs-trlink-finish-note"></span>
			</p>

			<p>
				<label style="display:inline-flex;align-items:center;gap:6px;">
					<input type="checkbox" id="cs-trlink-copy" checked>
					<span>انسخ الهيكل والمواعيد والتكرار من الكورس الإنجليزي للعربي قبل الربط
						(بيضمن نفس عدد السيشنز؛ بيانات المكان والسعر الموجودة في الشيت العربي تفضل كما هي)</span>
				</label>
			</p>

			<div id="cs-trlink-table-wrap"></div>

			<div class="notice notice-info inline" style="max-width:900px;margin-top:24px;">
				<p style="margin-top:6px;">
					<strong>🛠 أدوات الصيانة</strong><br>
					<strong>الوضع الحالي: من غير كرون خالص.</strong> كل السيشنز بتتولّد وقتها
					بالعدد المضبوط (السنة كاملة، مش 90 يوم)، وكل سيشن بياخد لغته الصح لحظة
					ما بيتعمل. الزرارين دول للبيانات القديمة اللي اتعملت قبل التحديث ده، أو
					لو دخلت سنة جديدة.<br><br>
					لو في شاشة الكورسات لقيت عدد الإنجليزي مش مساوي العربي، أو لقيت
					<code>All</code> أكبر من <code>All languages</code> -- ده معناه إن فيه سيشنز
					اتولّدت في الخلفية وWPML داهم لغة الموقع الافتراضية بدل لغة الماستر بتاعهم
					(أو مداهمش لغة خالص). الزرار ده بيمشي على كل الكورسات ويصلّح أي سيشن
					لغته مش زي لغة الكورس بتاعه، ويربطه بالسيشن المقابل ليه لو الكورس ترجمة.
					تقدر تشغّله في أي وقت، ومش بيمسح أي حاجة.
				</p>
				<p>
					<button type="button" class="button" id="cs-trlink-repair">🩹 صلّح لغات السيشنز دلوقتي</button>
					<button type="button" class="button" id="cs-trlink-maint">🧹 صيانة: امسح القديم + كمّل الناقص</button>
					<span class="cs-mini" id="cs-trlink-repair-note"></span>
				</p>
				<div id="cs-trlink-repair-pw" style="display:none;max-width:860px;margin-bottom:10px;">
					<div style="background:#dcdcde;border-radius:4px;overflow:hidden;height:22px;">
						<div id="cs-trlink-repair-pb" style="height:100%;width:0;background:#8c5e00;color:#fff;font-size:11px;line-height:22px;text-align:center;transition:width .25s ease;">0%</div>
					</div>
				</div>
			</div>

			<p style="margin-top:16px;">
				<button type="button" class="button button-primary button-hero" id="cs-trlink-run" disabled>🔗 اربط الترجمة</button>
				<button type="button" class="button" id="cs-trlink-clear-all">🗑 فضّي اللستتين</button>
			</p>

			<div id="cs-trlink-progress-wrap" style="display:none;max-width:900px;margin-top:14px;">
				<div style="background:#dcdcde;border-radius:4px;overflow:hidden;height:26px;">
					<div id="cs-trlink-progress-bar" style="height:100%;width:0;background:#00733c;color:#fff;font-size:12px;line-height:26px;text-align:center;transition:width .25s ease;">0%</div>
				</div>
				<p id="cs-trlink-progress-text" style="margin:6px 0 0;color:#50575e;"></p>
			</div>

			<div id="cs-trlink-results" style="max-width:1000px;margin-top:14px;"></div>
		</div>

		<style>
			.cs-trlink .cs-trlink-cols {
				display: flex;
				gap: 18px;
				flex-wrap: wrap;
				margin-top: 18px;
			}

			.cs-trlink .cs-trlink-box {
				flex: 1 1 380px;
				background: #fff;
				border: 1px solid #c3c4c7;
				border-top-width: 4px;
				border-radius: 4px;
				padding: 14px 16px 18px;
			}

			.cs-trlink .cs-trlink-box h3 {
				margin: 0 0 10px;
			}

			.cs-trlink .cs-trlink-count {
				font-size: 34px;
				font-weight: 700;
				line-height: 1;
			}

			.cs-trlink .cs-trlink-summary {
				display: flex;
				gap: 14px;
				flex-wrap: wrap;
				align-items: center;
				margin: 10px 0 6px;
			}

			.cs-trlink .cs-badge {
				display: inline-block;
				padding: 6px 14px;
				border-radius: 999px;
				font-weight: 700;
			}

			.cs-trlink .cs-badge-ok {
				background: #d5f5e3;
				color: #0a5c36;
			}

			.cs-trlink .cs-badge-bad {
				background: #fde2e1;
				color: #8a1f11;
			}

			.cs-trlink table.cs-pairs {
				max-width: 1000px;
			}

			.cs-trlink table.cs-pairs td {
				vertical-align: middle;
			}

			.cs-trlink .cs-mini {
				color: #646970;
				font-size: 11px;
			}

			.cs-trlink .cs-row-bad {
				background: #fff4f4 !important;
			}
		</style>

		<script>
			(function() {
				var NONCE = '<?php echo esc_js($nonce); ?>';
				var SRC_LANG = '<?php echo esc_js($src_lang); ?>';
				var EDIT_BASE = '<?php echo esc_js(admin_url('post.php?action=edit&post=')); ?>';
				var stage = {
					src: [],
					trg: [],
					counts: {
						src: 0,
						trg: 0
					}
				};

				function esc(s) {
					var d = document.createElement('div');
					d.textContent = (s === null || s === undefined) ? '' : String(s);
					return d.innerHTML;
				}

				function post(action, fields, isForm) {
					var fd = isForm ? fields : new FormData();
					if (!isForm) {
						Object.keys(fields || {}).forEach(function(k) {
							fd.append(k, fields[k]);
						});
					}
					fd.append('action', action);
					fd.append('nonce', NONCE);
					return fetch(ajaxurl, {
							method: 'POST',
							body: fd,
							credentials: 'same-origin'
						})
						.then(function(r) {
							return r.json();
						});
				}

				/* ---------- رسم الجداول ---------- */

				function sideLabel(side) {
					return side === 'src' ? SRC_LANG.toUpperCase() : document.getElementById('cs-trlink-target').value.toUpperCase();
				}

				// خلية عدد السيشنز: بتوري العدد الفعلي مقابل العدد المفروض،
				// عشان تعرف فورًا لو فيه سيشنز ناقصة من غير ما تحسب بإيدك.
				function sessionsCell(c) {
					var exp = c.expected || 0;
					if (!exp) {
						return c.sessions;
					}
					if (c.sessions === exp) {
						return '<span style="color:#0a5c36;font-weight:600;">' + c.sessions + '</span> ' +
							'<span class="cs-mini">/ ' + exp + ' ✓</span>';
					}
					return '<span style="color:#8a1f11;font-weight:600;">' + c.sessions + '</span> ' +
						'<span class="cs-mini">/ ' + exp + ' (ناقص ' + (exp - c.sessions) + ')</span>';
				}

				function renderStage() {
					['src', 'trg'].forEach(function(side) {
						var box = document.getElementById('cs-trlink-list-' + side);
						var cnt = document.getElementById('cs-trlink-count-' + side);
						cnt.textContent = stage.counts[side];

						if (!stage[side].length) {
							box.innerHTML = '<p class="cs-mini">مفيش كورسات لسه في الناحية دي.</p>';
							return;
						}
						var h = '<table class="widefat striped"><thead><tr><th>#</th><th>العنوان</th><th>سيشنز</th><th>أماكن</th><th>لغة</th><th></th></tr></thead><tbody>';
						stage[side].forEach(function(c, i) {
							h += '<tr><td>' + (i + 1) + '</td>' +
								'<td><a href="' + EDIT_BASE + c.id + '" target="_blank">' + esc(c.title) + '</a> <span class="cs-mini">#' + c.id + '</span></td>' +
								'<td>' + sessionsCell(c) + '</td>' +
								'<td>' + c.locations + '</td>' +
								'<td>' + esc(c.lang || '-') + '</td>' +
								'<td><button type="button" class="button-link cs-trlink-remove" data-side="' + side + '" data-id="' + c.id + '" title="شيل من اللستة">✕</button></td></tr>';
						});
						h += '</tbody></table>';
						box.innerHTML = h;
					});

					renderSummary();
					renderPairs();
				}

				function renderSummary() {
					var s = stage.counts.src,
						t = stage.counts.trg;
					var ok = (s > 0 && s === t);
					var html = '<span class="cs-badge ' + (ok ? 'cs-badge-ok' : 'cs-badge-bad') + '">' +
						(ok ? '✅ العدد متطابق: ' + s + ' كورس في كل ناحية' :
							(s === 0 && t === 0 ? '⏳ لسه مفيش كورسات مرفوعة' :
								'❌ العدد مش متطابق: ' + s + ' مقابل ' + t)) + '</span>';
					if (!ok && (s || t)) {
						html += '<span class="cs-mini">لازم العددين يبقوا متساويين قبل ما تقدر تربط. شيل الزيادة أو ارفع الناقص.</span>';
					}
					document.getElementById('cs-trlink-summary').innerHTML = html;
					document.getElementById('cs-trlink-run').disabled = !ok;

					// تنبيه لو لسه فيه سيشنز في طابور التوليد الخلفي --
					// ده السبب الوحيد المعتاد لاختلاف عدد السيشنز بين
					// لغة والتانية حتى لو الشيتين متطابقين.
					// فيه كورسات سيشنزها ناقصة عن العدد المفروض؟
					var missing = 0;
					['src', 'trg'].forEach(function(side) {
						stage[side].forEach(function(c) {
							if (c.expected && c.sessions < c.expected) {
								missing += (c.expected - c.sessions);
							}
						});
					});
					if (missing > 0) {
						html += '<span class="cs-badge cs-badge-bad">⚠️ ناقص ' + missing + ' سيشن</span>';
					}
					document.getElementById('cs-trlink-summary').innerHTML = html;

					var note = document.getElementById('cs-trlink-pending-note');
					var p = stage.pending || 0;
					if (p > 0) {
						note.innerHTML = '<div class="notice notice-warning inline" style="max-width:900px;">' +
							'<p>⏳ لسه فيه <strong>' + p + '</strong> سيشن في طابور التوليد الخلفي. ' +
							'الأرقام اللي تحت مش نهائية وهتفضل تزيد لحد ما الطابور يخلص -- ' +
							'وده السبب الطبيعي لاختلاف عدد السيشنز بين اللغتين. ' +
							'دوس «كمّل توليد السيشنز الناقصة دلوقتي» بدل ما تستنى الكرون.</p></div>';
					} else {
						note.innerHTML = '';
					}
				}

				function renderPairs() {
					var wrap = document.getElementById('cs-trlink-table-wrap');
					if (!stage.counts.src || !stage.counts.trg) {
						wrap.innerHTML = '';
						return;
					}

					var n = Math.max(stage.counts.src, stage.counts.trg);
					var h = '<table class="widefat striped cs-pairs"><thead><tr>' +
						'<th style="width:40px;">#</th>' +
						'<th>الكورس (' + esc(SRC_LANG.toUpperCase()) + ')</th>' +
						'<th style="width:40px;text-align:center;">⇄</th>' +
						'<th>الترجمة المقابلة</th>' +
						'<th style="width:120px;">السيشنز</th>' +
						'<th style="width:90px;">الحالة</th>' +
						'</tr></thead><tbody>';

					for (var i = 0; i < n; i++) {
						var a = stage.src[i],
							b = stage.trg[i];
						var bad = (!a || !b);
						h += '<tr class="' + (bad ? 'cs-row-bad' : '') + '">';
						h += '<td>' + (i + 1) + '</td>';
						h += '<td>' + (a ? esc(a.title) + ' <span class="cs-mini">#' + a.id + ' · ' + a.sessions + ' سيشن</span>' : '<em class="cs-mini">فاضي</em>') + '</td>';
						h += '<td style="text-align:center;">⇄</td>';

						if (!stage.trg.length) {
							h += '<td><em class="cs-mini">فاضي</em></td>';
						} else {
							h += '<td><select class="cs-pair-select" data-index="' + i + '" style="max-width:420px;width:100%;">';
							h += '<option value="0">— مش مربوط —</option>';
							stage.trg.forEach(function(c) {
								var sel = (b && c.id === b.id) ? ' selected' : '';
								h += '<option value="' + c.id + '"' + sel + '>' + esc(c.title) + ' (#' + c.id + ' · ' + c.sessions + ' سيشن)</option>';
							});
							h += '</select></td>';
						}

						// عمود مقارنة عدد السيشنز -- مجرد مؤشر، مش مانع للربط:
						// زرار الربط بيولّد السنة كاملة للطرفين قبل المطابقة
						// فبيساويهم لوحده.
						var sess = '<span class="cs-mini">—</span>';
						if (a && b) {
							var same = (a.sessions === b.sessions);
							sess = '<span style="color:' + (same ? '#0a5c36' : '#996800') + ';">' +
								a.sessions + ' ⇄ ' + b.sessions + '</span>';
							if (!same) {
								sess += '<br><span class="cs-mini">هيتساووا بعد الربط</span>';
							}
						}
						h += '<td>' + sess + '</td>';

						var state = '<span class="cs-mini">—</span>';
						if (a && a.linked) {
							state = '<span style="color:#0a5c36;">مربوط ✓</span>';
						}
						h += '<td>' + state + '</td>';
						h += '</tr>';
					}
					h += '</tbody></table>';
					h += '<p class="cs-mini">الترتيب الافتراضي: أول كورس مع أول كورس. غيّر أي زوج من القايمة المنسدلة لو الترتيب مختلف.</p>';
					wrap.innerHTML = h;
				}

				function refreshStage() {
					return post('cs_trlink_stage_get', {
						target: document.getElementById('cs-trlink-target').value
					}).then(function(res) {
						if (res.success) {
							stage = res.data;
							renderStage();
						}
					});
				}

				/* ---------- الرفع ---------- */

				function bindUploader(side) {
					var form = document.getElementById('cs-trlink-form-' + side);
					var file = document.getElementById('cs-trlink-file-' + side);
					var btn = document.getElementById('cs-trlink-btn-' + side);
					var pw = document.getElementById('cs-trlink-pw-' + side);
					var pb = document.getElementById('cs-trlink-pb-' + side);
					var pt = document.getElementById('cs-trlink-pt-' + side);
					var out = document.getElementById('cs-trlink-out-' + side);

					function setP(done, total, label) {
						var pct = total > 0 ? Math.round((done / total) * 100) : 0;
						if (pct > 100) pct = 100;
						pb.style.width = pct + '%';
						pb.textContent = pct + '%';
						pt.textContent = label || ('اتعالج ' + done + ' من ' + total + ' صف...');
					}

					function fail(msg) {
						pw.style.display = 'none';
						btn.disabled = false;
						out.innerHTML = '<div class="notice notice-error inline"><p>' + esc(msg) + '</p></div>';
					}

					form.addEventListener('submit', function(e) {
						e.preventDefault();
						if (!file.files || !file.files.length) {
							return;
						}
						btn.disabled = true;
						out.innerHTML = '';
						pw.style.display = 'block';
						setP(0, 1, 'جارٍ رفع الملف...');

						var fd = new FormData();
						fd.append('side', side);
						fd.append('target', document.getElementById('cs-trlink-target').value);
						fd.append('cs_csv_file', file.files[0]);

						post('cs_trlink_import_start', fd, true).then(function(res) {
							if (!res.success) {
								fail((res.data && res.data.message) || 'خطأ غير متوقع.');
								return;
							}
							if (!res.data.total) {
								fail('مفيش صفوف صالحة في الملف.');
								return;
							}
							runBatch(res.data.token, 0, res.data.total, {
								created: 0,
								updated: 0,
								errors: []
							});
						}).catch(function(err) {
							fail('فشل الاتصال بالسيرفر: ' + err.message);
						});
					});

					function runBatch(token, offset, total, acc) {
						setP(offset, total);
						post('cs_trlink_import_batch', {
							token: token,
							offset: offset
						}).then(function(res) {
							if (!res.success) {
								fail((res.data && res.data.message) || 'خطأ غير متوقع أثناء الاستيراد.');
								return;
							}
							var d = res.data;
							acc.created += d.created;
							acc.updated += d.updated;
							acc.errors = acc.errors.concat(d.errors || []);

							if (d.phase === 'sessions') {
								setP(d.processed, d.total, 'بيتولّد السيشنز... ' + d.processed + ' من ' + d.total);
							} else {
								setP(d.processed, d.total);
							}

							if (d.done) {
								setP(d.total, d.total, 'تم الانتهاء ✓');
								btn.disabled = false;
								var h = '<div class="notice notice-success inline"><p>تم: <strong>' + acc.created +
									'</strong> جديد، <strong>' + acc.updated + '</strong> اتحدّث، أخطاء: <strong>' + acc.errors.length + '</strong>.</p></div>';
								if (acc.errors.length) {
									h += '<div class="notice notice-warning inline"><ul style="list-style:disc;margin-right:20px;">';
									acc.errors.forEach(function(e) {
										h += '<li>' + esc(e) + '</li>';
									});
									h += '</ul></div>';
								}
								out.innerHTML = h;
								file.value = '';
								refreshStage();
							} else {
								runBatch(token, d.next_offset, d.total, acc);
							}
						}).catch(function(err) {
							fail('فشل الاتصال بالسيرفر: ' + err.message);
						});
					}
				}

				bindUploader('src');
				bindUploader('trg');

				/* ---------- أزرار اللستة ---------- */

				document.addEventListener('click', function(e) {
					var rm = e.target.closest ? e.target.closest('.cs-trlink-remove') : null;
					if (rm) {
						e.preventDefault();
						post('cs_trlink_stage_update', {
							op: 'remove',
							side: rm.getAttribute('data-side'),
							id: rm.getAttribute('data-id'),
							target: document.getElementById('cs-trlink-target').value
						}).then(function(res) {
							if (res.success) {
								stage = res.data;
								renderStage();
							}
						});
						return;
					}

					var cl = e.target.closest ? e.target.closest('.cs-trlink-clear') : null;
					if (cl) {
						e.preventDefault();
						if (!confirm('متأكد إنك عايز تفضّي الناحية دي؟ (الكورسات مش هتتمسح من الموقع، بس هتخرج من لستة الربط)')) {
							return;
						}
						post('cs_trlink_stage_update', {
							op: 'clear',
							side: cl.getAttribute('data-side'),
							target: document.getElementById('cs-trlink-target').value
						}).then(function(res) {
							if (res.success) {
								stage = res.data;
								renderStage();
							}
						});
					}
				});

				document.getElementById('cs-trlink-clear-all').addEventListener('click', function(e) {
					e.preventDefault();
					if (!confirm('هيتفضّي اللستتين. الكورسات نفسها مش هتتمسح من الموقع. تمام؟')) {
						return;
					}
					post('cs_trlink_stage_update', {
						op: 'clear_all',
						target: document.getElementById('cs-trlink-target').value
					}).then(function(res) {
						if (res.success) {
							stage = res.data;
							renderStage();
						}
					});
				});

				document.getElementById('cs-trlink-refresh').addEventListener('click', function(e) {
					e.preventDefault();
					var b = this;
					b.disabled = true;
					refreshStage().then(function() {
						b.disabled = false;
					});
				});

				document.getElementById('cs-trlink-finish').addEventListener('click', function(e) {
					e.preventDefault();
					var b = this;
					var note = document.getElementById('cs-trlink-finish-note');
					b.disabled = true;

					function loop() {
						post('cs_trlink_drain', {
							target: document.getElementById('cs-trlink-target').value
						}).then(function(res) {
							if (!res.success) {
								note.textContent = 'حصل خطأ: ' + ((res.data && res.data.message) || '');
								b.disabled = false;
								return;
							}
							stage = res.data;
							renderStage();

							if (stage.pending > 0) {
								note.textContent = 'فاضل ' + stage.pending + ' سيشن...';
								loop();
							} else {
								note.textContent = 'خلص ✓ كل السيشنز اتولّدت.';
								b.disabled = false;
							}
						}).catch(function(err) {
							note.textContent = 'فشل الاتصال: ' + err.message;
							b.disabled = false;
						});
					}
					note.textContent = 'بيكمّل...';
					loop();
				});

				document.getElementById('cs-trlink-maint').addEventListener('click', function(e) {
					e.preventDefault();
					if (!confirm('هيتمسح السيشنز اللي تاريخها فات، وهيتكمّل أي سيشنز ناقصة لآخر السنة لكل الكورسات. تمام؟')) {
						return;
					}
					var b = this;
					var note = document.getElementById('cs-trlink-repair-note');
					var pw = document.getElementById('cs-trlink-repair-pw');
					var pb = document.getElementById('cs-trlink-repair-pb');
					b.disabled = true;
					pw.style.display = 'block';
					note.textContent = 'بيبدأ...';

					function setP(p) {
						if (p > 100) p = 100;
						pb.style.width = p + '%';
						pb.textContent = p + '%';
					}
					setP(0);

					post('cs_trlink_maint_start', {}).then(function(res) {
						if (!res.success) {
							note.textContent = (res.data && res.data.message) || 'خطأ';
							b.disabled = false;
							pw.style.display = 'none';
							return;
						}
						loop(res.data.token);
					}).catch(function(err) {
						note.textContent = 'فشل الاتصال: ' + err.message;
						b.disabled = false;
					});

					function loop(token) {
						post('cs_trlink_maint_batch', {
							token: token
						}).then(function(res) {
							if (!res.success) {
								note.textContent = (res.data && res.data.message) || 'خطأ';
								b.disabled = false;
								return;
							}
							var d = res.data;
							setP(d.percent);
							note.textContent = 'اتعمل ' + d.created + ' سيشن ناقص، واتمسح ' + d.deleted + ' قديم...';

							if (!d.done) {
								loop(token);
								return;
							}

							setP(100);
							b.disabled = false;
							note.textContent = '';
							document.getElementById('cs-trlink-results').innerHTML =
								'<div class="notice notice-success inline"><p>🧹 تمت الصيانة: اتعمل <strong>' + d.created +
								'</strong> سيشن ناقص، واتمسح <strong>' + d.deleted +
								'</strong> سيشن قديم. (اتفحص ' + d.total + ' كورس)</p></div>';
							refreshStage();
						}).catch(function(err) {
							note.textContent = 'فشل الاتصال: ' + err.message;
							b.disabled = false;
						});
					}
				});

				document.getElementById('cs-trlink-repair').addEventListener('click', function(e) {
					e.preventDefault();
					if (!confirm('هيتمشى على كل كورسات الموقع ويتصلّح لغة أي سيشن غلط. مفيش حاجة بتتمسح. تمام؟')) {
						return;
					}
					var b = this;
					var note = document.getElementById('cs-trlink-repair-note');
					var pw = document.getElementById('cs-trlink-repair-pw');
					var pb = document.getElementById('cs-trlink-repair-pb');
					b.disabled = true;
					pw.style.display = 'block';
					note.textContent = 'بيبدأ...';

					function setP(p) {
						if (p > 100) p = 100;
						pb.style.width = p + '%';
						pb.textContent = p + '%';
					}
					setP(0);

					post('cs_trlink_repair_start', {}).then(function(res) {
						if (!res.success) {
							note.textContent = (res.data && res.data.message) || 'خطأ';
							b.disabled = false;
							pw.style.display = 'none';
							return;
						}
						loop(res.data.token);
					}).catch(function(err) {
						note.textContent = 'فشل الاتصال: ' + err.message;
						b.disabled = false;
					});

					function loop(token) {
						post('cs_trlink_repair_batch', {
							token: token
						}).then(function(res) {
							if (!res.success) {
								note.textContent = (res.data && res.data.message) || 'خطأ';
								b.disabled = false;
								return;
							}
							var d = res.data;
							setP(d.percent);
							note.textContent = 'اتصلّح ' + d.fixed + ' سيشن (منهم ' + d.linked + ' اتربطوا بترجمتهم)...';

							if (!d.done) {
								loop(token);
								return;
							}

							setP(100);
							b.disabled = false;
							note.textContent = '';
							document.getElementById('cs-trlink-results').innerHTML =
								'<div class="notice notice-success inline"><p>🩹 تم الإصلاح: <strong>' + d.fixed +
								'</strong> سيشن اتظبطت لغته، منهم <strong>' + d.linked +
								'</strong> اتربطوا بالسيشن المقابل. (اتفحص ' + d.total + ' كورس)<br>' +
								'روح لشاشة الكورسات وحدّث الصفحة -- المفروض دلوقتي عدد الإنجليزي والعربي يبقى متساوي.</p></div>';
							refreshStage();
						}).catch(function(err) {
							note.textContent = 'فشل الاتصال: ' + err.message;
							b.disabled = false;
						});
					}
				});

				document.getElementById('cs-trlink-target').addEventListener('change', function() {
					post('cs_trlink_stage_update', {
						op: 'set_target',
						target: this.value
					}).then(function() {
						location.reload();
					});
				});

				/* ---------- الربط ---------- */

				function collectPairs() {
					var pairs = [];
					var used = {};
					var selects = document.querySelectorAll('.cs-pair-select');
					for (var i = 0; i < selects.length; i++) {
						var idx = parseInt(selects[i].getAttribute('data-index'), 10);
						var trgId = parseInt(selects[i].value, 10);
						var srcC = stage.src[idx];
						if (!srcC || !trgId) {
							continue;
						}
						if (used[trgId]) {
							return {
								error: 'الكورس رقم #' + trgId + ' متختار في أكتر من صف. كل ترجمة تتربط بكورس واحد بس.'
							};
						}
						used[trgId] = true;
						pairs.push([srcC.id, trgId]);
					}
					if (!pairs.length) {
						return {
							error: 'مفيش أي زوج متطابق للربط.'
						};
					}
					return {
						pairs: pairs
					};
				}

				var runBtn = document.getElementById('cs-trlink-run');
				var lpw = document.getElementById('cs-trlink-progress-wrap');
				var lpb = document.getElementById('cs-trlink-progress-bar');
				var lpt = document.getElementById('cs-trlink-progress-text');
				var lres = document.getElementById('cs-trlink-results');

				function setLP(pct, label) {
					if (pct > 100) pct = 100;
					lpb.style.width = pct + '%';
					lpb.textContent = pct + '%';
					lpt.textContent = label || '';
				}

				runBtn.addEventListener('click', function(e) {
					e.preventDefault();
					var got = collectPairs();
					if (got.error) {
						lres.innerHTML = '<div class="notice notice-error inline"><p>' + esc(got.error) + '</p></div>';
						return;
					}
					if (!confirm('هيتربط ' + got.pairs.length + ' كورس كترجمة (بما فيهم كل السيشنز). العملية دي بتعدّل بيانات WPML. تمام؟')) {
						return;
					}

					runBtn.disabled = true;
					lres.innerHTML = '';
					lpw.style.display = 'block';
					setLP(0, 'بيبدأ...');

					post('cs_trlink_link_start', {
						pairs: JSON.stringify(got.pairs),
						copy: document.getElementById('cs-trlink-copy').checked ? '1' : '0',
						target: document.getElementById('cs-trlink-target').value
					}).then(function(res) {
						if (!res.success) {
							lpw.style.display = 'none';
							runBtn.disabled = false;
							lres.innerHTML = '<div class="notice notice-error inline"><p>' + esc((res.data && res.data.message) || 'خطأ') + '</p></div>';
							return;
						}
						linkBatch(res.data.token);
					}).catch(function(err) {
						lpw.style.display = 'none';
						runBtn.disabled = false;
						lres.innerHTML = '<div class="notice notice-error inline"><p>فشل الاتصال: ' + esc(err.message) + '</p></div>';
					});
				});

				function linkBatch(token) {
					post('cs_trlink_link_batch', {
						token: token
					}).then(function(res) {
						if (!res.success) {
							runBtn.disabled = false;
							lres.innerHTML = '<div class="notice notice-error inline"><p>' + esc((res.data && res.data.message) || 'خطأ') + '</p></div>';
							return;
						}
						var d = res.data;
						setLP(d.percent, d.label);

						if (!d.done) {
							linkBatch(token);
							return;
						}

						setLP(100, 'تم الربط ✓');
						runBtn.disabled = false;

						var h = '<div class="notice notice-success inline"><p>تم ربط <strong>' + d.pairs_done +
							'</strong> كورس و <strong>' + d.sessions_done + '</strong> سيشن.</p></div>';
						if (d.log && d.log.length) {
							h += '<table class="widefat striped" style="max-width:1000px;"><thead><tr><th>الكورس الإنجليزي</th><th>الترجمة</th><th>سيشنز مربوطة</th><th>ملاحظات</th></tr></thead><tbody>';
							d.log.forEach(function(r) {
								h += '<tr><td>' + esc(r.src_title) + ' <span class="cs-mini">#' + r.src + '</span></td>' +
									'<td>' + esc(r.trg_title) + ' <span class="cs-mini">#' + r.trg + '</span></td>' +
									'<td>' + r.linked + ' / ' + r.total + '</td>' +
									'<td>' + esc(r.note || '') + '</td></tr>';
							});
							h += '</tbody></table>';
						}
						lres.innerHTML = h;
						refreshStage();
					}).catch(function(err) {
						runBtn.disabled = false;
						lres.innerHTML = '<div class="notice notice-error inline"><p>فشل الاتصال: ' + esc(err.message) + '</p></div>';
					});
				}

				refreshStage();
			})();
		</script>
<?php
	}

	/** بوكس رفع واحد (بيتنادى مرتين: للمصدر وللترجمة). */
	protected static function render_upload_box($side, $title, $color)
	{
?>
		<div class="cs-trlink-box" style="border-top-color:<?php echo esc_attr($color); ?>;">
			<h3><?php echo esc_html($title); ?></h3>

			<p>
				<span class="cs-trlink-count" id="cs-trlink-count-<?php echo esc_attr($side); ?>" style="color:<?php echo esc_attr($color); ?>;">0</span>
				<span class="cs-mini">كورس في اللستة</span>
			</p>

			<form id="cs-trlink-form-<?php echo esc_attr($side); ?>">
				<input type="file" id="cs-trlink-file-<?php echo esc_attr($side); ?>" accept=".csv" required>
				<button type="submit" class="button button-primary" id="cs-trlink-btn-<?php echo esc_attr($side); ?>">رفع واستيراد</button>
				<button type="button" class="button cs-trlink-clear" data-side="<?php echo esc_attr($side); ?>">تفضية</button>
			</form>

			<div id="cs-trlink-pw-<?php echo esc_attr($side); ?>" style="display:none;margin-top:12px;">
				<div style="background:#dcdcde;border-radius:4px;overflow:hidden;height:22px;">
					<div id="cs-trlink-pb-<?php echo esc_attr($side); ?>" style="height:100%;width:0;background:<?php echo esc_attr($color); ?>;color:#fff;font-size:11px;line-height:22px;text-align:center;transition:width .25s ease;">0%</div>
				</div>
				<p id="cs-trlink-pt-<?php echo esc_attr($side); ?>" class="cs-mini" style="margin:5px 0 0;"></p>
			</div>

			<div id="cs-trlink-out-<?php echo esc_attr($side); ?>"></div>
			<div id="cs-trlink-list-<?php echo esc_attr($side); ?>" style="margin-top:12px;"></div>
		</div>
<?php
	}

	/* ================= تخزين مؤقت للمهام (Jobs) ================= */

	protected static function job_path($token)
	{
		return self::import_tmp_dir() . '/' . preg_replace('/[^a-zA-Z0-9_\-]/', '', $token) . '.json';
	}

	protected static function job_save($token, $data)
	{
		return false !== @file_put_contents(self::job_path($token), wp_json_encode($data), LOCK_EX);
	}

	protected static function job_load($token)
	{
		$path = self::job_path($token);
		if (! file_exists($path)) {
			return false;
		}
		$json = @file_get_contents($path);
		if (false === $json || '' === $json) {
			return false;
		}
		$data = json_decode($json, true);
		return is_array($data) ? $data : false;
	}

	protected static function job_delete($token)
	{
		$path = self::job_path($token);
		if (file_exists($path)) {
			@unlink($path);
		}
	}

	/* ================= أجاكس: اللستة ================= */

	protected static function guard()
	{
		check_ajax_referer(self::LINK_NONCE, 'nonce');
		if (! current_user_can('edit_posts')) {
			wp_send_json_error(array('message' => 'غير مسموح.'));
		}
	}

	public static function ajax_stage_get()
	{
		self::guard();
		wp_send_json_success(self::stage_payload());
	}

	public static function ajax_stage_update()
	{
		self::guard();

		$op   = isset($_POST['op']) ? sanitize_key($_POST['op']) : '';
		$side = isset($_POST['side']) ? sanitize_key($_POST['side']) : '';
		$side = in_array($side, array('src', 'trg'), true) ? $side : '';

		$stage = self::get_stage();

		switch ($op) {
			case 'remove':
				$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
				if ($side && $id) {
					$stage[$side] = array_values(array_diff($stage[$side], array($id)));
					self::save_stage($stage);
				}
				break;

			case 'clear':
				if ($side) {
					$stage[$side] = array();
					self::save_stage($stage);
				}
				break;

			case 'clear_all':
				self::save_stage(array('src' => array(), 'trg' => array()));
				break;

			case 'set_target':
				$target = isset($_POST['target']) ? sanitize_key($_POST['target']) : '';
				$langs  = self::target_langs();
				if ($target && isset($langs[$target])) {
					update_option('cs_tr_target_lang', $target, false);
				}
				break;
		}

		wp_send_json_success(self::stage_payload());
	}

	/**
	 * بتكمّل توليد السيشنز الناقصة لكل الكورسات اللي في اللستتين، فورًا،
	 * من غير ما نستنى WP-Cron. ليه محتاجينها: بعد الرفع، جزء من السيشنز
	 * بيفضل في طابور خلفي بيتنفّذ على دفعات كل كام ثانية عن طريق الكرون --
	 * فلو بصيت على الأرقام في نص العملية بتلاقي كل لغة واقفة عند رقم
	 * مختلف (مش لأن فيه فرق في الشيتات، لأ لأن التوليد نفسه لسه شغال).
	 * الزرار ده بيصرّف الطابور بإيدينا لحد ما يخلص خالص.
	 */
	public static function ajax_drain()
	{
		try {
			self::guard();

			if (function_exists('set_time_limit')) {
				@set_time_limit(120);
			}

			$stage    = self::get_stage();
			$masters  = array_merge($stage['src'], $stage['trg']);
			$deadline = microtime(true) + 18;
			$done     = 0;

			if (class_exists('CS_Recurrence')) {
				while (microtime(true) < $deadline) {
					$target = 0;
					foreach ($masters as $mid) {
						if (CS_Recurrence::has_pending_queue($mid)) {
							$target = (int) $mid;
							break;
						}
					}
					if (! $target) {
						break;
					}

					$before = CS_Recurrence::pending_queue_count($target);
					CS_Recurrence::process_generate_sessions_batch($target);
					$after  = CS_Recurrence::pending_queue_count($target);

					$done += max(0, $before - $after);

					if ($after >= $before) {
						break; // مفيش تقدّم -- بلاش لفة فاضية.
					}
				}
			}

			$pending = 0;
			if (class_exists('CS_Recurrence')) {
				foreach ($masters as $mid) {
					$pending += CS_Recurrence::pending_queue_count($mid);
				}
			}

			$payload            = self::stage_payload();
			$payload['pending'] = $pending;
			$payload['drained'] = $done;

			wp_send_json_success($payload);
		} catch (\Throwable $e) {
			wp_send_json_error(array('message' => 'خطأ أثناء تكملة التوليد: ' . $e->getMessage()));
		}
	}

	/* ================= أجاكس: الاستيراد (لغة مفروضة) ================= */

	public static function ajax_import_start()
	{
		try {
			self::guard();

			$side = isset($_POST['side']) ? sanitize_key($_POST['side']) : '';
			if (! in_array($side, array('src', 'trg'), true)) {
				wp_send_json_error(array('message' => 'ناحية الرفع مش معروفة.'));
			}

			$lang = ('src' === $side) ? self::source_lang() : self::current_target_lang();

			if (empty($_FILES['cs_csv_file']['tmp_name']) || ! is_uploaded_file($_FILES['cs_csv_file']['tmp_name'])) {
				wp_send_json_error(array('message' => 'من فضلك اختار ملف CSV الأول.'));
			}

			$handle = fopen($_FILES['cs_csv_file']['tmp_name'], 'r');
			if (! $handle) {
				wp_send_json_error(array('message' => 'مقدرش أفتح الملف.'));
			}

			// شيل الـ BOM لو موجود (شيتات إكسيل العربي بتحطه).
			$bom = fread($handle, 3);
			if ($bom !== "\xEF\xBB\xBF") {
				rewind($handle);
			}

			$headers = fgetcsv($handle);
			if (! $headers) {
				fclose($handle);
				wp_send_json_error(array('message' => 'الملف فاضي أو مش CSV صحيح.'));
			}

			$headers = array_map(function ($h) {
				return strtolower(trim($h));
			}, $headers);

			$rows   = array();
			$errors = array();
			$line   = 1;

			while (($data = fgetcsv($handle)) !== false) {
				$line++;

				if (count(array_filter($data, function ($v) {
					return trim((string) $v) !== '';
				})) === 0) {
					continue;
				}

				$row = array();
				foreach ($headers as $i => $key) {
					$row[$key] = isset($data[$i]) ? trim($data[$i]) : '';
				}

				if (empty($row['title'])) {
					$errors[] = "صف {$line}: عمود title فاضي، اتخطّى.";
					continue;
				}

				// الصفحة دي بتتجاهل الربط اللي جاي من الشيت خالص -- اللغة
				// بتتحدد من ناحية الرفع، والربط بيحصل بالزرار بعدين.
				$row['lang'] = $lang;
				unset($row['source_course_id']);

				$rows[$line] = $row;
			}

			fclose($handle);

			if (empty($rows)) {
				wp_send_json_error(array('message' => 'مفيش أي صف صالح في الملف.', 'errors' => $errors));
			}

			$token = 'cs_trlink_' . wp_generate_password(20, false, false);

			$ok = self::job_save($token, array(
				'kind'            => 'import',
				'side'            => $side,
				'lang'            => $lang,
				'rows'            => $rows,
				'ids'             => array(),
				'touched_masters' => array(),
				'phase'           => 'rows',
				'sessions_total'  => 0,
				'sessions_done'   => 0,
			));

			if (! $ok) {
				wp_send_json_error(array('message' => 'مقدرش أخزّن الملف مؤقتًا على السيرفر (صلاحيات الكتابة على مجلد uploads).'));
			}

			wp_send_json_success(array(
				'token'  => $token,
				'total'  => count($rows),
				'errors' => $errors,
			));
		} catch (\Throwable $e) {
			wp_send_json_error(array(
				'message' => 'خطأ في السيرفر أثناء قراءة الملف: ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')',
			));
		}
	}

	public static function ajax_import_batch()
	{
		try {
			self::guard();

			if (function_exists('set_time_limit')) {
				@set_time_limit(120);
			}
			if (function_exists('ini_set')) {
				@ini_set('memory_limit', '256M');
			}

			$token  = isset($_POST['token']) ? sanitize_text_field(wp_unslash($_POST['token'])) : '';
			$offset = isset($_POST['offset']) ? max(0, (int) $_POST['offset']) : 0;

			$job = $token ? self::job_load($token) : false;
			if (! $job || empty($job['rows'])) {
				wp_send_json_error(array('message' => 'انتهت صلاحية جلسة الرفع. ارفع الملف تاني من فضلك.'));
			}

			/* ---- المرحلة التانية: استنى كل السيشنز تخلص فعلاً ---- */
			if ('sessions' === $job['phase']) {
				$deadline = microtime(true) + 20;

				while (microtime(true) < $deadline) {
					$master_id = 0;
					foreach ($job['touched_masters'] as $mid) {
						if (CS_Recurrence::has_pending_queue($mid)) {
							$master_id = (int) $mid;
							break;
						}
					}
					if (! $master_id) {
						break;
					}

					$before = CS_Recurrence::pending_queue_count($master_id);
					CS_Recurrence::process_generate_sessions_batch($master_id);
					$after  = CS_Recurrence::pending_queue_count($master_id);

					$job['sessions_done'] += max(0, $before - $after);

					if ($after >= $before) {
						break; // احتياطي ضد أي لفة لا نهائية.
					}
				}

				$still = false;
				foreach ($job['touched_masters'] as $mid) {
					if (CS_Recurrence::has_pending_queue($mid)) {
						$still = true;
						break;
					}
				}

				if (! $still) {
					self::stage_add($job['side'], $job['ids']);
					self::job_delete($token);
					wp_send_json_success(array(
						'created'     => 0,
						'updated'     => 0,
						'errors'      => array(),
						'processed'   => max((int) $job['sessions_total'], (int) $job['sessions_done']),
						'total'       => max((int) $job['sessions_total'], 1),
						'next_offset' => 0,
						'done'        => true,
						'phase'       => 'sessions',
					));
				}

				self::job_save($token, $job);

				wp_send_json_success(array(
					'created'     => 0,
					'updated'     => 0,
					'errors'      => array(),
					'processed'   => min((int) $job['sessions_done'], (int) $job['sessions_total']),
					'total'       => max((int) $job['sessions_total'], 1),
					'next_offset' => 0,
					'done'        => false,
					'phase'       => 'sessions',
				));
			}

			/* ---- المرحلة الأولى: صفوف الشيت ---- */
			$all_lines = array_keys($job['rows']);
			$total     = count($all_lines);
			$batch     = array_slice($all_lines, $offset, self::ROWS_BATCH);

			$created = 0;
			$updated = 0;
			$errors  = array();

			foreach ($batch as $line) {
				$row    = $job['rows'][$line];
				$title  = $row['title'];
				$result = self::import_row($row, $line);

				if (is_wp_error($result)) {
					$errors[] = "صف {$line} ({$title}): " . $result->get_error_message();
					continue;
				}

				if ('created' === $result['status']) {
					$created++;
				} else {
					$updated++;
				}

				if (! empty($result['image_warning'])) {
					$errors[] = "صف {$line} ({$title}): الكورس اتحفظ بس فيه مشكلة في الصورة -- " . $result['image_warning'];
				}

				$pid = (int) $result['post_id'];
				if ($pid && ! in_array($pid, $job['ids'], true)) {
					$job['ids'][] = $pid;
				}
				if ($pid && ! in_array($pid, $job['touched_masters'], true)) {
					$job['touched_masters'][] = $pid;
				}
			}

			$next_offset = $offset + count($batch);

			if ($next_offset < $total) {
				self::job_save($token, $job);
				wp_send_json_success(array(
					'created'     => $created,
					'updated'     => $updated,
					'errors'      => $errors,
					'processed'   => $next_offset,
					'total'       => $total,
					'next_offset' => $next_offset,
					'done'        => false,
					'phase'       => 'rows',
				));
			}

			// خلصنا الصفوف -- شوف لسه فيه سيشنز في الطابور ولا لأ.
			$sessions_total = 0;
			foreach ($job['touched_masters'] as $mid) {
				$sessions_total += CS_Recurrence::pending_queue_count($mid);
			}

			if (0 === $sessions_total) {
				self::stage_add($job['side'], $job['ids']);
				self::job_delete($token);
				wp_send_json_success(array(
					'created'     => $created,
					'updated'     => $updated,
					'errors'      => $errors,
					'processed'   => $next_offset,
					'total'       => $total,
					'next_offset' => $next_offset,
					'done'        => true,
					'phase'       => 'rows',
				));
			}

			$job['phase']          = 'sessions';
			$job['sessions_total'] = $sessions_total;
			$job['sessions_done']  = 0;
			self::job_save($token, $job);

			wp_send_json_success(array(
				'created'     => $created,
				'updated'     => $updated,
				'errors'      => $errors,
				'processed'   => 0,
				'total'       => $sessions_total,
				'next_offset' => 0,
				'done'        => false,
				'phase'       => 'sessions',
			));
		} catch (\Throwable $e) {
			wp_send_json_error(array(
				'message' => 'خطأ في السيرفر أثناء معالجة الصفوف: ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')',
			));
		}
	}

	/* ================= أجاكس: الربط ================= */

	public static function ajax_link_start()
	{
		try {
			self::guard();

			$raw   = isset($_POST['pairs']) ? wp_unslash($_POST['pairs']) : '';
			$pairs = json_decode($raw, true);

			if (! is_array($pairs) || empty($pairs)) {
				wp_send_json_error(array('message' => 'مفيش أزواج للربط.'));
			}

			$clean = array();
			$stage = self::get_stage();
			$allowed_src = array_fill_keys(array_map('intval', $stage['src']), true);
			$allowed_trg = array_fill_keys(array_map('intval', $stage['trg']), true);

			foreach ($pairs as $p) {
				$src = isset($p[0]) ? absint($p[0]) : 0;
				$trg = isset($p[1]) ? absint($p[1]) : 0;
				if (! $src || ! $trg || $src === $trg) {
					continue;
				}

				// The pair picker is populated directly from get_stage()/stage_payload().
				// Validate against that same server-side stage first. This avoids a
				// false "all pairs invalid" when WPML changes the visible post type
				// context/filter during an AJAX request. We still verify that both IDs
				// are real Course System posts.
				if (! isset($allowed_src[$src]) || ! isset($allowed_trg[$trg])) {
					continue;
				}
				if (! get_post($src) || ! get_post($trg)) {
					continue;
				}
				if (get_post_field('post_type', $src) !== CS_CPT || get_post_field('post_type', $trg) !== CS_CPT) {
					continue;
				}

				$clean[] = array($src, $trg);
			}

			if (empty($clean)) {
				wp_send_json_error(array('message' => 'كل الأزواج غير صالحة (كورسات مش موجودة أو نفس الـ ID).'));
			}

			$target = isset($_POST['target']) ? sanitize_key($_POST['target']) : self::current_target_lang();
			$langs  = self::target_langs();
			if (! isset($langs[$target])) {
				$target = self::current_target_lang();
			}

			$token = 'cs_trlink_job_' . wp_generate_password(20, false, false);

			$ok = self::job_save($token, array(
				'kind'          => 'link',
				'pairs'         => $clean,
				'lang'          => $target,
				'copy'          => ! empty($_POST['copy']) && '1' === $_POST['copy'],
				'i'             => 0,
				'phase'         => 'prep',
				'sess_offset'   => 0,
				'pair_linked'   => 0,
				'pair_total'    => 0,
				'sessions_done' => 0,
				'src_index'     => array(),
				'log'           => array(),
			));

			if (! $ok) {
				wp_send_json_error(array('message' => 'مقدرش أخزّن بيانات المهمة على السيرفر.'));
			}

			wp_send_json_success(array('token' => $token, 'total' => count($clean)));
		} catch (\Throwable $e) {
			wp_send_json_error(array('message' => 'خطأ: ' . $e->getMessage()));
		}
	}

	/**
	 * بتنفّذ شوية شغل (حوالي 15 ثانية) من مهمة الربط وبترجّع نسبة التقدّم.
	 * الجافاسكريبت بينادي الدالة دي ورا بعضها لحد ما ترجّع done = true.
	 *
	 * مراحل كل زوج:
	 *   prep     -> نسخ المواعيد/الأسعار (اختياري) + توليد سيشنز الترجمة.
	 *   gen      -> استنى طابور توليد السيشنز يخلص بالكامل.
	 *   sessions -> اربط كل سيشن بالسيشن المقابل له، دفعة دفعة.
	 */
	public static function ajax_link_batch()
	{
		try {
			self::guard();

			if (function_exists('set_time_limit')) {
				@set_time_limit(120);
			}
			if (function_exists('ini_set')) {
				@ini_set('memory_limit', '256M');
			}

			$token = isset($_POST['token']) ? sanitize_text_field(wp_unslash($_POST['token'])) : '';
			$job   = $token ? self::job_load($token) : false;

			if (! $job || empty($job['pairs'])) {
				wp_send_json_error(array('message' => 'انتهت صلاحية مهمة الربط. ابدأ من تاني.'));
			}

			$wpml     = class_exists('CS_WPML') && CS_WPML::active();
			$total    = count($job['pairs']);
			$deadline = microtime(true) + 15;
			$label    = '';

			while (microtime(true) < $deadline) {

				if ($job['i'] >= $total) {
					$job['phase'] = 'done';
					break;
				}

				$pair = $job['pairs'][$job['i']];
				$src  = (int) $pair[0];
				$trg  = (int) $pair[1];

				/* ---- prep ---- */
				if ('prep' === $job['phase']) {
					// امنع كرون التاجينج القديم من الاشتغال على الكورس ده --
					// إحنا هنعمل الربط بنفسنا هنا بشكل مضمون.
					delete_post_meta($trg, '_cs_pending_wpml_lang');
					delete_post_meta($trg, '_cs_pending_wpml_source');

					// حارس مؤقت (نص ساعة) بيمنع كرون CS_WPML من إنه
					// يشتغل على نفس الكورس وإحنا بنربطه يدوي -- الربط
					// اليدوي أدق لأنه بيربط سيشن بسيشن، والكرون كان ممكن
					// يستبدله بمجرد set_language. شوف
					// CS_WPML::tag_sessions_language_batch().
					set_transient('cs_manual_link_' . $trg, 1, 30 * MINUTE_IN_SECONDS);

					if (! empty($job['copy'])) {
						self::copy_structure($src, $trg);
					}

					// ⚠️ أهم حتة في الربط كله: بنولّد سيشنز *الاتنين* (الأصل
					// والترجمة) بنفس السنة الصريحة.
					//
					// من غير الخطوة دي، كل كورس بيتولّد بسقف "90 يوم من
					// النهاردة" وقت الرفع، والباقي بيكمّله كرون الصيانة
					// اليومي في الخلفية على دفعات. يعني لو رفعت الشيت
					// الإنجليزي الساعة 2 والعربي الساعة 3، كل واحد فيهم
					// بيبقى واقف عند رقم مختلف في اللحظة اللي بتبص فيها --
					// وده اللي كان بيخلي الأعداد تطلع 530 و 490 و 569 مع
					// إن الشيتين متطابقين تمامًا في التاريخ والتكرار وعدد
					// الأماكن. تمرير سنة صريحة بيلغي سقف الـ 90 يوم ويولّد
					// السنة كاملة للاتنين، فالعدد بيطلع متساوي ومحسوم.
					if (class_exists('CS_Recurrence')) {
						$year = self::generation_year($src);
						CS_Recurrence::generate_sessions($src, $year);
						CS_Recurrence::generate_sessions($trg, $year);
					}

					$job['phase'] = 'gen';
					continue;
				}

				/* ---- gen: استنى السيشنز تكمل ---- */
				if ('gen' === $job['phase']) {
					// بنستنى طابور التوليد بتاع *الاتنين* يخلص بالكامل قبل
					// ما نبدأ نطابق -- لو الأصل لسه بيولّد سيشنز، هنبني
					// فهرس ناقص وهيبقى فيه سيشنز عربي ملقتش مقابل.
					$still = 0;
					if (class_exists('CS_Recurrence')) {
						foreach (array($src, $trg) as $mid) {
							if (! CS_Recurrence::has_pending_queue($mid)) {
								continue;
							}
							$before = CS_Recurrence::pending_queue_count($mid);
							CS_Recurrence::process_generate_sessions_batch($mid);
							$after  = CS_Recurrence::pending_queue_count($mid);
							$still += $after;
							if ($after < $before) {
								$still = -1; // فيه تقدّم فعلي، كمّل.
								break;
							}
						}
					}

					if (-1 === $still) {
						$label = 'بيتولّد سيشنز الكورس ' . ($job['i'] + 1) . ' من ' . $total . '...';
						continue;
					}

					// اربط الماستر نفسه كترجمة.
					if ($wpml) {
						CS_WPML::link_translation($trg, $src, $job['lang']);
					}

					$job['src_index']   = self::build_source_index($src);
					$job['sess_offset'] = 0;
					$job['pair_linked'] = 0;
					$job['pair_total']  = 0;
					$job['phase']       = 'sessions';
					continue;
				}

				/* ---- sessions ---- */
				if ('sessions' === $job['phase']) {
					$res = self::link_sessions_chunk($trg, $job['src_index'], $job['lang'], (int) $job['sess_offset']);

					$job['sess_offset']   += $res['count'];
					$job['pair_linked']   += $res['linked'];
					$job['pair_total']    += $res['count'];
					$job['sessions_done'] += $res['linked'];

					$label = 'بيربط سيشنز الكورس ' . ($job['i'] + 1) . ' من ' . $total .
						' (' . $job['pair_total'] . ' سيشن لحد دلوقتي)';

					if ($res['count'] >= self::SESS_BATCH) {
						continue; // لسه فيه سيشنز.
					}

					// خلص الزوج ده.
					$note = '';
					if (! $wpml) {
						$note = 'WPML مش مفعّل -- مفيش ربط فعلي حصل.';
					} elseif ($job['pair_total'] && $job['pair_linked'] < $job['pair_total']) {
						$note = 'فيه سيشنز ملقتش ليها مقابل بنفس التاريخ/المكان -- اتحطلها اللغة بس.';
					} elseif (0 === $job['pair_total']) {
						$note = 'الكورس ده مالوش سيشنز.';
					}

					$job['log'][] = array(
						'src'       => $src,
						'trg'       => $trg,
						'src_title' => self::raw_title($src),
						'trg_title' => self::raw_title($trg),
						'linked'    => (int) $job['pair_linked'],
						'total'     => (int) $job['pair_total'],
						'note'      => $note,
					);

					delete_transient('cs_src_sessions_' . $src);

					// سيبنا علامة الربط على الكورس المترجم: لو الكرون
					// اليومي ولّد سيشنز جديدة للكورس ده بعدين (زي سيشنز
					// السنة الجاية)، CS_WPML هيلاقي العلامة دي ويربط
					// الجديد بالمقابل الإنجليزي أوتوماتيك من غير ما
					// ترجع تدوس الزرار تاني.
					if ($wpml) {
						update_post_meta($trg, '_cs_pending_wpml_lang', $job['lang']);
						update_post_meta($trg, '_cs_pending_wpml_source', $src);
					}

					$job['i']++;
					$job['phase']     = 'prep';
					$job['src_index'] = array();
					continue;
				}

				break;
			}

			$done    = ($job['i'] >= $total);
			$percent = $total > 0 ? (int) round(($job['i'] / $total) * 100) : 100;

			if ($done) {
				$log     = $job['log'];
				$sess    = (int) $job['sessions_done'];
				self::job_delete($token);

				wp_send_json_success(array(
					'done'          => true,
					'percent'       => 100,
					'label'         => 'تم الربط ✓',
					'pairs_done'    => $total,
					'sessions_done' => $sess,
					'log'           => $log,
				));
			}

			self::job_save($token, $job);

			wp_send_json_success(array(
				'done'          => false,
				'percent'       => $percent,
				'label'         => $label ? $label : ('بيشتغل على الكورس ' . ($job['i'] + 1) . ' من ' . $total . '...'),
				'pairs_done'    => (int) $job['i'],
				'sessions_done' => (int) $job['sessions_done'],
				'log'           => array(),
			));
		} catch (\Throwable $e) {
			wp_send_json_error(array(
				'message' => 'خطأ أثناء الربط: ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')',
			));
		}
	}

	/* ================= شغل الربط الفعلي ================= */

	/**
	 * السنة اللي هنولّد لحد آخرها. بنمرّرها صراحةً لـ generate_sessions()
	 * عشان نلغي سقف الـ 90 يوم اللي بيتطبق وقت الرفع العادي -- شوف الشرح
	 * الطويل جوه مرحلة prep. بناخد سنة تاريخ بداية الكورس (لو في المستقبل)
	 * أو السنة الحالية، أيهما أكبر.
	 */
	protected static function generation_year($master_id)
	{
		$base = get_field('cs_base_start_date', $master_id);
		$ts   = $base ? strtotime($base) : 0;
		$year = $ts ? (int) gmdate('Y', $ts) : 0;
		$now  = (int) current_time('Y');

		return $year > $now ? $year : $now;
	}

	/**
	 * بينسخ "الهيكل" بس من الكورس الأصلي للترجمة: تاريخ البداية، نوع
	 * التكرار، عدد الساعات، والأسعار/العملات -- من غير ما يلمس أي نص
	 * مترجم (الملخص، الأهداف، الجدول، وضع الحضور...).
	 *
	 * ⚠️ أسماء الأماكن: لو الترجمة عندها نفس عدد الأماكن، بنسيب اسم
	 * المكان العربي زي ما هو (مثلاً "القاهرة") وبنجيب السعر والعملة من
	 * الإنجليزي بس. كده الترتيب (loc_index) بيفضل متطابق في اللغتين، وده
	 * هو المفتاح اللي بنربط بيه السيشنز -- من غير ما نضطر نستبدل الاسم
	 * العربي باسم إنجليزي.
	 */
	protected static function copy_structure($src_id, $trg_id)
	{
		foreach (array('cs_base_start_date', 'cs_recurrence', 'cs_duration_hours') as $field) {
			$value = get_field($field, $src_id);
			if (null !== $value && '' !== $value) {
				update_field($field, $value, $trg_id);
			}
		}

		$src_rows = get_field('cs_country_prices', $src_id);
		$trg_rows = get_field('cs_country_prices', $trg_id);

		if (is_array($src_rows) && $src_rows) {
			$out = array();
			foreach ($src_rows as $i => $row) {
				$source_row = array(
					'country'  => isset($row['country']) ? $row['country'] : '',
					'price'    => isset($row['price']) ? $row['price'] : 0,
					'currency' => isset($row['currency']) && $row['currency'] !== '' ? $row['currency'] : 'USD',
				);

				// بيانات الترجمة الموجودة في الشيت الهدف هي المصدر الموثوق.
				// زر النسخ يضمن فقط الهيكل/عدد الأماكن؛ لا يستبدل السعر أو اسم
				// المدينة العربية ببيانات الإنجليزي إذا كانت موجودة بالفعل.
				if (
					is_array($trg_rows)
					&& isset($trg_rows[$i])
					&& is_array($trg_rows[$i])
				) {
					$target_row = $trg_rows[$i];
					$out[] = array(
						'country'  => (isset($target_row['country']) && '' !== trim((string) $target_row['country'])) ? $target_row['country'] : $source_row['country'],
						'price'    => (isset($target_row['price']) && '' !== (string) $target_row['price']) ? $target_row['price'] : $source_row['price'],
						'currency' => (isset($target_row['currency']) && '' !== trim((string) $target_row['currency'])) ? $target_row['currency'] : $source_row['currency'],
					);
				} else {
					$out[] = $source_row;
				}
			}
			update_field('cs_country_prices', $out, $trg_id);
		}

		// صورة الكورس وملف الـ PDF: بننسخهم بس لو الترجمة مالهاش واحدة أصلاً.
		if (! has_post_thumbnail($trg_id)) {
			$thumb = get_post_thumbnail_id($src_id);
			if ($thumb) {
				set_post_thumbnail($trg_id, $thumb);
			}
		}

		if (! get_field('cs_outline_pdf', $trg_id)) {
			$pdf = get_field('cs_outline_pdf', $src_id);
			if ($pdf) {
				update_field('cs_outline_pdf', $pdf, $trg_id);
			}
		}
	}

	/**
	 * فهرس سيشنز الكورس الأصلي عشان نلاقي بسرعة المقابل لكل سيشن مترجم.
	 * بنبني تلات مفاتيح بالترتيب من الأدق للأقل دقة:
	 *   loc  -> "تاريخ|ترتيب المكان"  (المطابقة الموثوقة، مش متأثرة بترجمة اسم المكان)
	 *   ctry -> "تاريخ|اسم المكان"    (احتياطي: بيفيد لو الاسم متطابق في اللغتين)
	 *   date -> "تاريخ" لوحده         (احتياطي أخير، وبيتستخدم بس لو التاريخ ده
	 *                                  عنده سيشن واحد بالظبط في الأصل -- يعني
	 *                                  مفيش أي لبس. بيغطّي الكورسات القديمة
	 *                                  اللي مالهاش ترتيب مكان متسجّل أصلاً.)
	 */
	protected static function build_source_index($src_master_id)
	{
		$index = array('loc' => array(), 'ctry' => array(), 'date' => array());
		$date_count = array();

		$sessions = get_posts(array(
			'post_type'      => CS_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => CS_META_MASTER,
			'meta_value'     => (int) $src_master_id,
		));

		foreach ($sessions as $sid) {
			$date      = get_post_meta($sid, CS_META_DATE, true);
			$country   = get_post_meta($sid, CS_META_COUNTRY, true);
			$loc_index = get_post_meta($sid, CS_META_LOC_INDEX, true);

			if ('' !== $loc_index) {
				$index['loc'][$date . '|' . (int) $loc_index] = (int) $sid;
			}
			$index['ctry'][$date . '|' . $country] = (int) $sid;

			$date_count[$date] = isset($date_count[$date]) ? $date_count[$date] + 1 : 1;
			$index['date'][$date] = (int) $sid;
		}

		// التواريخ اللي عندها أكتر من سيشن (يعني أكتر من مكان في نفس اليوم)
		// مينفعش نطابق بيها بالتاريخ لوحده -- هنبقى بنخمّن. شيلها.
		foreach ($date_count as $date => $n) {
			if ($n > 1) {
				unset($index['date'][$date]);
			}
		}

		return $index;
	}

	/**
	 * بتربط دفعة واحدة (SESS_BATCH) من سيشنز الكورس المترجم بالسيشنز
	 * المقابلة. بترجّع كام سيشن اتفحص فعلاً وكام واحد لقى مقابل واتربط.
	 */
	protected static function link_sessions_chunk($trg_master_id, $index, $lang, $offset)
	{
		$sessions = get_posts(array(
			'post_type'      => CS_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => self::SESS_BATCH,
			'offset'         => (int) $offset,
			'fields'         => 'ids',
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'meta_key'       => CS_META_MASTER,
			'meta_value'     => (int) $trg_master_id,
		));

		$linked = 0;
		$wpml   = class_exists('CS_WPML') && CS_WPML::active();

		foreach ($sessions as $sid) {
			$match = self::match_source_session($sid, $index);

			if (! $wpml) {
				continue;
			}

			if ($match && $match !== (int) $sid) {
				CS_WPML::link_translation($sid, $match, $lang);
				$linked++;
			} else {
				// مفيش مقابل -- على الأقل حدّد لغته عشان الفلترة تشتغل صح.
				CS_WPML::set_language($sid, $lang);
			}
		}

		return array(
			'count'  => count($sessions),
			'linked' => $linked,
		);
	}
}

CS_Translation_Link::init();
