<?php
/**
 * Feature: ACF Fields (بالكود)
 * كل حقول صفحة السنجل كورس اتعملت هنا بالكود عشان تتنشر بـ Code Snippets
 * من غير ما تحتاج تعمل import/export لـ ACF JSON.
 *
 * الحقول دي كلها على الـ MASTER بس. السيشن بيقرأ منها.
 * أسماء الحقول (name) هي نفسها أعمدة الشيت -> شوف class-cs-import + الـ CSV.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'acf/init', 'cs_register_acf_fields' );

function cs_register_acf_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	// ملحوظة: الدول اتشالت -- المكان/اللوكيشن بقى حقل نص حر (مش select من
	// قايمة ثابتة) عشان تقدر تكتب أي اسم (دولة أو مدينة زي "Cairo") زي ما
	// انت عايز، سواء من هنا أو من عمود countries_prices في شيت الاستيراد.

	// خيارات العملات.
	$currency_choices = array(
		'USD' => 'USD', 'EUR' => 'EUR', 'GBP' => 'GBP',
		'EGP' => 'EGP', 'SAR' => 'SAR', 'AED' => 'AED',
		'KWD' => 'KWD', 'QAR' => 'QAR', 'BHD' => 'BHD',
		'OMR' => 'OMR', 'JOD' => 'JOD',
	);

	acf_add_local_field_group( array(
		'key'      => 'group_cs_course',
		'title'    => 'Course Details',
		'fields'   => array(

			/* --- Hero / أساسي --- */
			array(
				'key'   => 'field_cs_summary',
				'label' => 'Summary (Hero description)',
				'name'  => 'cs_summary',
				'type'  => 'textarea',
				'rows'  => 3,
			),
			array(
				'key'          => 'field_cs_delivery_mode',
				'label'        => 'Delivery Mode(s)',
				'name'         => 'cs_delivery_mode',
				'type'         => 'text',
				'instructions' => 'اكتب وضع أو أكتر، مفصولين بـ / . مثال: Hybrid / Online / In-Person',
				'placeholder'  => 'Hybrid / Online / In-Person',
			),
			array(
				'key'          => 'field_cs_language',
				'label'        => 'Course Language(s)',
				'name'         => 'cs_language',
				'type'         => 'text',
				'instructions' => 'اكتب لغة أو أكتر، مفصولين بـ / . مثال: English / Arabic / French',
				'placeholder'  => 'English / Arabic / French',
			),
			array(
				'key'   => 'field_cs_duration_hours',
				'label' => 'Duration (hours)',
				'name'  => 'cs_duration_hours',
				'type'  => 'number',
				'default_value' => 40,
			),

			/* --- الدول + السعر لكل دولة (بيغذّوا السيشنز والتسعير) --- */
			array(
				'key'          => 'field_cs_country_prices',
				'label'        => 'Locations & Prices',
				'name'         => 'cs_country_prices',
				'type'         => 'repeater',
				'layout'       => 'table',
				'button_label' => 'Add Location',
				'instructions' => 'كل صف = المكان (دولة أو مدينة، اكتبه بحرية زي "Cairo" أو "Alexandria" أو "Egypt") اللي الكورس هيتعمل فيه + سعره + عملته. السيشنز بتتولّد لكل مكان هنا.',
				'sub_fields'   => array(
					array(
						'key'          => 'field_cs_cp_country',
						'label'        => 'Location',
						'name'         => 'country',
						'type'         => 'text',
						'placeholder'  => 'Cairo',
					),
					array(
						'key'   => 'field_cs_cp_price',
						'label' => 'Price',
						'name'  => 'price',
						'type'  => 'number',
					),
					array(
						'key'           => 'field_cs_cp_currency',
						'label'         => 'Currency',
						'name'          => 'currency',
						'type'          => 'select',
						'choices'       => $currency_choices,
						'default_value' => 'USD',
					),
				),
			),
			array(
				'key'   => 'field_cs_base_start_date',
				'label' => 'Base Start Date',
				'name'  => 'cs_base_start_date',
				'type'  => 'date_picker',
				'return_format' => 'Y-m-d',
				'instructions'  => 'تاريخ أول سيشن. لو فاضي، هياخد تاريخ النهاردة.',
			),
			array(
				'key'           => 'field_cs_recurrence',
				'label'         => 'Recurrence',
				'name'          => 'cs_recurrence',
				'type'          => 'radio',
				'choices'       => array(
					'weekly'   => 'كل أسبوع (5 أيام: الاتنين للجمعة)',
					'biweekly' => 'كل أسبوعين (10 أيام: أسبوعين شغل)',
				),
				'default_value' => 'weekly',
				'instructions'  => 'عدد أيام الكورس بيتحدد أوتوماتيك من هنا (السبت والأحد مالهومش وجود خالص) -- مفيش حاجة تانية تتكتب.',
			),

			/* --- Learning Objectives (repeater) --- */
			array(
				'key'        => 'field_cs_objectives',
				'label'      => 'Learning Objectives',
				'name'       => 'cs_objectives',
				'type'       => 'repeater',
				'layout'     => 'table',
				'button_label' => 'Add Objective',
				'sub_fields' => array(
					array(
						'key'   => 'field_cs_objective_text',
						'label' => 'Objective',
						'name'  => 'text',
						'type'  => 'text',
					),
				),
			),

			/* --- Daily Schedule (repeater جوه repeater) --- */
			array(
				'key'        => 'field_cs_schedule',
				'label'      => 'Daily Schedule',
				'name'       => 'cs_schedule',
				'type'       => 'repeater',
				'layout'     => 'block',
				'button_label' => 'Add Day',
				'sub_fields' => array(
					array(
						'key'   => 'field_cs_day_title',
						'label' => 'Day Title',
						'name'  => 'day_title',
						'type'  => 'text',
					),
					array(
						'key'        => 'field_cs_day_points',
						'label'      => 'Points',
						'name'       => 'points',
						'type'       => 'repeater',
						'layout'     => 'table',
						'button_label' => 'Add Point',
						'sub_fields' => array(
							array(
								'key'   => 'field_cs_day_point_text',
								'label' => 'Point',
								'name'  => 'text',
								'type'  => 'text',
							),
						),
					),
				),
			),

			/* --- Outline PDF --- */
			array(
				'key'          => 'field_cs_outline_pdf',
				'label'        => 'Course Outline PDF',
				'name'         => 'cs_outline_pdf',
				'type'         => 'file',
				'return_format' => 'url',
			),
		),

		'location' => array(
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => CS_CPT,
				),
			),
		),
	) );
}