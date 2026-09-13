<?php
/**
 * Frontend: Where Meest
 * -----------------------------------------------------
 * ضيف الكود ده في المكان اللي عايز السيكشن يظهر فيه
 * -----------------------------------------------------
 */

$wm_heading      = get_field( 'wm_heading', 'option' );
$wm_paragraph    = get_field( 'wm_paragraph', 'option' );
$wm_button_text  = get_field( 'wm_button_text', 'option' );
$wm_button_file  = get_field( 'wm_button_file', 'option' );
$wm_button_icon  = get_field( 'wm_button_icon', 'option' );

// نصوص افتراضية لو الحقول فاضية
if ( empty( $wm_heading ) ) {
    $wm_heading = 'Where Strategy Meets Culture. Where Vision Becomes Execution.';
}
if ( empty( $wm_paragraph ) ) {
    $wm_paragraph = 'Premium boutique consulting, HR advisory, and executive training — seamlessly delivered across Europe and the MENA region. Tailored for decision-makers shaping the future. Unlock your potential today:';
}
if ( empty( $wm_button_text ) ) {
    $wm_button_text = 'Download Our Portfolio';
}
if ( empty( $wm_button_icon ) ) {
    $wm_button_icon = get_template_directory_uri() . '/assets/images/icons/Margin.png';
}
?>

<section class="Where_Meest">
    <div class="Where_Meest_Data" style="position: relative; overflow: hidden;">

        <h2 class="wow animate__animated animate__backInLeft">
            <?php echo esc_html( $wm_heading ); ?>
        </h2>

        <div class="Download_Data wow animate__animated animate__backInRight">
            <p><?php echo esc_html( $wm_paragraph ); ?></p>

            <a href="<?php echo $wm_button_file ? esc_url( $wm_button_file ) : '#'; ?>" <?php echo $wm_button_file ? 'download' : ''; ?>>
                <?php echo esc_html( $wm_button_text ); ?>
                <img src="<?php echo esc_url( $wm_button_icon ); ?>" width="23" alt="">
            </a>
        </div>

    </div>
</section>