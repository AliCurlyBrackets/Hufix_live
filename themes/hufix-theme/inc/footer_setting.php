<?php
/**
 * Backend: Footer Setting (كامل)
 * -----------------------------------------------------
 * ضيف الكود ده كله في functions.php بدل الكود القديم بتاع
 * الفوتر (Footer Setting) اللي عندك - ده نفسه بالظبط + إضافة
 * جزء التحكم في الـ CTA لكل صفحة لوحدها.
 *
 * القسم 1: تسجيل أماكن قوائم الفوتر (زي ما هو)
 * القسم 2: صفحة أوبشنز "Footer Setting" (زي ما هي)
 * القسم 3: حقول الأوبشنز العامة (زي ما هي)
 * القسم 4: تعبئة البيانات الافتراضية أول مرة (زي ما هي)
 * القسم 5: [جديد] حقول التحكم في الـ CTA لكل صفحة لوحدها
 * -----------------------------------------------------
 */


/* =====================================================
 * القسم 1: تسجيل أماكن القوائم (Menu Locations) لأول عمودين في الفوتر
 * ===================================================== */
function register_footer_menus() {
    register_nav_menus( array(
        'footer-menu-1' => __( 'Footer 1 Menu', 'mytheme' ),
        'footer-menu-2' => __( 'Footer 2 Menu', 'mytheme' ),
    ) );
}
add_action( 'after_setup_theme', 'register_footer_menus' );


/* =====================================================
 * القسم 2: صفحة أوبشنز في الأدمن اسمها "Footer Setting"
 * ===================================================== */
if ( function_exists( 'acf_add_options_page' ) ) {
    acf_add_options_page( array(
        'page_title' => 'Footer Setting',
        'menu_title' => 'Footer Setting',
        'menu_slug'  => 'footer-setting-options',
        'capability' => 'edit_posts',
        'icon_url'   => 'dashicons-align-center',
        'position'   => 31,
        'redirect'   => false,
    ) );
}


/* =====================================================
 * القسم 3: حقول الفوتر العامة (Footer Setting)
 * ===================================================== */
if ( function_exists( 'acf_add_local_field_group' ) ) {
    acf_add_local_field_group( array(
        'key'    => 'group_footer_setting',
        'title'  => 'Footer Fields',
        'fields' => array(

            /* ===================== CTA Card ===================== */
            array(
                'key'   => 'field_tab_footer_cta',
                'label' => 'CTA Card',
                'type'  => 'tab',
            ),
            array(
                'key'           => 'field_footer_cta_icon',
                'label'         => 'Icon',
                'name'          => 'footer_cta_icon',
                'type'          => 'image',
                'return_format' => 'url',
                'instructions'  => 'الأيقونة دي عامة لكل الصفحات، حتى لو الصفحة عاملة Override للعنوان/النص/الأزرار.',
            ),
            array(
                'key'           => 'field_footer_cta_title',
                'label'         => 'Title',
                'name'          => 'footer_cta_title',
                'type'          => 'text',
                'default_value' => 'Ready to lead with confidence?',
                'instructions'  => 'ده العنوان الافتراضي لأي صفحة معملتش Override خاص بيها (من صفحة تحرير الصفحة نفسها).',
            ),
            array(
                'key'           => 'field_footer_cta_text',
                'label'         => 'Text',
                'name'          => 'footer_cta_text',
                'type'          => 'text',
                'default_value' => "Let's design the future — together.",
            ),
            array(
                'key'          => 'field_footer_cta_buttons',
                'label'        => 'Buttons',
                'name'         => 'footer_cta_buttons',
                'type'         => 'repeater',
                'layout'       => 'table',
                'button_label' => 'Add Button',
                'sub_fields'   => array(
                    array(
                        'key'   => 'field_cta_btn_text',
                        'label' => 'Text',
                        'name'  => 'text',
                        'type'  => 'text',
                    ),
                    array(
                        'key'   => 'field_cta_btn_link',
                        'label' => 'Link',
                        'name'  => 'link',
                        'type'  => 'url',
                    ),
                    array(
                        'key'     => 'field_cta_btn_style',
                        'label'   => 'Style',
                        'name'    => 'style',
                        'type'    => 'select',
                        'choices' => array(
                            'filled'  => 'Filled',
                            'outline' => 'Outline',
                        ),
                        'default_value' => 'outline',
                    ),
                    array(
                        'key'          => 'field_cta_btn_id',
                        'label'        => 'Element ID (optional)',
                        'name'         => 'element_id',
                        'type'         => 'text',
                        'instructions' => 'مثال: openTrainingPopup لو الزرار ده بيفتح بوب أب معين',
                    ),
                ),
            ),

            /* ===================== Footer Columns Titles ===================== */
            array(
                'key'   => 'field_tab_footer_columns',
                'label' => 'Columns Titles',
                'type'  => 'tab',
            ),
            array(
                'key'           => 'field_footer_col1_title',
                'label'         => 'Column 1 Title',
                'name'          => 'footer_col1_title',
                'type'          => 'text',
                'default_value' => 'HUFIX',
                'instructions'  => 'محتوى الروابط بتاعتها بييجي من Appearance > Menus > Footer 1 Menu',
            ),
            array(
                'key'           => 'field_footer_col2_title',
                'label'         => 'Column 2 Title',
                'name'          => 'footer_col2_title',
                'type'          => 'text',
                'default_value' => 'SUPPORT',
                'instructions'  => 'محتوى الروابط بتاعتها بييجي من Appearance > Menus > Footer 2 Menu',
            ),

            /* ===================== Contact Info ===================== */
            array(
                'key'   => 'field_tab_footer_contact',
                'label' => 'Contact Info',
                'type'  => 'tab',
            ),
            array(
                'key'           => 'field_footer_contact_title',
                'label'         => 'Column Title',
                'name'          => 'footer_contact_title',
                'type'          => 'text',
                'default_value' => 'CONTACT US',
            ),
            array(
                'key'           => 'field_footer_address',
                'label'         => 'Address',
                'name'          => 'footer_address',
                'type'          => 'text',
                'default_value' => '123 Innovation Way, Tech District, Netherlands',
            ),
            array(
                'key'           => 'field_footer_email',
                'label'         => 'Email',
                'name'          => 'footer_email',
                'type'          => 'email',
                'default_value' => 'info@hufix.com',
            ),
            array(
                'key'           => 'field_footer_phone',
                'label'         => 'Phone',
                'name'          => 'footer_phone',
                'type'          => 'text',
                'default_value' => '+31 (0) 123 456 789',
            ),
            array(
                'key'          => 'field_footer_whatsapp_enabled',
                'label'        => 'Show WhatsApp Button',
                'name'         => 'footer_whatsapp_enabled',
                'type'         => 'true_false',
                'ui'           => 1,
                'default_value'=> 0,
            ),
            array(
                'key'           => 'field_footer_whatsapp_link',
                'label'         => 'WhatsApp Link',
                'name'          => 'footer_whatsapp_link',
                'type'          => 'url',
                'conditional_logic' => array(
                    array(
                        array(
                            'field'    => 'field_footer_whatsapp_enabled',
                            'operator' => '==',
                            'value'    => '1',
                        ),
                    ),
                ),
            ),

            /* ===================== Payment ===================== */
            array(
                'key'   => 'field_tab_footer_payment',
                'label' => 'Payment',
                'type'  => 'tab',
            ),
            array(
                'key'           => 'field_footer_payment_title',
                'label'         => 'Column Title',
                'name'          => 'footer_payment_title',
                'type'          => 'text',
                'default_value' => 'PAYMENT',
            ),
            array(
                'key'          => 'field_footer_payment_badges',
                'label'        => 'Badges',
                'name'         => 'footer_payment_badges',
                'type'         => 'repeater',
                'layout'       => 'table',
                'button_label' => 'Add Badge',
                'sub_fields'   => array(
                    array(
                        'key'   => 'field_payment_badge_text',
                        'label' => 'Text',
                        'name'  => 'text',
                        'type'  => 'text',
                    ),
                ),
            ),

            /* ===================== Bottom Bar ===================== */
            array(
                'key'   => 'field_tab_footer_bottom',
                'label' => 'Bottom Bar',
                'type'  => 'tab',
            ),
            array(
                'key'           => 'field_footer_copyright',
                'label'         => 'Copyright Text',
                'name'          => 'footer_copyright',
                'type'          => 'text',
                'default_value' => '© 2026 Hufix . All rights reserved. Website designed by Sireen Shway.',
            ),
            array(
                'key'          => 'field_footer_socials',
                'label'        => 'Social Links',
                'name'         => 'footer_socials',
                'type'         => 'repeater',
                'layout'       => 'table',
                'button_label' => 'Add Social',
                'sub_fields'   => array(
                    array(
                        'key'           => 'field_social_icon',
                        'label'         => 'Icon',
                        'name'          => 'icon',
                        'type'          => 'image',
                        'return_format' => 'url',
                    ),
                    array(
                        'key'   => 'field_social_link',
                        'label' => 'Link',
                        'name'  => 'link',
                        'type'  => 'url',
                    ),
                ),
            ),

        ),
        'location' => array(
            array(
                array(
                    'param'    => 'options_page',
                    'operator' => '==',
                    'value'    => 'footer-setting-options',
                ),
            ),
        ),
    ) );
}


/* =====================================================
 * القسم 4: تعبئة البيانات الحالية مرة واحدة بس لو الأوبشنز لسه فاضية
 * ===================================================== */
function seed_default_footer_setting() {

    $existing = get_field( 'footer_cta_buttons', 'option' );
    if ( ! empty( $existing ) ) {
        return;
    }

    $theme_uri = get_template_directory_uri();

    update_field( 'footer_cta_icon', $theme_uri . '/assets/images/footer/Icon.png', 'option' );

    update_field( 'footer_cta_buttons', array(
        array( 'text' => 'Book a Consultation', 'link' => '#', 'style' => 'filled', 'element_id' => '' ),
        array( 'text' => 'Request Custom Training', 'link' => '#', 'style' => 'outline', 'element_id' => 'openTrainingPopup' ),
        array( 'text' => 'Partner With Us', 'link' => '#', 'style' => 'outline', 'element_id' => '' ),
    ), 'option' );

    update_field( 'footer_payment_badges', array(
        array( 'text' => 'VISA' ),
        array( 'text' => 'MASTERCARD' ),
        array( 'text' => 'IDEAL' ),
        array( 'text' => 'PAYPAL' ),
        array( 'text' => 'KLARNA' ),
    ), 'option' );

    update_field( 'footer_socials', array(
        array( 'icon' => $theme_uri . '/assets/images/footer/LinkedIn.png', 'link' => '#' ),
        array( 'icon' => $theme_uri . '/assets/images/footer/SVG (4).png', 'link' => '#' ),
        array( 'icon' => $theme_uri . '/assets/images/footer/Link - Instagram.png', 'link' => '#' ),
    ), 'option' );
}
add_action( 'acf/init', 'seed_default_footer_setting' );


/* =====================================================
 * القسم 5: [جديد] تحكم في الـ CTA لكل صفحة لوحدها
 * -----------------------------------------------------
 * بيظهر Metabox في صفحة تحرير أي Page اسمه
 * "Footer CTA (This Page)" فيه زرار Override:
 * لو مقفول -> الصفحة تاخد من "Footer Setting" العامة فوق.
 * لو مفتوح -> عنوان/نص/أزرار خاصة بالصفحة دي بس، وكل زرار
 * تختار له Action: Link (رابط/تحميل ملف) أو Popup (بوب أب).
 * ===================================================== */
if ( function_exists( 'acf_add_local_field_group' ) ) {
    acf_add_local_field_group( array(
        'key'      => 'group_page_cta_override',
        'title'    => 'Footer CTA (This Page)',
        'fields'   => array(

            array(
                'key'           => 'field_page_cta_override',
                'label'         => 'Override Global CTA',
                'name'          => 'page_cta_override',
                'type'          => 'true_false',
                'ui'            => 1,
                'default_value' => 0,
                'instructions'  => 'فعّل ده لو عايز كارت الـ CTA في الصفحة دي يبقى مختلف عن العام (Footer Setting). لو مقفول، الصفحة هتاخد نفس العنوان/النص/الأزرار العامة زي كل الصفحات.',
            ),

            array(
                'key'               => 'field_page_cta_title',
                'label'             => 'Title',
                'name'              => 'page_cta_title',
                'type'              => 'text',
                'conditional_logic' => array( array( array( 'field' => 'field_page_cta_override', 'operator' => '==', 'value' => '1' ) ) ),
            ),
            array(
                'key'               => 'field_page_cta_text',
                'label'             => 'Text',
                'name'              => 'page_cta_text',
                'type'              => 'text',
                'conditional_logic' => array( array( array( 'field' => 'field_page_cta_override', 'operator' => '==', 'value' => '1' ) ) ),
            ),

            array(
                'key'               => 'field_page_cta_buttons',
                'label'             => 'Buttons',
                'name'              => 'page_cta_buttons',
                'type'              => 'repeater',
                'layout'            => 'table',
                'button_label'      => 'Add Button',
                'conditional_logic' => array( array( array( 'field' => 'field_page_cta_override', 'operator' => '==', 'value' => '1' ) ) ),
                'sub_fields'        => array(

                    array(
                        'key'   => 'field_page_btn_text',
                        'label' => 'Button Text',
                        'name'  => 'text',
                        'type'  => 'text',
                    ),

                    array(
                        'key'           => 'field_page_btn_action',
                        'label'         => 'What does it do?',
                        'name'          => 'action',
                        'type'          => 'select',
                        'choices'       => array(
                            'link'  => 'Go to a Link (URL / file download)',
                            'popup' => 'Open a Popup Form',
                        ),
                        'default_value' => 'link',
                    ),

                    array(
                        'key'               => 'field_page_btn_link',
                        'label'             => 'Link (URL or file)',
                        'name'              => 'link',
                        'type'              => 'url',
                        'conditional_logic' => array( array( array( 'field' => 'field_page_btn_action', 'operator' => '==', 'value' => 'link' ) ) ),
                    ),

                    // الاختيارات دي مبنية على النماذج الظاهرة في ملف Buttons.docx
                    // (Book a Service, Request Training, HR Consultation, Free Consultation)
                    // لو ضفت بوب أب جديد بعدين، زوّد اختيار جديد هنا واعمل نفس النمط
                    // في الفرونت إند (data-popup-id).
                    array(
                        'key'               => 'field_page_btn_popup',
                        'label'             => 'Which Popup?',
                        'name'              => 'popup',
                        'type'              => 'select',
                        'choices'           => array(
                            'service'      => 'Book a Service',
                            'training'     => 'Request Training',
                            'hr'           => 'Request HR Consultation',
                            'consultation' => 'Request Free Consultation',
                        ),
                        'conditional_logic' => array( array( array( 'field' => 'field_page_btn_action', 'operator' => '==', 'value' => 'popup' ) ) ),
                    ),

                    array(
                        'key'           => 'field_page_btn_style',
                        'label'         => 'Style',
                        'name'          => 'style',
                        'type'          => 'select',
                        'choices'       => array(
                            'filled'  => 'Filled',
                            'outline' => 'Outline',
                        ),
                        'default_value' => 'outline',
                    ),
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param'    => 'post_type',
                    'operator' => '==',
                    'value'    => 'page',
                ),
            ),
        ),
        'position' => 'normal',
    ) );
}