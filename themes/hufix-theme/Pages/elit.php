<?php
/**
 * Frontend: Elite Recruitment Services Page (page id = 124)
 * -----------------------------------------------------
 * حط الملف ده في تمبلت الصفحة دي
 * -----------------------------------------------------
 */

/* Template Name: elit */
$rec_page_id = get_queried_object_id();
get_header();
?>

<!-- ===== Training ===== -->
<?php
$training_title       = get_field( 'rec_training_title', $rec_page_id );
$training_desc        = get_field( 'rec_training_desc', $rec_page_id );
$training_button_text = get_field( 'rec_training_button_text', $rec_page_id );
$training_button_link = get_field( 'rec_training_button_link', $rec_page_id );
$training_button_popup = get_field( 'rec_training_button_popup', $rec_page_id );
$training_image       = get_field( 'rec_training_image', $rec_page_id );
?>
<section class="training">
    <div class="training_container" style="position: relative; overflow: hidden;">

        <div class="training_left wow animate__animated animate__bounceInLeft">
            <h2 class="training_title"><?php echo esc_html( $training_title ); ?></h2>

            <div class="training_desc"><?php echo wp_kses_post( $training_desc ); ?></div>

            <?php if ( $training_button_popup ) : ?>
                <a href="#" class="training_btn hero-btn-popup" data-popup="<?php echo esc_attr( $training_button_popup ); ?>" style="width: 316px;">
                    <?php echo esc_html( $training_button_text ); ?>
                </a>
            <?php else : ?>
                <a href="<?php echo $training_button_link ? esc_url( $training_button_link ) : '#'; ?>" class="training_btn" style="width: 316px;">
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


<!-- ===== ROI ===== -->
<?php
$roi_bg    = get_field( 'rec_roi_bg', $rec_page_id );
$roi_title = get_field( 'rec_roi_title', $rec_page_id );
$roi_quote = get_field( 'rec_roi_quote', $rec_page_id );
?>
<section class="roi">

    <div class="roi_bg" <?php if ( $roi_bg ) : ?>style="background-image: url('<?php echo esc_url( $roi_bg ); ?>');"<?php endif; ?>></div>

    <div class="roi_container">

        <h2 class="roi_title"><?php echo esc_html( $roi_title ); ?></h2>

        <div class="roi_grid">
            <?php if ( have_rows( 'rec_roi_cards', $rec_page_id ) ) : ?>
                <?php while ( have_rows( 'rec_roi_cards', $rec_page_id ) ) : the_row();
                    $r_icon  = get_sub_field( 'icon' );
                    $r_title = get_sub_field( 'title' );
                    $r_text  = get_sub_field( 'text' );
                    ?>
                    <div class="roi_card">
                        <?php if ( $r_icon ) : ?>
                            <div class="roi_icon">
                                <img src="<?php echo esc_url( $r_icon ); ?>" alt="<?php echo esc_attr( $r_title ); ?>">
                            </div>
                        <?php endif; ?>
                        <h3><?php echo esc_html( $r_title ); ?></h3>
                        <p><?php echo esc_html( $r_text ); ?></p>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>

        <?php if ( $roi_quote ) : ?>
            <div class="roi_quote">
                <p><?php echo esc_html( $roi_quote ); ?></p>
            </div>
        <?php endif; ?>

    </div>
</section>


<!-- ===== What We Deliver ===== -->
<?php
$deliver_title  = get_field( 'rec_deliver_title', $rec_page_id );
$deliver_footer = get_field( 'rec_deliver_footer', $rec_page_id );
$deliver_image  = get_field( 'rec_deliver_image', $rec_page_id );
?>
<section class="deliver-section">
    <div class="deliver-container" style="position: relative; overflow: hidden;">

        <div class="deliver-content wow animate__animated animate__bounceInLeft">
            <h2 class="deliver-title"><?php echo nl2br( esc_html( $deliver_title ) ); ?></h2>

            <?php if ( have_rows( 'rec_deliver_list', $rec_page_id ) ) : ?>
                <ul class="deliver-list">
                    <?php while ( have_rows( 'rec_deliver_list', $rec_page_id ) ) : the_row();
                        $d_icon = get_sub_field( 'icon' );
                        $d_text = get_sub_field( 'text' );
                        ?>
                        <li class="deliver-item">
                            <span class="deliver-icon-wrap">
                                <?php if ( $d_icon ) : ?>
                                    <img src="<?php echo esc_url( $d_icon ); ?>" alt="" class="deliver-icon">
                                <?php endif; ?>
                            </span>
                            <span class="deliver-item-text"><?php echo esc_html( $d_text ); ?></span>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php endif; ?>

            <?php if ( $deliver_footer ) : ?>
                <p class="deliver-footer"><?php echo esc_html( $deliver_footer ); ?></p>
            <?php endif; ?>
        </div>

        <div class="deliver-image-wrapper wow animate__animated animate__bounceInRight">
            <?php if ( $deliver_image ) : ?>
                <img src="<?php echo esc_url( $deliver_image ); ?>" alt="Hufix Meeting" class="deliver-image">
            <?php endif; ?>
        </div>

    </div>
</section>


<!-- ===== Process ===== -->
<?php
$process_title  = get_field( 'rec_process_title', $rec_page_id );
$process_footer = get_field( 'rec_process_footer', $rec_page_id );
?>
<section class="process-section">
    <div class="process-container">

        <h2 class="process-title"><?php echo esc_html( $process_title ); ?></h2>

        <div class="process-steps">

            <?php if ( have_rows( 'rec_process_steps', $rec_page_id ) ) : ?>
                <?php
                $steps = array();
                while ( have_rows( 'rec_process_steps', $rec_page_id ) ) : the_row();
                    $steps[] = get_sub_field( 'text' );
                endwhile;

                $total = count( $steps );
                foreach ( $steps as $index => $step_text ) :
                    $step_number = $index + 1;
                    $is_first    = ( $step_number === 1 );
                    $is_last     = ( $step_number === $total );

                    $step_class   = 'process-step' . ( $is_last ? ' process-step--last' : '' );
                    $circle_class = 'process-circle' . ( $is_first ? ' process-circle--filled' : '' );
                    ?>
                    <div class="<?php echo esc_attr( $step_class ); ?>">
                        <div class="<?php echo esc_attr( $circle_class ); ?>"><?php echo esc_html( $step_number ); ?></div>
                        <?php if ( ! $is_last ) : ?>
                            <div class="process-line"></div>
                        <?php endif; ?>
                        <p class="process-text"><?php echo esc_html( $step_text ); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>

        <?php if ( $process_footer ) : ?>
            <p class="process-footer"><?php echo esc_html( $process_footer ); ?></p>
        <?php endif; ?>

    </div>
</section>


<!-- ===== Trust ===== -->
<?php
$trust_title    = get_field( 'rec_trust_title', $rec_page_id );
$trust_subtitle = get_field( 'rec_trust_subtitle', $rec_page_id );
$trust_quote    = get_field( 'rec_trust_quote', $rec_page_id );
?>
<section class="trust-section">
    <div class="trust-container">

        <div class="trust-header">
            <h2 class="trust-title"><?php echo esc_html( $trust_title ); ?></h2>
            <p class="trust-subtitle"><?php echo nl2br( esc_html( $trust_subtitle ) ); ?></p>
        </div>

        <div class="trust-cards">
            <?php if ( have_rows( 'rec_trust_cards', $rec_page_id ) ) : ?>
                <?php while ( have_rows( 'rec_trust_cards', $rec_page_id ) ) : the_row();
                    $tc_icon  = get_sub_field( 'icon' );
                    $tc_title = get_sub_field( 'title' );
                    $tc_text  = get_sub_field( 'text' );
                    ?>
                    <div class="trust-card">
                        <?php if ( $tc_icon ) : ?>
                            <div class="trust-icon-wrap">
                                <img src="<?php echo esc_url( $tc_icon ); ?>" alt="<?php echo esc_attr( $tc_title ); ?>" class="trust-icon">
                            </div>
                        <?php endif; ?>
                        <h3 class="trust-card-title"><?php echo esc_html( $tc_title ); ?></h3>
                        <p class="trust-card-text"><?php echo esc_html( $tc_text ); ?></p>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>

        <?php if ( $trust_quote ) : ?>
            <div class="trust-quote">
                <p class="trust-quote-text">"<?php echo esc_html( $trust_quote ); ?>"</p>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php get_footer(); ?>