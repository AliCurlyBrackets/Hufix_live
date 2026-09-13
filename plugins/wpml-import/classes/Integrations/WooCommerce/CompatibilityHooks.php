<?php

namespace WPML\Import\Integrations\WooCommerce;

use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class CompatibilityHooks implements \IWPML_Action {

	const VARIATION_POST_TYPE = 'product_variation';

	public function add_hooks() {
		Hooks::onFilter( 'wpml_import_skip_set_post_type_invisible', 10, 2 )
			->then( spreadArgs( [ $this, 'skipVariations' ] ) );
	}

	/**
	 * @param  bool   $skip
	 * @param  string $postType
	 *
	 * @return bool
	 */
	public function skipVariations( $skip, $postType ) {
		if ( self::VARIATION_POST_TYPE === $postType ) {
			return true;
		}
		return $skip;
	}
}
