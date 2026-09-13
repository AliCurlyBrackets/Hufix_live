<?php
/**
 * Backend: Where Meest
 * -----------------------------------------------------
 * ضيف الكود ده في functions.php بتاع الثيم
 * أو داخل بلجن مخصص. محتاج بلجن Advanced Custom Fields
 * -----------------------------------------------------
 */

// 1) إنشاء صفحة أوبشنز في الأدمن اسمها "Where Meest"
if ( function_exists( 'acf_add_options_page' ) ) {
    acf_add_options_page( array(
        'page_title' => 'Where Meest',
        'menu_title' => 'Where Meest',
        'menu_slug'  => 'where-meest-options',
        'capability' => 'edit_posts',
        'icon_url'   => 'dashicons-format-aside',
        'position'   => 26,
        'redirect'   => false,
    ) );
}

// 2) تسجيل الحقول
if ( function_exists( 'acf_add_local_field_group' ) ) {
    acf_add_local_field_group( array(
        'key'    => 'group_where_meest',
        'title'  => 'Where Meest Fields',
        'fields' => array(

            array(
                'key'           => 'field_wm_heading',
                'label'         => 'Heading',
                'name'          => 'wm_heading',
                'type'          => 'text',
                'default_value' => 'Where Strategy Meets Culture. Where Vision Becomes Execution.',
            ),

            array(
                'key'           => 'field_wm_paragraph',
                'label'         => 'Paragraph',
                'name'          => 'wm_paragraph',
                'type'          => 'textarea',
                'rows'          => 4,
                'default_value' => 'Premium boutique consulting, HR advisory, and executive training — seamlessly delivered across Europe and the MENA region. Tailored for decision-makers shaping the future. Unlock your potential today:',
            ),

            array(
                'key'           => 'field_wm_button_text',
                'label'         => 'Download Button Text',
                'name'          => 'wm_button_text',
                'type'          => 'text',
                'default_value' => 'Download Our Portfolio',
            ),

            array(
                'key'         => 'field_wm_button_file',
                'label'       => 'Download File (PDF)',
                'name'        => 'wm_button_file',
                'type'        => 'file',
                'return_format' => 'url',
                'library'     => 'all',
            ),

            array(
                'key'           => 'field_wm_button_icon',
                'label'         => 'Button Icon',
                'name'          => 'wm_button_icon',
                'type'          => 'image',
                'return_format' => 'url',
                'preview_size'  => 'thumbnail',
                'library'       => 'all',
            ),

        ),
        // الحقول دي هتظهر جوه صفحة الأوبشنز اللي عملناها فوق
        'location' => array(
            array(
                array(
                    'param'    => 'options_page',
                    'operator' => '==',
                    'value'    => 'where-meest-options',
                ),
            ),
        ),
    ) );
}