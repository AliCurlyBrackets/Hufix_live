<?php
/**
 * Feature: Stripe Checkout — دفع مباشر لما المستخدم يدوس "Initial
 * Enrollment Request" في صفحة الكورس المفرد (السعر بتاع السيشن المحدد
 * اللي ظاهر في الصفحة، مش الماستر).
 *
 * المفاتيح لازم تتحط في wp-config.php (مش هنا جوه البلجن):
 *   define( 'CS_STRIPE_SECRET_KEY',      'sk_live_...' );
 *   define( 'CS_STRIPE_PUBLISHABLE_KEY', 'pk_live_...' ); // مش مستخدم هنا فعليًا (Checkout مستضاف بالكامل عند Stripe) لكن سايبينه لو احتجناه مستقبلًا لأي Stripe.js تاني.
 *
 * بنستخدم REST API بتاع Stripe مباشرة عن طريق wp_remote_post() بدل ما
 * نجيب الـ SDK الرسمي (composer) عشان نتجنب أي تعديل على بيئة السيرفر أو
 * تعارض إصدارات -- إندبوينت الـ Checkout Session واحد وبسيط.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CS_Stripe {

	const STRIPE_API = 'https://api.stripe.com/v1/checkout/sessions';

	public static function init() {
		add_action( 'wp_ajax_cs_stripe_checkout', array( __CLASS__, 'handle_create_checkout' ) );
		add_action( 'wp_ajax_nopriv_cs_stripe_checkout', array( __CLASS__, 'handle_create_checkout' ) );
	}

	/**
	 * بيولّد Stripe Checkout Session لسيشن كورس محدد، وبيرجّع رابط الدفع
	 * عشان الجافاسكريبت يحوّل المتصفح عليه مباشرة.
	 */
	public static function handle_create_checkout() {
		check_ajax_referer( 'cs_course_requests', 'nonce' );

		if ( ! defined( 'CS_STRIPE_SECRET_KEY' ) || ! CS_STRIPE_SECRET_KEY ) {
			wp_send_json_error( array(
				'message' => __( 'Payment is not configured yet. Please contact us to enroll.', 'courses-system' ),
			) );
		}

		$session_id = isset( $_POST['session_id'] ) ? (int) $_POST['session_id'] : 0;
		if ( ! $session_id || get_post_type( $session_id ) !== CS_CPT ) {
			wp_send_json_error( array( 'message' => __( 'Invalid course session.', 'courses-system' ) ) );
		}

		$master_id = CS_CPT::master_id( $session_id );
		$country   = get_post_meta( $session_id, CS_META_COUNTRY, true );
		$loc_index = get_post_meta( $session_id, CS_META_LOC_INDEX, true );
		$date      = get_post_meta( $session_id, CS_META_DATE, true );

		$pc    = CS_Listing::country_price( $master_id, $country, $loc_index );
		$price = (float) $pc['price'];
		$curr  = self::normalize_currency( $pc['currency'] );

		if ( $price <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'This course does not have a valid price yet.', 'courses-system' ) ) );
		}

		$title = get_the_title( $master_id );
		if ( $date ) {
			$title .= ' — ' . date_i18n( 'F j, Y', strtotime( $date ) );
		}
		if ( $country ) {
			$title .= ' (' . CS_Countries::name( $country ) . ')';
		}

		$success_url = add_query_arg( array( 'cs_paid' => '1' ), get_permalink( $session_id ) );
		$cancel_url  = add_query_arg( array( 'cs_paid' => '0' ), get_permalink( $session_id ) );

		$body = array(
			'mode'                 => 'payment',
			'success_url'          => $success_url . '&stripe_session={CHECKOUT_SESSION_ID}',
			'cancel_url'           => $cancel_url,
			'line_items'           => array(
				array(
					'quantity'   => 1,
					'price_data' => array(
						'currency'    => $curr,
						'unit_amount' => (int) round( $price * 100 ), // Stripe بياخد المبلغ بأصغر وحدة عملة (سنت).
						'product_data' => array(
							'name' => $title,
						),
					),
				),
			),
			'metadata'             => array(
				'session_id' => (string) $session_id,
				'master_id'  => (string) $master_id,
				'country'    => (string) $country,
				'date'       => (string) $date,
			),
		);

		$response = wp_remote_post( self::STRIPE_API, array(
			'timeout' => 20,
			'headers' => array(
				'Authorization' => 'Bearer ' . CS_STRIPE_SECRET_KEY,
				'Content-Type'  => 'application/x-www-form-urlencoded',
			),
			'body'    => $body,
		) );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'message' => __( 'Could not reach the payment gateway. Please try again.', 'courses-system' ) ) );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 400 || empty( $data['url'] ) ) {
			$err_msg = ! empty( $data['error']['message'] ) ? $data['error']['message'] : __( 'Payment gateway error. Please try again.', 'courses-system' );
			// بنلوج الرسالة الحقيقية بتاعة Stripe للأدمن (مش للمستخدم) عشان نقدر نظبط أي مشكلة إعداد.
			error_log( 'CS_Stripe checkout error: ' . $err_msg ); // phpcs:ignore
			wp_send_json_error( array( 'message' => __( 'Payment gateway error. Please try again.', 'courses-system' ) ) );
		}

		wp_send_json_success( array( 'url' => $data['url'] ) );
	}

	/**
	 * لازم يبقى كود عملة صحيح مكوّن من 3 حروف عشان Stripe يقبله (زي "eur"،
	 * "usd")، مش رمز زي "$" -- لو العملة المخزّنة في الكورس رمز بدل كود،
	 * بنرجع لعملة افتراضية (USD) بدل ما نبعت طلب هيترفض من Stripe.
	 */
	private static function normalize_currency( $currency ) {
		$currency = strtolower( trim( (string) $currency ) );
		if ( 3 === strlen( $currency ) && ctype_alpha( $currency ) ) {
			return $currency;
		}
		return 'usd';
	}
}

CS_Stripe::init();
