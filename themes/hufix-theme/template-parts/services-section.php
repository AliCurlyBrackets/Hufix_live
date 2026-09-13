<?php
/**
 * Frontend: Services (Repeater)
 * -----------------------------------------------------
 * ضيف الكود ده في المكان اللي عايز السيكشن يظهر فيه
 * -----------------------------------------------------
 */
?>

<section class="services">
    <div class="services_container" style="position: relative; overflow: hidden;">

        <?php $is_ar = (strpos($_SERVER['REQUEST_URI'], '/ar') !== false); ?>

<h2 class="services_title"><?php echo $is_ar ? 'خدماتنا' : 'Our Services'; ?></h2>

        <div class="services_grid">

            <?php
            if ( have_rows( 'services_repeater', 'option' ) ) :
                $delay = 0;

                while ( have_rows( 'services_repeater', 'option' ) ) : the_row();

                    $title       = get_sub_field( 'title' );
                    $image       = get_sub_field( 'image' );
                    $description = get_sub_field( 'description' );
                    $button_text = get_sub_field( 'button_text' );
                    $button_link = get_sub_field( 'button_link' );
                    $is_wide     = get_sub_field( 'wide' );

                    $card_class = 'service_card wow animate__animated animate__zoomInDown';
                    if ( $is_wide ) {
                        $card_class .= ' card_wide';
                    }
                    ?>

                    <div class="<?php echo esc_attr( $card_class ); ?>" data-wow-delay="<?php echo esc_attr( $delay ); ?>s">
                        <?php if ( $image ) : ?>
                            <img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $title ); ?>">
                        <?php endif; ?>

                        <div class="card_overlay"></div>

                        <div class="card_content">
                            <?php if ( $title ) : ?>
                                <h3><?php echo esc_html( $title ); ?></h3>
                            <?php endif; ?>

                            <?php if ( $description ) : ?>
                                <p><?php echo esc_html( $description ); ?></p>
                            <?php endif; ?>

                            <?php if ( $button_text ) : ?>
                                <a href="<?php echo $button_link ? esc_url( $button_link ) : '#'; ?>">
                                    <?php echo esc_html( $button_text ); ?>
                                    <span><img src="<?php echo get_template_directory_uri(); ?>/assets/images/arrow_link.png" alt=""></span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php
                    $delay += 0.2;
                endwhile;
            endif;
            ?>

        </div>
    </div>
</section>