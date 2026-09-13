<?php
/**
 * Backend: Why Hufix
 * -----------------------------------------------------
 * محتاج بلجن ACF PRO عشان حقل الـ Repeater
 * -----------------------------------------------------
 */

// 1) صفحة أوبشنز في الأدمن اسمها "Why Hufix"
if ( function_exists( 'acf_add_options_page' ) ) {
    acf_add_options_page( array(
        'page_title' => 'Why Hufix',
        'menu_title' => 'Why Hufix',
        'menu_slug'  => 'why-hufix-options',
        'capability' => 'edit_posts',
        'icon_url'   => 'dashicons-star-half',
        'position'   => 29,
        'redirect'   => false,
    ) );
}

// 2) الحقول: العنوان - الوصف - صورة الخلفية - ريبيتر العناصر
if ( function_exists( 'acf_add_local_field_group' ) ) {
    acf_add_local_field_group( array(
        'key'    => 'group_why_hufix',
        'title'  => 'Why Hufix Fields',
        'fields' => array(

            array(
                'key'           => 'field_why_title',
                'label'         => 'Title',
                'name'          => 'why_title',
                'type'          => 'text',
                'default_value' => 'Why Hufix',
            ),

            array(
                'key'           => 'field_why_subtitle',
                'label'         => 'Subtitle',
                'name'          => 'why_subtitle',
                'type'          => 'text',
                'default_value' => 'Elite Thinking. Grounded Solutions. Cross-Continental Confidence.',
            ),

            array(
                'key'           => 'field_why_bg',
                'label'         => 'Background Image',
                'name'          => 'why_bg',
                'type'          => 'image',
                'return_format' => 'url',
                'preview_size'  => 'medium',
                'library'       => 'all',
            ),

            array(
                'key'          => 'field_why_items',
                'label'        => 'Items',
                'name'         => 'why_items',
                'type'         => 'repeater',
                'layout'       => 'table',
                'button_label' => 'Add Item',
                'sub_fields'   => array(

                    array(
                        'key'           => 'field_why_item_icon',
                        'label'         => 'Icon',
                        'name'          => 'icon',
                        'type'          => 'image',
                        'return_format' => 'url',
                        'preview_size'  => 'thumbnail',
                        'library'       => 'all',
                    ),

                    array(
                        'key'   => 'field_why_item_text',
                        'label' => 'Text',
                        'name'  => 'text',
                        'type'  => 'text',
                    ),

                ),
            ),

        ),
        'location' => array(
            array(
                array(
                    'param'    => 'options_page',
                    'operator' => '==',
                    'value'    => 'why-hufix-options',
                ),
            ),
        ),
    ) );
}


// 3) تعبئة العناصر الـ 5 الموجودة حاليًا تلقائيًا أول مرة بس
function seed_default_why_items() {

    $existing = get_field( 'why_items', 'option' );
    if ( ! empty( $existing ) ) {
        return;
    }

    $theme_uri = get_template_directory_uri();

    $default_items = array(
        array(
            'icon' => $theme_uri . '/assets/images/icons/Icon.png',
            'text' => 'European Expertise, GCC Insight',
        ),
        array(
            'icon' => $theme_uri . '/assets/images/icons/Icon (1).png',
            'text' => 'Custom Solutions for Leaders',
        ),
        array(
            'icon' => $theme_uri . '/assets/images/icons/Icon (2).png',
            'text' => 'Partnership-Driven, Not Just Advisory',
        ),
        array(
            'icon' => $theme_uri . '/assets/images/icons/Icon (3).png',
            'text' => 'EAC-Certified Quality Standards',
        ),
        array(
            'icon' => $theme_uri . '/assets/images/icons/Icon (4).png',
            'text' => 'Aligned with Vision 2030 Goals',
        ),
    );

    update_field( 'why_items', $default_items, 'option' );
}
add_action( 'acf/init', 'seed_default_why_items' );