<?php
/**
 * Frontend: HR Outsourcing & Advisory Page (page id = 98)
 * -----------------------------------------------------
 * حط الملف ده في تمبلت الصفحة دي
 * -----------------------------------------------------
 */

/* Template Name: hr_qut */

$ho_page_id = get_queried_object_id();
get_header(); 
?>

<!-- ===== Training ===== -->
<?php
$training_title       = get_field( 'ho_training_title', $ho_page_id );
$training_desc        = get_field( 'ho_training_desc', $ho_page_id );
$training_button_text = get_field( 'ho_training_button_text', $ho_page_id );
$training_button_link = get_field( 'ho_training_button_link', $ho_page_id );
$training_button_popup = get_field( 'ho_training_button_popup', $ho_page_id );
$training_image       = get_field( 'ho_training_image', $ho_page_id );
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

        <div class="training_right wow animate__animated animate__bounceInRight">
            <?php if ( $training_image ) : ?>
                <img src="<?php echo esc_url( $training_image ); ?>" alt="<?php echo esc_attr( $training_title ); ?>">
            <?php endif; ?>
        </div>

    </div>
</section>


<!-- ===== HR Support Blocks ===== -->
<?php if ( have_rows( 'ho_support_blocks', $ho_page_id ) ) : ?>
    <?php while ( have_rows( 'ho_support_blocks', $ho_page_id ) ) : the_row();

        $top_icon        = get_sub_field( 'top_icon' );
        $s_title         = get_sub_field( 'title' );
        $s_desc          = get_sub_field( 'description' );
        $items_label     = get_sub_field( 'items_label' );
        $footer          = get_sub_field( 'footer' );
        $s_image         = get_sub_field( 'image' );
        $image_position  = get_sub_field( 'image_position' );
        $bg_variant      = get_sub_field( 'bg_variant' );

        $bg_class = 'hr_support_bg' . ( $bg_variant ? ' hr_support_bg_2' : '' );
        ?>

        <section class="hr_support">

            <div class="<?php echo esc_attr( $bg_class ); ?>"></div>

            <div class="hr_support_container" style="position: relative; overflow: hidden;">

                <?php if ( $image_position === 'left' ) : ?>
                    <div class="hr_support_right wow animate__animated animate__bounceInRight">
                        <?php if ( $s_image ) : ?>
                            <img src="<?php echo esc_url( $s_image ); ?>" alt="<?php echo esc_attr( $s_title ); ?>">
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="hr_support_left wow animate__animated animate__bounceInLeft">

                    <?php if ( $top_icon ) : ?>
                        <div class="hr_support_top_icon">
                            <img src="<?php echo esc_url( $top_icon ); ?>" alt="HR Support">
                        </div>
                    <?php endif; ?>

                    <h2 class="hr_support_title"><?php echo esc_html( $s_title ); ?></h2>

                    <div class="hr_support_desc"><?php echo wp_kses_post( $s_desc ); ?></div>

                    <?php if ( $items_label ) : ?>
                        <p class="hr_support_desc"><?php echo esc_html( $items_label ); ?></p>
                    <?php endif; ?>

                    <?php if ( have_rows( 'items' ) ) : ?>
                        <div class="hr_support_grid">
                            <?php while ( have_rows( 'items' ) ) : the_row();
                                $item_icon = get_sub_field( 'icon' );
                                $item_text = get_sub_field( 'text' );
                                ?>
                                <div class="hr_support_item">
                                    <?php if ( $item_icon ) : ?>
                                        <div class="hr_support_icon">
                                            <img src="<?php echo esc_url( $item_icon ); ?>" alt="">
                                        </div>
                                    <?php endif; ?>
                                    <span><?php echo esc_html( $item_text ); ?></span>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( $footer ) : ?>
                        <p class="hr_support_footer_text"><?php echo esc_html( $footer ); ?></p>
                    <?php endif; ?>

                </div>

                <?php if ( $image_position === 'right' ) : ?>
                    <div class="hr_support_right wow animate__animated animate__bounceInRight">
                        <?php if ( $s_image ) : ?>
                            <img src="<?php echo esc_url( $s_image ); ?>" alt="<?php echo esc_attr( $s_title ); ?>">
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>
        </section>

    <?php endwhile; ?>
<?php endif; ?>


<!-- ===== Why Trust ===== -->
<?php
$trust_bg    = get_field( 'ho_trust_bg', $ho_page_id );
$trust_title = get_field( 'ho_trust_title', $ho_page_id );
$trust_quote = get_field( 'ho_trust_quote', $ho_page_id );
?>
<section class="why_trust">

    <div class="why_trust_bg" <?php if ( $trust_bg ) : ?>style="background-image: url('<?php echo esc_url( $trust_bg ); ?>');"<?php endif; ?>></div>

    <div class="why_trust_container">

        <h2 class="why_trust_title"><?php echo esc_html( $trust_title ); ?></h2>

        <div class="why_trust_grid" style="position: relative; overflow: hidden;">

            <?php if ( have_rows( 'ho_trust_cards', $ho_page_id ) ) : ?>
                <?php
                $delay = 0;
                while ( have_rows( 'ho_trust_cards', $ho_page_id ) ) : the_row();
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