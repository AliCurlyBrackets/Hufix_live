<?php

/**
 * Feature: CSV Import
 * صفحة أدمن تحت مينيو Courses اسمها "Import CSV" بترفع شيت وتنشئ/تحدّث
 * كورسات (Master) بالظبط بنفس أسماء حقول ACF الموجودة في class-cs-acf.php.
 * بعد ما يتسجل كل كورس، بننادي CS_Recurrence::generate_sessions() يدوي
 * (نفس اللي بيحصل لما تحفظ الكورس من الشاشة العادية).
 *
 * أعمدة الشيت المتوقعة (بالترتيب ده أو بأي ترتيب، العبرة بالاسم في أول صف):
 *
 * title              - اسم الكورس (مطلوب)
 * category           - اسم الكاتيجوري (لو مش موجود هيتعمل تلقائي)، أو ممكن
 *                        تكتب term_id بتاع كاتيجوري موجودة بالفعل بدل الاسم
 *                        (رقم صريح بس، زي 42) -- ده بيتفادى مشكلة عدم تطابق
 *                        الاسم حرفيًا بين شيتي عربي/إنجليزي (فروق مسافات، نوع
 *                        الفاصلة، حروف مخفية من النسخ/اللصق..الخ). لقيمة الـ
 *                        term_id: Courses > Categories في لوحة التحكم، بص على
 *                        رابط "Edit" لأي كاتيجوري وهتلاقي tag_ID=XX في اللينك.
 * summary            - الوصف اللي بيظهر تحت العنوان في صفحة السنجل كورس
 * delivery_mode      - وضع أو أكتر مفصولين بـ / . مثال: Hybrid / Online / In-Person
 *                        (مش لازم قايمة محددة -- اكتب أي وضع عايزه)
 * language           - لغة أو أكتر مفصولين بـ / . مثال: English / Arabic / French
 *                        (مش لازم قايمة محددة -- اكتب أي لغة عايزها)
 * duration_hours     - رقم
 * base_start_date    - تاريخ أول سيشن بصيغة YYYY-MM-DD
 * recurrence         - weekly / biweekly (عدد أيام الكورس بيتحدد أوتوماتيك منها:
 *                        weekly = 5 أيام شغل، biweekly = 10 أيام شغل. مفيش عمود
 *                        منفصل لعدد الأيام، والسبت والأحد مالهومش وجود خالص)
 * countries_prices   - Cairo:1500:USD|Alex:800:USD|Mansoura:900:USD
 *                        (مكان:سعر:عملة مفصولين بـ |). المكان نص حر بالكامل --
 *                        ممكن يبقى اسم دولة ("Egypt") أو مدينة ("Cairo") أو أي
 *                        حاجة تانية، اكتبه زي ما انت عايز، مش لازم يكون من قايمة
 *                        دول محددة.
 * objectives         - Objective 1|Objective 2|Objective 3
 * schedule           - Day 1 Title: Point A;Point B||Day 2 Title: Point C;Point D
 * outline_pdf_url     - رابط ملف PDF لو موجود بالفعل في مكتبة الميديا (اختياري)
 * image_url           - رابط صورة الكورس (اختياري). لو موجود هيتنزّل ويتحط
 *                        كصورة مميزة (Featured Image) للكورس، وهيظهر في الكارت
 *                        وفي كل السيشنز المتولدة منه أوتوماتيك. لو العمود فاضي،
 *                        الكارت هيتعرض عادي من غير صورة.
 * course_id           - لو موجود، هيعدّل الكورس ده بدل ما يعمل واحد جديد (اختياري)
 */

if (! defined('ABSPATH')) {
	exit;
}

/**
 * "English / Arabic / French" أو "Hybrid / Online / In-Person" -> نفس
 * النص بعد تنضيف المسافات الزيادة حوالين كل قيمة. بيسيب النص زي ما
 * اتكتب بالظبط -- مفيش قايمة قيم محددة، اكتب أي حاجة عايزها مفصولة بـ "/".
 * مستخدمة لكل من delivery_mode و language.
 */
function cs_sanitize_multi_value($raw)
{
	$parts = array_map('trim', explode('/', $raw));
	$parts = array_filter($parts, function ($p) {
		return '' !== $p;
	});
	$parts = array_map('sanitize_text_field', $parts);
	return implode(' / ', $parts);
}

class CS_Import
{

	const NONCE_ACTION = 'cs_import_csv';

	// نونس الأجاكس بتاع الرفع على دفعات (منفصل عن نونس الفورم العادي).
	const AJAX_NONCE_ACTION = 'cs_import_ajax';

	// عدد الصفوف اللي بتتعالج في كل طلب أجاكس. صف واحد بس عمدًا لأن كل
	// صف ممكن يتضمن تنزيل صورة من الإنترنت (أبطأ حاجة في العملية)، فبنفضّل
	// نطلب دفعات صغيرة جدًا بدل طلب واحد كبير ممكن يضرب مهلة تنفيذ
	// السيرفر (php-fpm/nginx) اللي بتقفل الطلب بالقوة من غير ما تدّي
	// فرصة لكودنا يرجّع رسالة خطأ واضحة.
	const BATCH_SIZE = 1;

	/**
	 * ليه مش بنخزّن صفوف الشيت في transient عادي (زي ما كان الكود قبل كده):
	 * set_transient()/get_transient() بتعتمد على أي object cache خارجي
	 * (Redis/Memcached) لو الاستضافة مفعّلاه. لو الـ object cache ده مش
	 * مظبوط صح (شائع جدًا في استضافات مشتركة/متعددة السيرفرات)، ممكن
	 * تتخزن في طلب وتضيع قبل ما الطلب اللي بعده (اللي بييجي فورًا) يلاقيها
	 * -- وده كان بيظهر بالظبط كرسالة "انتهت صلاحية جلسة الرفع" حتى لو
	 * أول مرة بتوت الملف. عشان كده بنخزنها في ملف فعلي على الديسك بدل
	 * كده -- أكثر ثباتًا وميعتمدش على أي كاش خارجي.
	 */
	protected static function import_tmp_dir()
	{
		$upload_dir = wp_upload_dir();
		$dir        = trailingslashit($upload_dir['basedir']) . 'cs-import-tmp';

		if (! file_exists($dir)) {
			wp_mkdir_p($dir);
		}
		// امنع الوصول المباشر للملفات من برا (سيرفرات Apache/LiteSpeed).
		if (! file_exists($dir . '/.htaccess')) {
			@file_put_contents($dir . '/.htaccess', "Deny from all\n");
		}
		if (! file_exists($dir . '/index.php')) {
			@file_put_contents($dir . '/index.php', "<?php\n// Silence is golden.\n");
		}

		return $dir;
	}

	protected static function import_tmp_path($token)
	{
		// $token اتعمل بـ wp_generate_password(20, false, false) -- حروف
		// وأرقام بس، فآمن يتحط في اسم ملف من غير أي تنضيف إضافي، بس
		// بنتأكد على أي حال.
		return self::import_tmp_dir() . '/' . preg_replace('/[^a-zA-Z0-9_\-]/', '', $token) . '.json';
	}

	protected static function store_import_rows($token, $rows)
	{
		// نضّف أي ملفات قديمة (أكتر من ساعتين) كل ما حد يعمل رفع جديد --
		// بديل بسيط لجدولة WP-Cron، ومش لازم نستنى cron يشتغل.
		self::cleanup_old_import_files();

		$path = self::import_tmp_path($token);
		$json = wp_json_encode(array(
			'rows'            => $rows,
			// كل الماسترز (post_id) اللي اتعملوا/اتحدّثوا في الرفعة دي --
			// بنستخدمها بعد ما كل الصفوف تخلص عشان نستنى توليد كل
			// سيشناتهم فعليًا قبل ما نقول "تم الانتهاء". شوف
			// drain_pending_sessions() تحت.
			'touched_masters' => array(),
			// 'rows' | 'sessions' -- مرحلة الاستيراد الحالية.
			'phase'           => 'rows',
			'sessions_total'  => 0,
			'sessions_done'   => 0,
		));
		return false !== @file_put_contents($path, $json, LOCK_EX);
	}

	/**
	 * بيحدّث الملف المؤقت من غير ما يمسح أي حاجة تانية جواه (rows مثلاً).
	 * $patch مصفوفة بالمفاتيح اللي عايزين نغيّرها بس.
	 */
	protected static function patch_import_data($token, array $patch)
	{
		$data = self::load_import_rows($token);
		if (false === $data) {
			return false;
		}
		$data = array_merge($data, $patch);
		$path = self::import_tmp_path($token);
		$json = wp_json_encode($data);
		return false !== @file_put_contents($path, $json, LOCK_EX);
	}

	protected static function load_import_rows($token)
	{
		$path = self::import_tmp_path($token);
		if (! file_exists($path)) {
			return false;
		}
		$json = @file_get_contents($path);
		if (false === $json || '' === $json) {
			return false;
		}
		$data = json_decode($json, true);
		if (! is_array($data) || empty($data['rows'])) {
			return false;
		}
		return $data;
	}

	protected static function delete_import_rows($token)
	{
		$path = self::import_tmp_path($token);
		if (file_exists($path)) {
			@unlink($path);
		}
	}

	protected static function cleanup_old_import_files()
	{
		$dir = self::import_tmp_dir();
		foreach (glob($dir . '/*.json') ?: array() as $file) {
			if (is_file($file) && (time() - filemtime($file)) > 2 * HOUR_IN_SECONDS) {
				@unlink($file);
			}
		}
	}

	public static function init()
	{
		add_action('admin_menu', array(__CLASS__, 'add_menu'));
		add_action('admin_post_cs_download_sample', array(__CLASS__, 'download_sample'));
		add_action('admin_post_cs_rebuild_sessions', array(__CLASS__, 'handle_rebuild_sessions'));

		// إندبوينتات الاستيراد على دفعات (progress bar) بدل الطلب الواحد
		// الطويل اللي كان بيضرب مهلة السيرفر (PHP/Nginx/Apache/Cloudflare)
		// وترجع صفحة بيضا من غير أي رسالة واضحة.
		add_action('wp_ajax_cs_import_start', array(__CLASS__, 'ajax_start'));
		add_action('wp_ajax_cs_import_batch', array(__CLASS__, 'ajax_batch'));
	}

	public static function add_menu()
	{
		add_submenu_page(
			'edit.php?post_type=' . CS_CPT,
			'Import CSV',
			'Import CSV',
			'edit_posts',
			'cs-import-csv',
			array(__CLASS__, 'render_page')
		);
	}

	/* ================= صفحة الأدمن ================= */

	public static function render_page()
	{
		if (! current_user_can('edit_posts')) {
			return;
		}

		$results = null;

		if (! empty($_POST['cs_import_submit'])) {
			check_admin_referer(self::NONCE_ACTION);

			if (! empty($_FILES['cs_csv_file']['tmp_name'])) {
				$results = self::handle_upload($_FILES['cs_csv_file']['tmp_name']);
			} else {
				$results = array('error' => 'من فضلك اختار ملف CSV الأول.');
			}
		}

		$sample_url = wp_nonce_url(
			admin_url('admin-post.php?action=cs_download_sample'),
			'cs_download_sample'
		);

		$rebuild_url = wp_nonce_url(
			admin_url('admin-post.php?action=cs_rebuild_sessions'),
			'cs_rebuild_sessions'
		);

		$rebuild_result = get_transient('cs_rebuild_result');
		if ($rebuild_result) {
			delete_transient('cs_rebuild_result');
		}
?>
		<div class="wrap">
			<h1>استيراد الكورسات من CSV</h1>

			<p>
				ارفع ملف CSV وهيتم إنشاء كورس (Master) لكل صف، وهيتولّد له السيشنز أوتوماتيك
				بنفس منطق التكرار المعتاد (زي لو حفظته يدوي من شاشة الكورس).
			</p>

			<p>
				<a href="<?php echo esc_url($sample_url); ?>" class="button">
					⬇ تحميل نموذج CSV فاضي (Sample Template)
				</a>
			</p>

			<div class="notice notice-info inline" style="max-width:900px;">
				<p>
					💡 <strong>فيه طريقة أسهل للترجمة:</strong> صفحة
					<a href="<?php echo esc_url(admin_url('edit.php?post_type=' . CS_CPT . '&page=cs-translation-link')); ?>"><strong>«ربط الترجمة»</strong></a>
					بتخليك ترفع الشيت الإنجليزي لوحده والعربي لوحده (وتكرر الرفع زي ما تحب)،
					وبتوريك عدد الكورسات في كل لغة وتتأكد إنهم متطابقين، وبعدين زرار واحد
					بيربطهم كلهم كترجمة في WPML -- من غير ما تحتاج تكتب <code>source_course_id</code>
					في الشيت خالص.
				</p>
			</div>

			<?php if (! empty($_GET['rebuilt']) && $rebuild_result) : ?>
				<div class="notice notice-success">
					<p>
						تم إعادة بناء السيشنز لـ <strong><?php echo (int) $rebuild_result['masters']; ?></strong> كورس:
						اتمسح <strong><?php echo (int) $rebuild_result['deleted']; ?></strong> سيشن قديم،
						واتولّد <strong><?php echo (int) $rebuild_result['created']; ?></strong> سيشن جديد بالبيانات الحالية.
						<?php if (! empty($rebuild_result['ghosts_removed'])) : ?>
							<br>وكمان اتشال <strong><?php echo (int) $rebuild_result['ghosts_removed']; ?></strong> نسخة "شبح" مكررة كان WPML عملها أوتوماتيك بالغلط (اتحطت في السلة، ممكن تراجع منها لو حصل خطأ).
						<?php endif; ?>
					</p>
				</div>
			<?php endif; ?>

			<div class="notice notice-info inline" style="padding:12px 12px 14px;margin-top:14px;">
				<p style="margin-top:0;">
					<strong>إعادة بناء كل السيشنز (Rebuild All Sessions)</strong><br>
					بتمسح وتعيد توليد سيشنز <u>كل</u> الكورسات من الصفر بإعداداتها الحالية.
					استخدمها مرة واحدة لو لاحظت كورسات قديمة (اتحفظت قبل آخر تحديث للنظام)
					بتفتح صفحتها وتظهر ناقصة (بدون سعر/دولة/تاريخ) -- ده غالبًا معناه إن
					فيه سيشنز قديمة متسجلة بصيغة قديمة (زي كود دولة ISO بدل اسم المكان
					الحر الجديد) ومحتاجة تتنضّف. العملية ممكن تاخد وقت لو عندك كورسات كتير.
				</p>
				<p>
					<a href="<?php echo esc_url($rebuild_url); ?>" class="button" onclick="return confirm('متأكد؟ هيتمسح ويتعاد بناء سيشنز كل الكورسات من الصفر.');">
						🔄 إعادة بناء كل السيشنز
					</a>
				</p>
			</div>

			<?php if ($results) : ?>
				<?php self::render_results($results); ?>
			<?php endif; ?>

			<form method="post" enctype="multipart/form-data" style="margin-top:20px;" id="cs-import-form">
				<?php wp_nonce_field(self::NONCE_ACTION); ?>
				<table class="form-table">
					<tr>
						<th><label for="cs_csv_file">ملف CSV</label></th>
						<td><input type="file" name="cs_csv_file" id="cs_csv_file" accept=".csv" required></td>
					</tr>
				</table>
				<?php submit_button('رفع واستيراد', 'primary', 'cs_import_submit', true, array('id' => 'cs-import-submit-btn')); ?>
			</form>

			<!-- شريط التقدّم: بيظهر بمجرد ما اليوزر يدوس "رفع واستيراد"، وبيتحدّث
				 بنسبة مئوية حقيقية (10%، 15%، 20%...) على حسب عدد الصفوف اللي
				 اتعالجت فعلاً، بدل ما الصفحة تفضل بيضا وهي مستنية رد واحد كبير. -->
			<div id="cs-import-progress-wrap" style="display:none;max-width:900px;margin-top:16px;">
				<div style="background:#dcdcde;border-radius:4px;overflow:hidden;height:26px;">
					<div id="cs-import-progress-bar" style="height:100%;width:0;background:#2271b1;color:#fff;font-size:12px;line-height:26px;text-align:center;white-space:nowrap;transition:width .25s ease;">0%</div>
				</div>
				<p id="cs-import-progress-text" style="margin:6px 0 0;color:#50575e;"></p>
			</div>

			<div id="cs-import-ajax-results" style="max-width:900px;margin-top:16px;"></div>

			<script>
			(function () {
				var form         = document.getElementById('cs-import-form');
				var fileInput    = document.getElementById('cs_csv_file');
				var submitBtn    = document.getElementById('cs-import-submit-btn');
				var progressWrap = document.getElementById('cs-import-progress-wrap');
				var progressBar  = document.getElementById('cs-import-progress-bar');
				var progressText = document.getElementById('cs-import-progress-text');
				var resultsWrap  = document.getElementById('cs-import-ajax-results');

				// لو المتصفح مش فاهم fetch/FormData (نادر جدًا)، سيب الفورم يشتغل
				// عادي بالطريقة القديمة (طلب واحد) بدل ما نكسر حاجة.
				if (!form || !window.fetch || !window.FormData) {
					return;
				}

				var NONCE           = '<?php echo esc_js(wp_create_nonce(self::AJAX_NONCE_ACTION)); ?>';
				var ADMIN_EDIT_BASE = '<?php echo esc_js(admin_url('post.php?action=edit&post=')); ?>';

				form.addEventListener('submit', function (e) {
					if (!fileInput.files || !fileInput.files.length) {
						return; // سيب رسالة "required" الافتراضية للمتصفح تظهر.
					}
					e.preventDefault();
					startImport();
				});

				function esc(s) {
					var d = document.createElement('div');
					d.textContent = (s === null || s === undefined) ? '' : String(s);
					return d.innerHTML;
				}

				function setProgress(processed, total, label) {
					var pct = total > 0 ? Math.round((processed / total) * 100) : 0;
					if (pct > 100) { pct = 100; }
					progressBar.style.width = pct + '%';
					progressBar.textContent = pct + '%';
					progressText.textContent = label || ('اتعالج ' + processed + ' من ' + total + ' صف...');
				}

				function showFatalError(msg) {
					progressWrap.style.display = 'none';
					submitBtn.disabled = false;
					resultsWrap.innerHTML = '<div class="notice notice-error"><p>' + esc(msg) + '</p></div>';
				}

				function startImport() {
					submitBtn.disabled = true;
					resultsWrap.innerHTML = '';
					progressWrap.style.display = 'block';
					setProgress(0, 1, 'جارٍ رفع الملف...');

					var fd = new FormData();
					fd.append('action', 'cs_import_start');
					fd.append('nonce', NONCE);
					fd.append('cs_csv_file', fileInput.files[0]);

					fetch(ajaxurl, { method: 'POST', body: fd, credentials: 'same-origin' })
						.then(function (r) { return r.json(); })
						.then(function (res) {
							if (!res.success) {
								showFatalError((res.data && res.data.message) || 'حصل خطأ غير متوقع أثناء رفع الملف.');
								return;
							}
							var acc = {
								created: 0,
								updated: 0,
								errors: (res.data.initial_errors || []).slice(),
								rows: []
							};
							if (!res.data.total) {
								submitBtn.disabled = false;
								progressWrap.style.display = 'none';
								renderResults(acc);
								return;
							}
							runBatches(res.data.token, 0, res.data.total, acc);
						})
						.catch(function (err) {
							showFatalError('فشل الاتصال بالسيرفر: ' + err.message);
						});
				}

				function runBatches(token, offset, total, acc) {
					setProgress(offset, total);

					var fd = new FormData();
					fd.append('action', 'cs_import_batch');
					fd.append('nonce', NONCE);
					fd.append('token', token);
					fd.append('offset', offset);

					fetch(ajaxurl, { method: 'POST', body: fd, credentials: 'same-origin' })
						.then(function (r) { return r.json(); })
						.then(function (res) {
							if (!res.success) {
								showFatalError((res.data && res.data.message) || 'حصل خطأ غير متوقع أثناء الاستيراد.');
								return;
							}
							var d = res.data;
							acc.created += d.created;
							acc.updated += d.updated;
							acc.errors   = acc.errors.concat(d.errors);
							acc.rows     = acc.rows.concat(d.rows);

							if (d.phase === 'sessions') {
								setProgress(d.processed, d.total, 'بيتولّد سيشنز الكورسات في الخلفية... ' + d.processed + ' من ' + d.total + ' سيشن (متسيبش الصفحة، ده بيخلص لوحده)');
							} else {
								setProgress(d.processed, d.total);
							}

							if (d.done) {
								setProgress(d.total, d.total, 'تم الانتهاء ✓ (كل السيشنز اتعملت فعليًا)');
								submitBtn.disabled = false;
								renderResults(acc);
							} else {
								runBatches(token, d.next_offset, d.total, acc);
							}
						})
						.catch(function (err) {
							showFatalError('فشل الاتصال بالسيرفر: ' + err.message);
						});
				}

				function renderResults(acc) {
					var html = '';
					html += '<div class="notice notice-success"><p>تم بنجاح: <strong>' + acc.created +
						'</strong> كورس جديد، <strong>' + acc.updated +
						'</strong> كورس اتحدّث. عدد الأخطاء: <strong>' + acc.errors.length + '</strong>.</p></div>';

					if (acc.errors.length) {
						html += '<div class="notice notice-warning"><p><strong>أخطاء:</strong></p><ul style="list-style:disc;margin-right:20px;">';
						acc.errors.forEach(function (e) { html += '<li>' + esc(e) + '</li>'; });
						html += '</ul></div>';
					}

					if (acc.rows.length) {
						html += '<table class="widefat striped" style="max-width:900px;"><thead><tr><th>الصف</th><th>العنوان</th><th>الحالة</th><th>Course ID</th></tr></thead><tbody>';
						acc.rows.forEach(function (r) {
							var idCell = r.post_id ? '<a href="' + ADMIN_EDIT_BASE + encodeURIComponent(r.post_id) + '">#' + esc(r.post_id) + '</a>' : '-';
							html += '<tr><td>' + esc(r.line) + '</td><td>' + esc(r.title) + '</td><td>' + esc(r.status) + '</td><td>' + idCell + '</td></tr>';
						});
						html += '</tbody></table>';
					}

					resultsWrap.innerHTML = html;
				}
			})();
			</script>

			<h2>شرح أعمدة الشيت</h2>
			<table class="widefat striped" style="max-width:900px;">
				<thead>
					<tr>
						<th>العمود</th>
						<th>الشرح</th>
						<th>مثال</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><code>title</code></td>
						<td>اسم الكورس (مطلوب)</td>
						<td>Strategic Leadership Program</td>
					</tr>
					<tr>
						<td><code>category</code></td>
						<td>
							اسم الكاتيجوري (بيتعمل تلقائي لو مش موجود)، أو <strong>term_id</strong> رقم
							صريح لكاتيجوري موجودة بالفعل (أضمن -- مبيعتمدش على تطابق الاسم حرفيًا).
							تلاقي الـ term_id من Courses &rarr; Categories، رابط "Edit" لأي كاتيجوري
							(tag_ID=XX في اللينك).
						</td>
						<td>Leadership & Executive Coaching <em>أو</em> 42</td>
					</tr>
					<tr>
						<td><code>summary</code></td>
						<td>وصف الهيرو</td>
						<td>A modern capability-building program...</td>
					</tr>
					<tr>
						<td><code>delivery_mode</code></td>
						<td>وضع أو أكتر مفصولين بـ <code>/</code> (مش قايمة محددة، اكتب أي وضع)</td>
						<td>Hybrid / Online / In-Person</td>
					</tr>
					<tr>
						<td><code>language</code></td>
						<td>لغة أو أكتر مفصولين بـ <code>/</code> (مش قايمة محددة، اكتب أي لغة)</td>
						<td>English / Arabic / French</td>
					</tr>
					<tr>
						<td><code>duration_hours</code></td>
						<td>رقم</td>
						<td>40</td>
					</tr>
					<tr>
						<td><code>base_start_date</code></td>
						<td>YYYY-MM-DD</td>
						<td>2026-03-05</td>
					</tr>
					<tr>
						<td><code>recurrence</code></td>
						<td>weekly / biweekly — عدد الأيام بيتحدد أوتوماتيك منها (weekly=5, biweekly=10)، مفيش عمود منفصل لعدد الأيام</td>
						<td>weekly</td>
					</tr>
					<tr>
						<td><code>countries_prices</code></td>
						<td>المكان:السعر:العملة (المكان نص حر -- مدينة أو دولة أو أي حاجة)، كل مكان مفصول بـ <code>|</code></td>
						<td>Cairo:1500:USD|Alex:800:USD|Mansoura:900:USD</td>
					</tr>
					<tr>
						<td><code>objectives</code></td>
						<td>مفصولين بـ <code>|</code></td>
						<td>Frame the main decisions...|Use practical tools...</td>
					</tr>
					<tr>
						<td><code>schedule</code></td>
						<td>يوم:نقطة1;نقطة2، وكل يوم مفصول بـ <code>||</code></td>
						<td>Day 1 – Intro: Point A;Point B||Day 2 – Deep Dive: Point C</td>
					</tr>
					<tr>
						<td><code>outline_pdf_url</code></td>
						<td>لينك ملف PDF لو موجود في مكتبة الميديا (اختياري)</td>
						<td>https://site.com/wp-content/uploads/file.pdf</td>
					</tr>
					<tr>
						<td><code>image_url</code></td>
						<td>رابط صورة الكورس (اختياري). هتتنزّل وتتحط كصورة الكورس، وتتكرر تلقائي في كل السيشنز. سيبه فاضي لو مفيش صورة -- الكارت هيتعرض عادي من غيرها</td>
						<td>https://site.com/wp-content/uploads/course-cover.jpg</td>
					</tr>
					<tr>
						<td><code>course_id</code></td>
						<td>لو حطيته، هيعدّل الكورس ده بدل إنشاء واحد جديد (اختياري)</td>
						<td>142</td>
					</tr>
					<tr>
						<td><code>lang</code></td>
						<td>كود لغة الصف ده (محتاج WPML شغّال). سيبه فاضي للكورس الأصلي بلغة الموقع الافتراضية</td>
						<td>ar</td>
					</tr>
					<tr>
						<td><code>source_course_id</code></td>
						<td>لو الصف ده ترجمة لكورس أصلي: حط ID الكورس الإنجليزي هنا، وهيتستنسخله كل المواعيد والدول والأسعار تلقائي، وهيتربط كترجمة له في WPML، وهيتولّدله نفس عدد السيشنز</td>
						<td>142</td>
					</tr>
				</tbody>
			</table>

			<?php if (class_exists('CS_WPML') && ! CS_WPML::active()) : ?>
				<div class="notice notice-warning inline">
					<p>
						⚠️ عمودين <code>lang</code> و <code>source_course_id</code> مش هيشتغلوا لحد ما تثبّت وتفعّل بلجن <strong>WPML Multilingual CMS</strong>.
					</p>
				</div>
			<?php endif; ?>
		</div>
<?php
	}

	protected static function render_results($results)
	{
		if (! empty($results['error'])) {
			echo '<div class="notice notice-error"><p>' . esc_html($results['error']) . '</p></div>';
			return;
		}

		printf(
			'<div class="notice notice-success"><p>تم بنجاح: <strong>%d</strong> كورس جديد، <strong>%d</strong> كورس اتحدّث. عدد الأخطاء: <strong>%d</strong>.</p></div>',
			(int) $results['created'],
			(int) $results['updated'],
			count($results['errors'])
		);

		echo '<div class="notice notice-info"><p>';
		echo 'ملحوظة: لو أي كورس عنده أماكن/سيشنز كتير (زي كورس في 50 دولة)، أول شوية سيشنز بس بتتعمل فورًا، والباقي بيكمل في الخلفية على دفعات صغيرة كل كام ثانية -- ممكن ياخد كذا دقيقة لحد ما تظهر كل السيشنز. ';
		if (class_exists('CS_WPML') && CS_WPML::active()) {
			echo 'وربط ترجمة السيشنز بالإنجليزي (لو الصف ده ترجمة) بيستنى لحد ما كل السيشنز دي تخلص توليد الأول، وبعدين بيحصل هو كمان في الخلفية. ';
		}
		echo 'مفيش حاجة تانية مطلوبة منك، هيكمل لوحده.';
		echo '</p></div>';

		if (! empty($results['errors'])) {
			echo '<div class="notice notice-warning"><p><strong>أخطاء:</strong></p><ul style="list-style:disc;margin-right:20px;">';
			foreach ($results['errors'] as $err) {
				echo '<li>' . esc_html($err) . '</li>';
			}
			echo '</ul></div>';
		}

		if (! empty($results['rows'])) {
			echo '<table class="widefat striped" style="max-width:900px;"><thead><tr><th>الصف</th><th>العنوان</th><th>الحالة</th><th>Course ID</th></tr></thead><tbody>';
			foreach ($results['rows'] as $row) {
				echo '<tr>';
				echo '<td>' . (int) $row['line'] . '</td>';
				echo '<td>' . esc_html($row['title']) . '</td>';
				echo '<td>' . esc_html($row['status']) . '</td>';
				echo '<td>' . ($row['post_id'] ? '<a href="' . esc_url(get_edit_post_link($row['post_id'])) . '">#' . (int) $row['post_id'] . '</a>' : '-') . '</td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
		}
	}

	/* ================= المعالجة ================= */

	/**
	 * إعادة بناء سيشنز كل الكورسات من الصفر (مسح كامل + توليد بالإعدادات
	 * الحالية). أداة صيانة يدوية -- تُستخدم مرة كل ما نحتاج ننضّف بيانات
	 * سيشنز قديمة (زي أكواد دول ISO قديمة قبل ما المكان بقى نص حر).
	 */
	public static function handle_rebuild_sessions()
	{
		if (! current_user_can('edit_posts')) {
			wp_die('غير مسموح.');
		}
		check_admin_referer('cs_rebuild_sessions');

		if (function_exists('set_time_limit')) {
			@set_time_limit(0);
		}

		// تنضيف أولًا: أي بوست عليه علامة WPML "نسخة شبح تلقائية"
		// (_icl_lang_duplicate_of) اتعامل معاه قبل كده كماستر مستقل بالغلط
		// (باگ قديم اتصلح)، فيبقى عنده سيشنز كاملة مكررة لازم تتشال، والبوست
		// الشبح نفسه يترحّل للسلة بدل ما يفضل يظهر كـ"كورس" وهمي في اللوحة.
		$ghost_duplicates = get_posts(array(
			'post_type'      => CS_CPT,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(array('key' => '_icl_lang_duplicate_of', 'compare' => 'EXISTS')),
		));

		$ghost_sessions_deleted = 0;
		$ghost_masters_trashed  = 0;
		foreach ($ghost_duplicates as $ghost_id) {
			$ghost_sessions_deleted += CS_Recurrence::delete_sessions($ghost_id);
			// لو الشبح نفسه سيشن (مش ماستر) تابع لماستر تاني، امسحه هو
			// نفسه كمان بدل ما يفضل بوست يتيم.
			wp_trash_post($ghost_id);
			$ghost_masters_trashed++;
		}

		$masters = get_posts(array(
			'post_type'      => CS_CPT,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array('key' => CS_META_IS_MASTER, 'value' => 1),
				array('key' => '_icl_lang_duplicate_of', 'compare' => 'NOT EXISTS'),
			),
		));

		$total_deleted = $ghost_sessions_deleted;
		$total_created = 0;

		foreach ($masters as $master_id) {
			$total_deleted += CS_Recurrence::delete_sessions($master_id);
			$gen = CS_Recurrence::generate_sessions($master_id);
			$total_created += (int) $gen['created'];
		}

		set_transient('cs_rebuild_result', array(
			'masters'       => count($masters),
			'deleted'       => $total_deleted,
			'created'       => $total_created,
			'ghosts_removed' => $ghost_masters_trashed,
		), 60);

		wp_safe_redirect(admin_url('edit.php?post_type=' . CS_CPT . '&page=cs-import-csv&rebuilt=1'));
		exit;
	}

	protected static function handle_upload($tmp_path)
	{
		// شيت فيه عشرات/مئات الصفوف ممكن ياخد وقت (خصوصًا مع تنزيل صور)، فمنسيبش
		// الحد الافتراضي لـ PHP (غالبًا 30 ثانية) يقفل الطلب في نصه. بعض
		// الاستضافات بتمنع set_time_limit -- عشان كده جوه @ ومحوطة بـ try.
		if (function_exists('set_time_limit')) {
			@set_time_limit(0);
		}

		$handle = fopen($tmp_path, 'r');
		if (! $handle) {
			return array('error' => 'مقدرش أفتح الملف.');
		}

		// شيل الـ BOM لو موجود عشان أول عمود متتكسرش.
		$bom = fread($handle, 3);
		if ($bom !== "\xEF\xBB\xBF") {
			rewind($handle);
		}

		$headers = fgetcsv($handle);
		if (! $headers) {
			fclose($handle);
			return array('error' => 'الملف فاضي أو مش CSV صحيح.');
		}

		$headers = array_map(function ($h) {
			return strtolower(trim($h));
		}, $headers);

		$created = 0;
		$updated = 0;
		$errors  = array();
		$rows    = array();
		$line    = 1; // الصف 1 = الهيدر.

		while (($data = fgetcsv($handle)) !== false) {
			$line++;

			// صف فاضي بالكامل -> تخطّاه.
			if (count(array_filter($data, function ($v) {
				return trim((string) $v) !== '';
			})) === 0) {
				continue;
			}

			$row = array();
			foreach ($headers as $i => $key) {
				$row[$key] = isset($data[$i]) ? trim($data[$i]) : '';
			}

			$title = isset($row['title']) ? $row['title'] : '';
			if ('' === $title) {
				$errors[] = "صف {$line}: عمود title فاضي، اتخطّى.";
				continue;
			}

			$result = self::import_row($row, $line);

			if (is_wp_error($result)) {
				$errors[] = "صف {$line} ({$title}): " . $result->get_error_message();
				$rows[]   = array('line' => $line, 'title' => $title, 'status' => 'فشل', 'post_id' => 0);
				continue;
			}

			if ('created' === $result['status']) {
				$created++;
			} else {
				$updated++;
			}

			if (! empty($result['image_warning'])) {
				$errors[] = "صف {$line} ({$title}): الكورس اتحفظ، بس مشكلة في الصورة -- " . $result['image_warning'];
			}

			$rows[] = array(
				'line'    => $line,
				'title'   => $title,
				'status'  => 'created' === $result['status'] ? 'اتعمل' : 'اتحدّث',
				'post_id' => $result['post_id'],
			);
		}

		fclose($handle);

		return array(
			'created' => $created,
			'updated' => $updated,
			'errors'  => $errors,
			'rows'    => $rows,
		);
	}

	/* ================= استيراد على دفعات (Ajax + Progress Bar) ================= */

	/**
	 * الخطوة الأولى: بتستقبل ملف الـ CSV، بتقراه وتقسّمه لصفوف بس (من غير
	 * ما تعمل أي حاجة في قاعدة البيانات لسه)، وبتخزن الصفوف دي مؤقتًا في
	 * ملف مؤقت على الديسك (مش transient، شوف السبب في store_import_rows())
	 * وبترجع token + العدد الكلي للصفوف. الجافاسكريبت بعد كده
	 * بينادي ajax_batch() مرة ورا التانية لحد ما يخلّص كل الصفوف، وبيحدّث
	 * شريط التقدّم بنسبة حقيقية في كل مرة -- بدل ما يفضل مستني رد واحد
	 * كبير لكل الملف (وده اللي كان بياخد وقت طويل وممكن يضرب مهلة
	 * السيرفر (PHP/Nginx/Apache/Cloudflare) فترجع صفحة بيضا من غير أي
	 * رسالة، خصوصًا مع شيتات فيها صور بتتنزّل من الإنترنت لكل صف).
	 */
	public static function ajax_start()
	{
		// أي Fatal Error يحصل جوه الجزء ده (PHP 7+ بيرميه كـ Throwable قابل
		// للمسك) بيترجم لرسالة JSON واضحة بدل ما السيرفر يرجّع صفحة HTML
		// خام برقم 500 (زي اللي كان بيحصل قبل ما نضيف الحماية دي).
		try {
			check_ajax_referer(self::AJAX_NONCE_ACTION, 'nonce');

			if (! current_user_can('edit_posts')) {
				wp_send_json_error(array('message' => 'غير مسموح.'));
			}

			if (empty($_FILES['cs_csv_file']['tmp_name']) || ! is_uploaded_file($_FILES['cs_csv_file']['tmp_name'])) {
				$upload_err = isset($_FILES['cs_csv_file']['error']) ? (int) $_FILES['cs_csv_file']['error'] : -1;
				wp_send_json_error(array('message' => 'من فضلك اختار ملف CSV الأول. (upload error code: ' . $upload_err . ')'));
			}

			$tmp_path = $_FILES['cs_csv_file']['tmp_name'];
			$handle   = fopen($tmp_path, 'r');
			if (! $handle) {
				wp_send_json_error(array('message' => 'مقدرش أفتح الملف.'));
			}

			// شيل الـ BOM لو موجود عشان أول عمود متتكسرش (خصوصًا في الشيتات
			// المحفوظة بالعربي من إكسيل).
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

			$rows_by_line = array();
			$errors       = array();
			$line         = 1; // الصف 1 = الهيدر.

			while (($data = fgetcsv($handle)) !== false) {
				$line++;

				// صف فاضي بالكامل -> تخطّاه.
				if (count(array_filter($data, function ($v) {
					return trim((string) $v) !== '';
				})) === 0) {
					continue;
				}

				$row = array();
				foreach ($headers as $i => $key) {
					$row[$key] = isset($data[$i]) ? trim($data[$i]) : '';
				}

				$title = isset($row['title']) ? $row['title'] : '';
				if ('' === $title) {
					$errors[] = "صف {$line}: عمود title فاضي، اتخطّى.";
					continue;
				}

				$rows_by_line[$line] = $row;
			}

			fclose($handle);

			if (empty($rows_by_line)) {
				wp_send_json_error(array(
					'message' => 'مفيش أي صف صالح في الملف (كل الصفوف فاضية أو من غير title).',
					'errors'  => $errors,
				));
			}

			// توكن فريد لكل عملية رفع، عشان أي طلب أجاكس بعد كده يعرف يرجع
			// لنفس الصفوف اللي اتخزنت، من غير ما نضطر نبعت كل بيانات الشيت
			// تاني في كل طلب. متخزنة في ملف على الديسك (مش transient) --
			// شوف شرح السبب فوق مكان تعريف store_import_rows().
			$token = 'cs_import_' . wp_generate_password(20, false, false);

			if (! self::store_import_rows($token, $rows_by_line)) {
				wp_send_json_error(array(
					'message' => 'مقدرش أخزّن بيانات الملف مؤقتًا على السيرفر (مشكلة صلاحيات كتابة على مجلد uploads؟). كلم مسؤول الاستضافة.',
				));
			}

			wp_send_json_success(array(
				'token'          => $token,
				'total'          => count($rows_by_line),
				'initial_errors' => $errors,
				'batch_size'     => self::BATCH_SIZE,
			));
		} catch (\Throwable $e) {
			wp_send_json_error(array(
				'message' => 'حصل خطأ في السيرفر أثناء قراءة الملف: ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')',
			));
		}
	}

	/**
	 * بتعالج دفعة صغيرة (BATCH_SIZE صف) من الصفوف المخزّنة في الملف المؤقت
	 * بتاع token معيّن، وبترجع نتيجة الدفعة دي (كام اتعمل/اتحدّث/فشل) +
	 * كام صف اتعالج لحد دلوقتي من إجمالي العدد -- عشان الجافاسكريبت يقدر
	 * يحسب نسبة مئوية حقيقية ويحدّث بيها شريط التقدّم.
	 */
	public static function ajax_batch()
	{
		try {
			check_ajax_referer(self::AJAX_NONCE_ACTION, 'nonce');

			if (! current_user_can('edit_posts')) {
				wp_send_json_error(array('message' => 'غير مسموح.'));
			}

			// دفعة صغيرة، مش المفروض تاخد وقت طويل، بس بنسيب هامش أمان.
			if (function_exists('set_time_limit')) {
				@set_time_limit(120);
			}

			// محاولة (اختيارية، مفيش مشكلة لو الاستضافة مانعاها) نرفع سقف
			// الذاكرة المسموح بيه للطلب ده بالذات -- معالجة الصور
			// (تنزيل + توليد الأحجام) هي أكتر حاجة بتستهلك ذاكرة في العملية.
			if (function_exists('ini_set')) {
				@ini_set('memory_limit', '256M');
			}

			$token  = isset($_POST['token']) ? sanitize_text_field(wp_unslash($_POST['token'])) : '';
			$offset = isset($_POST['offset']) ? max(0, (int) $_POST['offset']) : 0;

			$data = $token ? self::load_import_rows($token) : false;
			if (false === $data || empty($data['rows'])) {
				wp_send_json_error(array('message' => 'انتهت صلاحية جلسة الرفع (أو الصفحة اتفتحت من جهاز تاني). من فضلك ارفع الملف تاني.'));
			}

			$phase = isset($data['phase']) ? $data['phase'] : 'rows';

			// ============ المرحلة التانية: استنى توليد كل السيشنز فعليًا ============
			// بعد ما كل صفوف الشيت تتعالج، مش بنقول "تم الانتهاء" على طول --
			// كل ماستر ممكن يكون لسه وراه سيشنز في طابور خلفي (شوف
			// CS_Recurrence::queue_remaining_sessions()) اللي أصلًا بيتعمل
			// عن طريق WP-Cron. لو الاستضافة بتمنع WP-Cron (شائع جدًا)، كان
			// الطابور ده بيفضل واقف من غير ما حد ينتبه -- فبدل ما نستناه،
			// بنسحب الدفعات إحنا بنفسنا هنا (بنفس آلية "استنى وكمّل" اللي
			// الصفوف بتستخدمها)، لحد ما كل سيشن يتعمل فعليًا.
			if ('sessions' === $phase) {
				$touched  = isset($data['touched_masters']) ? $data['touched_masters'] : array();
				$total    = isset($data['sessions_total']) ? (int) $data['sessions_total'] : 0;
				$done_ct  = isset($data['sessions_done']) ? (int) $data['sessions_done'] : 0;

				// وقت أمان للطلب ده (~20 ثانية) عشان مانضربش مهلة تنفيذ
				// PHP -- لو لسه فاضل سيشنز بعد كده، الجافاسكريبت هيكمّل
				// بطلب تاني على طول.
				$deadline = microtime(true) + 20;

				while (microtime(true) < $deadline) {
					$master_id = 0;
					foreach ($touched as $mid) {
						if (CS_Recurrence::has_pending_queue($mid)) {
							$master_id = (int) $mid;
							break;
						}
					}
					if (! $master_id) {
						break; // كل الماسترز خلّصوا.
					}

					$before = CS_Recurrence::pending_queue_count($master_id);
					CS_Recurrence::process_generate_sessions_batch($master_id);
					$after  = CS_Recurrence::pending_queue_count($master_id);

					$done_ct += max(0, $before - $after);

					// لو الدفعة معملتش أي تقدّم فعلي (احتياطي أمان ضد أي
					// حلقة لا نهائية)، وقف فورًا بدل ما نستهلك وقت الطلب
					// كله من غير فايدة.
					if ($after >= $before) {
						break;
					}
				}

				$still_pending = false;
				foreach ($touched as $mid) {
					if (CS_Recurrence::has_pending_queue($mid)) {
						$still_pending = true;
						break;
					}
				}

				self::patch_import_data($token, array('sessions_done' => $done_ct));

				if (! $still_pending) {
					self::delete_import_rows($token);
					wp_send_json_success(array(
						'created'     => 0,
						'updated'     => 0,
						'errors'      => array(),
						'rows'        => array(),
						'processed'   => max($total, $done_ct),
						'total'       => max($total, 1),
						'next_offset' => 0,
						'done'        => true,
						'phase'       => 'sessions',
					));
				}

				wp_send_json_success(array(
					'created'     => 0,
					'updated'     => 0,
					'errors'      => array(),
					'rows'        => array(),
					'processed'   => min($done_ct, $total),
					'total'       => max($total, 1),
					'next_offset' => 0,
					'done'        => false,
					'phase'       => 'sessions',
				));
			}

			// ============ المرحلة الأولى: معالجة صفوف الـ CSV ============
			$all_lines   = array_keys($data['rows']);
			$total       = count($all_lines);
			$batch_lines = array_slice($all_lines, $offset, self::BATCH_SIZE);

			$created         = 0;
			$updated         = 0;
			$errors          = array();
			$rows_out        = array();
			$touched_masters = isset($data['touched_masters']) ? $data['touched_masters'] : array();

			foreach ($batch_lines as $line) {
				$row   = $data['rows'][$line];
				$title = $row['title'];

				$result = self::import_row($row, $line);

				if (is_wp_error($result)) {
					$errors[]   = "صف {$line} ({$title}): " . $result->get_error_message();
					$rows_out[] = array('line' => $line, 'title' => $title, 'status' => 'فشل', 'post_id' => 0);
					continue;
				}

				if ('created' === $result['status']) {
					$created++;
				} else {
					$updated++;
				}

				if (! empty($result['image_warning'])) {
					$errors[] = "صف {$line} ({$title}): الكورس اتحفظ، بس مشكلة في الصورة -- " . $result['image_warning'];
				}

				if (! in_array((int) $result['post_id'], $touched_masters, true)) {
					$touched_masters[] = (int) $result['post_id'];
				}

				$rows_out[] = array(
					'line'    => $line,
					'title'   => $title,
					'status'  => 'created' === $result['status'] ? 'اتعمل' : 'اتحدّث',
					'post_id' => $result['post_id'],
				);
			}

			$next_offset = $offset + count($batch_lines);
			$rows_done   = $next_offset >= $total;

			if (! $rows_done) {
				self::patch_import_data($token, array('touched_masters' => $touched_masters));

				wp_send_json_success(array(
					'created'     => $created,
					'updated'     => $updated,
					'errors'      => $errors,
					'rows'        => $rows_out,
					'processed'   => $next_offset,
					'total'       => $total,
					'next_offset' => $next_offset,
					'done'        => false,
					'phase'       => 'rows',
				));
			}

			// خلّصنا كل صفوف الشيت -- قبل ما نقول "تم الانتهاء"، احسب كام
			// سيشن لسه في طابور الخلفية لكل ماستر اتلمس في الرفعة دي، عشان
			// نكمّل عليهم في المرحلة الجاية بدل ما نسيبهم للـ WP-Cron لوحده.
			$sessions_total = 0;
			foreach ($touched_masters as $mid) {
				$sessions_total += CS_Recurrence::pending_queue_count($mid);
			}

			if (0 === $sessions_total) {
				self::delete_import_rows($token);
				wp_send_json_success(array(
					'created'     => $created,
					'updated'     => $updated,
					'errors'      => $errors,
					'rows'        => $rows_out,
					'processed'   => $next_offset,
					'total'       => $total,
					'next_offset' => $next_offset,
					'done'        => true,
					'phase'       => 'rows',
				));
			}

			self::patch_import_data($token, array(
				'touched_masters' => $touched_masters,
				'phase'           => 'sessions',
				'sessions_total'  => $sessions_total,
				'sessions_done'   => 0,
			));

			wp_send_json_success(array(
				'created'     => $created,
				'updated'     => $updated,
				'errors'      => $errors,
				'rows'        => $rows_out,
				'processed'   => 0,
				'total'       => $sessions_total,
				'next_offset' => 0,
				'done'        => false,
				'phase'       => 'sessions',
			));
		} catch (\Throwable $e) {
			wp_send_json_error(array(
				'message' => 'حصل خطأ في السيرفر أثناء معالجة الصفوف: ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')',
			));
		}
	}

	/**
	 * استيراد صف واحد -> إنشاء أو تحديث كورس ماستر + توليد السيشنز بتاعته.
	 *
	 * @return array|WP_Error { status: created|updated, post_id: int }
	 */
	protected static function import_row($row, $line)
	{

		$title = sanitize_text_field($row['title']);

		// لو الصف ترجمة، source_course_id هو المفتاح الأساسي الحقيقي.
		// ده يمنع مشكلة إن course_id في الشيت العربي يكون هو ID الإنجليزي،
		// فيتعدل الأصل أو يفشل الصف بدل إنشاء/تحديث الترجمة العربية.
		$post_id   = 0;
		$status    = 'created';
		$source_id = 0;
		$row_lang  = ! empty($row['lang']) ? sanitize_key($row['lang']) : '';

		if (! empty($row['source_course_id'])) {
			$maybe_source = (int) $row['source_course_id'];
			if (! $maybe_source || get_post_type($maybe_source) !== CS_CPT) {
				return new WP_Error('bad_source', 'عمود source_course_id مش بيشاور على كورس موجود فعلاً.');
			}
			$source_id = $maybe_source;

			if (class_exists('CS_WPML') && CS_WPML::active() && ! $row_lang) {
				return new WP_Error('missing_lang', 'صف الترجمة يحتوي على source_course_id لكن عمود lang فارغ. اكتب كود اللغة مثل ar.');
			}

			// أول اختيار دائمًا: الترجمة الموجودة بالفعل لنفس الأصل ونفس اللغة.
			if (class_exists('CS_WPML') && CS_WPML::active() && $row_lang) {
				$existing_translation = CS_WPML::get_translation_id($source_id, $row_lang);
				if ($existing_translation) {
					$post_id = (int) $existing_translation;
					$status  = 'updated';
				}
			}

			// توافق مع الشيتات القديمة: لو course_id فعلًا ID ترجمة مستقلة نستخدمه.
			// أما لو يساوي source_course_id (ID الإنجليزي) نتجاهله بأمان.
			if (! $post_id && ! empty($row['course_id'])) {
				$maybe_id = (int) $row['course_id'];
				if ($maybe_id && $maybe_id !== $source_id && get_post_type($maybe_id) === CS_CPT) {
					$can_use = true;
					if (class_exists('CS_WPML') && CS_WPML::active() && $row_lang) {
						$id_lang = CS_WPML::post_language($maybe_id);
						$can_use = (! $id_lang || $id_lang === $row_lang);
					}
					if ($can_use) {
						$post_id = $maybe_id;
						$status  = 'updated';
					}
				}
			}
		} elseif (! empty($row['course_id'])) {
			// الصف الأصلي (مش ترجمة): course_id يفضل ID الكورس نفسه.
			$maybe_id = (int) $row['course_id'];
			if (get_post_type($maybe_id) === CS_CPT) {
				$post_id = $maybe_id;
				$status  = 'updated';
			}
		}

		if (! $post_id) {
			// Fallback إنقاذي للبيانات القديمة غير المربوطة في WPML:
			// نبحث بالعنوان + اللغة، لكن لا نسمح أبدًا باختيار الأصل نفسه.
			global $wpdb;
			$candidate_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT p.ID FROM {$wpdb->posts} p
					 INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = %s AND m.meta_value = '1'
					 WHERE p.post_title = %s AND p.post_type = %s AND p.post_status != 'trash'
					 ORDER BY p.ID ASC",
					CS_META_IS_MASTER,
					$title,
					CS_CPT
				)
			);

			if ($candidate_ids) {
				if (class_exists('CS_WPML') && CS_WPML::active()) {
					$target_lang = $row_lang ?: CS_WPML::default_language();
					foreach ($candidate_ids as $cid) {
						$cid = (int) $cid;
						if ($source_id && $cid === $source_id) {
							continue;
						}
						$cid_lang = CS_WPML::post_language($cid);
						if (! $cid_lang) {
							$cid_lang = CS_WPML::default_language();
						}
						if ($cid_lang === $target_lang) {
							$post_id = $cid;
							break;
						}
					}
				} else {
					foreach ($candidate_ids as $cid) {
						$cid = (int) $cid;
						if (! $source_id || $cid !== $source_id) {
							$post_id = $cid;
							break;
						}
					}
				}

				if ($post_id) {
					$status = 'updated';
				}
			}
		}

		$postarr = array(
			'post_title'  => $title,
			'post_type'   => CS_CPT,
			'post_status' => 'publish',
		);

		if ($post_id) {
			$postarr['ID'] = $post_id;
			$result_id     = wp_update_post($postarr, true);
		} else {
			$result_id = wp_insert_post($postarr, true);
		}

		if (is_wp_error($result_id)) {
			return $result_id;
		}

		$post_id = $result_id;

		// لو الصف ترجمة، استنسخ حقول الأصل الأول ثم أعمدة الشيت الحالية
		// تستبدل القيم المترجمة. source_id اتراجع واتحدد قبل إنشاء/تحديث البوست.
		if ($source_id) {
			if ($source_id === (int) $post_id) {
				return new WP_Error('bad_source', 'تعذر إنشاء الترجمة لأن ID الترجمة يساوي ID الكورس الأصلي. تأكد من source_course_id و lang.');
			}
			if (class_exists('CS_WPML')) {
				CS_WPML::clone_master_fields($source_id, $post_id);
			}
		}

		// علّمه كماستر، واربط لغته/ترجمته في WPML *قبل* أي حاجة تانية بتلمس
		// تاكسونومي أو حقول -- شوف الشرح الكامل تحت (نفس السبب اللي خلّى
		// لينكينج اللغة يتقدّم قبل توليد السيشنز).
		update_post_meta($post_id, CS_META_IS_MASTER, 1);

		// لو الصف ده محتاج ربط لغة/ترجمة WPML:
		// - لغة الماستر نفسه (set_language/link_translation) عملية سريعة
		//   جدًا (مش بتلف على حاجة)، فبنعملها *فورًا* هنا، *قبل* ما نولّد
		//   أي سيشن -- وكمان *قبل* ما نحط أي كاتيجوري عليه (شوف تحت).
		//   ده مهم جدًا: لو سبنا الماستر من غير لغة محددة لحد ما السيشنز
		//   تخلص توليد (زي ما كنا بنعمل قبل كده)، WPML كان بيعتبر الماستر
		//   بلغة الموقع الافتراضية طول فترة التوليد، وأي سيشن بيتولد في
		//   الفترة دي كان بياخد كاتيجوري عربي على بوست "افتراضي/إنجليزي"
		//   -- وده تعارض لغة كان بيخلي WPML ينشئ تلقائي نسخة مكررة وهمية
		//   من الكاتيجوري (وده اللي كان بيظهر كاتيجوري غريبة زي "Digital
		//   Transformation, Data & AI" تحت تبويب Arabic).
		// - نفس المشكلة بالظبط كانت بتحصل على *الماستر نفسه* لو الكاتيجوري
		//   اتحطت عليه (شوف تحت) قبل ما نحدد لغته: WPML كان شايف الماستر
		//   لسه بلغة الموقع الافتراضية (إنجليزي) وقت وضع كاتيجوري عربي
		//   موجودة بالفعل عليه، فبيدبلكيتها (بينشئ نسخة "إنجليزي" وهمية
		//   منها) بدل ما يستخدم الموجودة -- وده بالظبط اللي كان بيظهر
		//   كـ"كريت كاتيجوري جديدة موجودة أصلاً" وقت رفع شيت عربي.
		// - ربط كل سيشن بالسيشن الإنجليزي المقابل له (tag_sessions_language)
		//   ده لوحده اللي لازم يستنى لحد ما كل السيشنز تخلص توليد (شوف
		//   CS_WPML::maybe_tag_after_generation()).
		if (class_exists('CS_WPML') && CS_WPML::active()) {
			$lang_code = ! empty($row['lang']) ? sanitize_key($row['lang']) : '';

			// لو الصف مش ترجمة (مفيش source_course_id) ومعندوش lang في
			// الشيت، يبقى ده الماستر الأصلي (الإنجليزي عادةً). كان بيتسيب
			// من غير لغة WPML محددة صراحةً هنا، فبيفضل معتمد على WPML يحددها
			// تلقائي في وقت مش مضمون قبل توليد السيشنز -- ولو ماحصلش في
			// الوقت المناسب، CS_WPML::tag_session_on_create() بترجع فورًا
			// من غير ما تعمل حاجة (لأن الماستر "مالوش لغة")، فكل سيشنز
			// الكورس ده بتتولد من غير أي لغة WPML، وده اللي كان بيخلي صفحة
			// السنجل كورس بالإنجليزي تفشل توصل لسعر/بيانات الماستر (بينما
			// العربي شغال لأن صف الترجمة بياخد لغته فورًا وبشكل صريح فوق).
			if (! $lang_code && ! $source_id) {
				$lang_code = CS_WPML::default_language();
			}

			if ($lang_code) {
				if ($source_id) {
					CS_WPML::link_translation($post_id, $source_id, $lang_code);
				} else {
					CS_WPML::set_language($post_id, $lang_code);
				}

				update_post_meta($post_id, '_cs_pending_wpml_lang', $lang_code);
				if ($source_id) {
					update_post_meta($post_id, '_cs_pending_wpml_source', $source_id);
				} else {
					delete_post_meta($post_id, '_cs_pending_wpml_source');
				}
			}
		}

		// الكاتيجوري. لازم تيجي *بعد* تحديد لغة الماستر فوق (مش قبلها)،
		// وإلا WPML هيلاقي بوست "بلغة افتراضية" بيتحط عليه كاتيجوري عربي
		// موجودة، وهيدبلكيتها بدل ما يستخدمها زي ما هي.
		if (! empty($row['category'])) {
			self::assign_category($post_id, $row['category']);
		}

		// سجّل الحالة دي (كاتيجوري لكل لغة) كمرجع في الحارس. لو أي حاجة
		// بعد كده -- مزامنة تاكسونومي في WPML بعد link_translation، كرون
		// توليد السيشنز، أو حتى حفظ يدوي من لوحة التحكم -- مسحت ترم لغة،
		// الحارس بيرجّعه. شوف class-cs-category-guard.php.
		if (class_exists('CS_Category_Guard')) {
			CS_Category_Guard::remember($post_id);
		}

		// حقول ACF البسيطة.
		if (isset($row['summary'])) {
			update_field('cs_summary', sanitize_textarea_field($row['summary']), $post_id);
		}
		if (! empty($row['delivery_mode'])) {
			update_field('cs_delivery_mode', cs_sanitize_multi_value($row['delivery_mode']), $post_id);
		}
		if (! empty($row['language'])) {
			update_field('cs_language', self::sanitize_languages($row['language']), $post_id);
		}
		if (! empty($row['duration_hours'])) {
			update_field('cs_duration_hours', (int) $row['duration_hours'], $post_id);
		}
		if (! empty($row['base_start_date'])) {
			$date = self::normalize_date($row['base_start_date']);
			if ($date) {
				update_field('cs_base_start_date', $date, $post_id);
			} else {
				return new WP_Error('bad_date', 'تاريخ base_start_date غلط. الصيغ المقبولة: 2026-03-05 أو 3/5/2026 أو 5/3/2026.');
			}
		}
		if (! empty($row['recurrence'])) {
			$rec = self::normalize_recurrence($row['recurrence']);
			if ($rec) {
				update_field('cs_recurrence', $rec, $post_id);
			}
		}

		// الأماكن والأسعار (repeater). المكان نص حر (مش لازم يبقى كود دولة).
		if (isset($row['countries_prices']) && '' !== $row['countries_prices']) {
			$country_rows = self::parse_country_prices($row['countries_prices']);
			if (empty($country_rows)) {
				return new WP_Error('bad_countries', 'عمود countries_prices متكتوبش صح، الصيغة: Cairo:1500:USD|Alex:800:USD');
			}
			update_field('cs_country_prices', $country_rows, $post_id);
		}

		// الأهداف (repeater).
		if (isset($row['objectives']) && '' !== $row['objectives']) {
			$objectives = array();
			foreach (explode('|', $row['objectives']) as $obj) {
				$obj = trim($obj);
				if ('' !== $obj) {
					$objectives[] = array('text' => sanitize_text_field($obj));
				}
			}
			if ($objectives) {
				update_field('cs_objectives', $objectives, $post_id);
			}
		}

		// الجدول اليومي (repeater جوه repeater).
		if (isset($row['schedule']) && '' !== $row['schedule']) {
			$schedule = self::parse_schedule($row['schedule']);
			if ($schedule) {
				update_field('cs_schedule', $schedule, $post_id);
			}
		}

		// ملف الـ PDF (اختياري، لازم يكون مرفوع في مكتبة الميديا بالفعل).
		if (! empty($row['outline_pdf_url'])) {
			$attachment_id = attachment_url_to_postid(esc_url_raw($row['outline_pdf_url']));
			if ($attachment_id) {
				update_field('cs_outline_pdf', $attachment_id, $post_id);
			}
		}

		// صورة الكورس (Featured Image). اختياري -- لو العمود فاضي، الكورس
		// (وكل السيشنز بتاعته) هيفضل من غير صورة عادي.
		$image_warning = '';
		if (! empty($row['image_url'])) {
			self::debug_log("صف {$line}: قبل تنزيل/رفع الصورة -- {$row['image_url']}");
			$image_result = self::attach_course_image(esc_url_raw($row['image_url']), $post_id, $title);
			self::debug_log("صف {$line}: بعد تنزيل/رفع الصورة (خلص من غير ما يقف).");
			if (is_wp_error($image_result)) {
				// خطأ في الصورة مش لازم يوقف باقي الصف -- سجّله وكمّل عادي.
				$image_warning = $image_result->get_error_message();
			}
		}


		// مهم جدًا: الاستيراد لازم ينتهي بنفس الـ finalization التي تحصل عند الضغط
		// على Update من لوحة التحكم. مجرد استدعاء generate_sessions() هنا لا
		// يشغّل acf/save_post، وبالتالي كان الكورس بعد الشيت يحتاج Update يدوي
		// حتى تستقر علاقات الـ Session -> Master والـ Category والـ WPML.
		//
		// on_master_save() هو المصدر الوحيد للحقيقة للحفظ اليدوي: يعمل reconcile،
		// يولّد/يكمّل السيشنز، ثم يفرض الـ taxonomy الصحيحة على السيشنز. نستدعيه
		// هنا بعد كتابة كل حقول الشيت، بحيث Import و Update يعطيا نفس النتيجة.
		if (class_exists('CS_Recurrence')) {
			self::debug_log("صف {$line}: قبل finalization (نفس مسار Update اليدوي).");
			CS_Recurrence::on_master_save($post_id);
			self::debug_log("صف {$line}: بعد finalization.");
		}

		return array('status' => $status, 'post_id' => $post_id, 'image_warning' => $image_warning);
	}

	/**
	 * تسجيل سطر تتبّع (breadcrumb) في wp-content/debug.log -- بس لو
	 * WP_DEBUG_LOG مفعّل. مستخدمة مؤقتًا عشان نعرف بالظبط الاستيراد
	 * بيوصل لحد فين قبل ما يقف، لو حصلت مشكلة زي نفاذ الذاكرة أو تجاوز
	 * مهلة السيرفر (النوع اللي مش بيتسجّل تلقائي كـ PHP Fatal Error عادي).
	 */
	protected static function debug_log($message)
	{
		if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
			error_log('[CS_Import] ' . $message);
		}
	}

	/* ================= Helpers ================= */

	/**
	 * ينزّل صورة من رابط (image_url في الشيت) ويرفعها كـ Featured Image
	 * للكورس (الماستر). الصورة دي هي اللي هتظهر في الكارت، وبما إن كل
	 * السيشنز بتاعت الكورس بتقرا الصورة من الماستر (get_the_post_thumbnail_url
	 * بيتنادى بـ master_id دايمًا)، فبيتكرر تلقائي مع كل نسخه من غير أي كود إضافي.
	 *
	 * @param string $url     رابط الصورة.
	 * @param int    $post_id ID الكورس (الماستر).
	 * @param string $title   اسم الكورس (يتستخدم كـ alt/description للصورة).
	 * @return int|WP_Error   ID الـ attachment، أو WP_Error لو فشل التنزيل.
	 */
	protected static function attach_course_image($url, $post_id, $title)
	{
		if (! $url) {
			return new WP_Error('bad_image_url', 'رابط الصورة فاضي.');
		}

		// لو صورة الكورس الحالية جايه بالظبط من نفس الرابط ده، بلاش نعيد
		// تنزيلها تاني من الإنترنت. ده أكبر سبب في بطء رفع نفس الشيت تاني
		// للتحديث (عشان رابط الصورة غالبًا مابيتغيّرش بين رفعة والتانية،
		// وكان بيتنزّل من جديد في كل مرة من غير داعي).
		$current_thumb_id = get_post_thumbnail_id($post_id);
		if ($current_thumb_id && get_post_meta($current_thumb_id, '_cs_source_image_url', true) === $url) {
			return $current_thumb_id;
		}

		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// media_sideload_image() بيحاول يحدد امتداد الصورة (jpg/png/..) من نفس
		// الرابط، ولو الرابط من نوع CDN زي Unsplash/imgix (رابط بيرجع صورة بس من
		// غير .jpg/.png ظاهر في المسار، وبدله بارامترز زي ?w=1200) بيفشل ويرجّع
		// "Invalid image URL" حتى لو الرابط شغال فعلاً وبيرجّع صورة حقيقية. عشان
		// كده بننزّل الملف بنفسنا الأول، وبعدين نحدد نوعه من محتواه الفعلي (مش من
		// شكل الرابط) باستخدام wp_check_filetype_and_ext().
		// مهلة قصيرة (20 ثانية) بدل الافتراضي (300 ثانية) -- لو رابط الصورة
		// بطيء أو مش راجع رد خالص، الأفضل نرجع خطأ واضح بسرعة بدل ما
		// نسيب الطلب مستني لحد ما السيرفر (php-fpm/nginx) يقفله بالقوة
		// برجوع صفحة 500 فاضية من غير أي رسالة.
		$tmp_file = download_url($url, 20);
		if (is_wp_error($tmp_file)) {
			return $tmp_file;
		}

		$filetype = wp_check_filetype_and_ext($tmp_file, $url);
		$ext      = ! empty($filetype['ext']) ? $filetype['ext'] : '';

		if (! $ext) {
			// احتياطي أخير: جرب نقرا نوع الصورة من الـ bytes نفسها مباشرة.
			$size = @getimagesize($tmp_file);
			$map  = array(
				IMAGETYPE_JPEG => 'jpg',
				IMAGETYPE_PNG  => 'png',
				IMAGETYPE_GIF  => 'gif',
				IMAGETYPE_WEBP => 'webp',
			);
			if ($size && isset($map[$size[2]])) {
				$ext = $map[$size[2]];
			}
		}

		if (! $ext) {
			@unlink($tmp_file);
			/* translators: %s: image URL from the import sheet. */
			return new WP_Error('bad_image_url', sprintf('الرابط ده مش صورة صالحة أو مش متاح: %s', $url));
		}

		$file_array = array(
			'name'     => sanitize_file_name($title) . '-' . wp_generate_password(6, false) . '.' . $ext,
			'tmp_name' => $tmp_file,
		);

		// توليد الـ thumbnails (كل الأحجام المسجّلة في الثيم/الإضافات) بيفتح
		// الصورة كاملة في الذاكرة لكل حجم على حدة -- لو الصورة الأصلية عالية
		// الدقة (زي صور من مواقع ستوك بدون تصغير)، ده ممكن يستهلك ذاكرة
		// كتير جدًا في نفس الطلب ويخلّي الاستضافة (خصوصًا الاستضافات
		// المشتركة اللي عندها حد أقصى للذاكرة/المعالجة للحساب) تقفل عملية
		// PHP فورًا من غير ما تسجّل أي خطأ حتى (500 فاضية تمامًا زي اللي
		// كانت بتظهر). فبنقلّل الاستهلاك ده وقت رفع صورة الكورس بس:
		// (1) بنحدد سقف أقصى لأبعاد الصورة الأصلية نفسها (1600px) قبل ما
		//     ووردبريس يعالجها، و(2) بنلغي توليد كل الأحجام الإضافية
		//     ونسيب بس النسخة الأساسية (الكارت والسيشنز بيقروا الصورة من
		//     الماستر مباشرة على أي حال).
		add_filter('big_image_size_threshold', array(__CLASS__, 'cap_image_threshold'), 999);
		add_filter('intermediate_image_sizes_advanced', '__return_empty_array', 999);

		$attachment_id = media_handle_sideload($file_array, $post_id, $title);

		remove_filter('big_image_size_threshold', array(__CLASS__, 'cap_image_threshold'), 999);
		remove_filter('intermediate_image_sizes_advanced', '__return_empty_array', 999);

		if (is_wp_error($attachment_id)) {
			@unlink($tmp_file);
			return $attachment_id;
		}

		// سجّل مصدر الصورة عشان المرة الجاية نعرف نتخطى التنزيل لو الرابط زي ما هو.
		update_post_meta($attachment_id, '_cs_source_image_url', $url);

		set_post_thumbnail($post_id, $attachment_id);

		return $attachment_id;
	}

	/**
	 * بتحدد سقف 1600px لأبعاد صورة الكورس وقت الرفع (بدل الافتراضي
	 * 2560px)، عشان تقلل استهلاك الذاكرة وقت معالجتها. مستخدمة بس مؤقتًا
	 * حوالين استدعاء media_handle_sideload() في attach_course_image().
	 */
	public static function cap_image_threshold()
	{
		return 1600;
	}

	/**
	 * "Weekly"/"أسبوعي" -> 'weekly'، "Biweekly"/"كل أسبوعين"/"نصف شهري" -> 'biweekly'.
	 * ليه مش sanitize_key() لوحدها: sanitize_key() بتشيل أي حرف مش
	 * إنجليزي/رقم، فلو الشيت عربي وكتب "أسبوعي" كانت القيمة بترجع فاضية
	 * تمامًا -- والفنكشن القديمة كانت ساكتة عن كده (بترجع من غير أي
	 * تحديث، والـ fallback الافتراضي في class-cs-recurrence.php بيبقى
	 * "weekly" على أي حال، فمكنش بيظهر كخطأ ظاهر إلا لو حد محتاج
	 * "biweekly" في شيت عربي تحديدًا -- كانت بترجع "weekly" غلط بصمت).
	 */
	protected static function normalize_recurrence($raw)
	{
		$raw = trim(mb_strtolower($raw, 'UTF-8'));

		$weekly_values = array('weekly', 'أسبوعي', 'اسبوعي', 'اسبوعيا', 'أسبوعياً');
		$biweekly_values = array('biweekly', 'bi-weekly', 'كل أسبوعين', 'كل اسبوعين', 'نصف شهري', 'نصف اسبوعي');

		if (in_array($raw, $weekly_values, true)) {
			return 'weekly';
		}
		if (in_array($raw, $biweekly_values, true)) {
			return 'biweekly';
		}

		// آخر محاولة: sanitize_key العادية (تغطي أي قيمة إنجليزي متكتوبة
		// بشكل مختلف شوية زي "Weekly " أو "WEEKLY").
		$key = sanitize_key($raw);
		if (in_array($key, array('weekly', 'biweekly'), true)) {
			return $key;
		}

		return '';
	}

	protected static function assign_category($post_id, $category_value)
	{
		$category_value = trim((string) $category_value);

		// لو القيمة رقم صريح بالكامل، اعتبرها term_id مباشرة بدل ما تتفهم
		// كاسم. ده بيتفادى مشكلة المطابقة بالاسم نهائيًا (فروق مسافات، نوع
		// الفاصلة العربية، حروف مخفية من نسخ/لصق إكسل أو جوجل شيتس بين شيت
		// عربي وشيت إنجليزي لنفس الكورس).
		if (ctype_digit($category_value)) {
			$term_id = (int) $category_value;

			if ($term_id) {
				// ⚠️ مش بنستخدم get_term() العادية هنا -- WPML بيهوك فلتر
				// "get_term" نفسه (بالظبط زي get_term_by()، شوف شرح
				// find_or_create_category_term() تحت) وبيرفض يرجّع term
				// مش في "لغة السياق الحالي" وقت الاستيراد (غالبًا إنجليزي،
				// لغة الأدمن الافتراضية)، حتى لو الـ ID اللي إنت مدّيه صحيح
				// 100% وموجود فعلاً وعربي. النتيجة: get_term(88, ...) كانت
				// بترجع فاضي دايمًا، فمكنش بيتحط أي كاتيجوري خالص -- بالظبط
				// نفس أعراض البگ الأصلي. WP_Term_Query مع suppress_filters
				// بيتجاهل فلتر WPML ده ويتأكد إن الـ term موجودة فعلاً
				// بالـ ID المطلوب من غير ما يهتم بلغتها.
				$query = new WP_Term_Query(array(
					'taxonomy'         => CS_TAX,
					'include'          => array($term_id),
					'hide_empty'       => false,
					'number'           => 1,
					'suppress_filters' => true,
				));

				// ⚠️ التأكد فوق (suppress_filters) بيضمن بس إن الـ term_id ده
				// موجود فعلاً -- لكن ده مش كفاية. wp_set_object_terms()
				// القياسية نفسها بتعمل جوّاها استدعاء *تاني* لـ term_exists()
				// لكل term، وده *برضه* بيتفلتر بواسطة WPML بنفس الطريقة --
				// فحتى بعد التأكد ده، wp_set_object_terms() لوحدها كانت
				// بترفض تحط أي term_id مش بلغة سياق الأدمن/الكرون الحالي
				// وقت الاستيراد (غالبًا إنجليزي)، من غير أي error يظهر.
				// النتيجة اللي كانت بتظهر: category ID إنجليزي بيتحط عادي
				// (بيطابق لغة السياق)، ونفس الكود بالظبط مع ID عربي
				// بيتجاهله بصمت. without_language_filter() بتوقف فلترة
				// اللغة دي تمامًا لحد ما wp_set_object_terms() تخلص.
				if (! empty($query->terms)) {
					// assign_category_term() (مش wp_set_object_terms() مباشرة):
					// لو الكورس ده معاه بالفعل كاتيجوري بلغة مختلفة (مثلاً
					// اتحطله عربي قبل كده وده صف الشيت الإنجليزي)، لازم
					// الاتنين يفضلوا محطوطين مع بعض بدل ما الإنجليزي يمسح
					// العربي -- شوف شرح كامل في CS_WPML::assign_category_term().
					if (class_exists('CS_WPML') && CS_WPML::active()) {
						CS_WPML::assign_category_term($post_id, $term_id);
					} else {
						wp_set_object_terms($post_id, $term_id, CS_TAX, false);
					}
				}
			}

			return;
		}

		$category_name = sanitize_text_field($category_value);

		// لغة الكورس نفسه (اتحددت فوق في import_row() قبل ما نوصل هنا).
		// لو الكاتيجوري دي هتتعمل جديدة دلوقتي، لازم تاخد نفس اللغة دي --
		// مش لغة سياق الريكوست الافتراضية (شوف الشرح في set_term_language()).
		$lang_code = (class_exists('CS_WPML') && CS_WPML::active())
			? CS_WPML::post_language($post_id)
			: '';

		$term_id = self::find_or_create_category_term($category_name, $lang_code);

		if (! $term_id) {
			return;
		}

		// نفس السبب اللي في المسار الرقمي فوق: wp_set_object_terms() بتعمل
		// term_exists() جوّاها لكل term، وده بيتفلتر بواسطة WPML حسب لغة
		// سياق الأدمن/الكرون الحالي -- حتى لو $term_id ده رجع لسه دلوقتي
		// من wp_insert_term()/find_or_create_category_term() ومضمون موجود.
		// نفس المنطق بالظبط اللي في المسار الرقمي فوق -- assign_category_term()
		// بتحافظ على أي كاتيجوري بلغة مختلفة موجودة على الكورس بالفعل بدل
		// ما تمسحها.
		if (class_exists('CS_WPML') && CS_WPML::active()) {
			CS_WPML::assign_category_term($post_id, (int) $term_id);
		} else {
			wp_set_object_terms($post_id, (int) $term_id, CS_TAX, false);
		}
	}

	/**
	 * بيدوّر على كاتيجوري باسم معيّن، ولو مش موجودة بينشئها. بيرجع الـ
	 * term_id، أو 0 لو مقدرش يوصلها خالص.
	 *
	 * ليه مش بنستخدم get_term_by() العادية: لما WPML شغّال، get_term_by()
	 * بتقصر البحث على "لغة الموقع الحالية" بس (الإنجليزي غالبًا)، حتى لو
	 * الاسم اللي بندور عليه عربي ومكتوب صح 100%. النتيجة: مكنش بيلاقيها
	 * أبدًا، فكان بيحاول ينشئها من جديد بـ wp_insert_term()، ووردبريس
	 * بيرفض لأنها موجودة فعلاً (term_exists) -- وكان الكود وقتها بيستسلم
	 * ويرجع من غير ما يحط أي كاتيجوري، فيفضل الكورس على آخر كاتيجوري
	 * كانت متحطة عليه قبل كده (غالبًا الإنجليزي، من استنساخ بيانات
	 * الترجمة). هنا بنستخدم WP_Term_Query مع suppress_filters عشان
	 * البحث ميتأثرش بفلترة لغة WPML، وكمان لو حصل term_exists برضه،
	 * بناخد الـ ID الموجود فعلاً من الخطأ نفسه بدل ما نستسلم.
	 *
	 * @param string $category_name
	 * @param string $lang_code      لغة الكورس اللي هيتحط عليه (لو WPML
	 *                                شغال). بتتحط على الـ term بس لو
	 *                                هتتعمل جديدة دلوقتي -- الموجودة
	 *                                مش بنلمس لغتها خالص (شوف set_term_language()).
	 */
	protected static function find_or_create_category_term($category_name, $lang_code = '')
	{
		$query = new WP_Term_Query(array(
			'taxonomy'         => CS_TAX,
			'name'             => $category_name,
			'hide_empty'       => false,
			'number'           => 1,
			'suppress_filters' => true,
		));

		if (! empty($query->terms)) {
			return (int) $query->terms[0]->term_id;
		}

		$inserted = wp_insert_term($category_name, CS_TAX);

		if (is_wp_error($inserted)) {
			$existing_id = $inserted->get_error_data('term_exists');
			return $existing_id ? (int) $existing_id : 0;
		}

		$term_id = (int) $inserted['term_id'];

		// term جديدة فعلاً (مش موجودة قبل كده) -- ديها لغة الكورس نفسه
		// دلوقتي، قبل ما WPML يحطلها لغة سياق الريكوست الافتراضية.
		if ($lang_code && class_exists('CS_WPML') && CS_WPML::active()) {
			CS_WPML::set_term_language($term_id, $lang_code);
		}

		return $term_id;
	}

	/**
	 * "Cairo:1500:USD|Alex:800:USD|Mansoura:900:USD" ->
	 * [ ['country'=>'Cairo','price'=>1500,'currency'=>'USD'], ... ]
	 *
	 * المكان (أول جزء قبل أول ":") نص حر بالكامل -- ممكن يبقى اسم دولة أو
	 * مدينة أو أي حاجة تانية، بيتاخد زي ما اتكتب بالظبط (بعد تنضيف بسيط)
	 * من غير أي تحقق من قايمة دول محددة.
	 */
	protected static function parse_country_prices($raw)
	{
		$rows = array();
		foreach (explode('|', $raw) as $chunk) {
			$chunk = trim($chunk);
			if ('' === $chunk) {
				continue;
			}
			$parts    = array_map('trim', explode(':', $chunk));
			$location = isset($parts[0]) ? sanitize_text_field($parts[0]) : '';

			if ('' === $location) {
				continue;
			}

			$rows[] = array(
				'country'  => $location,
				'price'    => isset($parts[1]) ? (float) $parts[1] : 0,
				'currency' => isset($parts[2]) && $parts[2] !== '' ? strtoupper($parts[2]) : 'USD',
			);
		}
		return $rows;
	}

	/**
	 * "English / Arabic / French" -> "English / Arabic / French" (بعد تنضيف
	 * المسافات الزيادة حوالين كل لغة). بيسيب النص زي ما اتكتب بالظبط --
	 * مفيش قايمة لغات محددة، اكتب أي لغة عايزها مفصولة بـ "/".
	 * (مجرد wrapper حوالين cs_sanitize_multi_value() المشتركة مع delivery_mode.)
	 */
	protected static function sanitize_languages($raw)
	{
		return cs_sanitize_multi_value($raw);
	}

	/**
	 * "Day 1 – Intro: Point A;Point B||Day 2: Point C" -> repeater array
	 */
	protected static function parse_schedule($raw)
	{
		$days = array();
		foreach (explode('||', $raw) as $day_chunk) {
			$day_chunk = trim($day_chunk);
			if ('' === $day_chunk) {
				continue;
			}

			$colon_pos = strpos($day_chunk, ':');
			if (false === $colon_pos) {
				$day_title = $day_chunk;
				$points_raw = '';
			} else {
				$day_title  = trim(substr($day_chunk, 0, $colon_pos));
				$points_raw = trim(substr($day_chunk, $colon_pos + 1));
			}

			$points = array();
			if ('' !== $points_raw) {
				foreach (explode(';', $points_raw) as $point) {
					$point = trim($point);
					if ('' !== $point) {
						$points[] = array('text' => sanitize_text_field($point));
					}
				}
			}

			$days[] = array(
				'day_title' => sanitize_text_field($day_title),
				'points'    => $points,
			);
		}
		return $days;
	}

	/**
	 * إكسيل بيحفظ التواريخ بصيغ مختلفة حسب لغة الجهاز (زي 3/5/2026 أو
	 * 05/03/2026)، مش بس YYYY-MM-DD. الدالة دي بتجرب كذا صيغة معروفة
	 * قبل ما ترفض التاريخ خالص.
	 */
	protected static function normalize_date($raw)
	{
		$raw = trim($raw);
		if ('' === $raw) {
			return false;
		}

		// الصيغ المقبولة بالترتيب. بنبدأ بـ يوم/شهر/سنة (زي مصر وأوروبا) لأن
		// دي الصيغة اللي بتتكتب بيها التواريخ في الشيت عادةً. اللي زي "3/5/2026"
		// هيتقرا 3 مايو (مش مارس 5). لو اليوم أكبر من 12 (يعني مينفعش يبقى شهر)،
		// بيرجع تلقائي لصيغة أمريكا شهر/يوم عشان يقدر يقرا تواريخ إكسيل الأمريكي.
		$formats = array('Y-m-d', 'j/n/Y', 'd/m/Y', 'n/j/Y', 'm/d/Y', 'Y/m/d');

		foreach ($formats as $format) {
			$date = DateTime::createFromFormat('!' . $format, $raw);
			if ($date instanceof DateTime) {
				$errors = DateTime::getLastErrors();
				if (empty($errors['warning_count']) && empty($errors['error_count'])) {
					return $date->format('Y-m-d');
				}
			}
		}

		return false;
	}

	/* ================= نموذج CSV فاضي ================= */

	public static function download_sample()
	{
		check_admin_referer('cs_download_sample');

		if (! current_user_can('edit_posts')) {
			wp_die('غير مسموح.');
		}

		$headers = array(
			'title',
			'category',
			'summary',
			'delivery_mode',
			'language',
			'duration_hours',
			'base_start_date',
			'recurrence',
			'countries_prices',
			'objectives',
			'schedule',
			'outline_pdf_url',
			'image_url',
			'course_id',
			'lang',
			'source_course_id',
		);

		$sample_row_en = array(
			'Customer Data, Analytics & Personalization Strategies',
			'Digital Transformation & Future Skills',
			'A modern capability-building program centered on customer data and personalization.',
			'Hybrid / Online / In-Person',
			'English / Arabic',
			'40',
			'2026-03-05',
			'weekly',
			'Cairo:1500:USD|Alex:800:USD|Riyadh:900:USD',
			'Frame the main decisions involved in customer data strategy.|Use practical tools to improve personalization.',
			'Day 1 - Data Landscape: Frame core concepts;Assess current practices||Day 2 - Segmentation: Define strong segmentation;Diagnose bottlenecks',
			'',
			'https://images.unsplash.com/photo-1552664730-d307ca884978?w=800',
			'',
			'en',
			'',
		);

		// مثال صف الترجمة العربية لنفس الكورس -- لاحظ إن كل أعمدة المواعيد
		// والدول سايبينها فاضية، لأنها هتتاخد أوتوماتيك من source_course_id.
		$sample_row_ar = array(
			'استراتيجيات بيانات العملاء والتحليلات والتخصيص',
			'التحول الرقمي ومهارات المستقبل',
			'برنامج بناء قدرات حديث يركز على بيانات العملاء والتخصيص.',
			'',
			'',
			'',
			'',
			'',
			'صياغة القرارات الأساسية المتعلقة باستراتيجية بيانات العملاء.|استخدام أدوات عملية لتحسين التخصيص.',
			'اليوم 1 - خريطة البيانات: صياغة المفاهيم الأساسية;تقييم الممارسات الحالية||اليوم 2 - التقسيم: تحديد تقسيم قوي;تشخيص نقاط الاختناق',
			'',
			'',
			'',
			'ar',
			'123', // غيّرها لـ ID الكورس الإنجليزي الحقيقي بعد ما يترفع.
		);

		nocache_headers();
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=courses-sample-template.csv');

		$out = fopen('php://output', 'w');
		fputs($out, "\xEF\xBB\xBF"); // BOM عشان اكسل يقرا العربي والإنجليزي صح.
		fputcsv($out, $headers);
		fputcsv($out, $sample_row_en);
		fputcsv($out, $sample_row_ar);
		fclose($out);
		exit;
	}
}

CS_Import::init();
