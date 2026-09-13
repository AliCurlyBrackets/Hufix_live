<?php
/**
 * Backend: Hero Section
 * -----------------------------------------------------
 * المكان: ضيف الكود ده في functions.php بتاع الثيم
 * أو داخل بلجن مخصص. محتاج بلجن Advanced Custom Fields
 * (المدفوع مش لازم، النسخة الفري كفاية للحقول دي)
 * -----------------------------------------------------
 */

// 1) إنشاء صفحة أوبشنز في الأدمن اسمها "Hero Section"
if ( function_exists( 'acf_add_options_page' ) ) {
    acf_add_options_page( array(
        'page_title' => 'Hero Section',
        'menu_title' => 'Hero Section',
        'menu_slug'  => 'hero-section-options',
        'capability' => 'edit_posts',
        'icon_url'   => 'dashicons-star-filled',
        'position'   => 25,
        'redirect'   => false,
    ) );
}

// 2) تسجيل الحقول (نص العنوان - نص الزرار - صورة الخلفية)
if ( function_exists( 'acf_add_local_field_group' ) ) {
    acf_add_local_field_group( array(
        'key'    => 'group_hero_section',
        'title'  => 'Hero Section Fields',
        'fields' => array(

            array(
                'key'           => 'field_hero_heading',
                'label'         => 'Hero Heading',
                'name'          => 'hero_heading',
                'type'          => 'text',
                'default_value' => 'Where Strategy Meets Culture',
            ),

            array(
                'key'           => 'field_hero_button_text',
                'label'         => 'Button Text',
                'name'          => 'hero_button_text',
                'type'          => 'text',
                'default_value' => 'Start Your Journey',
            ),

            array(
                'key'           => 'field_hero_button_link',
                'label'         => 'Button Link',
                'name'        => 'hero_button_link',
                'type'        => 'url',
                'placeholder' => 'https://example.com',
            ),

            array(
                'key'               => 'field_hero_button_popup',
                'label'             => 'Button Popup',
                'name'              => 'hero_button_popup',
                'type'              => 'select',
                'choices'           => array(
                    ''             => 'None (use link above)',
                    'service'      => 'Book a Service',
                    'training'     => 'Request Training',
                    'hr'           => 'Request HR Consultation',
                    'consultation' => 'Request Free Consultation',
                ),
                'default_value'     => '',
                'instructions'      => 'Select a popup to open instead of using the Button Link above.',
            ),

            array(
                'key'           => 'field_hero_background',
                'label'         => 'Background Image',
                'name'          => 'hero_background',
                'type'          => 'image',
                'return_format' => 'url',
                'preview_size'  => 'medium',
                'library'       => 'all',
            ),

        ),
        // الحقول دي هتظهر جوه صفحة الأوبشنز اللي عملناها فوق
        'location' => array(
            array(
                array(
                    'param'    => 'options_page',
                    'operator' => '==',
                    'value'    => 'hero-section-options',
                ),
            ),
        ),
    ) );
}