<?php
/**
 * Partial: Slider (sl-section)
 * بيتنادى من أي تمبليت كده:
 *   set_query_var( 'cs_term_id', $term_id ); // اختياري
 *   include CS_PATH . 'templates/partial-slider.php';
 *
 * بيطلع نفس ماركب الإسلايدر بتاعك بالظبط، بس البيانات ديناميك.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cs_term_id = get_query_var( 'cs_term_id' ) ?: null;
$cs_slides  = CS_Slider::get_slides( $cs_term_id );

if ( empty( $cs_slides ) ) {
	return;
}
?>
<section class="sl-section">
	<div class="sl-track" id="slTrack">
		<?php foreach ( $cs_slides as $slide ) : ?>
			<div class="sl-slide">
				<?php if ( ! empty( $slide['image'] ) ) : ?>
					<img class="sl-bg" src="<?php echo esc_url( $slide['image'] ); ?>" alt="<?php echo esc_attr( $slide['title'] ); ?>">
				<?php endif; ?>
				<div class="sl-overlay"></div>
				<div class="sl-content">
					<?php if ( ! empty( $slide['title'] ) ) : ?>
						<h2 class="sl-title"><?php echo esc_html( $slide['title'] ); ?></h2>
					<?php endif; ?>
					<?php if ( ! empty( $slide['desc'] ) ) : ?>
						<p class="sl-desc"><?php echo esc_html( $slide['desc'] ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $slide['link'] ) ) : ?>
						<a href="<?php echo esc_url( $slide['link'] ); ?>" class="sl-link">
							<span><?php echo esc_html( $slide['btn_text'] ?: 'Booking Now' ); ?></span>
							<img src="<?php echo esc_url( CS_URL . 'assets/images/booking_NOw.png' ); ?>" alt="">
						</a>
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="sl-arrow sl-arrow-prev" id="slPrev">
		<img src="<?php echo esc_url( CS_URL . 'assets/images/right_arrow.png' ); ?>" alt="prev">
	</div>
	<div class="sl-arrow sl-arrow-next" id="slNext">
		<img src="<?php echo esc_url( CS_URL . 'assets/images/left_arrow.png' ); ?>" alt="next">
	</div>

	<div class="sl-dots" id="slDots"></div>
</section>