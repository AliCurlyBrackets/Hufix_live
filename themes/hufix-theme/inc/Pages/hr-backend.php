<?php
/**
 * Backend: HR Infrastructure Page (page id = 76)
 * -----------------------------------------------------
 * حقول خاصة بالصفحة دي بس. محتاج ACF PRO (Repeater + Tab)
 * -----------------------------------------------------
 */

if ( ! function_exists( 'acf_add_local_field_group' ) ) {
    return;
}

acf_add_local_field_group( array(
    'key'    => 'group_hr_page',
    'title'  => 'HR Infrastructure Page Fields',
    'fields' => array(

        /* ===================== Training (Intro) ===================== */
        array(
            'key'   => 'field_tab_hr_training',
            'label' => 'Training',
            'type'  => 'tab',
        ),
        array(
            'key'           => 'field_hr_training_title',
            'label'         => 'Title',
            'name'          => 'hr_training_title',
            'type'          => 'text',
            'default_value' => 'HR Infrastructure Done Right – From Day One',
        ),
        array(
            'key'          => 'field_hr_training_desc',
            'label'        => 'Description',
            'name'         => 'hr_training_desc',
            'type'         => 'wysiwyg',
            'toolbar'      => 'basic',
            'media_upload' => 0,
        ),
        array(
            'key'           => 'field_hr_training_button_text',
            'label'         => 'Button Text',
            'name'          => 'hr_training_button_text',
            'type'          => 'text',
            'default_value' => 'Start Building',
        ),
         array(
                'key'   => 'field_hr_training_button_link',
                'label' => 'Button Link',
                'name'  => 'hr_training_button_link',
                'type'  => 'url',
            ),
            array(
                'key'               => 'field_hr_training_button_popup',
                'label'             => 'Button Popup',
                'name'              => 'hr_training_button_popup',
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
            'key'           => 'field_hr_training_image',
            'label'         => 'Right Image',
            'name'          => 'hr_training_image',
            'type'          => 'image',
            'return_format' => 'url',
        ),

        /* ===================== Build ===================== */
        array(
            'key'   => 'field_tab_hr_build',
            'label' => 'What We Build',
            'type'  => 'tab',
        ),
        array(
            'key'           => 'field_hr_build_bg',
            'label'         => 'Background Image',
            'name'          => 'hr_build_bg',
            'type'          => 'image',
            'return_format' => 'url',
        ),
        array(
            'key'           => 'field_hr_build_title',
            'label'         => 'Title',
            'name'          => 'hr_build_title',
            'type'          => 'text',
            'default_value' => 'What We Build for You',
        ),
        array(
            'key'          => 'field_hr_build_subtitle',
            'label'        => 'Subtitle',
            'name'         => 'hr_build_subtitle',
            'type'         => 'wysiwyg',
            'toolbar'      => 'basic',
            'media_upload' => 0,
        ),
        array(
            'key'          => 'field_hr_build_cards',
            'label'        => 'Cards',
            'name'         => 'hr_build_cards',
            'type'         => 'repeater',
            'layout'       => 'table',
            'button_label' => 'Add Card',
            'sub_fields'   => array(
                array(
                    'key'           => 'field_build_card_icon',
                    'label'         => 'Icon',
                    'name'          => 'icon',
                    'type'          => 'image',
                    'return_format' => 'url',
                ),
                array(
                    'key'  => 'field_build_card_title',
                    'label'=> 'Title',
                    'name' => 'title',
                    'type' => 'text',
                ),
            ),
        ),

        /* ===================== Tailored Sections (repeatable blocks) ===================== */
        array(
            'key'   => 'field_tab_hr_tailored',
            'label' => 'Tailored Sections',
            'type'  => 'tab',
        ),
        array(
            'key'          => 'field_hr_tailored_sections',
            'label'        => 'Sections',
            'name'         => 'hr_tailored_sections',
            'type'         => 'repeater',
            'layout'       => 'block',
            'button_label' => 'Add Section',
            'sub_fields'   => array(
                array(
                    'key'   => 'field_tailored_title',
                    'label' => 'Title',
                    'name'  => 'title',
                    'type'  => 'text',
                ),
                array(
                    'key'   => 'field_tailored_desc',
                    'label' => 'Description',
                    'name'  => 'description',
                    'type'  => 'text',
                ),
                array(
                    'key'           => 'field_tailored_image',
                    'label'         => 'Image',
                    'name'          => 'image',
                    'type'          => 'image',
                    'return_format' => 'url',
                ),
                array(
                    'key'          => 'field_tailored_image_position',
                    'label'        => 'Image Position',
                    'name'         => 'image_position',
                    'type'         => 'select',
                    'choices'      => array(
                        'left'  => 'Left (before content)',
                        'right' => 'Right (after content)',
                    ),
                    'default_value' => 'left',
                ),
                array(
                    'key'           => 'field_tailored_check_icon',
                    'label'         => 'List Check Icon',
                    'name'          => 'check_icon',
                    'type'          => 'image',
                    'return_format' => 'url',
                    'instructions'  => 'الأيقونة اللي هتظهر جنب كل بند في الليست',
                ),
                array(
                    'key'          => 'field_tailored_list',
                    'label'        => 'List Items',
                    'name'         => 'list',
                    'type'         => 'repeater',
                    'layout'       => 'table',
                    'button_label' => 'Add Item',
                    'sub_fields'   => array(
                        array(
                            'key'   => 'field_tailored_item_text',
                            'label' => 'Text',
                            'name'  => 'text',
                            'type'  => 'text',
                        ),
                    ),
                ),
                array(
                    'key'   => 'field_tailored_footer',
                    'label' => 'Footer Text',
                    'name'  => 'footer',
                    'type'  => 'text',
                ),
                array(
                    'key'          => 'field_tailored_transparent_bg',
                    'label'        => 'Transparent Background',
                    'name'         => 'transparent_bg',
                    'type'         => 'true_false',
                    'ui'           => 1,
                    'instructions' => 'فعّله لو عايز الخلفية شفافة (background: none)',
                ),
            ),
        ),

        /* ===================== Why Trust ===================== */
        array(
            'key'   => 'field_tab_hr_why_trust',
            'label' => 'Why Trust',
            'type'  => 'tab',
        ),
        array(
            'key'           => 'field_hr_trust_bg',
            'label'         => 'Background Image',
            'name'          => 'hr_trust_bg',
            'type'          => 'image',
            'return_format' => 'url',
        ),
        array(
            'key'           => 'field_hr_trust_title',
            'label'         => 'Title',
            'name'          => 'hr_trust_title',
            'type'          => 'text',
            'default_value' => 'Why Trust Hufix?',
        ),
        array(
            'key'          => 'field_hr_trust_cards',
            'label'        => 'Cards',
            'name'         => 'hr_trust_cards',
            'type'         => 'repeater',
            'layout'       => 'table',
            'button_label' => 'Add Card',
            'sub_fields'   => array(
                array(
                    'key'           => 'field_trust_card_icon',
                    'label'         => 'Icon',
                    'name'          => 'icon',
                    'type'          => 'image',
                    'return_format' => 'url',
                ),
                array(
                    'key'  => 'field_trust_card_title',
                    'label'=> 'Title',
                    'name' => 'title',
                    'type' => 'text',
                ),
                array(
                    'key'          => 'field_trust_card_offset',
                    'label'        => 'Offset (Second Row)',
                    'name'         => 'offset',
                    'type'         => 'true_false',
                    'ui'           => 1,
                    'instructions' => 'فعّله للكروت اللي في الصف التاني (النص المتوسط)',
                ),
            ),
        ),
        array(
            'key'          => 'field_hr_trust_quote',
            'label'        => 'Quote',
            'name'         => 'hr_trust_quote',
            'type'         => 'text',
            'default_value' => "Hufix didn't just help us comply — they helped us feel like a real company.",
        ),

    ),
    // الحقول دي تظهر بس جوه الصفحة دي (ID = 76)
    'location' => array(
        array(
            array(
                'param'    => 'page',
                'operator' => '==',
                'value'    => 76,
            ),
        ),
    ),
) );


/**
 * تعبئة المحتوى الحالي مرة واحدة بس لو الصفحة لسه فاضية
 */
function seed_default_hr_page() {

    $page_id = 76;

    $existing = get_field( 'hr_build_cards', $page_id );
    if ( ! empty( $existing ) ) {
        return;
    }

    $theme_uri = get_template_directory_uri();

    // Training
    update_field( 'hr_training_desc', '<p>Launching a new entity in Saudi Arabia or the GCC? Whether you\'re a startup, a global brand entering the market, or a local company scaling operations — Hufix builds your entire HR backbone from scratch with compliance, clarity, and cultural intelligence</p><p>With Hufix, you start strong. Fully compliant. Fully equipped. Future-ready.</p>', $page_id );
    update_field( 'hr_training_image', $theme_uri . '/assets/images/hr_hero.png', $page_id );

    // Build
    update_field( 'hr_build_subtitle', "<p>We don't provide templates — we design, activate, and operationalize your human resources function from the ground up. Foundation HR setup includes:</p>", $page_id );

    update_field( 'hr_build_cards', array(
        array( 'icon' => $theme_uri . '/assets/images/icons/hr/Icon (3).png', 'title' => 'Organization chart & workforce planning' ),
        array( 'icon' => $theme_uri . '/assets/images/icons/hr/Icon (4).png', 'title' => 'Job descriptions & role alignment' ),
        array( 'icon' => $theme_uri . '/assets/images/icons/hr/Icon (5).png', 'title' => 'HR policies & employee handbook' ),
        array( 'icon' => $theme_uri . '/assets/images/icons/hr/Icon (6).png', 'title' => 'Activation of government platforms (Qiwa, GOSI, Mudad, Muqeem, HRDF)' ),
        array( 'icon' => $theme_uri . '/assets/images/icons/hr/Icon (7).png', 'title' => 'Contracts, onboarding forms & templates' ),
        array( 'icon' => $theme_uri . '/assets/images/icons/hr/Icon (8).png', 'title' => 'Basic payroll structure and WPS compliance' ),
        array( 'icon' => $theme_uri . '/assets/images/icons/hr/Icon (9).png', 'title' => 'Medical insurance onboarding' ),
        array( 'icon' => $theme_uri . '/assets/images/icons/hr/Icon (10).png', 'title' => 'Staff induction training + HR system introduction' ),
    ), $page_id );

    // Tailored Sections
    update_field( 'hr_tailored_sections', array(
        array(
            'title'          => 'Designed for GCC Market Entry',
            'description'    => "If you're an international firm entering Saudi Arabia or the wider Gulf, we help you localize your people operations without losing your global identity.",
            'image'          => $theme_uri . '/assets/images/hr_2.png',
            'image_position' => 'left',
            'check_icon'     => $theme_uri . '/assets/images/right.png',
            'list'           => array(
                array( 'text' => 'Bilingual documentation (Arabic/English)' ),
                array( 'text' => 'Saudi labor law compliance' ),
                array( 'text' => 'Local holidays, working hours, and benefits integrated' ),
                array( 'text' => 'Cultural awareness included in onboarding processes' ),
                array( 'text' => 'Custom workflows for mixed or hybrid teams' ),
            ),
            'footer'         => 'We combine European structure with Saudi operational know-how.',
            'transparent_bg' => 1,
        ),
        array(
            'title'          => 'Ideal for Startups & First-Time Employers',
            'description'    => 'Are you a startup or a small team launching in Saudi Arabia? We help you:',
            'image'          => $theme_uri . '/assets/images/hr_3.png',
            'image_position' => 'right',
            'check_icon'     => $theme_uri . '/assets/images/empty_check.png',
            'list'           => array(
                array( 'text' => 'Appear professional from day one' ),
                array( 'text' => 'Stay legally compliant with MOHRE, MHRSD, and WPS' ),
                array( 'text' => 'Avoid costly HR errors' ),
                array( 'text' => 'Establish credibility with talent and investors' ),
            ),
            'footer'         => 'Even with limited staff or budget, we give you a scalable HR foundation that grows with you.',
            'transparent_bg' => 0,
        ),
    ), $page_id );

    // Why Trust
    update_field( 'hr_trust_cards', array(
        array( 'icon' => $theme_uri . '/assets/images/icons/Icon.png', 'title' => 'Deep GCC Market Knowledge', 'offset' => 0 ),
        array( 'icon' => $theme_uri . '/assets/images/icons/Icon (2).png', 'title' => 'Cross-Cultural Execution (EU–Gulf)', 'offset' => 0 ),
        array( 'icon' => $theme_uri . '/assets/images/Ico/Container (3).png', 'title' => 'Fully Aligned with Vision 2030 HR Mandates', 'offset' => 0 ),
        array( 'icon' => $theme_uri . '/assets/images/Ico/Container (4).png', 'title' => 'Trusted by Startups and Semi-Government Clients', 'offset' => 1 ),
        array( 'icon' => $theme_uri . '/assets/images/Ico/Container (5).png', 'title' => 'Certified by the European Accreditation Center', 'offset' => 1 ),
    ), $page_id );
}
add_action( 'acf/init', 'seed_default_hr_page' );