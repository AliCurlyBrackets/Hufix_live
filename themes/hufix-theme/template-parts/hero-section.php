<?php
/**
 * Frontend: Hero Section
 * -----------------------------------------------------
 * ضيف الكود ده في المكان اللي عايز السيكشن يظهر فيه
 * (header.php أو front-page.php أو أي تمبلت)
 * -----------------------------------------------------
 */

$hero_heading      = get_field( 'hero_heading', 'option' );
$hero_button_text  = get_field( 'hero_button_text', 'option' );
$hero_button_link  = get_field( 'hero_button_link', 'option' );
$hero_button_popup = get_field( 'hero_button_popup', 'option' );
$hero_background   = get_field( 'hero_background', 'option' );

// لو مفيش قيم متسجلة، هيرجع للنص الافتراضي اللي في الديزاين
if ( empty( $hero_heading ) ) {
    $hero_heading = 'Where Strategy Meets Culture';
}
if ( empty( $hero_button_text ) ) {
    $hero_button_text = 'Start Your Journey';
}
?>

<section class="Hero_Section" <?php if ( $hero_background ) : ?>style="background-image: url('<?php echo esc_url( $hero_background ); ?>');"<?php endif; ?>>
    <div class="Hero_Seaction_Data">
        <div class="Box">
            <h1><?php echo esc_html( $hero_heading ); ?></h1>

            <?php if ( $hero_button_popup ) : ?>
                <a href="#" class="hero-btn hero-btn-popup" data-popup="<?php echo esc_attr( $hero_button_popup ); ?>">
                    <?php echo esc_html( $hero_button_text ); ?>
                </a>
            <?php elseif ( $hero_button_link ) : ?>
                <a href="<?php echo esc_url( $hero_button_link ); ?>" class="hero-btn">
                    <?php echo esc_html( $hero_button_text ); ?>
                </a>
            <?php else : ?>
                <a class="hero-btn"><?php echo esc_html( $hero_button_text ); ?></a>
            <?php endif; ?>
        </div>
    </div>
</section>