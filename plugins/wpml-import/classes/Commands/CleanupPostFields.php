<?php

namespace WPML\Import\Commands;

class CleanupPostFields extends Base\CleanupFields {

	/**
	 * @return string
	 */
	public static function getTitle() {
		return __( 'Cleaning Up Post Data', 'wpml-import' );
	}

	/**
	 * @return string
	 */
	public static function getDescription() {
		return __( 'Removing temporary and import-related post meta data.', 'wpml-import' );
	}

	/**
	 * @return string
	 */
	protected function getFieldsTable() {
		return $this->wpdb->postmeta;
	}

	/**
	 * @param class-string $commandClass
	 *
	 * @return string[]
	 */
	protected function getCommandFields( $commandClass ) {
		if ( in_array( Base\TemporaryPostFields::class, class_implements( $commandClass ), true ) ) {
			/** @var class-string<Base\TemporaryPostFields> $commandClass */
			return $commandClass::getTemporaryPostFields();
		}

		return [];
	}
}
