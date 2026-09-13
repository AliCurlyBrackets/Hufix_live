<?php

namespace WPML\Import\Commands;

class CleanupTermFields extends Base\CleanupFields {

	/**
	 * @return string
	 */
	public static function getTitle() {
		return __( 'Cleaning Up Term Data', 'wpml-import' );
	}

	/**
	 * @return string
	 */
	public static function getDescription() {
		return __( 'Removing temporary and import-related term meta data.', 'wpml-import' );
	}

	/**
	 * @return string
	 */
	protected function getFieldsTable() {
		return $this->wpdb->termmeta;
	}

	/**
	 * @param class-string $commandClass
	 *
	 * @return string[]
	 */
	protected function getCommandFields( $commandClass ) {
		if ( in_array( Base\TemporaryTermFields::class, class_implements( $commandClass ), true ) ) {
			/** @var class-string<Base\TemporaryTermFields> $commandClass */
			return $commandClass::getTemporaryTermFields();
		}

		return [];
	}
}
