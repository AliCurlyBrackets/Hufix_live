<?php
/**
 * Backend: Clients Logos (Marquee)
 * -----------------------------------------------------
 * محتاج بلجن ACF PRO عشان حقل الـ Repeater
 * -----------------------------------------------------
 */

// 1) صفحة أوبشنز في الأدمن اسمها "Clients Logos"
if ( function_exists( 'acf_add_options_page' ) ) {
    acf_add_options_page( array(
        'page_title' => 'Clients Logos',
        'menu_title' => 'Clients Logos',
        'menu_slug'  => 'clients-logos-options',
        'capability' => 'edit_posts',
        'icon_url'   => 'dashicons-images-alt2',
        'position'   => 28,
        'redirect'   => false,
    ) );
}

// 2) حقل الريبيتر لكل لوجو
if ( function_exists( 'acf_add_local_field_group' ) ) {
    acf_add_local_field_group( array(
        'key'    => 'group_clients_logos',
        'title'  => 'Clients Logos Fields',
        'fields' => array(

            array(
                'key'          => 'field_clients_repeater',
                'label'        => 'Logos',
                'name'         => 'clients_logos',
                'type'         => 'repeater',
                'layout'       => 'table',
                'button_label' => 'Add Logo',
                'sub_fields'   => array(

                    array(
                        'key'           => 'field_client_logo',
                        'label'         => 'Logo Image',
                        'name'          => 'logo',
                        'type'          => 'image',
                        'return_format' => 'url',
                        'preview_size'  => 'thumbnail',
                        'library'       => 'all',
                    ),

                    array(
                        'key'   => 'field_client_name',
                        'label' => 'Client Name (Alt Text)',
                        'name'  => 'name',
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
                    'value'    => 'clients-logos-options',
                ),
            ),
        ),
    ) );
}


// 3) تعبئة اللوجوهات الـ 6 الموجودة حاليًا تلقائيًا أول مرة بس
function seed_default_client_logos() {

    $existing = get_field( 'clients_logos', 'option' );
    if ( ! empty( $existing ) ) {
        return;
    }

    $theme_uri = get_template_directory_uri();

    $default_logos = array();
    for ( $i = 1; $i <= 6; $i++ ) {
        $default_logos[] = array(
            'logo' => $theme_uri . '/assets/images/Clints_Logo/' . $i . '.png',
            'name' => 'Client ' . $i,
        );
    }

    update_field( 'clients_logos', $default_logos, 'option' );
}
add_action( 'acf/init', 'seed_default_client_logos' );