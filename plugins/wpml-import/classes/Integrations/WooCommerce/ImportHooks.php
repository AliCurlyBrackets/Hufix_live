<?php

namespace WPML\Import\Integrations\WooCommerce;

use WPML\Import\Fields;
use WPML\Import\Helper\Language;
use WPML\FP\Lst;

abstract class ImportHooks implements \IWPML_Action {

	/**
	 * Temporary SKU replacement:
	 * _wpml_import_sku_{LANGUAGE_CODE}_{ORIGINAL_SKU}.
	 */
	const SKU_PLACEHOLDER          = '_wpml_import_sku_%s_%s';
	const SKU_PLACEHOLDER_PREFIX   = '_wpml_import_sku_';
	const TRANSLATION_SKU_META_KEY = '_wpml_import_wc_translation_sku';
	const SKU_META_KEY             = '_sku';
	const ORIGINAL_ID_META_KEY     = '_original_id';

	const PRODUCT_IMPORTING_STATUS = 'importing';

	/**
	 * Import fields that reference other products:
	 * - Relative fields hold a single SKU
	 * - Relative comma fields hold a comma-separated list of SKUs
	 */

	/** @var array|null */
	private $relativeFields;

	/** @var array|null */
	private $relativeCommaFields;

	/** @var array|null */
	private $importFieldDefaults;

	/** @var array|null */
	private $importRawData;

	/** @var array|null */
	private $importKeys;

	/** @var array|null */
	private $importIndexes;

	/** @var string|null */
	private $languageKey;

	/** @var string|null */
	private $translationGroupKey;

	/** @var array|null */
	private $importRow;

	/** @var array */
	private $importValues;

	abstract public function add_hooks();

	/**
	 * @param string               $translationGroup
	 * @param string               $language
	 * @param \WC_Product_Importer $wcProductCsvImporter
	 */
	protected function setImportRowData( $translationGroup, $language, $wcProductCsvImporter ) {
		$this->setImportData( $wcProductCsvImporter );
		$this->setImportRow( $translationGroup, $language );
		$this->setimportValues();
	}

	/**
	 * @param \WC_Product_Importer $wcProductCsvImporter
	 */
	private function setImportData( $wcProductCsvImporter ) {
		if (
			null !== $this->importRawData
			&& null !== $this->importKeys
			&& null !== $this->importIndexes
			&& null !== $this->languageKey
			&& null !== $this->translationGroupKey
		) {
			return;
		}
		$this->languageKey         = Fields::LANGUAGE_CODE;
		$this->translationGroupKey = Fields::TRANSLATION_GROUP;
		$this->importRawData       = $wcProductCsvImporter->get_raw_data();
		$this->importKeys          = $wcProductCsvImporter->get_raw_keys();
		$this->importIndexes       = $this->getImportFieldDefaults();
		$callback                  = function ( &$value, $key ) {
			$value = $this->findColumnIndex( $key );
		};
		array_walk( $this->importIndexes, $callback );
	}

	/**
	 * Locate a column among the raw CSV headers, tolerating a missing
	 * "Meta:" prefix on the reserved WPML columns.
	 *
	 * @param  string $key
	 *
	 * @return int|false
	 */
	private function findColumnIndex( $key ) {
		foreach ( [ $key, ExportHooks::getFieldLabel( $key ) ] as $candidate ) {
			$index = array_search( $candidate, $this->importKeys, true );
			if ( false !== $index ) {
				return $index;
			}
		}

		return false;
	}

	/**
	 * @return array
	 */
	private function getImportFieldDefaults() {
		if ( null === $this->importFieldDefaults ) {
			$this->importFieldDefaults = [
				__( 'ID', 'woocommerce' )               => false,
				__( 'SKU', 'woocommerce' )              => false,
				__( 'Parent', 'woocommerce' )           => false,
				__( 'Grouped products', 'woocommerce' ) => false,
				__( 'Upsells', 'woocommerce' )          => false,
				__( 'Cross-sells', 'woocommerce' )      => false,
				$this->languageKey                      => false,
				$this->translationGroupKey              => false,
			];
		}
		return $this->importFieldDefaults;
	}

	/**
	 * @param string $translationGroup
	 * @param string $language
	 */
	private function setImportRow( $translationGroup, $language ) {
		if (
			false === $this->importIndexes[ $this->translationGroupKey ]
			|| false === $this->importIndexes[ $this->languageKey ]
		) {
			return;
		}

		$this->importRow = Lst::find(
			function ( $row ) use ( $translationGroup, $language ) {
				return (
					$row[ $this->importIndexes[ $this->translationGroupKey ] ] === $translationGroup
					&& $row[ $this->importIndexes[ $this->languageKey ] ] === $language
				);
			},
			$this->importRawData
		);
	}

	private function setimportValues() {
		$this->importValues = $this->getImportFieldDefaults();

		if ( ! $this->importRow ) {
			return;
		}

		$callback = function ( &$value, $key ) {
			$importIndex = $this->findColumnIndex( $key );
			if ( false !== $importIndex ) {
				$value = $this->importRow[ $importIndex ];
			}
		};
		array_walk( $this->importValues, $callback );
	}

	/**
	 * @return bool
	 */
	protected function hasImportRow() {
		return (bool) $this->importRow;
	}

	/**
	 * @param  string $key
	 *
	 * @return mixed
	 */
	protected function getImportValue( $key ) {
		return $this->importValues[ $key ] ?? false;
	}

	/**
	 * @param  array  $data
	 * @param  string $key
	 *
	 * @return string
	 */
	protected function getMetaValue( $data, $key ) {
		$match = Lst::find(
			function ( $item ) use ( $key ) {
				return $key === $item['key'];
			},
			$data
		);

		return $match['value'] ?? '';
	}

	/**
	 * @return array
	 */
	protected function getRelativeFields() {
		if ( null === $this->relativeFields ) {
			$this->relativeFields = [
				'parent_id' => __( 'Parent', 'woocommerce' ),
			];
		}
		return $this->relativeFields;
	}

	/**
	 * @return array
	 */
	protected function getRelativeCommaFields() {
		if ( null === $this->relativeCommaFields ) {
			$this->relativeCommaFields = [
				'children'       => __( 'Grouped products', 'woocommerce' ),
				'upsell_ids'     => __( 'Upsells', 'woocommerce' ),
				'cross_sell_ids' => __( 'Cross-sells', 'woocommerce' ),
			];
		}
		return $this->relativeCommaFields;
	}

	/**
	 * @param  array  $data
	 * @param  string $language
	 *
	 * @return array
	 */
	protected function manageRelatedProducts( $data, $language ) {
		$relativeFields = $this->getRelativeFields();
		array_walk(
			$relativeFields,
			function ( $keyInCsv, $keyInData ) use ( &$data, $language ) {
				if ( $this->hasRelatedFieldValue( $keyInCsv ) ) {
					$data[ $keyInData ] = $this->manageSimpleRelatedProduct( $this->importValues[ $keyInCsv ], $language, $data[ $keyInData ] );
				}
			}
		);

		$relativeCommaFields = $this->getRelativeCommaFields();
		array_walk(
			$relativeCommaFields,
			function ( $keyInCsv, $keyInData ) use ( &$data, $language ) {
				if ( $this->hasRelatedFieldValue( $keyInCsv ) ) {
					$data[ $keyInData ] = array_filter( $this->manageMultipleRelatedProducts( $this->importValues[ $keyInCsv ], $language, $data[ $keyInData ] ) );
				}
			}
		);

		return $data;
	}

	/**
	 * @param  string $fieldKey
	 *
	 * @return bool
	 */
	private function hasRelatedFieldValue( $fieldKey ) {
		if (
			array_key_exists( $fieldKey, $this->importValues )
			&& $this->importValues[ $fieldKey ]
		) {
			return true;
		}
		return false;
	}

	/**
	 * @param  string $originalValue
	 * @param  string $language
	 * @param  int    $processedValue
	 *
	 * @return int
	 */
	abstract protected function manageSimpleRelatedProduct( $originalValue, $language, $processedValue );

	/**
	 * @param  string $value
	 * @param  string $language
	 * @param  array  $processedValues
	 *
	 * @return array
	 */
	protected function manageMultipleRelatedProducts( $value, $language, $processedValues ) {
		$values = $this->explodeRelatedCommaField( $value );
		if ( count( $values ) !== count( $processedValues ) ) {
			return $processedValues;
		}
		$callback = function ( &$value, $key ) use ( $language, $processedValues ) {
			$value = $this->manageSimpleRelatedProduct( $value, $language, $processedValues[ $key ] );
		};
		array_walk( $values, $callback );

		return $values;
	}

	/**
	 * @see WC_Product_Importer::explode_values
	 *
	 * @param  string $value
	 *
	 * @return array
	 */
	private function explodeRelatedCommaField( $value ) {
		$value  = str_replace( '\\,', '::separator::', $value );
		$values = explode( ',', $value );

		return array_map(
			function ( $item ) {
				return trim( str_replace( '::separator::', ',', $item ) );
			},
			$values
		);
	}

	/**
	 * @param  string $fieldValue
	 *
	 * @return bool
	 */
	protected function shouldUpdateRelatedFieldValue( $fieldValue ) {
		if ( 0 !== strpos( $fieldValue, 'id:' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @param  string $sku
	 * @param  string $language
	 *
	 * @return int
	 */
	protected function getProductIdBySkuAndLanguage( $sku, $language ) {
		return Language::switchAndRun(
			$language,
			function () use ( $sku ) {
				$args    = [
					'post_type'              => [
						'product',
						'product_variation',
					],
					'meta_query'             => [
						[
							'key'   => self::SKU_META_KEY,
							'value' => $sku,
						],
					],
					'posts_per_page'         => 1,
					'cache_results'          => false,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'fields'                 => 'ids',
				];
				$query   = new \WP_Query( $args );
				$results = $query->posts;

				return $results[0] ?? 0;
			}
		);
	}
}
