<?php

// inc

include("inc/hero-section-backend.php");
include("inc/where_meest-section-backend.php");
include("inc/services-backend.php");
include("inc/marquee-section-backend.php");
include("inc/why-section-backend.php");
include("inc/about-section-backend.php");

// Pages

include("inc/Pages/about-backend.php");
include("inc/Pages/consultation-backend.php");
include("inc/Pages/hr-backend.php");
include("inc/Pages/hr_qut_backend.php");
include("inc/Pages/elit_backend.php");
include("inc/Pages/traning_backend.php");
include("inc/Pages/contact_us_backend.php");

// Settings

include("inc/topbar_backend.php");
include("inc/footer_setting.php");



function mytheme_setup()
{

    add_theme_support('custom-logo', array(
        'height'      => 100,
        'width'       => 300,
        'flex-height' => true,
        'flex-width'  => true,
    ));
}

add_action('after_setup_theme', 'mytheme_setup');


function mytheme_register_menus()
{

    register_nav_menus(array(
        'main-menu'     => __('Main Menu', 'mytheme'),
        'footer-menu-1' => __('Footer 1 Menu', 'mytheme'),
        'footer-menu-2' => __('Footer 2 Menu', 'mytheme'),
    ));
}

add_action('after_setup_theme', 'mytheme_register_menus');









/**
 * ACF Field Group: FAQ Accordion
 * حطّ الكود ده في ملف functions.php بتاع الثيم، أو في ملف منفصل وعمله require
 * لازم يكون ACF (Advanced Custom Fields) plugin مفعّل عندك
 */

add_action( 'acf/init', 'register_faq_accordion_fields' );

function register_faq_accordion_fields() {

	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group( array(
		'key'                   => 'group_faq_accordion',
		'title'                 => 'FAQ Accordion',
		'fields'                => array(
			array(
				'key'           => 'field_faq_section_title',
				'label'         => 'عنوان القسم',
				'name'          => 'faq_section_title',
				'type'          => 'text',
				'default_value' => 'الأسئلة الشائعة',
			),
			array(
				'key'           => 'field_faq_items',
				'label'         => 'الأسئلة والأجوبة',
				'name'          => 'faq_items',
				'type'          => 'repeater',
				'layout'        => 'block',
				'button_label'  => 'إضافة سؤال جديد',
				'sub_fields'    => array(
					array(
						'key'   => 'field_faq_question',
						'label' => 'السؤال',
						'name'  => 'question',
						'type'  => 'text',
						'required' => 1,
					),
					array(
						'key'   => 'field_faq_answer',
						'label' => 'الإجابة',
						'name'  => 'answer',
						'type'  => 'wysiwyg',
						'tabs'  => 'visual',
						'toolbar' => 'basic',
						'media_upload' => 0,
						'required' => 1,
					),
				),
			),
		),
		'location'              => array(
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					// الفيلد جروب هيظهر في كل صفحات "page"
					// غيّره لو عايزه يظهر في نوع محتوى تاني، أو حدد تمبلت معين
					'value'    => 'page',
				),
			),
		),
		'menu_order'            => 0,
		'position'              => 'normal',
		'style'                 => 'default',
		'active'                => true,
	) );

}
