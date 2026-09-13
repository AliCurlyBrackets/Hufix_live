<?php

namespace WPML\Import\Integrations\WooCommerce\Commands;

use WPML\Collect\Support\Collection;
use WPML\Import\Commands\Base\Command;
use WPML\Import\Commands\Base\Query;
use WPML\Import\Commands\Base\TemporaryPostFields;
use WPML\Import\Commands\Base\HasItemsPerBatch;
use WPML\Import\Helper\PostTypes;

class ConnectRelatedProducts implements Command, TemporaryPostFields {
	// TODO Improve this command:
	// 1. The query shoudl also get the trid so we do nto need to gather it afterwards. What a waste.
	// 2. The current command translates existing related products on translations, but it does not sync missing related products on translations from the product in the original language, which was a goal!
	// If we manage 2, we can reduce the logic on the import/create class since we only need to adjust the items in the default language (?). Oh no, nevermind.
	// But it is important anyway.
	use Query;
	use HasItemsPerBatch;

	const DEFAULT_LIMIT = 100;

	const FIELD_TEMPORARY_CONNECT_RELATED_PRODUCTS = '_wpml_import_connect_wc_related';

	const UPSELL_META_KEY    = '_upsell_ids';
	const CROSSSELL_META_KEY = '_crosssell_ids';
	const CHILDREN_META_KEY  = '_children';

	/** @var array */
	private $groups = [];

	/** @var array */
	private $processed = [];

	/** @var string */
	private $defaultLanguage;

	/**
	 * @var \wpdb $wpdb
	 */
	protected $wpdb;

	/**
	 * @var \SitePress $sitepress
	 */
	protected $sitepress;

	public function __construct( \wpdb $wpdb, \SitePress $sitepress ) {
		$this->wpdb      = $wpdb;
		$this->sitepress = $sitepress;
	}


	/**
	 * @return string
	 */
	public static function getTitle() {
		return __( 'Updating Related Products On Translations', 'wpml-import' );
	}

	/**
	 * @return string
	 */
	public static function getDescription() {
		return __( 'Setting up-sells, cross-sells and grouped products references in the right language.', 'wpml-import' );
	}

	/**
	 * @param Collection|null $args
	 *
	 * @return int
	 */
	public function countPendingItems( Collection $args = null ) {
		return count( $this->getPendingItems() );
	}

	/**
	 * @param Collection|null $args
	 *
	 * @return int Number of processed items.
	 */
	public function run( Collection $args = null ) {
		$countProcessed        = 0;
		$this->defaultLanguage = $this->sitepress->get_default_language();

		$items = $this->getPendingItems( $this->filterNumberOfItemsPerBatch( self::DEFAULT_LIMIT ) );

		foreach ( $items as $item ) {
			$this->processFields( $item );
			add_post_meta( $item->element_id, self::FIELD_TEMPORARY_CONNECT_RELATED_PRODUCTS, 1 );
			++$countProcessed;
		}

		return $countProcessed;
	}

	/**
	 * @param  string $fieldKey
	 * @param  object $item
	 *
	 * @return string|null
	 */
	private function getFieldValue( $fieldKey, $item ) {
		switch ( $fieldKey ) {
			case self::UPSELL_META_KEY:
				return $item->upsells;
			case self::CROSSSELL_META_KEY:
				return $item->crosssells;
			case self::CHILDREN_META_KEY:
				return $item->children;
		}
		return null;
	}

	/**
	 * @param object $item
	 */
	private function processFields( $item ) {
		$fields = [
			self::UPSELL_META_KEY,
			self::CROSSSELL_META_KEY,
			self::CHILDREN_META_KEY,
		];
		array_walk(
			$fields,
			function ( $fieldKey ) use ( $item ) {
				$this->processField( $fieldKey, $item );
			}
		);
	}

	/**
	 * @param string $fieldKey
	 * @param object $item
	 */
	private function processField( $fieldKey, $item ) {
		$fieldValue = $this->getFieldValue( $fieldKey, $item );
		if ( empty( $fieldValue ) ) {
			return;
		}

		$values           = maybe_unserialize( $fieldValue );
		$translatedValues = array_unique(
			array_filter(
				array_map(
					function ( $idToP ) use ( $item ) {
						if ( in_array( $idToP, $this->processed, true ) ) {
							$trId = $this->findTranslationGroup( $idToP );
						} else {
							$trId = $this->generateTranslationGroup( $idToP );
						}
						return $this->getTranslationId( $trId, $item->language_code, $item->post_type );
					},
					$values
				)
			)
		);

		if ( empty( $translatedValues ) ) {
			$this->wpdb->delete(
				$this->wpdb->postmeta,
				[
					'post_id'    => $item->element_id,
					'meta_key'   => $fieldKey,
					'meta_value' => $fieldValue,
				]
			);

			return;
		}

		if (
			count( $values ) === count( $translatedValues )
			&& empty( array_diff( $values, $translatedValues ) )
		) {
			return;
		}

		$this->wpdb->update(
			$this->wpdb->postmeta,
			[
				'meta_key'   => $fieldKey,
				'meta_value' => maybe_serialize( $translatedValues ),
			],
			[
				'post_id'    => $item->element_id,
				'meta_key'   => $fieldKey,
				'meta_value' => $fieldValue,
			]
		);
	}

	/**
	 * @param  int $elementId
	 *
	 * @return int|null
	 */
	private function findTranslationGroup( $elementId ) {
		foreach ( $this->groups as $groupTrId => $elements ) {
			$elementIds = array_values( $elements );
			if ( in_array( $elementId, $elementIds, true ) ) {
				return $groupTrId;
			}
		}
		return null;
	}

	/**
	 * @param  int $elementId
	 *
	 * @return int|null
	 */
	private function generateTranslationGroup( $elementId ) {
		$elementType = 'post_' . get_post_type( $elementId );
		$trId        = $this->sitepress->get_element_trid( $elementId, $elementType );
		if ( empty( $trId ) ) {
			$this->processed[] = $elementId;
			return null;
		}
		$translations          = $this->sitepress->get_element_translations( $trId, $elementType );
		$this->groups[ $trId ] = [];
		foreach ( $translations as $trans ) {
			$this->groups[ $trId ][ $trans->language_code ] = (int) $trans->element_id;
			$this->processed[]                              = (int) $trans->element_id;
		}
		return $trId;
	}

	/**
	 * @param  int $trId
	 *
	 * @return int|null
	 */
	private function getOriginalLanguageId( $trId ) {
		return $this->groups[ $trId ][ $this->defaultLanguage ] ?? null;
	}

	/**
	 * @param  int    $trId
	 * @param  string $languageCode
	 * @param  string $postType
	 *
	 * @return int|null
	 */
	private function getTranslationId( $trId, $languageCode, $postType ) {
		$fallbackValue = PostTypes::isDisplayAsTranslated( $postType )
			? $this->getOriginalLanguageId( $trId )
			: null;
		return $this->groups[ $trId ][ $languageCode ] ?? $fallbackValue;
	}

	/**
	 * @param int|null $limit
	 *
	 * @return array
	 */
	private function getPendingItems( $limit = null ) {
		if (
			! PostTypes::isTranslatable( 'product' )
			&& ! PostTypes::isTranslatable( 'product_variation' )
		) {
			return [];
		}

		return $this->getResultsWithLimit(
			"
			SELECT iclptr.element_id AS element_id,
				iclptr.language_code AS language_code,
				p.post_type AS post_type,
				(SELECT meta_value FROM {$this->wpdb->postmeta} WHERE post_id = iclptr.element_id AND meta_key = '" . self::UPSELL_META_KEY . "' LIMIT 1) AS upsells,
				(SELECT meta_value FROM {$this->wpdb->postmeta} WHERE post_id = iclptr.element_id AND meta_key = '" . self::CROSSSELL_META_KEY . "' LIMIT 1) AS crosssells,
				(SELECT meta_value FROM {$this->wpdb->postmeta} WHERE post_id = iclptr.element_id AND meta_key = '" . self::CHILDREN_META_KEY . "' LIMIT 1) AS children
			FROM {$this->wpdb->prefix}icl_translations AS iclptr
			LEFT JOIN {$this->wpdb->posts} AS p
				ON p.ID = iclptr.element_id
			LEFT JOIN {$this->wpdb->postmeta} AS tpm
				ON tpm.post_id = iclptr.element_id
				AND tpm.meta_key = '" . self::FIELD_TEMPORARY_CONNECT_RELATED_PRODUCTS . "'
				AND tpm.meta_value IS NULL
			WHERE iclptr.element_type LIKE 'post_%'
			HAVING (
				upsells IS NOT NULL
				OR crosssells IS NOT NULL
				OR children IS NOT NULL
			)
			ORDER BY iclptr.element_id ASC
			",
			$limit
		);
	}

	/**
	 * @return string[]
	 */
	public static function getTemporaryPostFields() {
		return [
			self::FIELD_TEMPORARY_CONNECT_RELATED_PRODUCTS,
		];
	}
}
