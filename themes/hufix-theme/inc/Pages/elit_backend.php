<?php
/**
 * Backend: Elite Recruitment Services Page (page id = 124)
 * -----------------------------------------------------
 * حقول خاصة بالصفحة دي بس. محتاج ACF PRO (Repeater + Tab)
 * -----------------------------------------------------
 */

if ( ! function_exists( 'acf_add_local_field_group' ) ) {
    return;
}

acf_add_local_field_group( array(
    'key'    => 'group_recruitment_page',
    'title'  => 'Recruitment Page Fields',
    'fields' => array(

        /* ===================== Training (Intro) ===================== */
        array(
            'key'   => 'field_tab_rec_training',
            'label' => 'Training',
            'type'  => 'tab',
        ),
        array(
            'key'           => 'field_rec_training_title',
            'label'         => 'Title',
            'name'          => 'rec_training_title',
            'type'          => 'text',
            'default_value' => 'Elite Recruitment Services',
        ),
        array(
            'key'          => 'field_rec_training_desc',
            'label'        => 'Description',
            'name'         => 'rec_training_desc',
            'type'         => 'wysiwyg',
            'toolbar'      => 'basic',
            'media_upload' => 0,
        ),
        array(
            'key'           => 'field_rec_training_button_text',
            'label'         => 'Button Text',
            'name'          => 'rec_training_button_text',
            'type'          => 'text',
            'default_value' => 'Request a Talent Consultation',
        ),
         array(
                'key'   => 'field_rec_training_button_link',
                'label' => 'Button Link',
                'name'  => 'rec_training_button_link',
                'type'  => 'url',
            ),
            array(
                'key'               => 'field_rec_training_button_popup',
                'label'             => 'Button Popup',
                'name'              => 'rec_training_button_popup',
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
            'key'           => 'field_rec_training_image',
            'label'         => 'Right Image',
            'name'          => 'rec_training_image',
            'type'          => 'image',
            'return_format' => 'url',
        ),

        /* ===================== ROI ===================== */
        array(
            'key'   => 'field_tab_rec_roi',
            'label' => 'ROI Section',
            'type'  => 'tab',
        ),
        array(
            'key'           => 'field_rec_roi_bg',
            'label'         => 'Background Image',
            'name'          => 'rec_roi_bg',
            'type'          => 'image',
            'return_format' => 'url',
        ),
        array(
            'key'           => 'field_rec_roi_title',
            'label'         => 'Title',
            'name'          => 'rec_roi_title',
            'type'          => 'text',
            'default_value' => 'Beyond Placement: Measurable ROI',
        ),
        array(
            'key'          => 'field_rec_roi_cards',
            'label'        => 'Cards',
            'name'         => 'rec_roi_cards',
            'type'         => 'repeater',
            'layout'       => 'block',
            'button_label' => 'Add Card',
            'sub_fields'   => array(
                array(
                    'key'           => 'field_roi_card_icon',
                    'label'         => 'Icon',
                    'name'          => 'icon',
                    'type'          => 'image',
                    'return_format' => 'url',
                ),
                array(
                    'key'  => 'field_roi_card_title',
                    'label'=> 'Title',
                    'name' => 'title',
                    'type' => 'text',
                ),
                array(
                    'key'  => 'field_roi_card_text',
                    'label'=> 'Text',
                    'name' => 'text',
                    'type' => 'text',
                ),
            ),
        ),
        array(
            'key'           => 'field_rec_roi_quote',
            'label'         => 'Quote',
            'name'          => 'rec_roi_quote',
            'type'          => 'text',
            'default_value' => 'Because hiring the wrong person costs you more than just a salary — it costs time, trust, and momentum.',
        ),

        /* ===================== Deliver ===================== */
        array(
            'key'   => 'field_tab_rec_deliver',
            'label' => 'What We Deliver',
            'type'  => 'tab',
        ),
        array(
            'key'           => 'field_rec_deliver_title',
            'label'         => 'Title',
            'name'          => 'rec_deliver_title',
            'type'          => 'textarea',
            'rows'          => 2,
            'instructions'  => 'كل سطر (Enter) = سطر جديد بالـ <br>',
            'default_value' => "What We Deliver\nTailored recruitment solutions:",
        ),
        array(
            'key'          => 'field_rec_deliver_list',
            'label'        => 'List Items',
            'name'         => 'rec_deliver_list',
            'type'         => 'repeater',
            'layout'       => 'table',
            'button_label' => 'Add Item',
            'sub_fields'   => array(
                array(
                    'key'           => 'field_deliver_item_icon',
                    'label'         => 'Icon',
                    'name'          => 'icon',
                    'type'          => 'image',
                    'return_format' => 'url',
                ),
                array(
                    'key'   => 'field_deliver_item_text',
                    'label' => 'Text',
                    'name'  => 'text',
                    'type'  => 'text',
                ),
            ),
        ),
        array(
            'key'           => 'field_rec_deliver_footer',
            'label'         => 'Footer Text',
            'name'          => 'rec_deliver_footer',
            'type'          => 'text',
            'default_value' => "Whether it's one role or a full team, Hufix delivers on-time, on-brand, and on-target.",
        ),
        array(
            'key'           => 'field_rec_deliver_image',
            'label'         => 'Image',
            'name'          => 'rec_deliver_image',
            'type'          => 'image',
            'return_format' => 'url',
        ),

        /* ===================== Process ===================== */
        array(
            'key'   => 'field_tab_rec_process',
            'label' => 'Process',
            'type'  => 'tab',
        ),
        array(
            'key'           => 'field_rec_process_title',
            'label'         => 'Title',
            'name'          => 'rec_process_title',
            'type'          => 'text',
            'default_value' => 'Our Proven Process',
        ),
        array(
            'key'          => 'field_rec_process_steps',
            'label'        => 'Steps',
            'name'         => 'rec_process_steps',
            'type'         => 'repeater',
            'layout'       => 'table',
            'button_label' => 'Add Step',
            'sub_fields'   => array(
                array(
                    'key'   => 'field_process_step_text',
                    'label' => 'Text',
                    'name'  => 'text',
                    'type'  => 'text',
                ),
            ),
        ),
        array(
            'key'           => 'field_rec_process_footer',
            'label'         => 'Footer Text',
            'name'          => 'rec_process_footer',
            'type'          => 'text',
            'default_value' => 'Every step is transparent, fast, and aligned with your internal HR standards.',
        ),

        /* ===================== Trust ===================== */
        array(
            'key'   => 'field_tab_rec_trust',
            'label' => 'Trust',
            'type'  => 'tab',
        ),
        array(
            'key'           => 'field_rec_trust_title',
            'label'         => 'Title',
            'name'          => 'rec_trust_title',
            'type'          => 'text',
            'default_value' => 'Why Our Clients Trust Hufix Recruitment',
        ),
        array(
            'key'           => 'field_rec_trust_subtitle',
            'label'         => 'Subtitle',
            'name'          => 'rec_trust_subtitle',
            'type'          => 'textarea',
            'rows'          => 3,
            'instructions'  => 'كل سطر (Enter) = سطر جديد بالـ <br>',
            'default_value' => "We are not a CV factory — we are talent architects.\nOur recruitment process is designed around the unique culture, needs, and strategic\ngoals of your organization.",
        ),
        array(
            'key'          => 'field_rec_trust_cards',
            'label'        => 'Cards',
            'name'         => 'rec_trust_cards',
            'type'         => 'repeater',
            'layout'       => 'block',
            'button_label' => 'Add Card',
            'sub_fields'   => array(
                array(
                    'key'           => 'field_rec_trust_card_icon',
                    'label'         => 'Icon',
                    'name'          => 'icon',
                    'type'          => 'image',
                    'return_format' => 'url',
                ),
                array(
                    'key'  => 'field_rec_trust_card_title',
                    'label'=> 'Title',
                    'name' => 'title',
                    'type' => 'text',
                ),
                array(
                    'key'  => 'field_rec_trust_card_text',
                    'label'=> 'Text',
                    'name' => 'text',
                    'type' => 'text',
                ),
            ),
        ),
        array(
            'key'           => 'field_rec_trust_quote',
            'label'         => 'Quote',
            'name'          => 'rec_trust_quote',
            'type'          => 'text',
            'default_value' => 'With Hufix, we were able to hire fast — and more importantly, hire right.',
        ),

    ),
    // الحقول دي تظهر بس جوه الصفحة دي (ID = 124)
    'location' => array(
        array(
            array(
                'param'    => 'page',
                'operator' => '==',
                'value'    => 124,
            ),
        ),
    ),
) );


/**
 * تعبئة المحتوى الحالي مرة واحدة بس لو الصفحة لسه فاضية
 */
function seed_default_recruitment_page() {

    $page_id = 124;

    $existing = get_field( 'rec_roi_cards', $page_id );
    if ( ! empty( $existing ) ) {
        return;
    }

    $theme_uri = get_template_directory_uri();

    // Training
    update_field( 'rec_training_desc', "<p>Precision Hiring. Hassle-Free Process. Measurable ROI.</p><p>At Hufix, we understand that recruitment is more than just filling positions — it's about finding the right people who accelerate performance, reduce turnover, and grow your organization.</p><p>With our zero-hassle recruitment model, we handle everything — so you can stay focused on what matters most.</p><p>We don't just promise quality talent — we guarantee it. Every hire is backed by our commitment to excellence, compliance, and cultural fit.</p>", $page_id );
    update_field( 'rec_training_image', $theme_uri . '/assets/images/elit.png', $page_id );

    // ROI
    update_field( 'rec_roi_cards', array(
        array( 'icon' => $theme_uri . '/assets/images/elit/Icon (3).png', 'title' => 'Time Saved', 'text' => 'You interview only top-tier, pre-screened profiles' ),
        array( 'icon' => $theme_uri . '/assets/images/elit/Icon (4).png', 'title' => 'Turnover Reduced', 'text' => 'Our retention rate after placement exceeds 90%' ),
        array( 'icon' => $theme_uri . '/assets/images/elit/Icon (5).png', 'title' => 'Productivity Boosted', 'text' => 'Faster onboarding, better cultural alignment' ),
        array( 'icon' => $theme_uri . '/assets/images/elit/Icon (6).png', 'title' => 'Cost Controlled', 'text' => 'Fixed-rate pricing, no hidden fees, guaranteed quality' ),
    ), $page_id );

    // Deliver
    update_field( 'rec_deliver_list', array(
        array( 'icon' => $theme_uri . '/assets/images/elit/elit/Margin (1).png', 'text' => 'Government & semi government entities' ),
        array( 'icon' => $theme_uri . '/assets/images/elit/elit/Icon (3).png', 'text' => 'Corporates and family businesses' ),
        array( 'icon' => $theme_uri . '/assets/images/elit/elit/Icon (4).png', 'text' => 'Multinationals entering the GCC' ),
        array( 'icon' => $theme_uri . '/assets/images/elit/elit/Icon (5).png', 'text' => 'Startups and newly launched ventures' ),
    ), $page_id );

    update_field( 'rec_deliver_image', $theme_uri . '/assets/images/elit_2.jpg', $page_id );

    // Process
    update_field( 'rec_process_steps', array(
        array( 'text' => 'Understand your goals, team dynamics & KPIs' ),
        array( 'text' => 'Create or refine job descriptions (if needed)' ),
        array( 'text' => 'Source from our GCC & international talent pool' ),
        array( 'text' => 'Screen, interview, & assess fit (behavioral + technical)' ),
        array( 'text' => 'Deliver final shortlist with recommendations' ),
        array( 'text' => 'Assist in offer negotiation & onboarding' ),
        array( 'text' => 'Post-hire support during integration period' ),
    ), $page_id );

    // Trust
    update_field( 'rec_trust_cards', array(
        array( 'icon' => $theme_uri . '/assets/images/elit/icons/Icon (3).png', 'title' => 'Guaranteed Placements', 'text' => 'Every candidate we place is backed by a structured replacement guarantee, minimizing hiring risk and cost of turnover.' ),
        array( 'icon' => $theme_uri . '/assets/images/elit/icons/Container (3).png', 'title' => 'Zero Hassle for You', 'text' => 'We manage the entire lifecycle of the recruitment process — from job profiling to final onboarding. You simply select from qualified, pre-screened candidates.' ),
        array( 'icon' => $theme_uri . '/assets/images/elit/icons/Container (4).png', 'title' => 'ROI-Focused Hiring', 'text' => 'Our recruitment strategy aims to reduce time-to-fill, increase long-term retention, and ensure productivity from day one.' ),
        array( 'icon' => $theme_uri . '/assets/images/elit/icons/Container (5).png', 'title' => 'Bespoke & Confidential', 'text' => 'Discreet and high-quality hiring, especially for senior or sensitive roles, fully aligned with your internal protocols.' ),
    ), $page_id );
}
add_action( 'acf/init', 'seed_default_recruitment_page' );