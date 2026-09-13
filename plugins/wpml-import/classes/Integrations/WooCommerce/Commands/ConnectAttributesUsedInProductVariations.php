<?php

namespace WPML\Import\Integrations\WooCommerce\Commands;

use WPML\Collect\Support\Collection;
use WPML\Import\Commands\Base\Command;
use WPML\Import\Commands\Base\Query;
use WPML\Import\Commands\Base\TemporaryTermFields;
use WPML\Import\Commands\Base\HasItemsPerBatch;
use WPML\Import\Fields;
use WPML\Import\Helper\Taxonomies;

class ConnectAttributesUsedInProductVariations implements Command, TemporaryTermFields {

	use Query;
	use HasItemsPerBatch;

	const DEFAULT_LIMIT = 100;

	const FIELD_TEMPORARY_ATTEMPT_RECONNECT_ATTRIBUTE = '_wpml_import_attempt_reconnect_wc_attribute';

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
		return __( 'Linking Product Attribute Translations', 'wpml-import' );
	}

	/**
	 * @return string
	 */
	public static function getDescription() {
		return __( 'Connecting product attributes to their translations based on associated product variations.', 'wpml-import' );
	}

	/**
	 * @param Collection|null $args
	 *
	 * @return int
	 */
	public function countPendingItems( Collection $args = null ) {
		$sql = $this->getQuery( 'COUNT(*)' );
		if ( null === $sql ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return (int) $this->wpdb->get_var( $sql );
	}

	/**
	 * @param Collection|null $args
	 *
	 * @return int Number of processed items.
	 */
	public function run( Collection $args = null ) {
		$pendingBefore   = $this->countPendingItems();
		$defaultLanguage = $this->sitepress->get_default_language();

		foreach ( $this->getPendingItemGroups( $this->filterNumberOfItemsPerBatch( self::DEFAULT_LIMIT ) ) as $groupedItems ) {
			if ( isset( $groupedItems[ $defaultLanguage ] ) ) {
				$originalAttribute = $groupedItems[ $defaultLanguage ];
			} else {
				$originalAttribute = reset( $groupedItems );
			}

			foreach ( $groupedItems as $attribute ) {
				add_term_meta( $attribute->attribute_term_id, self::FIELD_TEMPORARY_ATTEMPT_RECONNECT_ATTRIBUTE, 1, true );

				if ( $attribute->attribute_ttid === $originalAttribute->attribute_ttid ) {
					continue;
				}

				$this->sitepress->set_element_language_details(
					$attribute->attribute_ttid,
					'tax_pa_' . $attribute->attribute_name,
					$originalAttribute->attribute_trid,
					$attribute->attribute_language_code,
					$originalAttribute->attribute_source_language_code
				);
			}
		}

		// Return the actual delta in pending items, since marking a single term
		// can cascade-exclude many raw query rows that reference it.
		return max( 0, $pendingBefore - $this->countPendingItems() );
	}

	/**
	 * @param int|null $limit
	 *
	 * @return array
	 */
	private function getPendingItems( $limit = null ) {
		$sql = $this->getQuery(
			"
			tt.term_taxonomy_id AS attribute_ttid,
			tt.term_id AS attribute_term_id,
			REPLACE( pmattr.meta_key, 'attribute_pa_', '' ) AS attribute_name,
			pmattr.meta_value AS attribute_value,
			ptr.trid AS product_trid,
			atr.trid AS attribute_trid,
			atr.language_code AS attribute_language_code,
			atr.source_language_code AS attribute_source_language_code
			",
			$limit,
			'ptr.trid ASC, atr.language_code ASC, tt.term_taxonomy_id ASC'
		);
		if ( null === $sql ) {
			return [];
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return (array) $this->wpdb->get_results( $sql );
	}

	/**
	 * Composes the full SQL used by both countPendingItems() and getPendingItems().
	 *
	 * @param string      $fields SELECT list (e.g. 'COUNT(*)' or column expressions).
	 * @param int|null    $limit  Optional LIMIT.
	 * @param string|null $order  Optional ORDER BY clause (without the keyword).
	 *
	 * @return string|null Full SQL, or null if there are no translatable taxonomies.
	 */
	private function getQuery( $fields, $limit = null, $order = null ) {
		$translatableTaxTypes = Taxonomies::getTranslatable( true );
		if ( empty( $translatableTaxTypes ) ) {
			return null;
		}

		$sql = "
			SELECT {$fields}
			FROM {$this->wpdb->postmeta} AS pmattr
			LEFT JOIN {$this->wpdb->terms} AS t
				ON t.slug = pmattr.meta_value
			LEFT JOIN {$this->wpdb->term_taxonomy} AS tt
				ON tt.term_id = t.term_id AND tt.taxonomy = CONCAT( 'pa_', REPLACE( pmattr.meta_key, 'attribute_pa_', '' ) )
			LEFT JOIN {$this->wpdb->termmeta} AS tm
				ON tm.term_id = tt.term_id AND tm.meta_key = '" . self::FIELD_TEMPORARY_ATTEMPT_RECONNECT_ATTRIBUTE . "'
			LEFT JOIN {$this->wpdb->posts} AS p
				ON p.ID = pmattr.post_id
			LEFT JOIN {$this->wpdb->postmeta} AS pm
				ON pm.post_id = p.ID AND pm.meta_key = '" . Fields::TRANSLATION_GROUP . "'
			LEFT JOIN {$this->wpdb->prefix}icl_translations AS ptr
				ON ptr.element_id = p.ID AND ptr.element_type = 'post_product_variation'
			LEFT JOIN {$this->wpdb->prefix}icl_translations AS atr
				ON atr.element_id = tt.term_taxonomy_id AND atr.element_type = CONCAT( 'tax_pa_', REPLACE( pmattr.meta_key, 'attribute_pa_', '' ) )
			WHERE p.post_type = 'product_variation'
				AND pm.meta_value IS NOT NULL
				AND atr.source_language_code IS NULL
				AND tm.meta_value IS NULL
				AND atr.element_type IN(" . wpml_prepare_in( $translatableTaxTypes ) . ")
				AND pmattr.meta_key LIKE '" . $this->wpdb->esc_like( 'attribute_pa_' ) . "%'
		";

		if ( $order ) {
			$sql .= " ORDER BY {$order}";
		}

		$limit = (int) $limit;
		if ( $limit > 0 ) {
			$sql .= ' LIMIT ' . $limit;
		}

		return $sql;
	}

	/**
	 * @param int $limit
	 *
	 * @return array[]
	 */
	private function getPendingItemGroups( $limit ) {
		$activeLanguages = $this->sitepress->get_active_languages();
		$limit           = max( 1, (int) $limit, count( $activeLanguages ) );

		$items    = $this->getPendingItems( $limit + 1 );
		$sentinel = $this->extractSentinelItem( $items, $limit );

		$itemsGroups = [];

		foreach ( $items as $item ) {
			if ( ! array_key_exists( $item->product_trid, $itemsGroups ) ) {
				$itemsGroups[ $item->product_trid ] = [];
			}

			$itemsGroups[ $item->product_trid ][ $item->attribute_language_code ] = $item;
		}

		return $this->removeIncompleteLastGroup( $itemsGroups, $sentinel );
	}

	/**
	 * @param array $items
	 * @param int   $limit
	 *
	 * @return object|null
	 */
	private function extractSentinelItem( array &$items, $limit ) {
		if ( count( $items ) <= $limit ) {
			return null;
		}

		return array_pop( $items );
	}

	/**
	 * @param array       $itemsGroups
	 * @param object|null $sentinel
	 *
	 * @return array
	 */
	private function removeIncompleteLastGroup( array $itemsGroups, $sentinel ) {
		// Never empty the result — always guarantee at least one group for progress.
		if ( ! $sentinel || empty( $itemsGroups ) || count( $itemsGroups ) <= 1 ) {
			return $itemsGroups;
		}

		end( $itemsGroups );
		$lastProductTrid = key( $itemsGroups );
		reset( $itemsGroups );

		// Same product TRID means the sentinel is a continuation of the last group.
		if ( (string) $lastProductTrid === (string) $sentinel->product_trid ) {
			array_pop( $itemsGroups ); // Remove the last group to prevent incomplete ones.
		}

		return $itemsGroups;
	}

	/**
	 * @return string[]
	 */
	public static function getTemporaryTermFields() {
		return [
			self::FIELD_TEMPORARY_ATTEMPT_RECONNECT_ATTRIBUTE,
		];
	}
}
