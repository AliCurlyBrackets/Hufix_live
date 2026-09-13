<?php

namespace WPML\Import\Commands\Base;

trait HasItemsPerBatch {

	/**
	 * Returns the filtered number of items to process per batch for this command.
	 *
	 * @param int $defaultLimit The default batch size for the command.
	 *
	 * @return int
	 */
	protected function filterNumberOfItemsPerBatch( int $defaultLimit ) {
		$className      = static::class;
		$lastSeparator  = strrpos( $className, '\\' );
		$stepIdentifier = false !== $lastSeparator ? substr( $className, $lastSeparator + 1 ) : $className;

		/**
		 * Allows to customize the number of items processed per batch by each import command.
		 *
		 * Allows third-party code to tune the batch size for a specific command —
		 * for example, to reduce memory pressure on large imports or to speed up
		 * processing when items are lightweight.
		 *
		 * @since 1.2.0
		 *
		 * @param int    $defaultLimit Default batch size declared by the command.
		 * @param string $stepIdentifier Short class name used as a human-readable step identifier.
		 */
		return (int) apply_filters(
			'wpml_import_command_items_per_batch',
			$defaultLimit,
			$stepIdentifier
		);
	}
}
