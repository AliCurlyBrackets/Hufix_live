<?php

namespace WPML\Import\Integrations\Base\Strategies\Simulate;

use WPML\LIB\WP\Hooks;
use WPML\Import\Integrations\Base\Fields;

use function WPML\FP\spreadArgs;

abstract class ExportObjectsHooks implements \IWPML_Backend_Action, \IWPML_DIC_Action {
	use Fields;

	public function add_hooks() {
		$metaType = $this->getMetaType();
		Hooks::onFilter( 'default_' . $metaType . '_metadata', 10, 3 )->then( spreadArgs( [ $this, 'setMetaField' ] ) );
	}

	/**
	 * @return string
	 */
	abstract protected function getMetaType();

	/**
	 * @param  string $metaKey
	 *
	 * @return bool
	 */
	protected function isMetaField( $metaKey ) {
		$exportFields = $this->getImportFields();
		return in_array( $metaKey, $exportFields, true );
	}

	/**
	 * @param  int $objectId
	 *
	 * @return \stdClass|null
	 */
	abstract protected function getElementLanguageDetails( $objectId );

	/**
	 * Generate our meta fields on-the-fly, without actually writing them to the database.
	 *
	 * @param  mixed  $value
	 * @param  int    $objectId
	 * @param  string $metaKey
	 *
	 * @return mixed
	 */
	public function setMetaField( $value, $objectId, $metaKey ) {
		if ( ! $this->isMetaField( $metaKey ) ) {
			return $value;
		}

		$element = $this->getElementLanguageDetails( $objectId );
		if ( $element ) {
			return $this->getFieldValue( $metaKey, $element );
		}

		return $value;
	}
}
