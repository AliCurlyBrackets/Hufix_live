<?php
/**
 * Frontend: Why Hufix
 * -----------------------------------------------------
 * ضيف الكود ده في المكان اللي عايز السيكشن يظهر فيه
 * -----------------------------------------------------
 */

$why_title    = get_field( 'why_title', 'option' );
$why_subtitle = get_field( 'why_subtitle', 'option' );
$why_bg       = get_field( 'why_bg', 'option' );

if ( empty( $why_title ) ) {
    $why_title = 'Why Hufix';
}
if ( empty( $why_subtitle ) ) {
    $why_subtitle = 'Elite Thinking. Grounded Solutions. Cross-Continental Confidence.';
}
?>

<section class="why">

    <!-- Background Image -->
    <div class="why_bg" <?php if ( $why_bg ) : ?>style="background-image: url('<?php echo esc_url( $why_bg ); ?>');"<?php endif; ?>></div>

    <div class="why_container">

        <div class="why_header">
            <h2 class="why_title"><?php echo esc_html( $why_title ); ?></h2>
            <p class="why_subtitle"><?php echo esc_html( $why_subtitle ); ?></p>
        </div>

        <div class="why_grid" style="position: relative; overflow: hidden;">

            <?php
            if ( have_rows( 'why_items', 'option' ) ) :
                $delay = 0;

                while ( have_rows( 'why_items', 'option' ) ) : the_row();

                    $icon = get_sub_field( 'icon' );
                    $text = get_sub_field( 'text' );
                    ?>

                    <div class="why_item wow animate__animated animate__bounceInLeft" data-wow-delay="<?php echo esc_attr( $delay ); ?>s">
                        <?php if ( $icon ) : ?>
                            <div class="why_icon">
                                <img src="<?php echo esc_url( $icon ); ?>" alt="<?php echo esc_attr( $text ); ?>">
                            </div>
                        <?php endif; ?>

                        <?php if ( $text ) : ?>
                            <p><?php echo esc_html( $text ); ?></p>
                        <?php endif; ?>
                    </div>

                    <?php
                    $delay += 0.2;
                endwhile;
            endif;
            ?>

        </div>
    </div>
</section>