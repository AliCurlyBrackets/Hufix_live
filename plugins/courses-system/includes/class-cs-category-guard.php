<?php
/**
 * Feature: Category Guard (حارس كاتيجوريز اللغتين)
 *
 * المشكلة اللي بيحلها:
 * الكورس (الماستر) المفروض يشيل كاتيجوري لكل لغة في نفس الوقت -- الترم
 * الإنجليزي والترم العربي مع بعض -- عشان يظهر في أرشيف الكاتيجوري
 * الإنجليزي *و* أرشيف الكاتيجوري العربي. لكن فيه أكتر من مسار في
 * ووردبريس/WPML بيعمل wp_set_object_terms() بـ append=false (استبدال
 * كامل) وهو شايف ترمز لغة واحدة بس، فبيمسح ترم اللغة التانية بصمت:
 *
 *   1) ميتابوكس الكاتيجوري في شاشة تعديل الكورس: WPML بيفلتر قايمة
 *      الشيك-بوكسات على لغة سياق الأدمن الحالي بس، فترم اللغة التانية
 *      أصلاً مش بيتعرض ولا بيتبعت في $_POST -- وأي "Update" عادي كان
 *      بيمسحه. (اتصلّح كمان في CS_Taxonomy::fix_wpml_category_checkbox_save)
 *   2) CS_WPML::clone_master_fields() وقت الاستيراد كانت بتستبدل ترمز
 *      الترجمة بترمز الأصل. (اتصلّح -- بقت append)
 *   3) مزامنة WPML التلقائية للتاكسونومي بعد link_translation.
 *
 * الحل هنا: بنسجّل على الماستر نفسه (post meta) خريطة "لغة => ترمز"،
 * وبعد أي حفظ بنرجّع أي لغة اختفت من غير قصد. المفتاح: بنستثني اللغة
 * اللي بتتعدّل دلوقتي -- يعني لو إنت بتعدّل الكورس في سياق عربي وشلت
 * الكاتيجوري العربي عن قصد، ده بيتنفّذ عادي؛ اللي بيترجع بس هو ترم
 * اللغة *التانية* اللي مكانش المفروض تتلمس أصلاً.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CS_Category_Guard {

	const META = '_cs_cat_by_lang';
	const NO_LANG = '_none';

	public static function init() {
		add_action( 'save_post_' . CS_CPT, array( __CLASS__, 'on_save' ), 999 );
		add_action( 'cs_sessions_generation_done', array( __CLASS__, 'on_generation_done' ), 99 );
	}

	protected static function wpml() {
		return class_exists( 'CS_WPML' ) && CS_WPML::active();
	}

	public static function current_language() {
		return self::wpml() ? (string) apply_filters( 'wpml_current_language', null ) : '';
	}

	public static function read_terms( $post_id ) {
		$read = function () use ( $post_id ) {
			$terms = wp_get_object_terms( $post_id, CS_TAX, array(
				'fields' => 'ids',
				'suppress_filters' => true,
			) );
			return ( ! $terms || is_wp_error( $terms ) ) ? array() : array_map( 'intval', $terms );
		};
		return self::wpml() ? CS_WPML::without_language_filter( $read ) : $read();
	}

	protected static function term_lang( $term_id ) {
		return self::wpml() ? CS_WPML::term_language( (int) $term_id ) : '';
	}

	protected static function term_exists_raw( $term_id ) {
		$q = new WP_Term_Query( array(
			'taxonomy' => CS_TAX,
			'include' => array( (int) $term_id ),
			'hide_empty' => false,
			'number' => 1,
			'fields' => 'ids',
			'suppress_filters' => true,
		) );
		return ! empty( $q->terms );
	}

	/*
	 * المرجع هنا هو Category الخاصة بلغة الـ Post، وليس خريطة "كل اللغات
	 * على نفس الـ Post". ده هو السلوك الصحيح مع WPML لأن كل ترجمة Post
	 * لها Taxonomy term من لغتها.
	 */
	public static function remember( $post_id ) {
		$post_id = (int) $post_id;
		if ( ! $post_id || ! self::wpml() ) {
			return;
		}

		$lang = CS_WPML::post_language( $post_id );
		if ( ! $lang ) {
			return;
		}

		$ids = array();
		foreach ( self::read_terms( $post_id ) as $term_id ) {
			$term_lang = self::term_lang( $term_id );
			if ( ! $term_lang || $term_lang === $lang ) {
				$ids[] = (int) $term_id;
			}
		}

		$map = get_post_meta( $post_id, self::META, true );
		$map = is_array( $map ) ? $map : array();
		if ( empty( $ids ) ) {
			// لا نمسح الخريطة القديمة؛ هي مرجع الاسترجاع لو WPML مسح
			// Category أثناء ربط/حفظ ترجمة أخرى.
			$map[ $lang ] = array();
		} else {
			$map[ $lang ] = array_values( array_unique( $ids ) );
		}
		update_post_meta( $post_id, self::META, $map );
	}

	/*
	 * لا نضيف Category لغة أخرى إلى Post. لو WPML أو مسار آخر أضاف term
	 * من لغة مختلفة، نحذفه ونبقي فقط term الخاصة بلغة الـ Post.
	 */
	public static function restore( $post_id, $propagate = true ) {
		$post_id = (int) $post_id;
		if ( ! $post_id || ! self::wpml() ) {
			return false;
		}
		$post_lang = CS_WPML::post_language( $post_id );
		if ( ! $post_lang ) {
			return false;
		}
		$map = get_post_meta( $post_id, self::META, true );
		$map = is_array( $map ) ? $map : array();
		$wanted = isset( $map[ $post_lang ] ) ? array_values( array_unique( array_map( 'absint', (array) $map[ $post_lang ] ) ) ) : array();
		if ( empty( $wanted ) ) {
			return false;
		}
		$current = self::read_terms( $post_id );
		$valid = array();
		foreach ( $wanted as $term_id ) {
			$term_lang = self::term_lang( $term_id );
			if ( ! $term_lang || $term_lang === $post_lang ) {
				$valid[] = $term_id;
			}
		}
		$valid = array_values( array_unique( $valid ) );
		if ( $current === $valid ) {
			return false;
		}
		CS_WPML::set_terms_for_language( $post_id, $valid, $post_lang );
		self::remember( $post_id );
		return true;
	}

	public static function on_save( $post_id ) {
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! self::wpml() || get_post_type( $post_id ) !== CS_CPT ) {
			return;
		}
		self::restore( $post_id );
		self::remember( $post_id );
	}


	/**
	 * تنظيف مرة واحدة للبيانات التي اتلخبطت من الإصدارات السابقة.
	 * لكل Master نصل إلى كل ترجماته، ثم نعيد Category لغة كل Post فقط.
	 */
	public static function repair_all_translation_categories() {
		if ( ! self::wpml() ) {
			return;
		}
		$ids = get_posts( array(
			'post_type'      => CS_CPT,
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'meta_key'       => CS_META_MASTER,
			'meta_value'     => 1,
			'suppress_filters' => true,
		) );
		foreach ( $ids as $id ) {
			$id = (int) $id;
			$langs = apply_filters( 'wpml_active_languages', null, array( 'skip_missing' => 0 ) );
			if ( ! is_array( $langs ) ) {
				continue;
			}
			foreach ( array_keys( $langs ) as $lang ) {
				$tr = (int) apply_filters( 'wpml_object_id', $id, CS_CPT, false, sanitize_key( $lang ) );
				if ( $tr && $tr !== $id ) {
					self::remember( $tr );
				}
			}
			self::remember( $id );
			// repair_translation_categories() will infer a missing category
			// from its translated term when the other side has the correct one.
			foreach ( array_keys( $langs ) as $lang ) {
				$tr = (int) apply_filters( 'wpml_object_id', $id, CS_CPT, false, sanitize_key( $lang ) );
				if ( $tr && $tr !== $id ) {
					CS_WPML::repair_translation_categories( $id, $tr, sanitize_key( $lang ) );
				}
			}
		}
	}

	public static function on_generation_done( $master_id ) {
		$master_id = (int) $master_id;
		if ( ! $master_id || ! self::wpml() ) {
			return;
		}

		self::restore( $master_id, false );

		if ( class_exists( 'CS_Recurrence' ) ) {
			CS_Recurrence::sync_terms_after_generation( $master_id );
		}

		self::remember( $master_id );
	}
}

CS_Category_Guard::init();
