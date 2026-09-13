<?php
/**
 * Plugin Name: CS Repair Session Slugs (تشغيل مرة واحدة)
 * Description: بيصلح الـ slug المكرر بتاع السيشنز القديمة. فعّله مرة واحدة، افتح أي صفحة في لوحة التحكم، وبعدين احذفه.
 * Version: 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_notices', function () {
	if ( get_option( 'cs_repair_session_slugs_done' ) ) {
		echo '<div class="notice notice-info"><p>إصلاح روابط السيشنز اتعمل بالفعل. تقدر تحذف/تعطّل بلجن "CS Repair Session Slugs" دلوقتي.</p></div>';
		return;
	}

	global $wpdb;

	$session_ids = get_posts( array(
		'post_type'        => 'course',
		'post_status'      => 'any',
		'posts_per_page'   => -1,
		'fields'           => 'ids',
		'suppress_filters' => true, // WPML: نص على كل اللغات مش لغة واحدة بس.
		'meta_query'       => array(
			array( 'key' => '_cs_master', 'compare' => 'EXISTS' ),
		),
	) );

	$fixed      = 0;
	$already_ok = 0;

	foreach ( $session_ids as $session_id ) {
		$master_id = (int) get_post_meta( $session_id, '_cs_master', true );
		$master    = $master_id ? get_post( $master_id ) : null;

		if ( ! $master ) {
			continue;
		}

		$id_suffix = '-' . $session_id;
		$current   = (string) get_post_field( 'post_name', $session_id );

		if ( substr( $current, -strlen( $id_suffix ) ) === $id_suffix ) {
			$already_ok++;
			continue;
		}

		$base_slug = sanitize_title( $master->post_title );
		$base_slug = function_exists( '_truncate_post_slug' )
			? _truncate_post_slug( $base_slug, 200 - strlen( $id_suffix ) )
			: substr( $base_slug, 0, max( 0, 200 - strlen( $id_suffix ) ) );

		wp_update_post( array(
			'ID'        => $session_id,
			'post_name' => $base_slug . $id_suffix,
		) );

		$fixed++;
	}

	update_option( 'cs_repair_session_slugs_done', 1 );

	printf(
		'<div class="notice notice-success is-dismissible"><p><strong>تم إصلاح روابط السيشنز.</strong><br>اتصلح: %d سيشن.<br>كان مظبوط بالفعل: %d سيشن.<br>الإجمالي اللي اتفحص: %d.<br>تقدر تعطّل/تحذف البلجن ده دلوقتي.</p></div>',
		$fixed,
		$already_ok,
		count( $session_ids )
	);
} );
