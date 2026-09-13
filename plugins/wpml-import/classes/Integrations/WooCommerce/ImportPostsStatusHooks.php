<?php

namespace WPML\Import\Integrations\WooCommerce;

use WPML\Import\Fields;
use WPML\FP\Lst;
use WPML\LIB\WP\Hooks;
use WPML\Import\Integrations\Base\StatusManagement;
use function WPML\FP\spreadArgs;

class ImportPostsStatusHooks extends StatusManagement {

	// Regular import logic managing duplicated SKUs happens at 10: delay to avoid conflicts.
	const PRIORITY = 11;

	public function add_hooks() {
		Hooks::onFilter( 'woocommerce_product_import_pre_insert_product_object', self::PRIORITY, 2 )
			->then( spreadArgs( [ $this, 'forceInvisibleStatus' ] ) );
	}

	/**
	 * @param  \WC_Product $obj
	 * @param  array       $data
	 *
	 * @return \WC_Product
	 */
	public function forceInvisibleStatus( $obj, $data ) {
		if ( 'variation' === $obj->get_type() ) {
			// Variations can not be drafts.
			return $obj;
		}

		$metaData = $data['meta_data'] ?? [];
		if ( empty( $metaData ) ) {
			return $obj;
		}

		if (
			$this->hasStatusField( $metaData )
			|| ! $this->hasLanguageField( $metaData )
		) {
			return $obj;
		}

		$status = $obj->get_status();
		if ( $this->isAlreadyInvisible( $status ) ) {
			return $obj;
		}

		$obj->set_status( StatusManagement::INVISIBLE_POST_STATUS );
		$obj->update_meta_data( Fields::FINAL_POST_STATUS, $status );

		return $obj;
	}

	/**
	 * @param  string $field
	 * @param  array  $metaData
	 *
	 * @return bool
	 */
	protected function hasField( $field, $metaData ) {
		$match = Lst::find(
			function ( $item ) use ( $field ) {
				return $field === $item['key'];
			},
			$metaData
		);

		return (bool) $match;
	}
}
