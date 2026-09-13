<?php
/**
 * Feature: Slider
 * تحكم في الإسلايدر بتاع الصفحات. سلايدز عامة + كل سلايد ممكن تخصّها
 * لكاتيجوري معينة. التمبليت بيطلب سلايدز الكاتيجوري الحالية، ولو مفيش
 * بيطلع السلايدز العامة.
 *
 * محتاج ACF Pro (options page + repeater).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CS_Slider {

	public static function init() {
		add_action( 'acf/init', array( __CLASS__, 'register' ) );
	}

	public static function register() {
		if ( ! function_exists( 'acf_add_options_page' ) ) {
			return;
		}

		acf_add_options_page( array(
			'page_title' => 'Course Sliders',
			'menu_title' => 'Course Sliders',
			'menu_slug'  => 'cs-sliders',
			'capability' => 'manage_options',
			'icon_url'   => 'dashicons-images-alt2',
		) );

		acf_add_local_field_group( array(
			'key'    => 'group_cs_slider',
			'title'  => 'Sliders',
			'fields' => array(
				array(
					'key'          => 'field_cs_slides',
					'label'        => 'Slides',
					'name'         => 'cs_slides',
					'type'         => 'repeater',
					'layout'       => 'block',
					'button_label' => 'Add Slide',
					'sub_fields'   => array(
						array(
							'key'           => 'field_cs_slide_image',
							'label'         => 'Background Image',
							'name'          => 'image',
							'type'          => 'image',
							'return_format' => 'url',
						),
						array( 'key' => 'field_cs_slide_title', 'label' => 'Title', 'name' => 'title', 'type' => 'text' ),
						array( 'key' => 'field_cs_slide_desc', 'label' => 'Description', 'name' => 'desc', 'type' => 'textarea', 'rows' => 2 ),
						array( 'key' => 'field_cs_slide_btn', 'label' => 'Button Text', 'name' => 'btn_text', 'type' => 'text', 'default_value' => 'Booking Now' ),
						array( 'key' => 'field_cs_slide_link', 'label' => 'Button Link', 'name' => 'link', 'type' => 'url' ),
						array(
							'key'          => 'field_cs_slide_cat',
							'label'        => 'Show only on category (optional)',
							'name'         => 'category',
							'type'         => 'taxonomy',
							'taxonomy'     => CS_TAX,
							'field_type'   => 'select',
							'add_term'     => 0,
							'return_format'=> 'id',
							'allow_null'   => 1,
							'instructions' => 'سيبها فاضية = سلايد عام يظهر في كل الصفحات.',
						),
					),
				),
			),
			'location' => array(
				array(
					array( 'param' => 'options_page', 'operator' => '==', 'value' => 'cs-sliders' ),
				),
			),
		) );
	}

	/**
	 * يرجّع السلايدز المناسبة لكاتيجوري (أو العامة لو مفيش).
	 *
	 * @param int|null $term_id
	 * @return array
	 */
	public static function get_slides( $term_id = null ) {
		$all = get_field( 'cs_slides', 'option' );
		if ( empty( $all ) || ! is_array( $all ) ) {
			return array();
		}

		$scoped = array();  // خاصة بالكاتيجوري
		$global = array();  // عامة

		foreach ( $all as $slide ) {
			$cat = isset( $slide['category'] ) ? (int) $slide['category'] : 0;
			if ( $cat ) {
				if ( $term_id && $cat === (int) $term_id ) {
					$scoped[] = $slide;
				}
			} else {
				$global[] = $slide;
			}
		}

		return ! empty( $scoped ) ? $scoped : $global;
	}
}

CS_Slider::init();