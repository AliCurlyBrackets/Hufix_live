<?php

/**
 * Template Name: Contact Us Page
 * ============================================================
 * Front-end - Contact Us Page (Page ID: 1643)
 * حط الملف ده في روت الثيم مباشرة (نفس مكان header.php)
 * ============================================================
 */

get_header();

// ============ Hero Section ============
$hero_bg          = get_field('hero_background');
$hero_title       = get_field('hero_title') ?: "We're Here for You";
$hero_button_text = get_field('hero_button_text') ?: 'Start Your Journey';
$hero_button_url  = get_field('hero_button_url') ?: '#';
$hero_button_popup = get_field('hero_button_popup');

// ============ Banner Section ============
$banner_title = get_field('banner_title') ?: "Let's Start a Conversation";
$banner_text  = get_field('banner_text') ?: "Whether you're exploring a tailored training program, looking for trusted HR support, or simply want to understand how we work — we're here to guide you every step of the way.\nAt Hufix, you're never just sending a message — you're opening the door to a partnership.";

// ============ Contact Info Section ============
$contact_heading = get_field('contact_heading') ?: "Let's Talk";
$contact_items   = get_field('contact_items');

// أيقونات ثابتة زي ما هي - بالترتيب (Mail - Phone - Location)
$static_icons = array(
    array(
        'src' => get_template_directory_uri() . '/assets/images/Contact/mail.png',
        'alt' => 'email',
    ),
    array(
        'src' => get_template_directory_uri() . '/assets/images/Contact/phone.png',
        'alt' => 'phone',
    ),
    array(
        'src' => get_template_directory_uri() . '/assets/images/Contact/location.png',
        'alt' => 'globe',
    ),
);

// لو مفيش بيانات متضافة من ACF، استخدم الداتا الافتراضية الأصلية
if ( empty( $contact_items ) ) {
    $is_ar = (strpos($_SERVER['REQUEST_URI'], '/ar') !== false);

    $contact_items = $is_ar
        ? array(
            array( 'label' => 'راسلنا عبر البريد الإلكتروني', 'value' => 'info@hufix.eu' ),
            array( 'label' => 'منطقة الخليج (اتصال/واتساب)', 'value' => '+966 (xxx) xxx xxx' ),
            array( 'label' => 'أوروبا (اتصال/واتساب)', 'value' => '+31 (xxx) xxx xxx' ),
        )
        : array(
            array( 'label' => 'EMAIL US', 'value' => 'info@hufix.eu' ),
            array( 'label' => 'GULF REGION (CALL/WHATSAPP)', 'value' => '+966 (xxx) xxx xxx' ),
            array( 'label' => 'EUROPE (CALL/WHATSAPP)', 'value' => '+31 (xxx) xxx xxx' ),
        );
}

// ============ Form Card ============
$contact_form_title = get_field('contact_form_title') ?: 'Prefer to message us?';
?>

<section class="Hero_Section Header_Contact_Us"
    <?php if ($hero_bg) : ?>
    style="background-image: url('<?php echo esc_url($hero_bg); ?>');"
    <?php endif; ?>>
    <div class="Hero_Seaction_Data">
        <div class="Box">
            <h1><?php echo esc_html($hero_title); ?></h1>
            <?php if ( $hero_button_popup ) : ?>
                <a class="hero-btn-popup" data-popup="<?php echo esc_attr( $hero_button_popup ); ?>">
                    <?php echo esc_html($hero_button_text); ?>
                </a>
            <?php else : ?>
                <a onclick="window.location.href='<?php echo esc_url($hero_button_url); ?>'">
                    <?php echo esc_html($hero_button_text); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="contact-section">

    <!-- Top Banner -->
    <div class="contact-banner">
        <p class="contact-banner-title"><?php echo esc_html($banner_title); ?></p>
        <p class="contact-banner-text">
            <?php echo nl2br(esc_html($banner_text)); ?>
        </p>
    </div>

    <!-- Main Content -->
    <div class="contact-container">

        <!-- Left: Contact Info -->
        <div class="contact-info">
            <h2 class="contact-heading">
                <span class="contact-heading-bar"></span>
                <?php echo esc_html($contact_heading); ?>
            </h2>

            <?php foreach ($contact_items as $index => $item) :
                $icon = isset($static_icons[$index]) ? $static_icons[$index] : $static_icons[0];
            ?>
                <div class="contact-item">
                    <span class="contact-item-icon">
                        <img src="<?php echo esc_url($icon['src']); ?>" alt="<?php echo esc_attr($icon['alt']); ?>" class="contact-icon" />
                    </span>
                    <div>
                        <p class="contact-item-label"><?php echo esc_html($item['label']); ?></p>
                        <p class="contact-item-value"><?php echo esc_html($item['value']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Right: Form Card (الفورم ثابت زي ما هو من غير أي تعديل) -->
        <div class="contact-form-card">
            <h3 class="contact-form-title"><?php echo esc_html($contact_form_title); ?></h3>

    <?php
    $is_ar = (strpos($_SERVER['REQUEST_URI'], '/ar') !== false);
    ?>

<div class="contact-form">
    <div class="contact-row">
        <div class="contact-field">
            <label class="contact-label"><?php echo $is_ar ? 'الاسم الكامل' : 'Full Name'; ?></label>
            <input type="text" class="contact-input" placeholder="<?php echo $is_ar ? 'جون دو' : 'John Doe'; ?>" />
        </div>
        <div class="contact-field">
            <label class="contact-label"><?php echo $is_ar ? 'البريد الإلكتروني' : 'Email Address'; ?></label>
            <input type="email" class="contact-input" placeholder="john@company.com" />
        </div>
    </div>

    <div class="contact-field">
        <label class="contact-label"><?php echo $is_ar ? 'رقم الهاتف' : 'Phone Number'; ?></label>
        <input
            type="tel"
            class="contact-input numbers-only"
            placeholder="966xxxxxxxxx"
            inputmode="numeric"
            pattern="[0-9]*"
            autocomplete="tel" />
    </div>

    <div class="contact-field">
        <label class="contact-label"><?php echo $is_ar ? 'اسم الشركة' : 'Company'; ?></label>
        <input type="text" class="contact-input" placeholder="<?php echo $is_ar ? 'HUFIX للحلول العالمية' : 'Hufix Global Solutions'; ?>" />
    </div>

    <div class="contact-field">
        <label class="contact-label"><?php echo $is_ar ? 'الرسالة / الاستفسار' : 'Message / Inquiry'; ?></label>
        <textarea class="contact-textarea" placeholder="<?php echo $is_ar ? 'كيف يمكننا مساعدتكم على النمو؟' : 'How can we help you grow?'; ?>"></textarea>
    </div>

    <button class="contact-btn">
        <?php echo $is_ar ? 'إرسال الرسالة' : 'Send Message'; ?>
        <span class="contact-btn-arrow">
            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/Send.png'); ?>" alt="" />
        </span>
    </button>
</div>
        </div>

    </div>
</section>

<?php get_footer(); ?>