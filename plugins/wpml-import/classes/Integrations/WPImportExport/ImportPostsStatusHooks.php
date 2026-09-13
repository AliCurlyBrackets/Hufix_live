<?php

namespace WPML\Import\Integrations\WPImportExport;

use WPML\Import\Fields;
use WPML\FP\Lst;
use WPML\LIB\WP\Hooks;
use WPML\Import\Integrations\Base\StatusManagement;
use function WPML\FP\spreadArgs;

class ImportPostsStatusHooks extends StatusManagement {

	const FIELD_PLACEHOLDER = '{%s[1]}';

	public function add_hooks() {
		Hooks::onAction( 'wpie_after_completed_item_import', 10, 4 )
			->then( spreadArgs( [ $this, 'forceInvisibleStatus' ] ) );
	}

	/**
	 * @param  int   $id
	 * @param  array $record
	 * @param  array $data
	 * @param  array $option
	 */
	public function forceInvisibleStatus( $id, $record, $data, $option ) {
		if ( array_key_exists( 'ID', $data ) ) {
			// Updating an existing item: skip status management.
			return;
		}

		$importType = $option['wpie_import_type'] ?? '';
		if (
			empty( $importType )
			|| false === in_array( $importType, $this->getPostTypes(), true )
		) {
			return;
		}

		$data       = array_keys( $record );
		$postStatus = get_post_status( $id );

		$this->maybeSetPostInvisible( $id, $postStatus, $data );
	}

	/**
	 * @param  string $field
	 * @param  array  $data
	 *
	 * @return bool
	 */
	protected function hasField( $field, $data ) {
		$fieldKey = sprintf( self::FIELD_PLACEHOLDER, $field );
		return in_array( $fieldKey, $data, true );
	}
}
