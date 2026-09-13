<?php
/**
 * FAQ Accordion - Frontend
 * حطّ الكود ده في التمبلت اللي عايز الاكورديون يظهر فيه (مثلاً page-faq.php)
 * أو ادمجه جوه أي صفحة تانية عن طريق get_template_part
 */
 
 /* Template Name: faq */ 

get_header() ;

$faq_title = get_field( 'faq_section_title' );
$faq_items = get_field( 'faq_items' );
?>

<section class="faq-accordion-section" style="padding-bottom:190px;padding-top:50px;">
	<div class="faq-accordion-container">

		<?php if ( $faq_title ) : ?>
			<h2 class="faq-title"><?php echo esc_html( $faq_title ); ?></h2>
		<?php endif; ?>

		<?php if ( $faq_items ) : ?>
			<div class="faq-accordion">
				<?php foreach ( $faq_items as $index => $item ) : ?>
					<div class="faq-item">
						<button class="faq-question" type="button" aria-expanded="false">
							<span><?php echo esc_html( $item['question'] ); ?></span>
							<span class="faq-icon">+</span>
						</button>
						<div class="faq-answer">
							<div class="faq-answer-inner">
								<?php echo wp_kses_post( $item['answer'] ); ?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

	</div>
</section>

<style>
.faq-accordion-section {
	padding: 60px 20px;
	font-family: var(--bold);
}

.faq-accordion-container {
	max-width: 800px;
	margin: 0 auto;
}

.faq-title {
	text-align: center;
	color: #246A73;
	font-size: 32px;
	font-weight: 700;
	margin-bottom: 40px;
}

.faq-accordion {
	display: flex;
	flex-direction: column;
	gap: 12px;
}

.faq-item {
	border: 1px solid #E0E6E7;
	border-radius: 10px;
	overflow: hidden;
	transition: border-color 0.3s ease;
}

.faq-item.active {
	border-color: #246A73;
}

.faq-question {
	width: 100%;
	display: flex;
	justify-content: space-between;
	align-items: center;
	background: #fff;
	border: none;
	padding: 18px 22px;
	font-size: 17px;
	font-weight: 600;
	color: #1A2E30;
	cursor: pointer;
	text-align: right;
}

.faq-item.active .faq-question {
	background: #246A73;
	color: #fff;
}

.faq-icon {
	flex-shrink: 0;
	width: 26px;
	height: 26px;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 20px;
	font-weight: 400;
	transition: transform 0.3s ease;
}

.faq-item.active .faq-icon {
	transform: rotate(45deg);
}

.faq-answer {
	max-height: 0;
	overflow: hidden;
	transition: max-height 0.35s ease;
	background: #F7FAFA;
}

.faq-answer-inner {
	padding: 18px 22px;
	color: #4A5C5E;
	line-height: 1.8;
	font-size: 15px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
	var faqItems = document.querySelectorAll('.faq-item');

	faqItems.forEach(function (item) {
		var question = item.querySelector('.faq-question');
		var answer = item.querySelector('.faq-answer');

		question.addEventListener('click', function () {
			var isActive = item.classList.contains('active');

			// اقفل كل الأسئلة التانية (اكورديون بيفتح واحد بس في المرة)
			faqItems.forEach(function (otherItem) {
				otherItem.classList.remove('active');
				otherItem.querySelector('.faq-question').setAttribute('aria-expanded', 'false');
				otherItem.querySelector('.faq-answer').style.maxHeight = null;
			});

			// افتح الحالي لو كان مقفول
			if (!isActive) {
				item.classList.add('active');
				question.setAttribute('aria-expanded', 'true');
				answer.style.maxHeight = answer.scrollHeight + 'px';
			}
		});
	});
});
</script>

<?php get_footer() ; ?>