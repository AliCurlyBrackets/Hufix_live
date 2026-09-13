<?php
/**
 * Backend: About Us Page (page id = 34) - FIXED VERSION
 * -----------------------------------------------------
 * الفرق عن النسخة اللي فاتت:
 * 1) شلنا default_value من كل الـ Repeaters (ده كان سبب
 *    تكرار/تداخل البيانات) وبقينا نعبيها بـ update_field
 *    بعد التسجيل، بنفس الطريقة اللي اشتغلت تمام قبل كده
 * 2) ضفنا حقل خلفية للهيرو (au_hero_bg)
 * 3) كل حقول الوصف بقت Text Editor (wysiwyg) بدل textarea
 * -----------------------------------------------------
 */

if ( ! function_exists( 'acf_add_local_field_group' ) ) {
    return;
}

acf_add_local_field_group( array(
    'key'    => 'group_about_us_page',
    'title'  => 'About Us Page Fields',
    'fields' => array(

        /* ===================== Hero ===================== */
        array(
            'key'   => 'field_tab_hero',
            'label' => 'Hero',
            'type'  => 'tab',
        ),
        array(
            'key'           => 'field_au_hero_heading',
            'label'         => 'Hero Heading',
            'name'          => 'au_hero_heading',
            'type'          => 'textarea',
            'rows'          => 2,
            'instructions'  => 'اعمل سطر جديد (Enter) في المكان اللي عايز فيه break',
            'default_value' => "Welcome to Hufix\nBoutique consultation house",
        ),
        array(
            'key'           => 'field_au_hero_button',
            'label'         => 'Hero Button Text',
            'name'          => 'au_hero_button',
            'type'          => 'text',
            'default_value' => 'Request a Consultation',
        ),
        array(
            'key'               => 'field_au_hero_button_popup',
            'label'             => 'Hero Button Popup',
            'name'              => 'au_hero_button_popup',
            'type'              => 'select',
            'choices'           => array(
                ''             => 'None (use link above)',
                'service'      => 'Book a Service',
                'training'     => 'Request Training',
                'hr'           => 'Request HR Consultation',
                'consultation' => 'Request Free Consultation',
            ),
            'instructions'      => 'Select a popup to open when the hero button is clicked.',
        ),
        array(
            'key'           => 'field_au_hero_bg',
            'label'         => 'Hero Background Image',
            'name'          => 'au_hero_bg',
            'type'          => 'image',
            'return_format' => 'url',
            'preview_size'  => 'medium',
            'library'       => 'all',
        ),

        /* ===================== Intro (Where Meest) ===================== */
        array(
            'key'   => 'field_tab_intro',
            'label' => 'Intro Section',
            'type'  => 'tab',
        ),
        array(
            'key'           => 'field_au_intro_heading',
            'label'         => 'Heading',
            'name'          => 'au_intro_heading',
            'type'          => 'text',
            'default_value' => 'Global Insight Local Impact. Human-Centered Strategy.',
        ),
        array(
            'key'          => 'field_au_intro_paragraph',
            'label'        => 'Paragraph',
            'name'         => 'au_intro_paragraph',
            'type'         => 'wysiwyg',
            'toolbar'      => 'basic',
            'media_upload' => 0,
        ),

        /* ===================== Team ===================== */
        array(
            'key'   => 'field_tab_team',
            'label' => 'Team',
            'type'  => 'tab',
        ),
        array(
            'key'           => 'field_au_team_title',
            'label'         => 'Title',
            'name'          => 'au_team_title',
            'type'          => 'text',
            'default_value' => 'Our Team',
        ),
        array(
            'key'           => 'field_au_team_subtitle',
            'label'         => 'Subtitle',
            'name'          => 'au_team_subtitle',
            'type'          => 'text',
            'default_value' => 'Together, we lead Hufix with a vision of transformation rooted in both European rigor and GCC-specific insight.',
        ),
        array(
            'key'          => 'field_au_team_members',
            'label'        => 'Team Members',
            'name'         => 'au_team_members',
            'type'         => 'repeater',
            'layout'       => 'block',
            'button_label' => 'Add Member',
            'sub_fields'   => array(
                array(
                    'key'           => 'field_team_image',
                    'label'         => 'Image',
                    'name'          => 'image',
                    'type'          => 'image',
                    'return_format' => 'url',
                ),
                array(
                    'key'  => 'field_team_name',
                    'label'=> 'Name',
                    'name' => 'name',
                    'type' => 'text',
                ),
                array(
                    'key'          => 'field_team_bio',
                    'label'        => 'Bio',
                    'name'         => 'bio',
                    'type'         => 'wysiwyg',
                    'toolbar'      => 'basic',
                    'media_upload' => 0,
                ),
            ),
        ),

        /* ===================== Expertise ===================== */
        array(
            'key'   => 'field_tab_expertise',
            'label' => 'Expertise',
            'type'  => 'tab',
        ),
        array(
            'key'           => 'field_au_expertise_bg',
            'label'         => 'Background Image',
            'name'          => 'au_expertise_bg',
            'type'          => 'image',
            'return_format' => 'url',
        ),
        array(
            'key'           => 'field_au_expertise_label',
            'label'         => 'Label',
            'name'          => 'au_expertise_label',
            'type'          => 'text',
            'default_value' => 'Our Expertise network',
        ),
        array(
            'key'           => 'field_au_expertise_title',
            'label'         => 'Title',
            'name'          => 'au_expertise_title',
            'type'          => 'textarea',
            'rows'          => 2,
            'instructions'  => 'كل سطر (Enter) = سطر جديد بالـ <br>',
            'default_value' => "Elite Expertise Global Minds.\nRegional Relevance.",
        ),
        array(
            'key'          => 'field_au_expertise_desc',
            'label'        => 'Description',
            'name'         => 'au_expertise_desc',
            'type'         => 'wysiwyg',
            'toolbar'      => 'basic',
            'media_upload' => 0,
        ),
        array(
            'key'          => 'field_au_expertise_list',
            'label'        => 'List Items',
            'name'         => 'au_expertise_list',
            'type'         => 'repeater',
            'layout'       => 'table',
            'button_label' => 'Add Item',
            'sub_fields'   => array(
                array(
                    'key'   => 'field_expertise_item_text',
                    'label' => 'Text',
                    'name'  => 'text',
                    'type'  => 'text',
                ),
            ),
        ),

        /* ===================== Trust ===================== */
        array(
            'key'   => 'field_tab_trust',
            'label' => 'Trust',
            'type'  => 'tab',
        ),
        array(
            'key'           => 'field_au_trust_title',
            'label'         => 'Title',
            'name'          => 'au_trust_title',
            'type'          => 'text',
            'default_value' => 'Why Clients Trust Hufix?',
        ),
        array(
            'key'          => 'field_au_trust_desc',
            'label'        => 'Description',
            'name'         => 'au_trust_desc',
            'type'         => 'wysiwyg',
            'toolbar'      => 'basic',
            'media_upload' => 0,
        ),
        array(
            'key'          => 'field_au_trust_list',
            'label'        => 'List Items',
            'name'         => 'au_trust_list',
            'type'         => 'repeater',
            'layout'       => 'table',
            'button_label' => 'Add Item',
            'sub_fields'   => array(
                array(
                    'key'   => 'field_trust_item_text',
                    'label' => 'Text',
                    'name'  => 'text',
                    'type'  => 'text',
                ),
            ),
        ),
        array(
            'key'           => 'field_au_trust_button_text',
            'label'         => 'Button Text',
            'name'          => 'au_trust_button_text',
            'type'          => 'text',
            'default_value' => 'Request A Consultation',
        ),
        array(
            'key'   => 'field_au_trust_button_link',
            'label' => 'Button Link',
            'name'  => 'au_trust_button_link',
            'type'  => 'url',
        ),
        array(
            'key'           => 'field_au_trust_image',
            'label'         => 'Right Image',
            'name'          => 'au_trust_image',
            'type'          => 'image',
            'return_format' => 'url',
        ),

        /* ===================== Accreditation ===================== */
        array(
            'key'   => 'field_tab_accreditation',
            'label' => 'Accreditation',
            'type'  => 'tab',
        ),
        array(
            'key'           => 'field_au_acc_title',
            'label'         => 'Title',
            'name'          => 'au_acc_title',
            'type'          => 'text',
            'default_value' => 'Accreditation and certification',
        ),
        array(
            'key'          => 'field_au_acc_logos',
            'label'        => 'Logos',
            'name'         => 'au_acc_logos',
            'type'         => 'repeater',
            'layout'       => 'table',
            'button_label' => 'Add Logo',
            'sub_fields'   => array(
                array(
                    'key'           => 'field_acc_logo_image',
                    'label'         => 'Image',
                    'name'          => 'image',
                    'type'          => 'image',
                    'return_format' => 'url',
                ),
                array(
                    'key'   => 'field_acc_logo_alt',
                    'label' => 'Alt Text',
                    'name'  => 'alt',
                    'type'  => 'text',
                ),
            ),
        ),

        /* ===================== Core Focus Areas ===================== */
        array(
            'key'   => 'field_tab_focus',
            'label' => 'Core Focus Areas',
            'type'  => 'tab',
        ),
        array(
            'key'           => 'field_au_focus_title',
            'label'         => 'Title',
            'name'          => 'au_focus_title',
            'type'          => 'text',
            'default_value' => 'Core Focus Areas',
        ),
        array(
            'key'          => 'field_au_focus_items',
            'label'        => 'Items',
            'name'         => 'au_focus_items',
            'type'         => 'repeater',
            'layout'       => 'table',
            'button_label' => 'Add Item',
            'sub_fields'   => array(
                array(
                    'key'   => 'field_focus_badge_text',
                    'label' => 'Badge Text',
                    'name'  => 'badge_text',
                    'type'  => 'text',
                ),
                array(
                    'key'     => 'field_focus_badge_style',
                    'label'   => 'Badge Style',
                    'name'    => 'badge_style',
                    'type'    => 'select',
                    'choices' => array(
                        'badge--teal'  => 'Teal',
                        'badge--dark'  => 'Dark',
                        'badge--red'   => 'Red',
                        'badge--brown' => 'Brown',
                    ),
                ),
                array(
                    'key'   => 'field_focus_name',
                    'label' => 'Name',
                    'name'  => 'name',
                    'type'  => 'text',
                ),
            ),
        ),

        /* ===================== Testimonials ===================== */
        array(
            'key'   => 'field_tab_testimonials',
            'label' => 'Testimonials',
            'type'  => 'tab',
        ),
        array(
            'key'           => 'field_au_testi_title',
            'label'         => 'Title',
            'name'          => 'au_testi_title',
            'type'          => 'text',
            'default_value' => 'Clients Testimonials',
        ),
        array(
            'key'          => 'field_au_testimonials',
            'label'        => 'Testimonials',
            'name'         => 'au_testimonials',
            'type'         => 'repeater',
            'layout'       => 'block',
            'button_label' => 'Add Testimonial',
            'sub_fields'   => array(
                array(
                    'key'           => 'field_testi_image',
                    'label'         => 'Image',
                    'name'          => 'image',
                    'type'          => 'image',
                    'return_format' => 'url',
                ),
                array(
                    'key'  => 'field_testi_name',
                    'label'=> 'Name',
                    'name' => 'name',
                    'type' => 'text',
                ),
                array(
                    'key'          => 'field_testi_text',
                    'label'        => 'Text',
                    'name'         => 'text',
                    'type'         => 'wysiwyg',
                    'toolbar'      => 'basic',
                    'media_upload' => 0,
                ),
            ),
        ),

        /* ===================== Partners ===================== */
        array(
            'key'   => 'field_tab_partners',
            'label' => 'Partners',
            'type'  => 'tab',
        ),
        array(
            'key'           => 'field_au_partners_title',
            'label'         => 'Title',
            'name'          => 'au_partners_title',
            'type'          => 'text',
            'default_value' => 'Our Partners in Success',
        ),
        array(
            'key'           => 'field_au_partners_subtitle',
            'label'         => 'Subtitle',
            'name'          => 'au_partners_subtitle',
            'type'          => 'text',
            'default_value' => 'We work visionary organizations in both the public and private sectors ,including:',
        ),
        array(
            'key'          => 'field_au_partners_logos',
            'label'        => 'Logos',
            'name'         => 'au_partners_logos',
            'type'         => 'repeater',
            'layout'       => 'table',
            'button_label' => 'Add Logo',
            'sub_fields'   => array(
                array(
                    'key'           => 'field_partner_logo_image',
                    'label'         => 'Image',
                    'name'          => 'image',
                    'type'          => 'image',
                    'return_format' => 'url',
                ),
                array(
                    'key'   => 'field_partner_logo_alt',
                    'label' => 'Alt Text',
                    'name'  => 'alt',
                    'type'  => 'text',
                ),
            ),
        ),
        array(
            'key'          => 'field_au_partners_desc',
            'label'        => 'Description',
            'name'         => 'au_partners_desc',
            'type'         => 'wysiwyg',
            'toolbar'      => 'basic',
            'media_upload' => 0,
        ),

        /* ===================== DNA ===================== */
        array(
            'key'   => 'field_tab_dna',
            'label' => 'Hufix DNA',
            'type'  => 'tab',
        ),
        array(
            'key'           => 'field_au_dna_bg',
            'label'         => 'Background Image',
            'name'          => 'au_dna_bg',
            'type'          => 'image',
            'return_format' => 'url',
        ),
        array(
            'key'           => 'field_au_dna_title',
            'label'         => 'Title',
            'name'          => 'au_dna_title',
            'type'          => 'text',
            'default_value' => 'Hufix DNA',
        ),
        array(
            'key'          => 'field_au_dna_cards',
            'label'        => 'Cards',
            'name'         => 'au_dna_cards',
            'type'         => 'repeater',
            'layout'       => 'block',
            'button_label' => 'Add Card',
            'sub_fields'   => array(
                array(
                    'key'           => 'field_dna_icon',
                    'label'         => 'Icon',
                    'name'          => 'icon',
                    'type'          => 'image',
                    'return_format' => 'url',
                ),
                array(
                    'key'  => 'field_dna_title',
                    'label'=> 'Title',
                    'name' => 'title',
                    'type' => 'text',
                ),
                array(
                    'key'          => 'field_dna_text',
                    'label'        => 'Text',
                    'name'         => 'text',
                    'type'         => 'wysiwyg',
                    'toolbar'      => 'basic',
                    'media_upload' => 0,
                ),
            ),
        ),

    ),
    // الحقول دي تظهر بس جوه صفحة About Us (ID = 34)
    'location' => array(
        array(
            array(
                'param'    => 'page',
                'operator' => '==',
                'value'    => 34,
            ),
        ),
    ),
) );


/**
 * تعبئة البيانات الحالية مرة واحدة بس لو الصفحة لسه فاضية
 * (بنستخدم update_field بدل default_value عشان كده الطريقة
 * الآمنة اللي بتشتغل صح مع الريبيتر)
 */
function seed_default_about_us_page() {

    $page_id = 34;

    // لو فيه بيانات متسجلة قبل كده في الفريق مثلاً، متعملش حاجة خالص
    $existing = get_field( 'au_team_members', $page_id );
    if ( ! empty( $existing ) ) {
        return;
    }

    $theme_uri = get_template_directory_uri();

    // Team
    update_field( 'au_team_members', array(
        array( 'image' => $theme_uri . '/assets/images/team1.jpg', 'name' => 'Tim Altaweel', 'bio' => '<p>15+ years of experience in strategic management, HR development, and organizational transformation across Europe and the Middle East.</p><p>Certified Agile Coach (USA) and Certified Trainer (European Accreditation Center, NL).</p><p>Specializes in talent retention, cultural adaptation, and operational efficiency for public and private sectors.</p>' ),
        array( 'image' => $theme_uri . '/assets/images/team2.png', 'name' => 'Rania Aloulabi', 'bio' => '<p>15+ years of experience in strategic management, HR development, and organizational transformation across Europe and the Middle East.</p><p>Certified Agile Coach (USA) and Certified Trainer (European Accreditation Center, NL).</p><p>Specializes in talent retention, cultural adaptation, and operational efficiency for public and private sectors.</p>' ),
        array( 'image' => $theme_uri . '/assets/images/team1.jpg', 'name' => 'Rania Aloulabi', 'bio' => '<p>15+ years of experience in strategic management, HR development, and organizational transformation across Europe and the Middle East.</p><p>Certified Agile Coach (USA) and Certified Trainer (European Accreditation Center, NL).</p><p>Specializes in talent retention, cultural adaptation, and operational efficiency for public and private sectors.</p>' ),
        array( 'image' => $theme_uri . '/assets/images/team2.png', 'name' => 'Member Name', 'bio' => '<p>15+ years of experience in strategic management, HR development, and organizational transformation across Europe and the Middle East.</p><p>Certified Agile Coach (USA) and Certified Trainer (European Accreditation Center, NL).</p><p>Specializes in talent retention, cultural adaptation, and operational efficiency for public and private sectors.</p>' ),
        array( 'image' => $theme_uri . '/assets/images/team1.jpg', 'name' => 'Member Name', 'bio' => '<p>15+ years of experience in strategic management, HR development, and organizational transformation across Europe and the Middle East.</p><p>Certified Agile Coach (USA) and Certified Trainer (European Accreditation Center, NL).</p><p>Specializes in talent retention, cultural adaptation, and operational efficiency for public and private sectors.</p>' ),
    ), $page_id );

    // Intro paragraph (wysiwyg)
    update_field( 'au_intro_paragraph', '<p>We are Hufix, a European-MENA elite consulting firm, founded in the Netherlands and active across the Gulf region, Europe, and beyond.</p><p>With a strong foundation in European precision and a deep understanding of Gulf business culture, we help institutions build stronger organizations through strategic consulting, HR transformation, and executive education — all aligned with Vision 2030.</p><p>Our Mission : is to connect global expertise with regional relevance, delivering measurable impact and culturally adaptive execution.</p>', $page_id );

    // Expertise
    update_field( 'au_expertise_desc', '<p>Our certified consultants come from Europe, the United States, and the GCC, delivering executive-level programs built for impact. We choose consultants not just for their credentials — but for their ability to connect, inspire, and adapt across diverse environments. Whether we train executives in Riyadh, Amsterdam, or New York, our delivery bridges the gap between global standards and local understanding.</p><p>We deliver specialized programs in:</p>', $page_id );

    update_field( 'au_expertise_list', array(
        array( 'text' => 'Leadership development in the Gulf and Europe' ),
        array( 'text' => 'HR transformation initiatives in Saudi Arabia, UAE, and the EU' ),
        array( 'text' => 'Agile coaching, strategic execution, finance, and sustainability' ),
        array( 'text' => 'Interpersonal skills & change leadership for multicultural teams' ),
    ), $page_id );

    // Trust
    update_field( 'au_trust_desc', "<p>With Hufix, you're in trusted hands. They bring clarity, professionalism, and cross-cultural insight to every step of the journey.</p>", $page_id );

    update_field( 'au_trust_list', array(
        array( 'text' => 'Deep cultural intelligence in GCC business dynamics' ),
        array( 'text' => 'European-grade methodology and execution' ),
        array( 'text' => 'Certified by the European Accreditation Center' ),
        array( 'text' => 'Transparent processes, measurable results, and lasting ROI' ),
        array( 'text' => 'Long-term partnership approach — not transactional' ),
    ), $page_id );

    update_field( 'au_trust_image', $theme_uri . '/assets/images/learn.png', $page_id );

    // Accreditation
    update_field( 'au_acc_logos', array(
        array( 'image' => $theme_uri . '/assets/images/cer_1.png', 'alt' => 'ICP-ACC Certified Professional' ),
        array( 'image' => $theme_uri . '/assets/images/cer_2.png', 'alt' => 'European Accreditation Center' ),
    ), $page_id );

    // Focus
    update_field( 'au_focus_items', array(
        array( 'badge_text' => 'LATEST TREND', 'badge_style' => 'badge--teal', 'name' => 'Digital Leadership' ),
        array( 'badge_text' => 'NEWS COURSES', 'badge_style' => 'badge--dark', 'name' => 'ESG Fundamentals' ),
        array( 'badge_text' => 'LIMITED TIME ONLY', 'badge_style' => 'badge--red', 'name' => 'Strategy Masterclass' ),
        array( 'badge_text' => 'BEST SELLER', 'badge_style' => 'badge--brown', 'name' => 'HR Transformation' ),
    ), $page_id );

    // Testimonials
    update_field( 'au_testimonials', array(
        array( 'image' => $theme_uri . '/assets/images/Testmontional/1.jpg', 'name' => 'James Pattinson', 'text' => '<p>Lobortis leo pretium facilisis amet nisl at nec. Scelerisque risus tortor donec ipsum consequat semper consequat adipiscing ultrices.</p>' ),
        array( 'image' => $theme_uri . '/assets/images/Testmontional/2.jpg', 'name' => 'Greg Stuart', 'text' => '<p>Vestibulum, cum nam non amet consectetur morbi aenean condimentum eget. Ultrices integer nunc neque accumsan laoreet. Viverra nibh ultrices.</p>' ),
        array( 'image' => $theme_uri . '/assets/images/Testmontional/3.jpg', 'name' => 'Trevor Mitchell', 'text' => '<p>Ut tristique viverra sed porttitor senectus. A facilisis metus pretium ut habitant lorem. Velit vel bibendum eget aliquet sem nec, id sed. Tincidunt.</p>' ),
        array( 'image' => $theme_uri . '/assets/images/Testmontional/2.jpg', 'name' => 'Sarah Johnson', 'text' => '<p>Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum consectetur adipiscing.</p>' ),
        array( 'image' => $theme_uri . '/assets/images/Testmontional/1.jpg', 'name' => 'Michael Reed', 'text' => '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua ut enim.</p>' ),
    ), $page_id );

    // Partners
    $partner_rows = array();
    for ( $i = 1; $i <= 8; $i++ ) {
        $partner_rows[] = array(
            'image' => $theme_uri . '/assets/images/client_logo.png',
            'alt'   => 'Partner ' . $i,
        );
    }
    update_field( 'au_partners_logos', $partner_rows, $page_id );

    update_field( 'au_partners_desc', '<p>Our clients range from government bodies and semi-government entities to private sector innovators — all committed to growth, transformation, and excellence.</p>', $page_id );

    // DNA
    update_field( 'au_dna_cards', array(
        array( 'icon' => $theme_uri . '/assets/images/Vector.png', 'title' => 'Boutique Excellence', 'text' => '<p>We work with a carefully selected number of clients and treat every engagement as a crafted piece of work – precise, discreet, and delivered to the highest standards.</p>' ),
        array( 'icon' => $theme_uri . '/assets/images/Vector-1.png', 'title' => 'Client-First Partnerships', 'text' => "<p>We measure our success by our clients' success. Every project is built around your context, your challenges, and long-term relationships, not one-off assignments.</p>" ),
        array( 'icon' => $theme_uri . '/assets/images/Vector-2.png', 'title' => 'Cross-Cultural Insight', 'text' => '<p>We blend European rigor with deep Middle Eastern and Gulf regional understanding, giving you solutions that are globally informed and locally executable.</p>' ),
        array( 'icon' => $theme_uri . '/assets/images/Vector-3.png', 'title' => 'Integrity & Trust', 'text' => '<p>We say what we mean and do what we promise. You get honest advice, transparent communication, and a partner you can rely on in sensitive, high-stakes situations.</p>' ),
        array( 'icon' => $theme_uri . '/assets/images/Vector-4.png', 'title' => 'Sustainable Impact', 'text' => '<p>We focus on people, performance, and long-term value – helping you build capabilities, not dependency, and aligning with modern sustainability and ESG expectations.</p>' ),
    ), $page_id );
}
add_action( 'acf/init', 'seed_default_about_us_page' );