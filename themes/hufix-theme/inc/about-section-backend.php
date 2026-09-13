<?php
/**
 * Backend: About Section
 * -----------------------------------------------------
 * محتاج بلجن ACF PRO عشان حقل الـ Repeater
 * -----------------------------------------------------
 */

// 1) صفحة أوبشنز في الأدمن اسمها "About Section"
if ( function_exists( 'acf_add_options_page' ) ) {
    acf_add_options_page( array(
        'page_title' => 'About Section',
        'menu_title' => 'About Section',
        'menu_slug'  => 'about-section-options',
        'capability' => 'edit_posts',
        'icon_url'   => 'dashicons-info',
        'position'   => 30,
        'redirect'   => false,
    ) );
}

// 2) الحقول
if ( function_exists( 'acf_add_local_field_group' ) ) {
    acf_add_local_field_group( array(
        'key'    => 'group_about_section',
        'title'  => 'About Section Fields',
        'fields' => array(

            array(
                'key'           => 'field_about_image',
                'label'         => 'Image',
                'name'          => 'about_image',
                'type'          => 'image',
                'return_format' => 'url',
                'preview_size'  => 'medium',
                'library'       => 'all',
            ),

            array(
                'key'           => 'field_about_title',
                'label'         => 'Title',
                'name'          => 'about_title',
                'type'          => 'text',
                'default_value' => 'Who We Are',
            ),

            array(
                'key'           => 'field_about_paragraph',
                'label'         => 'Paragraph',
                'name'          => 'about_paragraph',
                'type'          => 'textarea',
                'rows'          => 5,
                'default_value' => 'Hufix is a cross-continental boutique consultancy blending European precision with GCC cultural fluency. Based in the Netherlands, we empower ambitious organizations across Europe and the Middle East through strategic business consulting, agile transformation, and certified HR solutions.',
            ),

            array(
                'key'          => 'field_about_list',
                'label'        => 'List Items',
                'name'         => 'about_list',
                'type'         => 'repeater',
                'layout'       => 'table',
                'button_label' => 'Add List Item',
                'sub_fields'   => array(

                    array(
                        'key'           => 'field_about_list_icon',
                        'label'         => 'Icon',
                        'name'          => 'icon',
                        'type'          => 'image',
                        'return_format' => 'url',
                        'preview_size'  => 'thumbnail',
                        'library'       => 'all',
                    ),

                    array(
                        'key'   => 'field_about_list_text',
                        'label' => 'Text',
                        'name'  => 'text',
                        'type'  => 'text',
                    ),

                ),
            ),

            array(
                'key'           => 'field_about_button_text',
                'label'         => 'Button Text',
                'name'          => 'about_button_text',
                'type'          => 'text',
                'default_value' => 'Learn More',
            ),

            array(
                'key'   => 'field_about_button_link',
                'label' => 'Button Link',
                'name'  => 'about_button_link',
                'type'  => 'url',
            ),

            array(
                'key'           => 'field_about_button_icon',
                'label'         => 'Button Icon',
                'name'          => 'about_button_icon',
                'type'          => 'image',
                'return_format' => 'url',
                'preview_size'  => 'thumbnail',
                'library'       => 'all',
            ),

        ),
        'location' => array(
            array(
                array(
                    'param'    => 'options_page',
                    'operator' => '==',
                    'value'    => 'about-section-options',
                ),
            ),
        ),
    ) );
}


// 3) تعبئة البيانات الموجودة حاليًا تلقائيًا أول مرة بس
function seed_default_about_section() {

    $existing = get_field( 'about_list', 'option' );
    if ( ! empty( $existing ) ) {
        return;
    }

    $theme_uri = get_template_directory_uri();

    // الصورة الرئيسية والعنوان والوصف
    update_field( 'about_image', $theme_uri . '/assets/images/cont.png', 'option' );
    update_field( 'about_button_icon', $theme_uri . '/assets/images/icons/Margin.png', 'option' );

    // عناصر الليست
    $default_list = array(
        array(
            'icon' => $theme_uri . '/assets/images/Margin.png',
            'text' => 'Proudly accredited by the European Accreditation Center',
        ),
        array(
            'icon' => $theme_uri . '/assets/images/Margin.png',
            'text' => 'Recognized by key players driving transformation across MENA region.',
        ),
    );

    update_field( 'about_list', $default_list, 'option' );
}
add_action( 'acf/init', 'seed_default_about_section' );