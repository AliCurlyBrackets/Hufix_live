<?php
/**
 * Frontend: About Section
 * -----------------------------------------------------
 * ضيف الكود ده في المكان اللي عايز السيكشن يظهر فيه
 * -----------------------------------------------------
 */

$about_image       = get_field( 'about_image', 'option' );
$about_title       = get_field( 'about_title', 'option' );
$about_paragraph   = get_field( 'about_paragraph', 'option' );
$about_button_text = get_field( 'about_button_text', 'option' );
$about_button_link = get_field( 'about_button_link', 'option' );
$about_button_icon = get_field( 'about_button_icon', 'option' );

if ( empty( $about_title ) ) {
    $about_title = 'Who We Are';
}
if ( empty( $about_button_text ) ) {
    $about_button_text = 'Learn More';
}
?>

<section class="About_Section">
    <div class="About_Section_Data">

        <?php if ( $about_image ) : ?>
            <img class="About_Image" src="<?php echo esc_url( $about_image ); ?>" alt="<?php echo esc_attr( $about_title ); ?>">
        <?php endif; ?>

        <div class="Content">
            <h3><?php echo esc_html( $about_title ); ?></h3>

            <?php if ( $about_paragraph ) : ?>
                <p><?php echo esc_html( $about_paragraph ); ?></p>
            <?php endif; ?>

            <?php if ( have_rows( 'about_list', 'option' ) ) : ?>
                <ul>
                    <?php while ( have_rows( 'about_list', 'option' ) ) : the_row();
                        $icon = get_sub_field( 'icon' );
                        $text = get_sub_field( 'text' );
                        ?>
                        <li>
                            <?php if ( $icon ) : ?>
                                <img src="<?php echo esc_url( $icon ); ?>" alt="">
                            <?php endif; ?>
                            <?php echo esc_html( $text ); ?>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php endif; ?>

            <a href="<?php echo $about_button_link ? esc_url( $about_button_link ) : '#'; ?>">
                <?php echo esc_html( $about_button_text ); ?>
                <?php if ( $about_button_icon ) : ?>
                    <img src="<?php echo esc_url( $about_button_icon ); ?>" alt="">
                <?php endif; ?>
            </a>
        </div>

    </div>
</section>