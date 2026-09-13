<?php
/**
 * Backend: Services (Repeater)
 * -----------------------------------------------------
 * محتاج بلجن ACF PRO عشان حقل الـ Repeater
 * (النسخة الفري مفيهاش ريبيتر)
 * -----------------------------------------------------
 */

// 1) صفحة أوبشنز في الأدمن اسمها "Services"
if ( function_exists( 'acf_add_options_page' ) ) {
    acf_add_options_page( array(
        'page_title' => 'Services',
        'menu_title' => 'Services',
        'menu_slug'  => 'services-options',
        'capability' => 'edit_posts',
        'icon_url'   => 'dashicons-portfolio',
        'position'   => 27,
        'redirect'   => false,
    ) );
}

// 2) حقل الريبيتر وكل الحقول الفرعية جوه الكارد
if ( function_exists( 'acf_add_local_field_group' ) ) {
    acf_add_local_field_group( array(
        'key'    => 'group_services_repeater',
        'title'  => 'Services Fields',
        'fields' => array(

            array(
                'key'          => 'field_services_repeater',
                'label'        => 'Service Cards',
                'name'         => 'services_repeater',
                'type'         => 'repeater',
                'layout'       => 'block',
                'button_label' => 'Add Service',
                'sub_fields'   => array(

                    array(
                        'key'   => 'field_service_title',
                        'label' => 'Title',
                        'name'  => 'title',
                        'type'  => 'text',
                    ),

                    array(
                        'key'           => 'field_service_image',
                        'label'         => 'Image',
                        'name'          => 'image',
                        'type'          => 'image',
                        'return_format' => 'url',
                        'preview_size'  => 'medium',
                        'library'       => 'all',
                    ),

                    array(
                        'key'   => 'field_service_description',
                        'label' => 'Description',
                        'name'  => 'description',
                        'type'  => 'textarea',
                        'rows'  => 3,
                    ),

                    array(
                        'key'   => 'field_service_button_text',
                        'label' => 'Button Text',
                        'name'  => 'button_text',
                        'type'  => 'text',
                    ),

                    array(
                        'key'   => 'field_service_button_link',
                        'label' => 'Button Link',
                        'name'  => 'button_link',
                        'type'  => 'url',
                    ),

                    array(
                        'key'          => 'field_service_wide',
                        'label'        => 'Wide Card',
                        'name'         => 'wide',
                        'type'         => 'true_false',
                        'ui'           => 1,
                        'instructions' => 'فعّل الخيار ده عشان الكارد ياخد عرض أكبر في الصف',
                    ),

                ),
            ),

        ),
        'location' => array(
            array(
                array(
                    'param'    => 'options_page',
                    'operator' => '==',
                    'value'    => 'services-options',
                ),
            ),
        ),
    ) );
}


// 3) تعبئة الخدمات الـ 5 الموجودة حاليًا تلقائيًا أول مرة بس (لو الحقل لسه فاضي)
function seed_default_services() {

    // لو فيه بيانات متسجلة قبل كده، متعملش حاجة
    $existing = get_field( 'services_repeater', 'option' );
    if ( ! empty( $existing ) ) {
        return;
    }

    $theme_uri = get_template_directory_uri();

    $default_services = array(
        array(
            'title'       => 'Strategic Business Consulting',
            'image'       => $theme_uri . '/assets/images/1.png',
            'description' => "Unlock growth with data-driven planning, transformation support, and change leadership tailored to your market and culture.",
            'button_text' => 'Explore Strategy',
            'button_link' => '#',
            'wide'        => 0,
        ),
        array(
            'title'       => 'Executive Training Programs',
            'image'       => $theme_uri . '/assets/images/2.png',
            'description' => "Elite-level programs in strategy, leadership, finance, governance, and sustainability designed for decision-makers.",
            'button_text' => 'View Curriculum',
            'button_link' => '#',
            'wide'        => 0,
        ),
        array(
            'title'       => 'Targeted Recruitment Solutions',
            'image'       => $theme_uri . '/assets/images/3.png',
            'description' => "We don't fill roles — we build teams. From executive search to behavioral evaluation, we match talent to your mission.",
            'button_text' => 'Submit Search',
            'button_link' => '#',
            'wide'        => 0,
        ),
        array(
            'title'       => 'Business Establishment Services',
            'image'       => $theme_uri . '/assets/images/4.png',
            'description' => "Launching in the GCC? We handle all your HR and admin onboarding, contracts, policies, and compliance systems.",
            'button_text' => 'Market Entry Solutions',
            'button_link' => '#',
            'wide'        => 1,
        ),
        array(
            'title'       => 'HR Outsourcing & Advisory',
            'image'       => $theme_uri . '/assets/images/5.png',
            'description' => "Delegate HR complexities. Focus on business growth. We offer payroll, compliance, performance frameworks, and more.",
            'button_text' => 'Management Solutions',
            'button_link' => '#',
            'wide'        => 0,
        ),
    );

    update_field( 'services_repeater', $default_services, 'option' );
}
add_action( 'acf/init', 'seed_default_services' );