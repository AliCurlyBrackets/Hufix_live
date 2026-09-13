<?php

namespace WPML\Forms\WPForms\Hooks;

use WPML\Forms\WPForms\SharedAPI\WpForms;
use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class TranslateEverythingHooks {

	const KINDS = [
		WpForms::SLUG => [
			'title'  => 'WP Form',
			'plural' => 'WP Forms',
			'slug'   => WpForms::SLUG,
		],
	];

	public function addHooks() {
		Hooks::onFilter( 'wpml_active_string_package_kinds' )
			->then( spreadArgs( [ $this, 'registerActiveStringPackageKinds' ] ) );
	}

	/**
	 * @param array $kinds
	 *
	 * @return array
	 */
	public function registerActiveStringPackageKinds( $kinds ) {
		return array_merge( $kinds, self::KINDS );
	}

}
