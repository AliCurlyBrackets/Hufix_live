<?php
/**
 * Frontend: HR Infrastructure Page (page id = 76)
 * -----------------------------------------------------
 * حط الملف ده في تمبلت الصفحة دي
 * -----------------------------------------------------
 */


/* Template Name: hr */
$hr_page_id = get_queried_object_id();
get_header();
?>

<!-- ===== Training ===== -->
<?php
$training_title       = get_field( 'hr_training_title', $hr_page_id );
$training_desc        = get_field( 'hr_training_desc', $hr_page_id );
$training_button_text = get_field( 'hr_training_button_text', $hr_page_id );
$training_button_link = get_field( 'hr_training_button_link', $hr_page_id );
$training_button_popup = get_field( 'hr_training_button_popup', $hr_page_id );
$training_image       = get_field( 'hr_training_image', $hr_page_id );
?>
<section class="training">
    <div class="training_container" style="position: relative; overflow: hidden;">

        <div class="training_left wow animate__animated animate__bounceInLeft">
            <h2 class="training_title"><?php echo esc_html( $training_title ); ?></h2>

            <div class="training_desc"><?php echo wp_kses_post( $training_desc ); ?></div>

            <?php if ( $training_button_popup ) : ?>
                <a href="#" class="training_btn hero-btn-popup" data-popup="<?php echo esc_attr( $training_button_popup ); ?>">
                    <?php echo esc_html( $training_button_text ); ?>
                </a>
            <?php else : ?>
                <a href="<?php echo $training_button_link ? esc_url( $training_button_link ) : '#'; ?>" class="training_btn">
                    <?php echo esc_html( $training_button_text ); ?>
                </a>
            <?php endif; ?>
        </div>

        <div class="training_right wow animate__animated animate__fadeInRight">
            <?php if ( $training_image ) : ?>
                <img src="<?php echo esc_url( $training_image ); ?>" alt="<?php echo esc_attr( $training_title ); ?>">
            <?php endif; ?>
        </div>

    </div>
</section>


<!-- ===== What We Build ===== -->
<?php
$build_bg       = get_field( 'hr_build_bg', $hr_page_id );
$build_title    = get_field( 'hr_build_title', $hr_page_id );
$build_subtitle = get_field( 'hr_build_subtitle', $hr_page_id );
?>
<section class="build">

    <div class="build_bg" <?php if ( $build_bg ) : ?>style="background-image: url('<?php echo esc_url( $build_bg ); ?>');"<?php endif; ?>></div>

    <div class="build_container">

        <div class="build_header">
            <h2 class="build_title"><?php echo esc_html( $build_title ); ?></h2>
            <div class="build_subtitle"><?php echo wp_kses_post( $build_subtitle ); ?></div>
        </div>

        <div class="build_grid" style="position: relative; overflow: hidden;">

            <?php if ( have_rows( 'hr_build_cards', $hr_page_id ) ) : ?>
                <?php
                $delay = 0;
                while ( have_rows( 'hr_build_cards', $hr_page_id ) ) : the_row();
                    $b_icon  = get_sub_field( 'icon' );
                    $b_title = get_sub_field( 'title' );
                    $anim    = ( $delay < 0.8 ) ? 'animate__bounceInLeft' : 'animate__fadeInLeft';
                    ?>
                    <div class="build_card wow animate__animated <?php echo esc_attr( $anim ); ?>" data-wow-delay="<?php echo esc_attr( $delay ); ?>s">
                        <?php if ( $b_icon ) : ?>
                            <div class="build_icon">
                                <img src="<?php echo esc_url( $b_icon ); ?>" alt="<?php echo esc_attr( $b_title ); ?>">
                            </div>
                        <?php endif; ?>
                        <h3><?php echo esc_html( $b_title ); ?></h3>
                    </div>
                    <?php
                    $delay += 0.2;
                endwhile;
                ?>
            <?php endif; ?>

        </div>
    </div>
</section>


<!-- ===== Tailored Sections ===== -->
<?php if ( have_rows( 'hr_tailored_sections', $hr_page_id ) ) : ?>
    <?php while ( have_rows( 'hr_tailored_sections', $hr_page_id ) ) : the_row();

        $t_title          = get_sub_field( 'title' );
        $t_desc           = get_sub_field( 'description' );
        $t_image           = get_sub_field( 'image' );
        $t_image_position  = get_sub_field( 'image_position' );
        $t_check_icon      = get_sub_field( 'check_icon' );
        $t_footer          = get_sub_field( 'footer' );
        $t_transparent_bg  = get_sub_field( 'transparent_bg' );

        $section_style = $t_transparent_bg ? ' style="background: none;"' : '';
        ?>

        <section class="tailored-section"<?php echo $section_style; ?>>
            <div class="tailored-container" style="position: relative; overflow: hidden;">

                <?php if ( $t_image_position === 'left' ) : ?>
                    <div class="tailored-image-wrapper wow animate__animated animate__fadeInLeft">
                        <?php if ( $t_image ) : ?>
                            <img src="<?php echo esc_url( $t_image ); ?>" alt="<?php echo esc_attr( $t_title ); ?>" class="tailored-image">
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="tailored-content wow animate__animated animate__fadeInRight">
                    <h2 class="tailored-title"><?php echo esc_html( $t_title ); ?></h2>
                    <p class="tailored-desc"><?php echo esc_html( $t_desc ); ?></p>

                    <?php if ( have_rows( 'list' ) ) : ?>
                        <ul class="tailored-list">
                            <?php while ( have_rows( 'list' ) ) : the_row(); ?>
                                <li class="tailored-item">
                                    <span class="tailored-icon-wrap">
                                        <?php if ( $t_check_icon ) : ?>
                                            <img src="<?php echo esc_url( $t_check_icon ); ?>" alt="check" class="tailored-icon">
                                        <?php endif; ?>
                                    </span>
                                    <span><?php echo esc_html( get_sub_field( 'text' ) ); ?></span>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if ( $t_footer ) : ?>
                        <p class="tailored-footer"><?php echo esc_html( $t_footer ); ?></p>
                    <?php endif; ?>
                </div>

                <?php if ( $t_image_position === 'right' ) : ?>
                    <div class="tailored-image-wrapper">
                        <?php if ( $t_image ) : ?>
                            <img src="<?php echo esc_url( $t_image ); ?>" alt="<?php echo esc_attr( $t_title ); ?>" class="tailored-image">
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>
        </section>

    <?php endwhile; ?>
<?php endif; ?>


<!-- ===== Why Trust ===== -->
<?php
$trust_bg    = get_field( 'hr_trust_bg', $hr_page_id );
$trust_title = get_field( 'hr_trust_title', $hr_page_id );
$trust_quote = get_field( 'hr_trust_quote', $hr_page_id );
?>
<section class="why_trust">

    <div class="why_trust_bg" <?php if ( $trust_bg ) : ?>style="background-image: url('<?php echo esc_url( $trust_bg ); ?>');"<?php endif; ?>></div>

    <div class="why_trust_container">

        <h2 class="why_trust_title"><?php echo esc_html( $trust_title ); ?></h2>

        <div class="why_trust_grid" style="position: relative; overflow: hidden;">

            <?php if ( have_rows( 'hr_trust_cards', $hr_page_id ) ) : ?>
                <?php
                $delay = 0;
                while ( have_rows( 'hr_trust_cards', $hr_page_id ) ) : the_row();
                    $w_icon   = get_sub_field( 'icon' );
                    $w_title  = get_sub_field( 'title' );
                    $w_offset = get_sub_field( 'offset' );

                    $card_class = 'why_trust_card wow animate__animated animate__fadeInLeft';
                    if ( $w_offset ) {
                        $card_class .= ' why_trust_card--offset';
                    }
                    ?>
                    <div class="<?php echo esc_attr( $card_class ); ?>" data-wow-delay="<?php echo esc_attr( $delay ); ?>s">
                        <?php if ( $w_icon ) : ?>
                            <div class="why_trust_icon">
                                <img src="<?php echo esc_url( $w_icon ); ?>" alt="<?php echo esc_attr( $w_title ); ?>">
                            </div>
                        <?php endif; ?>
                        <h3><?php echo esc_html( $w_title ); ?></h3>
                    </div>
                    <?php
                    $delay += 0.2;
                endwhile;
                ?>
            <?php endif; ?>

        </div>

        <?php if ( $trust_quote ) : ?>
            <div class="why_trust_quote">
                <p>"<?php echo esc_html( $trust_quote ); ?>"</p>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php get_footer(); ?>