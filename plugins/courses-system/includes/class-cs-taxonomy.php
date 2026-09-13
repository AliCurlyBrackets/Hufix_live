<?php
/**
 * Feature: Course Category (Taxonomy) + Category Image
 * الكاتيجوري باسم + صورة. الصورة بتتخزن كـ term meta (attachment ID).
 * صفحة عرض الكاتيجوريز بتقرأ الاسم + الصورة من هنا.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CS_Taxonomy {

	const IMG_META = 'cs_category_image';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );

		// حقل صورة في صفحة إضافة/تعديل الكاتيجوري.
		add_action( CS_TAX . '_add_form_fields', array( __CLASS__, 'add_field' ) );
		add_action( CS_TAX . '_edit_form_fields', array( __CLASS__, 'edit_field' ), 10, 2 );
		add_action( 'created_' . CS_TAX, array( __CLASS__, 'save_field' ) );
		add_action( 'edited_' . CS_TAX, array( __CLASS__, 'save_field' ) );

		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'media_scripts' ) );

		// فيكس: نفس بگ فلترة اللغة بتاع WPML اللي بيضرب الاستيراد (شوف
		// find_or_create_category_term() في class-cs-import.php)، بس هنا
		// بيضرب checkbox الكاتيجوري في شاشة تعديل الكورس العادية. شوف
		// شرح كامل فوق fix_wpml_category_checkbox_save().
		add_action( 'save_post_' . CS_CPT, array( __CLASS__, 'fix_wpml_category_checkbox_save' ), 20 );
	}

	/**
	 * فيكس: كاتيجوري بلغة غير لغة سياق الأدمن الحالية (مثلاً كاتيجوري
	 * عربي واللوحة شغالة إنجليزي) بتتشال بصمت لما تحفظ من الـ Categories
	 * checkbox العادي في شاشة تعديل الكورس -- رغم إن التيك اتحط صح.
	 *
	 * السبب: wp_set_object_terms() القياسية بتاعة ووردبريس بتنادي
	 * term_exists() لكل term_id متأشّر عليه، وterm_exists() بتستخدم
	 * get_term_by() اللي WPML بيهوكها ويرفض يرجّع أي term مش في "لغة
	 * السياق الحالي" للوحة التحكم -- حتى لو الـ ID صحيح ومربوط بالبوست
	 * فعلاً. النتيجة: كاتيجوري بنفس لغة سياق الأدمن ("English" غالبًا)
	 * بيتحفظ عادي، وأي كاتيجوري بلغة تانية بيتجاهله ووردبريس تمامًا من
	 * غير أي error يظهر -- بالظبط نفس أعراض بگ الاستيراد القديم
	 * (find_or_create_category_term() في class-cs-import.php)، بس هنا
	 * في مسار الحفظ العادي مش الاستيراد.
	 *
	 * الحل: بعد ما ووردبريس يخلص الـ save العادي بتاعه (واللي يكون
	 * ممكن يكون سقّط بعض الـ IDs)، بنقرا tax_input الخام من $_POST
	 * ونطبّقه إحنا تاني، لكن بـ WP_Term_Query مع suppress_filters=true
	 * عشان نتأكد إن كل term_id اتبعت فعلاً موجود -- بغض النظر عن لغته --
	 * قبل ما نحطه، بدل ما نسيب term_exists() تفلترهم حسب اللغة.
	 *
	 * @param int $post_id
	 */
	public static function fix_wpml_category_checkbox_save( $post_id ) {
		if ( ! class_exists( 'CS_WPML' ) || ! CS_WPML::active() ) {
			return;
		}
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( get_post_type( $post_id ) !== CS_CPT ) {
			return;
		}
		if ( ! isset( $_POST['tax_input'][ CS_TAX ] ) ) {
			return;
		}

		$posted = array_filter( array_map( 'absint', (array) $_POST['tax_input'][ CS_TAX ] ) );
		$post_lang = CS_WPML::post_language( $post_id );

		$query = new WP_Term_Query( array(
			'taxonomy'         => CS_TAX,
			'include'          => $posted,
			'hide_empty'       => false,
			'suppress_filters' => true,
			'fields'           => 'ids',
		) );

		$valid_ids = is_array( $query->terms ) ? array_map( 'intval', $query->terms ) : array();

		/*
		 * كل Post ترجمة لازم يحمل Category من نفس لغته فقط.
		 * لو المستخدم شال كل الـ Categories، نسمح له بالحفظ بدون Category.
		 */
		$language_valid_ids = array();
		foreach ( $valid_ids as $term_id ) {
			$term_lang = CS_WPML::term_language( $term_id );
			if ( ! $post_lang || ! $term_lang || $term_lang === $post_lang ) {
				$language_valid_ids[] = $term_id;
			}
		}

		remove_action( 'save_post_' . CS_CPT, array( __CLASS__, 'fix_wpml_category_checkbox_save' ), 20 );

		CS_WPML::without_language_filter( function () use ( $post_id, $language_valid_ids ) {
			wp_set_object_terms( $post_id, $language_valid_ids, CS_TAX, false );
		} );

		// لو WPML عمل taxonomy sync ومسح Category الترجمة المقابلة،
		// رجّع كل ترجمة إلى Category الخاصة بلغتها.
		if ( ! empty( $language_valid_ids ) ) {
			CS_WPML::sync_category_to_translations( $post_id, $language_valid_ids );
		}

		add_action( 'save_post_' . CS_CPT, array( __CLASS__, 'fix_wpml_category_checkbox_save' ), 20 );

		if ( class_exists( 'CS_Category_Guard' ) ) {
			CS_Category_Guard::remember( $post_id );
		}
	}

	public static function register() {
		$labels = array(
			'name'          => 'Course Categories',
			'singular_name' => 'Category',
			'menu_name'     => 'Categories',
			'add_new_item'  => 'Add New Category',
		);

		register_taxonomy( CS_TAX, CS_CPT, array(
			'labels'            => $labels,
			'public'            => true,
			'hierarchical'      => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'course-category' ),
		) );
	}

	/** رابط صورة الكاتيجوري (لاستخدامه في صفحة العرض). */
	public static function image_url( $term_id, $size = 'medium' ) {
		$attachment_id = (int) get_term_meta( $term_id, self::IMG_META, true );
		if ( ! $attachment_id ) {
			return '';
		}
		return wp_get_attachment_image_url( $attachment_id, $size );
	}

	/* ================= Admin UI ================= */

	public static function media_scripts( $hook ) {
		if ( strpos( $hook, 'edit-tags' ) !== false || strpos( $hook, 'term' ) !== false ) {
			wp_enqueue_media();
		}
	}

	public static function add_field() {
		?>
		<div class="form-field">
			<label>Category Image</label>
			<input type="hidden" name="cs_category_image" id="cs_category_image" value="">
			<button type="button" class="button cs-upload-img">Choose Image</button>
			<div id="cs_category_image_preview" style="margin-top:8px"></div>
		</div>
		<?php self::inline_js();
	}

	public static function edit_field( $term ) {
		$attachment_id = (int) get_term_meta( $term->term_id, self::IMG_META, true );
		$url           = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'thumbnail' ) : '';
		?>
		<tr class="form-field">
			<th><label>Category Image</label></th>
			<td>
				<input type="hidden" name="cs_category_image" id="cs_category_image" value="<?php echo esc_attr( $attachment_id ); ?>">
				<button type="button" class="button cs-upload-img">Choose Image</button>
				<div id="cs_category_image_preview" style="margin-top:8px">
					<?php if ( $url ) : ?><img src="<?php echo esc_url( $url ); ?>" style="max-width:120px"><?php endif; ?>
				</div>
			</td>
		</tr>
		<?php self::inline_js();
	}

	public static function save_field( $term_id ) {
		if ( isset( $_POST['cs_category_image'] ) ) {
			update_term_meta( $term_id, self::IMG_META, absint( $_POST['cs_category_image'] ) );
		}
	}

	protected static function inline_js() {
		?>
		<script>
		jQuery(function($){
			$(document).on('click', '.cs-upload-img', function(e){
				e.preventDefault();
				var frame = wp.media({ title:'Select Image', multiple:false });
				frame.on('select', function(){
					var a = frame.state().get('selection').first().toJSON();
					$('#cs_category_image').val(a.id);
					$('#cs_category_image_preview').html('<img src="'+a.url+'" style="max-width:120px">');
				});
				frame.open();
			});
		});
		</script>
		<?php
	}
}

CS_Taxonomy::init();