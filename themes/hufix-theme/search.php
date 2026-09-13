<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$theme_img = get_stylesheet_directory_uri() . '/assets/images/';

$search = get_search_query();

$cards = CS_Listing::search_cards( $search );
?>

<section class="cs-search-results">

    <div class="cs-main">

        <div class="cs-results">
            Showing
            <span><?php echo count( $cards ); ?></span>
            Results for
            <strong>"<?php echo esc_html( $search ); ?>"</strong>
        </div>

        <div class="cs-grid">

            <?php echo CS_Listing::cards_html( $cards, $theme_img ); ?>

        </div>

    </div>

</section>

<?php get_footer(); ?>