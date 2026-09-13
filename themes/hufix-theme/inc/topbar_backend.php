<?php
/**
 * Backend: Topbar Setting
 * -----------------------------------------------------
 * ضيف الكود ده في functions.php أو بلجن مخصص
 * محتاج بلجن Advanced Custom Fields
 * -----------------------------------------------------
 */

// 1) صفحة أوبشنز في الأدمن اسمها "Topbar Setting"
if ( function_exists( 'acf_add_options_page' ) ) {
    acf_add_options_page( array(
        'page_title' => 'Topbar Setting',
        'menu_title' => 'Topbar Setting',
        'menu_slug'  => 'topbar-setting-options',
        'capability' => 'edit_posts',
        'icon_url'   => 'dashicons-admin-generic',
        'position'   => 24,
        'redirect'   => false,
    ) );
}

// 2) تسجيل الحقول
if ( function_exists( 'acf_add_local_field_group' ) ) {
    acf_add_local_field_group( array(
        'key'    => 'group_topbar_setting',
        'title'  => 'Topbar Fields',
        'fields' => array(

            array(
                'key'           => 'field_topbar_address',
                'label'         => 'Address',
                'name'          => 'topbar_address',
                'type'          => 'text',
                'default_value' => '6391 Elgin St. Celina, Delaware 10299, USA',
            ),

            array(
                'key'           => 'field_topbar_phone',
                'label'         => 'Phone',
                'name'          => 'topbar_phone',
                'type'          => 'text',
                'default_value' => '(+31) 3456 7890',
            ),

            array(
                'key'           => 'field_topbar_email',
                'label'         => 'Email',
                'name'          => 'topbar_email',
                'type'          => 'email',
                'default_value' => 'info@hufix.eu',
            ),

            array(
                'key'          => 'field_topbar_languages',
                'label'        => 'Languages',
                'name'         => 'topbar_languages',
                'type'         => 'repeater',
                'layout'       => 'table',
                'button_label' => 'Add Language',
                'sub_fields'   => array(
                    array(
                        'key'   => 'field_topbar_lang_label',
                        'label' => 'Label',
                        'name'  => 'label',
                        'type'  => 'text',
                    ),
                    array(
                        'key'   => 'field_topbar_lang_link',
                        'label' => 'Link',
                        'name'  => 'link',
                        'type'  => 'url',
                    ),
                ),
            ),

        ),
        // الحقول دي تظهر جوه صفحة الأوبشنز اللي عملناها فوق
        'location' => array(
            array(
                array(
                    'param'    => 'options_page',
                    'operator' => '==',
                    'value'    => 'topbar-setting-options',
                ),
            ),
        ),
    ) );
}

