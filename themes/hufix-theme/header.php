<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>

    <?php wp_head(); ?>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>

    <link rel="stylesheet" href="<?php echo get_template_directory_uri() . '/assets/css/style.css'; ?>">

    <style>
    
    @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@200;200;200;200&display=swap');

    html[lang="ar"] * {
        font-family: 'Cairo' !important;
    }

    .header {
    position: relative; /* الحالة الأصلية */
    width: 100%;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

/* بيتفعل بس لما يوصل 100px */
.header.scrolled {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    background: #fff; /* أو أي لون عندك */
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transform: translateY(0);
}

/* هايد الهيدر لأعلى */
.header.scrolled.hidden {
    transform: translateY(-100%);
}

.header_Mobile {
    position: relative;
    width: 100%;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.header_Mobile.scrolled {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    background: #fff;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transform: translateY(0);
}

.header_Mobile.scrolled.hidden {
    transform: translateY(-100%);
}

    </style>
</head>

<body>

 <?php
/**
 * Frontend: Topbar
 * -----------------------------------------------------
 * ضيف الكود ده في header.php فوق الهيدر
 * -----------------------------------------------------
 */

$topbar_address   = get_field( 'topbar_address', 'option' );
$topbar_phone     = get_field( 'topbar_phone', 'option' );
$topbar_email     = get_field( 'topbar_email', 'option' );


?>

<div class="topbar">
    <div class="topbar_container">

        <div class="topbar_left">
            <?php if ( $topbar_address ) : ?>
                <div class="topbar_item">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/topbR/Icon-1.png" alt="Location">
                    <span><?php echo esc_html( $topbar_address ); ?></span>
                </div>
            <?php endif; ?>

            <?php if ( $topbar_address && $topbar_phone ) : ?>
                <div class="topbar_divider"></div>
            <?php endif; ?>

            <?php if ( $topbar_phone ) : ?>
                <div class="topbar_item">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/topbR/Vector.png" alt="Phone">
                    <span><?php echo esc_html( $topbar_phone ); ?></span>
                </div>
            <?php endif; ?>
        </div>

        <div class="topbar_right">
            <?php if ( $topbar_email ) : ?>
                <div class="topbar_item">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/topbR/Icon.png" alt="Email">
                    <span><?php echo esc_html( $topbar_email ); ?></span>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>


<section class="header_Mobile">
    <div class="header_Mobile_Data">

        <div class="Logo">
            <a href="index.html">
                <?php
if ( function_exists( 'the_custom_logo' ) && has_custom_logo() ) {
    the_custom_logo();
} else {
    ?>
    <a href="<?php echo esc_url( home_url('/') ); ?>">
        <?php bloginfo('name'); ?>
    </a>
    <?php
}
?>
            </a>
        </div>

        <div class="open_menu">
            <img src="<?php echo get_template_directory_uri() . '/assets/images/open_menu.png' ?>" alt="">
        </div>

    </div>
</section>

    <section class="header">
        <div class="header_data">

            <div class="Logo">
                <a href="index.html">
                    <?php
if ( function_exists( 'the_custom_logo' ) && has_custom_logo() ) {
    the_custom_logo();
} else {
    ?>
    <a href="<?php echo esc_url( home_url('/') ); ?>">
        <?php bloginfo('name'); ?>
    </a>
    <?php
}
?>
                </a>
            </div>

            <div class="Navs">
                <div class="Navs">
    <?php
    wp_nav_menu(array(
        'theme_location' => 'main-menu',
        'container'      => false,
        'menu_class'     => 'Main_Nav',
    ));
    ?>
</div>
            </div>

            <div class="Search" id="searchBtn">
                <img src="assets/images/header/search.png" alt="">
                <span><?php echo (strpos($_SERVER['REQUEST_URI'], '/ar') !== false) ? 'بحث' : 'Search'; ?></span>
            </div>

        </div>
    </section>