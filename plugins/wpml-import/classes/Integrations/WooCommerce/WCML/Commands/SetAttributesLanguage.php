<?php

namespace WPML\Import\Integrations\WooCommerce\WCML\Commands;

use WPML\Collect\Support\Collection;
use WPML\FP\Fns;
use WPML\FP\Just;
use WPML\FP\Obj;
use WPML\Import\Commands\Base\Command;
use WPML\Import\Commands\Base\Query;
use WPML\Import\Commands\Base\TemporaryTermFields;
use WPML\Import\Commands\Base\HasItemsPerBatch;
use WPML\Import\Fields;
use WPML\Import\Helper\Taxonomies;

class SetAttributesLanguage implements Command, TemporaryTermFields {

	use Query;
	use HasItemsPerBatch;

	const DEFAULT_LIMIT = 100;

	const FIELD_TEMPORARY_ASSIGN_ATTRIBUTE_LANGUAGE = '_wpml_import_assigned_attribute_language';

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
		return __( 'Identifying Product Attribute Languages', 'wpml-import' );
	}

	/**
	 * @return string
	 */
	public static function getDescription() {
		return __( 'Identifying and setting the language of attributes created during product imports.', 'wpml-import' );
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
	 * @codeCoverageIgnore
	 *
	 * @param Collection|null $args
	 *
	 * @return int
	 */
	public function run( Collection $args = null ) {
		$items = $this->getPendingItems( $this->filterNumberOfItemsPerBatch( self::DEFAULT_LIMIT ) );
		foreach ( $items as $item ) {
			$this->sitepress->set_element_language_details( $item['ttid'], 'tax_' . $item['taxonomy'], 0, $item['newTtidLang'] );
			add_term_meta( $item['term_id'], self::FIELD_TEMPORARY_ASSIGN_ATTRIBUTE_LANGUAGE, 1 );
		}

		return count( $items );
	}

	private function getAttributeTaxonomies() {
		$attributes   = wc_get_attribute_taxonomies();
		$syncSettings = $this->sitepress->get_setting( 'taxonomies_sync_option', [] );

		$getAttributeName = Obj::prop( 'attribute_name' );
		$getTaxonomyName  = 'wc_attribute_taxonomy_name';
		$isTranslatable   = function ( $attributeName ) use ( $syncSettings ) {
			return Obj::propOr( false, $attributeName, $syncSettings );
		};

		return wpml_collect( $attributes )
			->map( $getAttributeName )
			->map( $getTaxonomyName )
			->filter( $isTranslatable )
			->values()
			->toArray();
	}

	/**
	 * @codeCoverageIgnore
	 *
	 * @param int $limit
	 *
	 * @return array
	 */
	private function getPendingItems( $limit = null ) {
		$attributeTaxonomies = $this->getAttributeTaxonomies();
		$defaultLanguage     = $this->sitepress->get_default_language();

		if ( empty( $attributeTaxonomies ) ) {
			return [];
		}

		$items = $this->getResultsWithLimit(
			"
			SELECT DISTINCT
				tr.term_taxonomy_id AS ttid,
				tt.term_id AS term_id,
				tt.taxonomy AS taxonomy,
				iclptr.language_code AS post_language_code
			FROM {$this->wpdb->term_relationships} AS tr
			LEFT JOIN {$this->wpdb->term_taxonomy} AS tt
				ON tt.term_taxonomy_id = tr.term_taxonomy_id
			LEFT JOIN {$this->wpdb->postmeta} AS pm
				ON pm.post_id = tr.object_id
			LEFT JOIN {$this->wpdb->termmeta} AS tm
					ON tm.term_id = tt.term_id AND tm.meta_key = '" . self::FIELD_TEMPORARY_ASSIGN_ATTRIBUTE_LANGUAGE . "'
			LEFT JOIN {$this->wpdb->prefix}icl_translations AS iclttr
				ON iclttr.element_id = tr.term_taxonomy_id AND iclttr.element_type LIKE '" . $this->wpdb->esc_like( 'tax_' ) . "%'
			LEFT JOIN {$this->wpdb->prefix}icl_translations AS iclptr
				ON iclptr.element_id = pm.post_id AND iclptr.element_type LIKE '" . $this->wpdb->esc_like( 'post_' ) . "%'
			WHERE tt.taxonomy IN(" . wpml_prepare_in( $attributeTaxonomies ) . ")
				AND iclttr.translation_id IS NULL	
				AND pm.meta_key = '" . Fields::TRANSLATION_GROUP . "'
				AND tm.meta_value IS NULL
			GROUP BY iclptr.language_code, tr.term_taxonomy_id
			ORDER BY tr.term_taxonomy_id ASC
			",
			$limit
		);

		/**
		 * @param array  $carry
		 * @param object $item
		 *
		 * @return array
		 */
		$groupPossibleLangsForTtids = function ( $carry, $item ) {
			if ( ! isset( $carry[ $item->ttid ] ) ) {
				$carry[ $item->ttid ] = [
					'ttid'        => $item->ttid,
					'term_id'     => $item->term_id,
					'taxonomy'    => $item->taxonomy,
					'targetLangs' => [ $item->post_language_code ],
				];
			}

			if ( ! in_array( $item->post_language_code, $carry[ $item->ttid ]['targetLangs'], true ) ) {
				$carry[ $item->ttid ]['targetLangs'][] = $item->post_language_code;
			}

			return $carry;
		};

		/**
		 * @param array $item
		 *
		 * @return array
		 */
		$mapTtidToNewLanguage = function ( $item ) use ( $defaultLanguage ) {
			return [
				'ttid'        => $item['ttid'],
				'term_id'     => $item['term_id'],
				'taxonomy'    => $item['taxonomy'],
				'newTtidLang' => in_array( $defaultLanguage, $item['targetLangs'], true ) ? $defaultLanguage : reset( $item['targetLangs'] ),
			];
		};

		return Just::of( $items )
			->map( Fns::reduce( $groupPossibleLangsForTtids, [] ) )
			->map( Fns::map( $mapTtidToNewLanguage ) )
			->get();
	}

	/**
	 * @return string[]
	 */
	public static function getTemporaryTermFields() {
		return [
			self::FIELD_TEMPORARY_ASSIGN_ATTRIBUTE_LANGUAGE,
		];
	}
}
