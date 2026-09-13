<?php

/**
 * ============================================================
 * Backend - Contact Us Page (Page ID: 1643)
 * ضيف الكود ده في functions.php بتاعت الثيم، أو في ملف مستقل
 * وعمل له include من جوه functions.php
 * ============================================================
 */

if (! defined('ABSPATH')) {
    exit; // منع الوصول المباشر
}

/**
 * 1) تسجيل قالب الصفحة (Page Template) تلقائيًا لصفحة الـ Contact
 *    مش هتحتاج تختار التمبلت يدوي من صفحة تعديل الصفحة،
 *    الكود هيفرضه تلقائي على الصفحة اللي ID بتاعها 1643
 */
add_filter('template_include', function ($template) {

    if (is_page(1643)) {
        $custom_template = locate_template('page-contact.php');
        if ($custom_template) {
            return $custom_template;
        }
    }

    return $template;
});

/**
 * (اختياري) لو حبيت تخلي التمبلت يظهر كمان في قايمة
 * "Page Attributes -> Template" عشان تقدر تختاره يدوي على أي صفحة تانية
 * تأكد إن أول سطر في ملف page-contact.php فيه:
 * /* Template Name: Contact Us Page * /
 */


/**
 * 2) تسجيل حقول ACF بتاعة صفحة الـ Contact
 *    هتظهر تلقائي بس على الصفحة ID = 1643
 */
add_action('acf/init', function () {

    if (! function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group(array(
        'key'    => 'group_contact_page_1643',
        'title'  => 'بيانات صفحة اتصل بنا',
        'fields' => array(

            // ============ Hero Section ============
            array(
                'key'   => 'field_hero_tab',
                'label' => 'Hero Section',
                'type'  => 'tab',
            ),
            array(
                'key'     => 'field_hero_bg',
                'label'   => 'خلفية الهيرو / صورة عنوان الصفحة',
                'name'    => 'hero_background',
                'type'    => 'image',
                'return_format' => 'url',
                'preview_size'  => 'medium',
            ),
            array(
                'key'   => 'field_hero_title',
                'label' => 'عنوان الهيرو',
                'name'  => 'hero_title',
                'type'  => 'text',
                'default_value' => 'We\'re Here for You',
            ),
            array(
                'key'   => 'field_hero_btn_text',
                'label' => 'نص زرار الهيرو',
                'name'  => 'hero_button_text',
                'type'  => 'text',
                'default_value' => 'Start Your Journey',
            ),
            array(
                'key'   => 'field_hero_btn_url',
                'label' => 'رابط زرار الهيرو',
                'name'  => 'hero_button_url',
                'type'  => 'url',
            ),
            array(
                'key'               => 'field_hero_btn_popup',
                'label'             => 'Hero Button Popup',
                'name'              => 'hero_button_popup',
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

            // ============ Banner Section ============
            array(
                'key'   => 'field_banner_tab',
                'label' => 'Banner Section',
                'type'  => 'tab',
            ),
            array(
                'key'   => 'field_banner_title',
                'label' => 'عنوان البانر',
                'name'  => 'banner_title',
                'type'  => 'text',
                'default_value' => 'Let\'s Start a Conversation',
            ),
            array(
                'key'   => 'field_banner_text',
                'label' => 'نص البانر',
                'name'  => 'banner_text',
                'type'  => 'textarea',
                'rows'  => 4,
            ),

            // ============ Contact Info Section ============
            array(
                'key'   => 'field_info_tab',
                'label' => 'Contact Info Section',
                'type'  => 'tab',
            ),
            array(
                'key'   => 'field_contact_heading',
                'label' => 'عنوان قسم البيانات (Let\'s Talk)',
                'name'  => 'contact_heading',
                'type'  => 'text',
                'default_value' => "Let's Talk",
            ),
            array(
                'key'          => 'field_contact_items',
                'label'        => 'بيانات التواصل (الأيقونة هتفضل ثابتة زي ما هي بالترتيب)',
                'name'         => 'contact_items',
                'type'         => 'repeater',
                'min'          => 0,
                'max'          => 3, // بحسب عدد الأيقونات الثابتة (Mail - Phone - Location)
                'layout'       => 'block',
                'button_label' => 'إضافة سطر بيانات',
                'sub_fields'   => array(
                    array(
                        'key'   => 'field_item_label',
                        'label' => 'العنوان (Label)',
                        'name'  => 'label',
                        'type'  => 'text',
                    ),
                    array(
                        'key'   => 'field_item_value',
                        'label' => 'القيمة (Email / Phone)',
                        'name'  => 'value',
                        'type'  => 'text',
                    ),
                ),
            ),

            // ============ Form Card (نص العنوان بس، الفورم نفسه ثابت) ============
            array(
                'key'   => 'field_form_tab',
                'label' => 'Form Card',
                'type'  => 'tab',
            ),
            array(
                'key'   => 'field_form_title',
                'label' => 'عنوان كارت الفورم',
                'name'  => 'contact_form_title',
                'type'  => 'text',
                'default_value' => 'Prefer to message us?',
            ),
        ),

        // الحقول دي هتظهر بس على الصفحة اللي ID بتاعها 1643
        'location' => array(
            array(
                array(
                    'param'    => 'page',
                    'operator' => '==',
                    'value'    => '1643',
                ),
            ),
        ),
    ));
});
