<?php
/**
 * Backend: Strategic Business Consulting Page (page id = 60)
 * -----------------------------------------------------
 * حقول خاصة بالصفحة دي بس. محتاج ACF PRO (Repeater + Tab)
 * الأوصاف كلها wysiwyg، والريبيترز بتتعبى بـ update_field
 * -----------------------------------------------------
 */

if ( ! function_exists( 'acf_add_local_field_group' ) ) {
    return;
}

acf_add_local_field_group( array(
    'key'    => 'group_consulting_page',
    'title'  => 'Strategic Business Consulting Page Fields',
    'fields' => array(

        /* ===================== Training (Intro) ===================== */
        array(
            'key'   => 'field_tab_training',
            'label' => 'Training',
            'type'  => 'tab',
        ),
        array(
            'key'           => 'field_cp_training_title',
            'label'         => 'Title',
            'name'          => 'cp_training_title',
            'type'          => 'text',
            'default_value' => 'Strategic Business Consulting',
        ),
        array(
            'key'          => 'field_cp_training_desc',
            'label'        => 'Description',
            'name'         => 'cp_training_desc',
            'type'         => 'wysiwyg',
            'toolbar'      => 'basic',
            'media_upload' => 0,
        ),
        array(
            'key'           => 'field_cp_training_button_text',
            'label'         => 'Button Text',
            'name'          => 'cp_training_button_text',
            'type'          => 'text',
            'default_value' => 'Request a Strategy Session',
        ),
            array(
                'key'   => 'field_cp_training_button_link',
                'label' => 'Button Link',
                'name'  => 'cp_training_button_link',
                'type'  => 'url',
            ),
            array(
                'key'               => 'field_cp_training_button_popup',
                'label'             => 'Button Popup',
                'name'              => 'cp_training_button_popup',
                'type'              => 'select',
                'choices'           => array(
                    ''             => 'None (use link above)',
                    'service'      => 'Book a Service',
                    'training'     => 'Request Training',
                    'hr'           => 'Request HR Consultation',
                    'consultation' => 'Request Free Consultation',
                ),
                'instructions'      => 'Select a popup to open when the button is clicked.',
            ),
        array(
            'key'           => 'field_cp_training_image',
            'label'         => 'Right Image',
            'name'          => 'cp_training_image',
            'type'          => 'image',
            'return_format' => 'url',
        ),

        /* ===================== Core Areas ===================== */
        array(
            'key'   => 'field_tab_core_areas',
            'label' => 'Core Areas',
            'type'  => 'tab',
        ),
        array(
            'key'           => 'field_cp_core_bg',
            'label'         => 'Background Image',
            'name'          => 'cp_core_bg',
            'type'          => 'image',
            'return_format' => 'url',
        ),
        array(
            'key'           => 'field_cp_core_title',
            'label'         => 'Title',
            'name'          => 'cp_core_title',
            'type'          => 'text',
            'default_value' => 'Core Areas of Expertise',
        ),
        array(
            'key'           => 'field_cp_core_subtitle',
            'label'         => 'Subtitle',
            'name'          => 'cp_core_subtitle',
            'type'          => 'text',
            'default_value' => 'Hufix offers a comprehensive suite of consultancy services across key business domains:',
        ),
        array(
            'key'          => 'field_cp_core_cards',
            'label'        => 'Cards',
            'name'         => 'cp_core_cards',
            'type'         => 'repeater',
            'layout'       => 'block',
            'button_label' => 'Add Card',
            'sub_fields'   => array(
                array(
                    'key'           => 'field_core_card_icon',
                    'label'         => 'Icon',
                    'name'          => 'icon',
                    'type'          => 'image',
                    'return_format' => 'url',
                ),
                array(
                    'key'  => 'field_core_card_title',
                    'label'=> 'Title',
                    'name' => 'title',
                    'type' => 'text',
                ),
                array(
                    'key'          => 'field_core_card_desc',
                    'label'        => 'Description',
                    'name'         => 'description',
                    'type'         => 'wysiwyg',
                    'toolbar'      => 'basic',
                    'media_upload' => 0,
                ),
            ),
        ),

        /* ===================== Consulting Approach ===================== */
        array(
            'key'   => 'field_tab_consulting',
            'label' => 'Consulting Approach',
            'type'  => 'tab',
        ),
        array(
            'key'           => 'field_cp_consulting_title',
            'label'         => 'Title',
            'name'          => 'cp_consulting_title',
            'type'          => 'text',
            'default_value' => 'Our Consulting Approach',
        ),
        array(
            'key'          => 'field_cp_consulting_desc',
            'label'        => 'Intro Description',
            'name'         => 'cp_consulting_desc',
            'type'         => 'wysiwyg',
            'toolbar'      => 'basic',
            'media_upload' => 0,
        ),
        array(
            'key'           => 'field_cp_consulting_list_label',
            'label'         => 'List Label',
            'name'          => 'cp_consulting_list_label',
            'type'          => 'text',
            'default_value' => 'Our approach is defined by:',
        ),
        array(
            'key'          => 'field_cp_consulting_list',
            'label'        => 'List Items',
            'name'         => 'cp_consulting_list',
            'type'         => 'repeater',
            'layout'       => 'block',
            'button_label' => 'Add Item',
            'sub_fields'   => array(
                array(
                    'key'   => 'field_consulting_item_title',
                    'label' => 'Title',
                    'name'  => 'title',
                    'type'  => 'text',
                ),
                array(
                    'key'   => 'field_consulting_item_text',
                    'label' => 'Text',
                    'name'  => 'text',
                    'type'  => 'text',
                ),
            ),
        ),
        array(
            'key'           => 'field_cp_consulting_image',
            'label'         => 'Right Image',
            'name'          => 'cp_consulting_image',
            'type'          => 'image',
            'return_format' => 'url',
        ),
        array(
            'key'           => 'field_cp_consulting_badge_num',
            'label'         => 'Badge Number',
            'name'          => 'cp_consulting_badge_num',
            'type'          => 'text',
            'default_value' => '20+',
        ),
        array(
            'key'           => 'field_cp_consulting_badge_text',
            'label'         => 'Badge Text',
            'name'          => 'cp_consulting_badge_text',
            'type'          => 'text',
            'default_value' => 'Years Experience',
        ),

        /* ===================== What Sets Hufix Apart ===================== */
        array(
            'key'   => 'field_tab_sets_apart',
            'label' => 'Sets Apart',
            'type'  => 'tab',
        ),
        array(
            'key'           => 'field_cp_apart_bg',
            'label'         => 'Background Image',
            'name'          => 'cp_apart_bg',
            'type'          => 'image',
            'return_format' => 'url',
        ),
        array(
            'key'           => 'field_cp_apart_title',
            'label'         => 'Title',
            'name'          => 'cp_apart_title',
            'type'          => 'text',
            'default_value' => 'What Sets Hufix Apart',
        ),
        array(
            'key'          => 'field_cp_apart_cards',
            'label'        => 'Cards',
            'name'         => 'cp_apart_cards',
            'type'         => 'repeater',
            'layout'       => 'block',
            'button_label' => 'Add Card',
            'sub_fields'   => array(
                array(
                    'key'           => 'field_apart_card_icon',
                    'label'         => 'Icon',
                    'name'          => 'icon',
                    'type'          => 'image',
                    'return_format' => 'url',
                ),
                array(
                    'key'  => 'field_apart_card_title',
                    'label'=> 'Title',
                    'name' => 'title',
                    'type' => 'text',
                ),
                array(
                    'key'  => 'field_apart_card_text',
                    'label'=> 'Text',
                    'name' => 'text',
                    'type' => 'text',
                ),
            ),
        ),

    ),
    // الحقول دي تظهر بس جوه الصفحة دي (ID = 60)
    'location' => array(
        array(
            array(
                'param'    => 'page',
                'operator' => '==',
                'value'    => 60,
            ),
        ),
    ),
) );


/**
 * تعبئة المحتوى الحالي مرة واحدة بس لو الصفحة لسه فاضية
 */
function seed_default_consulting_page() {

    $page_id = 60;

    $existing = get_field( 'cp_core_cards', $page_id );
    if ( ! empty( $existing ) ) {
        return;
    }

    $theme_uri = get_template_directory_uri();

    // Training
    update_field( 'cp_training_desc', '<p>Elite Strategy Culturally Aligned Results-Driven.</p><p>At Hufix, we offer more than consulting — we deliver strategic transformation tailored to the MENA and European business landscape. With deep understanding of both global frameworks and local dynamics, our team empowers public and private sector leaders to make confident, future-ready decisions.</p><p>We don\'t offer generic advice. We build bold strategies, culturally adapted and designed for measurable impact.</p>', $page_id );
    update_field( 'cp_training_image', $theme_uri . '/assets/images/consultaion.png', $page_id );

    // Core Areas
    update_field( 'cp_core_cards', array(
        array(
            'icon'        => $theme_uri . '/assets/images/Icon (3).png',
            'title'       => 'Strategic Business Consulting',
            'description' => '<p>Organizational design & restructuring.</p><p>Corporate planning & growth roadmaps.</p><p>Strategic audits & scenario planning.</p>',
        ),
        array(
            'icon'        => $theme_uri . '/assets/images/Icon (4).png',
            'title'       => 'Human Capital & HR Innovation',
            'description' => '<p>Workforce strategy & talent management.</p><p>Culture transformation & engagement.</p><p>Agile HR model implementation.</p>',
        ),
        array(
            'icon'        => $theme_uri . '/assets/images/Icon (5).png',
            'title'       => 'Sustainability & Transformation',
            'description' => '<p>Circular economy models. ESG strategy & reporting. Sustainable development aligned with Vision 2030.</p>',
        ),
        array(
            'icon'        => $theme_uri . '/assets/images/Icon (6).png',
            'title'       => 'Governance, Risk & Compliance',
            'description' => '<p>GRC frameworks tailored to Gulf-specific regulations. Internal control enhancement. Risk culture assessment.</p>',
        ),
        array(
            'icon'        => $theme_uri . '/assets/images/Icon (7).png',
            'title'       => 'Finance & Investment Advisory',
            'description' => '<p>Business model optimization. Investment readiness consulting. Strategic budgeting & KPI modeling.</p>',
        ),
    ), $page_id );

    // Consulting Approach
    update_field( 'cp_consulting_desc', '<p>Every organization is unique — so are our solutions. We partner with you to identify challenges, clarify goals, and deliver tailor-made, actionable strategies backed by data and cultural insight.</p>', $page_id );

    update_field( 'cp_consulting_list', array(
        array( 'title' => 'Precision', 'text' => 'Evidence-based methods rooted in European consulting discipline.' ),
        array( 'title' => 'Adaptability', 'text' => 'Localized to Saudi Arabia, UAE, and wider Gulf contexts.' ),
        array( 'title' => 'Confidentiality', 'text' => 'Executive-level discretion and integrity.' ),
        array( 'title' => 'Partnership', 'text' => 'We walk the full journey with you — from planning to execution.' ),
    ), $page_id );

    update_field( 'cp_consulting_image', $theme_uri . '/assets/images/couls.jpg', $page_id );

    // Sets Apart
    update_field( 'cp_apart_cards', array(
        array( 'icon' => $theme_uri . '/assets/images/icons/new/Icon (3).png', 'title' => 'Cross-Continental Expertise', 'text' => 'Europian methodology meets Gulf realities' ),
        array( 'icon' => $theme_uri . '/assets/images/icons/new/Icon (4).png', 'title' => 'Client-Centric Advisory', 'text' => 'Built for leaders, not templates' ),
        array( 'icon' => $theme_uri . '/assets/images/icons/new/Icon (5).png', 'title' => 'Long-Term Partnership Model', 'text' => "We're with you from design to delivery" ),
        array( 'icon' => $theme_uri . '/assets/images/icons/new/Icon (6).png', 'title' => 'Accredited & Confidential', 'text' => 'Certified by the European Accreditation Center' ),
    ), $page_id );
}
add_action( 'acf/init', 'seed_default_consulting_page' );