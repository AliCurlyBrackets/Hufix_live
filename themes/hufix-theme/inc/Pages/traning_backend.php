<?php
/**
 * Backend: Training Page (page id = 141)
 * -----------------------------------------------------
 * ملحوظة: سيكشن "Training category" (السلايدر) اتسيب
 * ثابت زي ما هو من غير أي حقول ACF بناء على طلبك
 * محتاج ACF PRO (Repeater + Tab)
 * -----------------------------------------------------
 */

if ( ! function_exists( 'acf_add_local_field_group' ) ) {
    return;
}

acf_add_local_field_group( array(
    'key'    => 'group_training_page',
    'title'  => 'Training Page Fields',
    'fields' => array(

/* ===================== Training (Intro) ===================== */
        array(
            'key'   => 'field_tab_tr_intro',
            'label' => 'Intro',
            'type'  => 'tab',
        ),
        array(
            'key'           => 'field_tr_intro_title',
            'label'         => 'Title',
            'name'          => 'tr_intro_title',
            'type'          => 'text',
            'default_value' => 'Training That Delivers RealImpact',
        ),
        array(
            'key'          => 'field_tr_intro_desc',
            'label'        => 'Description',
            'name'         => 'tr_intro_desc',
            'type'         => 'wysiwyg',
            'toolbar'      => 'basic',
            'media_upload' => 0,
        ),
        array(
            'key'           => 'field_tr_intro_button_text',
            'label'         => 'Button Text',
            'name'          => 'tr_intro_button_text',
            'type'          => 'text',
            'default_value' => 'Request a Training Proposal',
        ),
        array(
            'key'   => 'field_tr_intro_button_link',
            'label' => 'Button Link',
            'name'  => 'tr_intro_button_link',
            'type'  => 'url',
        ),
        array(
            'key'               => 'field_tr_intro_button_popup',
            'label'             => 'Button Popup',
            'name'              => 'tr_intro_button_popup',
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
            'key'           => 'field_tr_intro_image',
            'label'         => 'Right Image',
            'name'          => 'tr_intro_image',
            'type'          => 'image',
            'return_format' => 'url',
        ),

        /* ===================== Training Category (Slider) ===================== */
        array(
            'key'   => 'field_tab_tr_training_category',
            'label' => 'Training Category',
            'type'  => 'tab',
        ),
        array(
            'key'               => 'field_tr_view_all_button_text',
            'label'             => 'View All Button Text',
            'name'              => 'tr_view_all_button_text',
            'type'              => 'text',
            'default_value'     => 'View All Training',
        ),
        array(
            'key'               => 'field_tr_view_all_button_link',
            'label'             => 'View All Button Link',
            'name'              => 'tr_view_all_button_link',
            'type'              => 'url',
            'placeholder'       => 'https://example.com',
        ),
        array(
            'key'               => 'field_tr_view_all_button_popup',
            'label'             => 'View All Button Popup',
            'name'              => 'tr_view_all_button_popup',
            'type'              => 'select',
            'choices'           => array(
                ''             => 'None (use link above)',
                'service'      => 'Book a Service',
                'training'     => 'Request Training',
                'hr'           => 'Request HR Consultation',
                'consultation' => 'Request Free Consultation',
            ),
            'instructions'      => 'Select a popup to open when the "View All" button is clicked.',
        ),

        /* ===================== Tailored Sections ===================== */
        array(
            'key'   => 'field_tab_tr_tailored',
            'label' => 'Tailored Sections',
            'type'  => 'tab',
        ),
        array(
            'key'          => 'field_tr_tailored_sections',
            'label'        => 'Sections',
            'name'         => 'tr_tailored_sections',
            'type'         => 'repeater',
            'layout'       => 'block',
            'button_label' => 'Add Section',
            'sub_fields'   => array(
                array(
                    'key'   => 'field_tr_tailored_title',
                    'label' => 'Title',
                    'name'  => 'title',
                    'type'  => 'text',
                ),
                array(
                    'key'   => 'field_tr_tailored_desc',
                    'label' => 'Description',
                    'name'  => 'description',
                    'type'  => 'text',
                ),
                array(
                    'key'           => 'field_tr_tailored_image',
                    'label'         => 'Image',
                    'name'          => 'image',
                    'type'          => 'image',
                    'return_format' => 'url',
                ),
                array(
                    'key'           => 'field_tr_tailored_image_position',
                    'label'         => 'Image Position',
                    'name'          => 'image_position',
                    'type'          => 'select',
                    'choices'       => array(
                        'left'  => 'Left (before content)',
                        'right' => 'Right (after content)',
                    ),
                    'default_value' => 'left',
                ),
                array(
                    'key'           => 'field_tr_tailored_check_icon',
                    'label'         => 'List Check Icon',
                    'name'          => 'check_icon',
                    'type'          => 'image',
                    'return_format' => 'url',
                ),
                array(
                    'key'          => 'field_tr_tailored_list',
                    'label'        => 'List Items',
                    'name'         => 'list',
                    'type'         => 'repeater',
                    'layout'       => 'table',
                    'button_label' => 'Add Item',
                    'sub_fields'   => array(
                        array(
                            'key'   => 'field_tr_tailored_item_text',
                            'label' => 'Text',
                            'name'  => 'text',
                            'type'  => 'text',
                        ),
                    ),
                ),
                array(
                    'key'   => 'field_tr_tailored_footer',
                    'label' => 'Footer Text',
                    'name'  => 'footer',
                    'type'  => 'text',
                ),
            ),
        ),

        /* ===================== Trainer Network ===================== */
        array(
            'key'   => 'field_tab_tr_trainer',
            'label' => 'Trainer Network',
            'type'  => 'tab',
        ),
        array(
            'key'           => 'field_tr_trainer_title',
            'label'         => 'Title',
            'name'          => 'tr_trainer_title',
            'type'          => 'text',
            'default_value' => 'Our Trainer Network',
        ),
        array(
            'key'           => 'field_tr_trainer_subtitle',
            'label'         => 'Subtitle',
            'name'          => 'tr_trainer_subtitle',
            'type'          => 'text',
            'default_value' => 'Elite Expertise. Global Minds. Regional Relevance.',
        ),
        array(
            'key'          => 'field_tr_trainer_desc',
            'label'        => 'Description',
            'name'         => 'tr_trainer_desc',
            'type'         => 'wysiwyg',
            'toolbar'      => 'basic',
            'media_upload' => 0,
        ),
        array(
            'key'           => 'field_tr_trainer_programs_label',
            'label'         => 'Programs Label',
            'name'          => 'tr_trainer_programs_label',
            'type'          => 'text',
            'default_value' => 'We deliver specialized programs in:',
        ),
        array(
            'key'           => 'field_tr_trainer_check_icon',
            'label'         => 'List Check Icon',
            'name'          => 'tr_trainer_check_icon',
            'type'          => 'image',
            'return_format' => 'url',
        ),
        array(
            'key'          => 'field_tr_trainer_list',
            'label'        => 'List Items',
            'name'         => 'tr_trainer_list',
            'type'         => 'repeater',
            'layout'       => 'table',
            'button_label' => 'Add Item',
            'sub_fields'   => array(
                array(
                    'key'   => 'field_tr_trainer_item_text',
                    'label' => 'Text',
                    'name'  => 'text',
                    'type'  => 'text',
                ),
            ),
        ),
        array(
            'key'           => 'field_tr_trainer_footer',
            'label'         => 'Footer Text',
            'name'          => 'tr_trainer_footer',
            'type'          => 'text',
            'default_value' => 'Whether we train executives in Riyadh, Amsterdam, or New York, our delivery bridges the gap between global standards and local understanding.',
        ),

        /* ===================== Why Choose ===================== */
        array(
            'key'   => 'field_tab_tr_why',
            'label' => 'Why Choose',
            'type'  => 'tab',
        ),
        array(
            'key'           => 'field_tr_why_title',
            'label'         => 'Title',
            'name'          => 'tr_why_title',
            'type'          => 'text',
            'default_value' => 'Why Choose Hufix for Training?',
        ),
        array(
            'key'          => 'field_tr_why_cards',
            'label'        => 'Cards',
            'name'         => 'tr_why_cards',
            'type'         => 'repeater',
            'layout'       => 'table',
            'button_label' => 'Add Card',
            'sub_fields'   => array(
                array(
                    'key'           => 'field_tr_why_card_icon',
                    'label'         => 'Icon',
                    'name'          => 'icon',
                    'type'          => 'image',
                    'return_format' => 'url',
                ),
                array(
                    'key'   => 'field_tr_why_card_text',
                    'label' => 'Text',
                    'name'  => 'text',
                    'type'  => 'text',
                ),
            ),
        ),

    ),
    // الحقول دي تظهر بس جوه الصفحة دي (ID = 141)
    'location' => array(
        array(
            array(
                'param'    => 'page',
                'operator' => '==',
                'value'    => 141,
            ),
        ),
    ),
) );


/**
 * تعبئة المحتوى الحالي مرة واحدة بس لو الصفحة لسه فاضية
 */
function seed_default_training_page() {

    $page_id = 141;

    $existing = get_field( 'tr_tailored_sections', $page_id );
    if ( ! empty( $existing ) ) {
        return;
    }

    $theme_uri = get_template_directory_uri();

    // Intro
    update_field( 'tr_intro_desc', "<p>At Hufix, we believe that training is only valuable if it creates measurable results. That's why every program we deliver is tailored to your organization's goals, culture, and operational environment — whether it's in Saudi Arabia, the UAE, Europe, or beyond.</p><p>We don't teach for the sake of training. We build capabilities that improve performance, inspire leadership, and drive ROI.</p>", $page_id );
    update_field( 'tr_intro_image', $theme_uri . '/assets/images/Hero_Traning.png', $page_id );

    // Tailored Sections
    update_field( 'tr_tailored_sections', array(
        array(
            'title'          => 'Tailored Programs – Designed Around You',
            'description'    => 'We never offer off-the-shelf training. Every Hufix course is designed with your business objectives and workforce in mind — aligning with your KPIs, culture, and strategic direction.',
            'image'          => $theme_uri . '/assets/images/tt.png',
            'image_position' => 'left',
            'check_icon'     => $theme_uri . '/assets/images/right.png',
            'list'           => array(
                array( 'text' => 'Pre-training needs analysis' ),
                array( 'text' => 'Custom content & real case studies' ),
                array( 'text' => 'Bilingual materials (Arabic/English)' ),
                array( 'text' => 'Post-training evaluation & impact follow-up' ),
                array( 'text' => 'ROI and productivity tracking (upon request)' ),
            ),
            'footer'         => "Whether you're looking to develop leadership, optimize HR, implement agile practices, or elevate cross-functional collaboration — we create programs that fit.",
        ),
        array(
            'title'          => 'In-House Training at Your Offices',
            'description'    => 'Prefer your team to stay on-site? Hufix delivers full-scale in-house training programs at your location — with minimal disruption and full impact.',
            'image'          => $theme_uri . '/assets/images/overs.png',
            'image_position' => 'right',
            'check_icon'     => $theme_uri . '/assets/images/right.png',
            'list'           => array(
                array( 'text' => 'Delivered at client HQ or branch locations' ),
                array( 'text' => 'Customized for your operational environment' ),
                array( 'text' => 'Delivered in English, Arabic, or bilingual' ),
                array( 'text' => 'Fully equipped trainers and materials provided' ),
                array( 'text' => 'Flexible scheduling: single-day intensives or multi-week formats' ),
                array( 'text' => 'Multi-City Delivery – Wherever You Operate' ),
            ),
            'footer'         => "Your team doesn't have to travel to grow — we bring the knowledge to you.",
        ),
    ), $page_id );

    // Trainer Network
    update_field( 'tr_trainer_desc', '<p>Our certified trainers come from Europe, the United States, and the GCC, delivering executive-level programs built for impact. We choose trainers not just for their credentials — but for their ability to connect, inspire, and adapt across diverse environments.</p>', $page_id );
    update_field( 'tr_trainer_check_icon', $theme_uri . '/assets/images/right2.png', $page_id );

    update_field( 'tr_trainer_list', array(
        array( 'text' => 'Leadership development in the Gulf and Europe' ),
        array( 'text' => 'HR transformation initiatives in Saudi Arabia, UAE, and the EU' ),
        array( 'text' => 'Agile coaching, strategic execution, finance, and sustainability' ),
        array( 'text' => 'Interpersonal skills & change leadership for multicultural teams' ),
    ), $page_id );

    // Why Choose
    update_field( 'tr_why_cards', array(
        array( 'icon' => $theme_uri . '/assets/images/Why/Margin (1).png', 'text' => 'Fully customized, not pre-packaged' ),
        array( 'icon' => $theme_uri . '/assets/images/Why/Margin (2).png', 'text' => 'GCC-based experts with EU-standard design' ),
        array( 'icon' => $theme_uri . '/assets/images/Why/Margin (3).png', 'text' => 'Strong government/private sector experience' ),
        array( 'icon' => $theme_uri . '/assets/images/Why/Margin (4).png', 'text' => 'ROI-focused methodology' ),
        array( 'icon' => $theme_uri . '/assets/images/Why/Margin (5).png', 'text' => 'Certified by the European Accreditation Center' ),
        array( 'icon' => $theme_uri . '/assets/images/Why/Margin (6).png', 'text' => 'Cross-regional consistency' ),
        array( 'icon' => $theme_uri . '/assets/images/Why/Margin (7).png', 'text' => 'Access to regional case studies & context' ),
        array( 'icon' => $theme_uri . '/assets/images/Why/Margin (8).png', 'text' => 'Trainers with local and global expertise' ),
        array( 'icon' => $theme_uri . '/assets/images/Why/Margin (9).png', 'text' => 'Seamless coordination with your local HR teams' ),
    ), $page_id );
}
add_action( 'acf/init', 'seed_default_training_page' );