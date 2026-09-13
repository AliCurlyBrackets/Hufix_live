<?php

namespace WPML\Import\Integrations\WooCommerce\ImportHooks;

use WPML\Import\Fields;
use WPML\Import\Integrations\WooCommerce\ImportHooks;
use WPML\LIB\WP\Hooks;

use function WPML\FP\spreadArgs;

class Update extends ImportHooks {

	public function add_hooks() {
		Hooks::onFilter( 'woocommerce_product_importer_parsed_data', 10, 2 )
			->then( spreadArgs( [ $this, 'resolveProductId' ] ) );
	}

	/**
	 * @param  array                $data
	 * @param  \WC_Product_Importer $wcProductCsvImporter
	 *
	 * @return array
	 */
	public function resolveProductId( $data, $wcProductCsvImporter ) {
		$sku      = $data['sku'] ?? '';
		$metaData = $data['meta_data'] ?? [];

		if ( empty( $sku ) ) {
			return $data;
		}

		$language = $this->getMetaValue( $metaData, Fields::LANGUAGE_CODE );

		$resolvedId = $this->getProductIdBySkuAndLanguage( $sku, $language );

		if ( $resolvedId ) {
			$data['id'] = $resolvedId;
		}

		return $data;
	}

	/**
	 * @param  string $originalValue
	 * @param  string $language
	 * @param  int    $processedValue
	 *
	 * @return int
	 */
	protected function manageSimpleRelatedProduct( $originalValue, $language, $processedValue ) {
		$resolvedId = $this->getProductIdBySkuAndLanguage( $originalValue, $language );
		return $resolvedId ?: $processedValue;
	}
}
