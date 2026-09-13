<?php
/**
 * Feature: AJAX Filtering
 * بيستقبل الفلاتر ويرجّع HTML الكروت + العدد، ويحمّل الجافاسكريبت
 * على صفحة أرشيف الكاتيجوري.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CS_Ajax {

	public static function init() {
		add_action( 'wp_ajax_cs_courses_filter', array( __CLASS__, 'handle' ) );
		add_action( 'wp_ajax_nopriv_cs_courses_filter', array( __CLASS__, 'handle' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );

		// إندبوينتات فورمات صفحة الكورس المفرد (Initial Enrollment / Request Another Date-City).
		add_action( 'wp_ajax_cs_enrollment_request', array( __CLASS__, 'handle_enrollment_request' ) );
		add_action( 'wp_ajax_nopriv_cs_enrollment_request', array( __CLASS__, 'handle_enrollment_request' ) );
		add_action( 'wp_ajax_cs_another_date_request', array( __CLASS__, 'handle_another_date_request' ) );
		add_action( 'wp_ajax_nopriv_cs_another_date_request', array( __CLASS__, 'handle_another_date_request' ) );
	}

	public static function handle() {
    // إجبار WPML يبدّل للغة الصفحة الحالية (اللي جاية من الفرونت إند)
    // عشان get_locale() جوه الطلب ده يرجّع صح، مش يعتمد على كوكي/referer.
    if ( ! empty( $_GET['ui_lang'] ) ) {
        do_action( 'wpml_switch_language', sanitize_text_field( wp_unslash( $_GET['ui_lang'] ) ) );
    }

    // إندبوينت قراءة عام (فلترة كورسات) — من غير nonce عشان ميفشلش مع الكاش.
    $args = array(
        'term_id'   => isset( $_GET['term_id'] ) ? (int) $_GET['term_id'] : 0,
        'master_id' => isset( $_GET['course'] ) ? (int) $_GET['course'] : 0,
        'search'    => isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '',
        'mode'     => isset( $_GET['mode'] ) ? sanitize_text_field( wp_unslash( $_GET['mode'] ) ) : '',
        'language' => isset( $_GET['lang'] ) ? sanitize_text_field( wp_unslash( $_GET['lang'] ) ) : '',
        'country'  => isset( $_GET['loc'] ) ? sanitize_text_field( wp_unslash( $_GET['loc'] ) ) : '',
        'month'    => isset( $_GET['month'] ) ? (int) $_GET['month'] : 0,
    );

    $theme_img = get_stylesheet_directory_uri() . '/assets/images/';
    $cards     = CS_Listing::get_cards( $args );

    wp_send_json_success( array(
        'html'  => CS_Listing::cards_html( $cards, $theme_img ),
        'count' => count( $cards ),
    ) );
}

	/**
	 * "Initial Enrollment Request" (البوب أب اللي بيتفتح من زرار السنجل كورس).
	 */
	public static function handle_enrollment_request() {
		check_ajax_referer( 'cs_course_requests', 'nonce' );

		$session_id = isset( $_POST['session_id'] ) ? (int) $_POST['session_id'] : 0;
		$name       = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$email      = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$phone      = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$company    = isset( $_POST['company'] ) ? sanitize_text_field( wp_unslash( $_POST['company'] ) ) : '';
		$people     = isset( $_POST['participants'] ) ? (int) $_POST['participants'] : 1;
		$notes      = isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '';

		if ( ! $name || ! $email || ! $phone ) {
			wp_send_json_error( array( 'message' => __( 'Please fill in all required fields.', 'courses-system' ) ) );
		}

		$course_title = $session_id ? get_the_title( CS_CPT::master_id( $session_id ) ) : '';

		$body  = "New Initial Enrollment Request\n\n";
		$body .= 'Course: ' . $course_title . "\n";
		$body .= 'Name: ' . $name . "\n";
		$body .= 'Email: ' . $email . "\n";
		$body .= 'Phone: ' . $phone . "\n";
		$body .= 'Company: ' . $company . "\n";
		$body .= 'Participants: ' . $people . "\n";
		$body .= 'Notes: ' . $notes . "\n";

		wp_mail( get_option( 'admin_email' ), 'New Enrollment Request: ' . $course_title, $body );

		wp_send_json_success( array( 'message' => __( 'Your enrollment request has been submitted successfully.', 'courses-system' ) ) );
	}

	/**
	 * "Request Another Date or City" (البوب أب التاني في السنجل كورس).
	 */
	public static function handle_another_date_request() {
		check_ajax_referer( 'cs_course_requests', 'nonce' );

		$master_id = isset( $_POST['master_id'] ) ? (int) $_POST['master_id'] : 0;
		$name      = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$company   = isset( $_POST['company'] ) ? sanitize_text_field( wp_unslash( $_POST['company'] ) ) : '';
		$email     = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$phone     = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$city      = isset( $_POST['city'] ) ? sanitize_text_field( wp_unslash( $_POST['city'] ) ) : '';
		$start     = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '';
		$end       = isset( $_POST['end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) ) : '';
		$notes     = isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '';

		if ( ! $name || ! $email || ! $phone ) {
			wp_send_json_error( array( 'message' => __( 'Please fill in all required fields.', 'courses-system' ) ) );
		}

		$course_title = $master_id ? get_the_title( $master_id ) : '';

		$body  = "New 'Request Another Date or City' Request\n\n";
		$body .= 'Course: ' . $course_title . "\n";
		$body .= 'Name: ' . $name . "\n";
		$body .= 'Company: ' . $company . "\n";
		$body .= 'Email: ' . $email . "\n";
		$body .= 'Phone: ' . $phone . "\n";
		$body .= 'Preferred City: ' . $city . "\n";
		$body .= 'Preferred Start Date: ' . $start . "\n";
		$body .= 'Preferred End Date: ' . $end . "\n";
		$body .= 'Notes: ' . $notes . "\n";

		wp_mail( get_option( 'admin_email' ), 'New Date/City Request: ' . $course_title, $body );

		wp_send_json_success( array( 'message' => __( 'Your request has been submitted successfully.', 'courses-system' ) ) );
	}

	public static function enqueue() {
		if ( is_admin() ) {
			return;
		}
		wp_enqueue_script(
			'cs-filter',
			CS_URL . 'assets/js/cs-filter.js',
			array(),
			CS_VERSION,
			true
		);
		wp_localize_script( 'cs-filter', 'CS_FILTER', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'cs_filter' ),
		) );
	}
}

CS_Ajax::init();
