<?php
/**
 * Frontend: Clients Logos (Marquee)
 * -----------------------------------------------------
 * ضيف الكود ده في المكان اللي عايز السيكشن يظهر فيه
 * بنكرر اللوجوهات مرتين جوه نفس التراك عشان تأثير
 * السكرول اللانهائي (Marquee) يفضل مستمر من غير قطع
 * -----------------------------------------------------
 */
?>

<section class="marquee">
    <div class="track" id="track">

        <?php
        // بنلف على اللوجوهات مرتين متتاليين (نفس الليست) عشان الحركة تبقى متصلة
        for ( $repeat = 0; $repeat < 2; $repeat++ ) :

            if ( have_rows( 'clients_logos', 'option' ) ) :
                while ( have_rows( 'clients_logos', 'option' ) ) : the_row();

                    $logo = get_sub_field( 'logo' );
                    $name = get_sub_field( 'name' );

                    if ( ! $logo ) {
                        continue;
                    }
                    ?>

                    <div class="marquee-item">
                        <img src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( $name ); ?>">
                    </div>

                    <?php
                endwhile;
            endif;

        endfor;
        ?>

    </div>
</section>