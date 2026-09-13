<?php
/**
 * Backend: HR Outsourcing & Advisory Page (page id = 98)
 * -----------------------------------------------------
 * حقول خاصة بالصفحة دي بس. محتاج ACF PRO (Repeater + Tab)
 * -----------------------------------------------------
 */

if ( ! function_exists( 'acf_add_local_field_group' ) ) {
    return;
}

acf_add_local_field_group( array(
    'key'    => 'group_hr_outsourcing_page',
    'title'  => 'HR Outsourcing Page Fields',
    'fields' => array(

        /* ===================== Training (Intro) ===================== */
        array(
            'key'   => 'field_tab_ho_training',
            'label' => 'Training',
            'type'  => 'tab',
        ),
        array(
            'key'           => 'field_ho_training_title',
            'label'         => 'Title',
            'name'          => 'ho_training_title',
            'type'          => 'text',
            'default_value' => 'HR Outsourcing & Advisory Programs – Hufix',
        ),
        array(
            'key'          => 'field_ho_training_desc',
            'label'        => 'Description',
            'name'         => 'ho_training_desc',
            'type'         => 'wysiwyg',
            'toolbar'      => 'basic',
            'media_upload' => 0,
        ),
        array(
            'key'           => 'field_ho_training_button_text',
            'label'         => 'Button Text',
            'name'          => 'ho_training_button_text',
            'type'          => 'text',
            'default_value' => 'Book an HR Audit',
        ),
         array(
                'key'   => 'field_ho_training_button_link',
                'label' => 'Button Link',
                'name'  => 'ho_training_button_link',
                'type'  => 'url',
            ),
            array(
                'key'               => 'field_ho_training_button_popup',
                'label'             => 'Button Popup',
                'name'              => 'ho_training_button_popup',
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
            'key'           => 'field_ho_training_image',
            'label'         => 'Right Image',
            'name'          => 'ho_training_image',
            'type'          => 'image',
            'return_format' => 'url',
        ),

        /* ===================== HR Support Blocks (repeatable) ===================== */
        array(
            'key'   => 'field_tab_ho_support',
            'label' => 'HR Support Blocks',
            'type'  => 'tab',
        ),
        array(
            'key'          => 'field_ho_support_blocks',
            'label'        => 'Blocks',
            'name'         => 'ho_support_blocks',
            'type'         => 'repeater',
            'layout'       => 'block',
            'button_label' => 'Add Block',
            'sub_fields'   => array(

                array(
                    'key'           => 'field_support_top_icon',
                    'label'         => 'Top Icon',
                    'name'          => 'top_icon',
                    'type'          => 'image',
                    'return_format' => 'url',
                ),
                array(
                    'key'  => 'field_support_title',
                    'label'=> 'Title',
                    'name' => 'title',
                    'type' => 'text',
                ),
                array(
                    'key'          => 'field_support_desc',
                    'label'        => 'Description',
                    'name'         => 'description',
                    'type'         => 'wysiwyg',
                    'toolbar'      => 'basic',
                    'media_upload' => 0,
                ),
                array(
                    'key'   => 'field_support_items_label',
                    'label' => 'Items Label',
                    'name'  => 'items_label',
                    'type'  => 'text',
                    'instructions' => 'مثال: "Our operational HR services include:"',
                ),
                array(
                    'key'          => 'field_support_items',
                    'label'        => 'Service Items',
                    'name'         => 'items',
                    'type'         => 'repeater',
                    'layout'       => 'table',
                    'button_label' => 'Add Item',
                    'sub_fields'   => array(
                        array(
                            'key'           => 'field_support_item_icon',
                            'label'         => 'Icon',
                            'name'          => 'icon',
                            'type'          => 'image',
                            'return_format' => 'url',
                        ),
                        array(
                            'key'   => 'field_support_item_text',
                            'label' => 'Text',
                            'name'  => 'text',
                            'type'  => 'text',
                        ),
                    ),
                ),
                array(
                    'key'   => 'field_support_footer',
                    'label' => 'Footer Text',
                    'name'  => 'footer',
                    'type'  => 'text',
                ),
                array(
                    'key'           => 'field_support_image',
                    'label'         => 'Right Image',
                    'name'          => 'image',
                    'type'          => 'image',
                    'return_format' => 'url',
                ),
                array(
                    'key'           => 'field_support_image_position',
                    'label'         => 'Image Position',
                    'name'          => 'image_position',
                    'type'          => 'select',
                    'choices'       => array(
                        'right' => 'Right (after content)',
                        'left'  => 'Left (before content)',
                    ),
                    'default_value' => 'right',
                ),
                array(
                    'key'          => 'field_support_bg_variant',
                    'label'        => 'Alternate Background',
                    'name'         => 'bg_variant',
                    'type'         => 'true_false',
                    'ui'           => 1,
                    'instructions' => 'فعّله عشان يتحط كلاس hr_support_bg_2 (خلفية مختلفة)',
                ),

            ),
        ),

        /* ===================== Why Trust ===================== */
        array(
            'key'   => 'field_tab_ho_why_trust',
            'label' => 'Why Trust',
            'type'  => 'tab',
        ),
        array(
            'key'           => 'field_ho_trust_bg',
            'label'         => 'Background Image',
            'name'          => 'ho_trust_bg',
            'type'          => 'image',
            'return_format' => 'url',
        ),
        array(
            'key'           => 'field_ho_trust_title',
            'label'         => 'Title',
            'name'          => 'ho_trust_title',
            'type'          => 'text',
            'default_value' => 'Why Companies Trust Hufix for HR Outsourcing & Advisory',
        ),
        array(
            'key'          => 'field_ho_trust_cards',
            'label'        => 'Cards',
            'name'         => 'ho_trust_cards',
            'type'         => 'repeater',
            'layout'       => 'table',
            'button_label' => 'Add Card',
            'sub_fields'   => array(
                array(
                    'key'           => 'field_ho_trust_card_icon',
                    'label'         => 'Icon',
                    'name'          => 'icon',
                    'type'          => 'image',
                    'return_format' => 'url',
                ),
                array(
                    'key'  => 'field_ho_trust_card_title',
                    'label'=> 'Title',
                    'name' => 'title',
                    'type' => 'text',
                ),
                array(
                    'key'          => 'field_ho_trust_card_offset',
                    'label'        => 'Offset (Second Row)',
                    'name'         => 'offset',
                    'type'         => 'true_false',
                    'ui'           => 1,
                    'instructions' => 'فعّله للكروت اللي في الصف التاني (النص المتوسط)',
                ),
            ),
        ),
        array(
            'key'           => 'field_ho_trust_quote',
            'label'         => 'Quote',
            'name'          => 'ho_trust_quote',
            'type'          => 'text',
            'default_value' => 'Hufix turned our HR into a high-functioning, risk-free asset — without adding overhead.',
        ),

    ),
    // الحقول دي تظهر بس جوه الصفحة دي (ID = 98)
    'location' => array(
        array(
            array(
                'param'    => 'page',
                'operator' => '==',
                'value'    => 98,
            ),
        ),
    ),
) );


/**
 * تعبئة المحتوى الحالي مرة واحدة بس لو الصفحة لسه فاضية
 */
function seed_default_hr_outsourcing_page() {

    $page_id = 98;

    $existing = get_field( 'ho_support_blocks', $page_id );
    if ( ! empty( $existing ) ) {
        return;
    }

    $theme_uri = get_template_directory_uri();

    // Training
    update_field( 'ho_training_desc', "<p>Your External HR Partner — Operationally Efficient, Legally Compliant, Strategically Aligned</p><p>At Hufix, we offer full-spectrum HR outsourcing services combined with strategic HR advisory programs that empower organizations to grow confidently in Saudi Arabia and across the GCC.</p><p>Whether you're a fast-scaling startup or a government entity managing complex HR operations — we ensure smooth, professional, and fully compliant execution.</p><p>With Hufix, your HR function is not just supported — it's optimized.</p>", $page_id );
    update_field( 'ho_training_image', $theme_uri . '/assets/images/hr_qut.jpg', $page_id );

    // HR Support Blocks
    update_field( 'ho_support_blocks', array(

        array(
            'top_icon'       => $theme_uri . '/assets/images/hr_q/Container (3).png',
            'title'          => 'Operational HR Support – Stress-Free Execution',
            'description'    => '<p>We become your external HR department, taking care of the day-to-day tasks that are essential, yet time-consuming — ensuring accuracy, legal integrity, and employee satisfaction.</p>',
            'items_label'    => 'Our operational HR services include:',
            'items'          => array(
                array( 'icon' => $theme_uri . '/assets/images/hr_q/Icon (3).png', 'text' => 'Employee record management' ),
                array( 'icon' => $theme_uri . '/assets/images/hr_q/Icon (4).png', 'text' => 'Payroll calculation & payslip issuance' ),
                array( 'icon' => $theme_uri . '/assets/images/hr_q/Icon (5).png', 'text' => 'Insurance & WPS compliance' ),
                array( 'icon' => $theme_uri . '/assets/images/hr_q/Icon (6).png', 'text' => 'Visa processing & Iqama management' ),
                array( 'icon' => $theme_uri . '/assets/images/hr_q/Icon (7).png', 'text' => 'Leave tracking and end-of-service processing' ),
                array( 'icon' => $theme_uri . '/assets/images/hr_q/Icon (8).png', 'text' => 'Digital HR file archiving' ),
                array( 'icon' => $theme_uri . '/assets/images/hr_q/Icon (9).png', 'text' => 'Full Platform Management : GOSI, Qiwa, Mudad, Muqeem' ),
            ),
            'footer'         => 'You focus on growth. We take care of the systems, the deadlines, and the documentation — reliably and on time.',
            'image'          => $theme_uri . '/assets/images/hr_q_2.png',
            'image_position' => 'right',
            'bg_variant'     => 0,
        ),

        array(
            'top_icon'       => $theme_uri . '/assets/images/hr_q/Container (3).png',
            'title'          => 'Strategic HR Advisory – Build Resilient Teams',
            'description'    => '<p>In addition to operations, we help organizations future-proof their HR function through advisory programs led by certified experts.</p>',
            'items_label'    => 'What we deliver:',
            'items'          => array(
                array( 'icon' => $theme_uri . '/assets/images/hr_q/N_2/Icon (3).png', 'text' => 'HR strategy & workforce planning' ),
                array( 'icon' => $theme_uri . '/assets/images/hr_q/N_2/Icon (8).png', 'text' => 'Leadership development frameworks' ),
                array( 'icon' => $theme_uri . '/assets/images/hr_q/N_2/Icon (4).png', 'text' => 'Policy development and cultural alignment' ),
                array( 'icon' => $theme_uri . '/assets/images/hr_q/N_2/Icon (7).png', 'text' => 'Compliance audits and risk management' ),
                array( 'icon' => $theme_uri . '/assets/images/hr_q/N_2/Icon (5).png', 'text' => 'Performance management systems' ),
                array( 'icon' => $theme_uri . '/assets/images/hr_q/N_2/Icon (6).png', 'text' => 'Full Platform Management : GOSI, Qiwa, Mudad, Muqeem' ),
            ),
            'footer'         => 'Our advisory model combines international HR frameworks with a strong understanding of regional talent trends.',
            'image'          => $theme_uri . '/assets/images/hr_q_3.png',
            'image_position' => 'left',
            'bg_variant'     => 1,
        ),

    ), $page_id );

    // Why Trust
    update_field( 'ho_trust_cards', array(
        array( 'icon' => $theme_uri . '/assets/images/hr_q/N/Icon (3).png', 'title' => 'Deep expertise in labor regulations', 'offset' => 0 ),
        array( 'icon' => $theme_uri . '/assets/images/hr_q/N/Icon (4).png', 'title' => 'Trusted partner for government and semi-government sectors', 'offset' => 0 ),
        array( 'icon' => $theme_uri . '/assets/images/hr_q/N/Icon (5).png', 'title' => 'Certified by the European Accreditation Center', 'offset' => 0 ),
        array( 'icon' => $theme_uri . '/assets/images/hr_q/N/Icon (6).png', 'title' => 'Transparent pricing and contractual flexibility', 'offset' => 1 ),
        array( 'icon' => $theme_uri . '/assets/images/hr_q/N/Icon (7).png', 'title' => 'Strategic mindset + operational precision', 'offset' => 1 ),
    ), $page_id );
}
add_action( 'acf/init', 'seed_default_hr_outsourcing_page' );