<?php
/**
 * Feature: Category Cleanup
 * أداة أدمن لتنضيف الكاتيجوريز المكررة اللي اتعملت بسبب الـ bug القديم
 * (ترتيب لينكينج اللغة في WPML قبل تعيين الكاتيجوري -- شوف class-cs-import.php).
 * البگ ده اتصلّح في الاستيراد الجديد، بس الكاتيجوريز المكررة اللي اتعملت
 * *قبل* الفيكس لسه قاعدة في الداتابيز -- الصفحة دي بتوريها كلها في جدول
 * واحد (مع لغة كل واحدة لو WPML شغّال)، وبتدّيك زرار "ادمجهم" يختار
 * الكاتيجوري الصح، وينقل كل الكورسات (والسيشنز بتاعتها) من المكررة
 * للصح، وبعدين يمسح المكررة نهائي.
 */

if (! defined('ABSPATH')) {
	exit;
}

class CS_Category_Cleanup
{
	const NONCE_ACTION = 'cs_category_cleanup';

	public static function init()
	{
		add_action('admin_menu', array(__CLASS__, 'add_menu'));
	}

	public static function add_menu()
	{
		add_submenu_page(
			'edit.php?post_type=' . CS_CPT,
			'دمج الكاتيجوريز المكررة',
			'دمج الكاتيجوريز المكررة',
			'manage_categories',
			'cs-category-cleanup',
			array(__CLASS__, 'render_page')
		);
	}

	/**
	 * كود لغة term معيّن (WPML بيخزّن عناصر التاكسونومي بـ term_taxonomy_id
	 * مش term_id -- عكس البوستات).
	 */
	protected static function term_language($term)
	{
		if (! (defined('ICL_SITEPRESS_VERSION') || class_exists('SitePress'))) {
			return '';
		}
		$lang = apply_filters('wpml_element_language_code', null, array(
			'element_id'   => $term->term_taxonomy_id,
			'element_type' => 'tax_' . CS_TAX,
		));
		return $lang ? $lang : '';
	}

	/**
	 * كل البوستات (ماستر + سيشنز) المتحطة على term معيّن، بغض النظر عن
	 * فلترة لغة WPML (suppress_filters) عشان الجدول يعرض العدد الحقيقي.
	 */
	protected static function objects_in_term($term_id)
	{
		$ids = get_objects_in_term($term_id, CS_TAX);
		if (is_wp_error($ids)) {
			return array();
		}
		return array_map('intval', $ids);
	}

	public static function render_page()
	{
		if (! current_user_can('manage_categories')) {
			return;
		}

		$notice = '';

		if (! empty($_POST['cs_cleanup_submit'])) {
			check_admin_referer(self::NONCE_ACTION);
			$notice = self::handle_merge();
		}

		$terms = get_terms(array(
			'taxonomy'   => CS_TAX,
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		));

		if (is_wp_error($terms)) {
			$terms = array();
		}

		$wpml_active = defined('ICL_SITEPRESS_VERSION') || class_exists('SitePress');

?>
		<div class="wrap">
			<h1>دمج الكاتيجوريز المكررة</h1>

			<p>
				الصفحة دي بتوري كل كاتيجوريز الكورسات، مع عدد الكورسات (ماستر + سيشنز) المتحطة
				على كل واحدة، ولغتها في WPML لو شغّال. لو لاقيت كاتيجوريين بنفس المعنى (مثلاً
				واحدة بالإنجليزي "Digital Transformation, Data & AI" وواحدة بالعربي "التحول
				الرقمي، والبيانات، والذكاء الاصطناعي") وهما نفس الحاجة فعليًا:
			</p>
			<ol>
				<li>حدد الاتنين (أو أكتر) من عمود "دمج" جنب اسم الكاتيجوري.</li>
				<li>حدد واحدة بس كـ"الصح" (اللي هتفضل) من عمود "الصح".</li>
				<li>دوس "ادمج المحدد". كل الكورسات هتتنقل للكاتيجوري الصح، والباقي هيتمسح نهائي.</li>
			</ol>
			<p><strong>ملحوظة:</strong> الدمج مينفعش يترجع. لو مش متأكد، سيب الصفحة زي ما هي وارجعلي.</p>

			<?php if ($notice) : ?>
				<div class="notice notice-success" style="padding:10px 12px;"><?php echo wp_kses_post($notice); ?></div>
			<?php endif; ?>

			<form method="post">
				<?php wp_nonce_field(self::NONCE_ACTION); ?>
				<table class="widefat striped" style="max-width:1000px;margin-top:16px;">
					<thead>
						<tr>
							<th style="width:60px;">دمج</th>
							<th style="width:60px;">الصح</th>
							<th>الاسم</th>
							<th>Slug</th>
							<?php if ($wpml_active) : ?><th style="width:90px;">اللغة</th><?php endif; ?>
							<th style="width:90px;">term_id</th>
							<th style="width:110px;">عدد الكورسات</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($terms as $term) :
							$count = count(self::objects_in_term($term->term_id));
							$lang  = $wpml_active ? self::term_language($term) : '';
						?>
							<tr>
								<td><input type="checkbox" name="cs_merge_terms[]" value="<?php echo (int) $term->term_id; ?>"></td>
								<td><input type="radio" name="cs_merge_target" value="<?php echo (int) $term->term_id; ?>"></td>
								<td><?php echo esc_html($term->name); ?></td>
								<td><code><?php echo esc_html($term->slug); ?></code></td>
								<?php if ($wpml_active) : ?><td><?php echo esc_html($lang ?: '—'); ?></td><?php endif; ?>
								<td><?php echo (int) $term->term_id; ?></td>
								<td><?php echo (int) $count; ?></td>
							</tr>
						<?php endforeach; ?>
						<?php if (empty($terms)) : ?>
							<tr><td colspan="7">مفيش أي كاتيجوريز لسه.</td></tr>
						<?php endif; ?>
					</tbody>
				</table>

				<p class="submit">
					<button type="submit" name="cs_cleanup_submit" value="1" class="button button-primary"
						onclick="return confirm('متأكد؟ الدمج ده مينفعش يترجع.');">
						ادمج المحدد
					</button>
				</p>
			</form>
		</div>
<?php
	}

	protected static function handle_merge()
	{
		$selected = isset($_POST['cs_merge_terms']) ? array_map('absint', (array) $_POST['cs_merge_terms']) : array();
		$target   = isset($_POST['cs_merge_target']) ? absint($_POST['cs_merge_target']) : 0;

		$selected = array_values(array_unique(array_filter($selected)));

		if (! $target) {
			return 'من فضلك حدد كاتيجوري واحدة "الصح" (اللي هتفضل).';
		}
		if (! in_array($target, $selected, true)) {
			return 'الكاتيجوري "الصح" اللي حددتها لازم تكون كمان محددة في عمود "دمج".';
		}
		if (count($selected) < 2) {
			return 'اختار كاتيجوريين على الأقل عشان تدمجهم.';
		}
		if (! get_term($target, CS_TAX)) {
			return 'كاتيجوري "الصح" مش موجودة.';
		}

		$moved_posts = 0;
		$deleted     = 0;

		foreach ($selected as $source_id) {
			if ($source_id === $target) {
				continue;
			}
			if (! get_term($source_id, CS_TAX)) {
				continue;
			}

			$post_ids = self::objects_in_term($source_id);
			foreach ($post_ids as $post_id) {
				// استبدال كامل (زي assign_category بالظبط -- كاتيجوري واحدة
				// بس لكل كورس/سيشن). ملفوف بـ without_language_filter()
				// لنفس سبب باقي الأماكن -- لو الكاتيجوري الهدف بلغة مختلفة
				// عن سياق الأدمن وقت الدمج، wp_set_object_terms() كانت
				// هترفضها بصمت.
				if (class_exists('CS_WPML') && CS_WPML::active()) {
					CS_WPML::without_language_filter(function () use ($post_id, $target) {
						wp_set_object_terms($post_id, array((int) $target), CS_TAX, false);
					});
				} else {
					wp_set_object_terms($post_id, array((int) $target), CS_TAX, false);
				}
				$moved_posts++;
			}

			$result = wp_delete_term($source_id, CS_TAX);
			if ($result && ! is_wp_error($result)) {
				$deleted++;
			}
		}

		return sprintf(
			'تم النقل: %d بوست (كورسات + سيشنز)، وتم مسح %d كاتيجوري مكررة.',
			$moved_posts,
			$deleted
		);
	}
}

CS_Category_Cleanup::init();
