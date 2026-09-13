<?php

namespace GFML\Strings;

use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class TranslateEverythingHooks implements \IWPML_Backend_Action, \IWPML_Frontend_Action {

	const KINDS = array(
		\Gravity_Forms_Multilingual::SLUG => array(
			'title'  => 'Gravity Form',
			'plural' => 'Gravity Forms',
			'slug'   => \Gravity_Forms_Multilingual::SLUG,
		),
	);

	public function add_hooks() {
		Hooks::onFilter( 'wpml_active_string_package_kinds' )
			->then( spreadArgs( array( $this, 'registerActiveStringPackageKinds' ) ) );
	}


	/**
	 * @param array|mixed $packages
	 */
	public function registerActiveStringPackageKinds( $packages ): array {
		if ( is_array( $packages ) ) {
			return array_merge( $packages, self::KINDS );
		}

		return self::KINDS;
	}
}
