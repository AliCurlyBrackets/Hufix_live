<?php
/**
 * Plugin Name: WPML Export and Import
 * Description: A multipurpose plugin to export and import multilingual content.
 * Author: OnTheGoSystems
 * Author URI: http://www.onthegosystems.com
 * Version: 1.2.1
 * Plugin Slug: wpml-import
 *
 * @package wpml/template-plugin
 */

if ( defined( 'WPML_IMPORT_VERSION' ) ) {
	return;
}

define( 'WPML_IMPORT_VERSION', '1.2.1' );
define( 'WPML_IMPORT_PLUGIN_PATH', __DIR__ );
define( 'WPML_IMPORT_PLUGIN_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'WPML_IMPORT_ADMIN_PAGE_SLUG', 'wpml/import' );

add_action(
	'wpml_loaded',
	function () {
		if ( ! class_exists( 'WPML_Core_Version_Check' ) ) {
			require_once WPML_IMPORT_PLUGIN_PATH . '/vendor/wpml-shared/wpml-lib-dependencies/src/dependencies/class-wpml-core-version-check.php';
		}

		if ( ! WPML_Core_Version_Check::is_ok( WPML_IMPORT_PLUGIN_PATH . '/wpml-dependencies.json' ) ) {
			return;
		}

		require_once WPML_IMPORT_PLUGIN_PATH . '/vendor/autoload.php';

		load_plugin_textdomain( 'wpml-import', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		\WPML\Import\App::run();
	}
);

require_once WPML_IMPORT_PLUGIN_PATH . '/classes/EarlyHooks.php';

\WPML\Import\EarlyHooks::init();
