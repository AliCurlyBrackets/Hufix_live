<?php
/**
 * Frontend: Strategic Business Consulting Page (page id = 60)
 * -----------------------------------------------------
 * حط الملف ده في تمبلت الصفحة دي
 * -----------------------------------------------------
 */
/* Template Name: consultation */
$cp_page_id = get_queried_object_id();
get_header();
?>

<!-- ===== Training ===== -->
<?php
$training_title       = get_field( 'cp_training_title', $cp_page_id );
$training_desc        = get_field( 'cp_training_desc', $cp_page_id );
$training_button_text = get_field( 'cp_training_button_text', $cp_page_id );
$training_button_link = get_field( 'cp_training_button_link', $cp_page_id );
$training_button_popup = get_field( 'cp_training_button_popup', $cp_page_id );
$training_image       = get_field( 'cp_training_image', $cp_page_id );
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


<!-- ===== Core Areas ===== -->
<?php
$core_bg       = get_field( 'cp_core_bg', $cp_page_id );
$core_title    = get_field( 'cp_core_title', $cp_page_id );
$core_subtitle = get_field( 'cp_core_subtitle', $cp_page_id );
?>
<section class="core_areas">

    <div class="core_areas_bg" <?php if ( $core_bg ) : ?>style="background-image: url('<?php echo esc_url( $core_bg ); ?>');"<?php endif; ?>></div>

    <div class="core_areas_container">

        <div class="core_areas_header">
            <h2 class="core_areas_title"><?php echo esc_html( $core_title ); ?></h2>
            <p class="core_areas_subtitle"><?php echo esc_html( $core_subtitle ); ?></p>
        </div>

        <div class="core_areas_grid" style="position: relative; overflow: hidden;">

            <?php if ( have_rows( 'cp_core_cards', $cp_page_id ) ) : ?>
                <?php
                $delay = 0;
                while ( have_rows( 'cp_core_cards', $cp_page_id ) ) : the_row();
                    $c_icon  = get_sub_field( 'icon' );
                    $c_title = get_sub_field( 'title' );
                    $c_desc  = get_sub_field( 'description' );
                    ?>
                    <div class="core_areas_card wow animate__animated animate__fadeInLeft" data-wow-delay="<?php echo esc_attr( $delay ); ?>s">
                        <?php if ( $c_icon ) : ?>
                            <div class="core_areas_icon">
                                <img src="<?php echo esc_url( $c_icon ); ?>" alt="<?php echo esc_attr( $c_title ); ?>">
                            </div>
                        <?php endif; ?>
                        <h3><?php echo esc_html( $c_title ); ?></h3>
                        <?php echo wp_kses_post( $c_desc ); ?>
                    </div>
                    <?php
                    $delay += 0.2;
                endwhile;
                ?>
            <?php endif; ?>

        </div>
    </div>
</section>


<!-- ===== Consulting Approach ===== -->
<?php
$consulting_title      = get_field( 'cp_consulting_title', $cp_page_id );
$consulting_desc       = get_field( 'cp_consulting_desc', $cp_page_id );
$consulting_list_label = get_field( 'cp_consulting_list_label', $cp_page_id );
$consulting_image      = get_field( 'cp_consulting_image', $cp_page_id );
$consulting_badge_num  = get_field( 'cp_consulting_badge_num', $cp_page_id );
$consulting_badge_text = get_field( 'cp_consulting_badge_text', $cp_page_id );
?>
<section class="consulting" style="position: relative; overflow: hidden;">
    <div class="consulting_container">

        <div class="consulting_left wow animate__animated animate__fadeInLeft">

            <h2 class="consulting_title"><?php echo esc_html( $consulting_title ); ?></h2>

            <div class="consulting_desc"><?php echo wp_kses_post( $consulting_desc ); ?></div>

            <?php if ( $consulting_list_label ) : ?>
                <p class="consulting_desc"><?php echo esc_html( $consulting_list_label ); ?></p>
            <?php endif; ?>

            <?php if ( have_rows( 'cp_consulting_list', $cp_page_id ) ) : ?>
                <ul class="consulting_list">
                    <?php while ( have_rows( 'cp_consulting_list', $cp_page_id ) ) : the_row();
                        $item_title = get_sub_field( 'title' );
                        $item_text  = get_sub_field( 'text' );
                        ?>
                        <li>
                            <div class="consulting_check">
                                <img src="<?php echo get_template_directory_uri(); ?>/assets/images/Overlays.png" alt="check">
                            </div>
                            <div class="consulting_list_text">
                                <h4><?php echo esc_html( $item_title ); ?></h4>
                                <p><?php echo esc_html( $item_text ); ?></p>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="consulting_right wow animate__animated animate__fadeInRight">
            <div class="consulting_img_wrapper">
                <?php if ( $consulting_image ) : ?>
                    <img src="<?php echo esc_url( $consulting_image ); ?>" alt="<?php echo esc_attr( $consulting_title ); ?>">
                <?php endif; ?>
            </div>

            <div class="consulting_badge">
                <span class="consulting_badge_num"><?php echo esc_html( $consulting_badge_num ); ?></span>
                <span class="consulting_badge_text"><?php echo esc_html( $consulting_badge_text ); ?></span>
            </div>
        </div>

    </div>
</section>


<!-- ===== What Sets Hufix Apart ===== -->
<?php
$apart_bg    = get_field( 'cp_apart_bg', $cp_page_id );
$apart_title = get_field( 'cp_apart_title', $cp_page_id );
?>
<section class="sets_apart">

    <div class="sets_apart_bg" <?php if ( $apart_bg ) : ?>style="background-image: url('<?php echo esc_url( $apart_bg ); ?>');"<?php endif; ?>></div>

    <div class="sets_apart_container">

        <h2 class="sets_apart_title"><?php echo esc_html( $apart_title ); ?></h2>

        <div class="sets_apart_grid" style="position: relative; overflow: hidden;">

            <?php if ( have_rows( 'cp_apart_cards', $cp_page_id ) ) : ?>
                <?php
                $delay = 0;
                while ( have_rows( 'cp_apart_cards', $cp_page_id ) ) : the_row();
                    $a_icon  = get_sub_field( 'icon' );
                    $a_title = get_sub_field( 'title' );
                    $a_text  = get_sub_field( 'text' );
                    ?>
                    <div class="sets_apart_card wow animate__animated animate__fadeInLeft" data-wow-delay="<?php echo esc_attr( $delay ); ?>s">
                        <?php if ( $a_icon ) : ?>
                            <div class="sets_apart_icon">
                                <img src="<?php echo esc_url( $a_icon ); ?>" alt="<?php echo esc_attr( $a_title ); ?>">
                            </div>
                        <?php endif; ?>
                        <h3><?php echo esc_html( $a_title ); ?></h3>
                        <p><?php echo esc_html( $a_text ); ?></p>
                    </div>
                    <?php
                    $delay += 0.2;
                endwhile;
                ?>
            <?php endif; ?>

        </div>
    </div>
</section>


<?php get_footer(); ?>