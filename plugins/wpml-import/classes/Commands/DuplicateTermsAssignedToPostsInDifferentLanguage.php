<?php

namespace WPML\Import\Commands;

use WPML\Collect\Support\Collection;
use WPML\FP\Obj;
use WPML\Import\Fields;
use WPML\Import\Helper\Language;
use WPML\Import\Helper\Taxonomies;

// phpcs:disable Squiz.Strings.DoubleQuoteUsage.NotRequired
// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.LikeWithoutWildcards
// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnescapedLiteral

/**
 * When a post is assigned to a term in a different
 * language and the term does not have any translation
 * in that language, we'll duplicate the term for that
 * language.
 *
 * This applies only to translatable terms.
 *
 * Note: Another command will take care of the reassignment.
 *
 * @see ReassignPostsToTranslatedTerms
 */
class DuplicateTermsAssignedToPostsInDifferentLanguage implements Base\Command, Base\TemporaryTermFields {

	use Base\Query;
	use Base\HasItemsPerBatch;

	const DEFAULT_LIMIT = 20;

	const FIELD_TEMPORARY_DUPLICATED_TERM = '_wpml_import_duplicated_term';

	/**
	 * @var \wpdb $wpdb
	 */
	protected $wpdb;

	/**
	 * @var \SitePress $sitepress
	 */
	private $sitepress;

	public function __construct( \wpdb $wpdb, \SitePress $sitepress ) {
		$this->wpdb      = $wpdb;
		$this->sitepress = $sitepress;
	}

	/**
	 * @return string
	 */
	public static function getTitle() {
		return __( 'Duplicating Terms with Shared Names Across Languages', 'wpml-import' );
	}

	/**
	 * @return string
	 */
	public static function getDescription() {
		return __( 'Creating duplicates in the correct language for terms that share the same name across languages, but belong to posts in different languages.', 'wpml-import' );
	}

	/**
	 * @codeCoverageIgnore
	 *
	 * @param Collection|null $args
	 *
	 * @return int
	 */
	public function countPendingItems( Collection $args = null ) {
		return (int) count( $this->getPendingItems() );
	}

	/**
	 * @codeCoverageIgnore
	 *
	 * @param Collection|null $args
	 *
	 * @return int Number of processed items.
	 */
	public function run( Collection $args = null ) {
		$items = $this->getPendingItems( $this->filterNumberOfItemsPerBatch( self::DEFAULT_LIMIT ) );

		foreach ( $items as $item ) {
			$termToDuplicate = get_term_by( 'term_taxonomy_id', $item->ttid_to_duplicate );

			if ( $termToDuplicate ) {
				$termArgs = [
					'alias_of'    => '',
					'description' => $termToDuplicate->description,
					'parent'      => $this->sitepress->get_object_id( $termToDuplicate->parent, 'tax_' . $termToDuplicate->taxonomy, true, $item->target_language_code ),
					'slug'        => $termToDuplicate->slug,
				];

				$duplicatedTermTaxonomyId = Language::switchAndRun(
					$item->target_language_code,
					function () use ( $termToDuplicate, $termArgs ) {
						return Obj::prop( 'term_taxonomy_id', wp_insert_term( $termToDuplicate->name, $termToDuplicate->taxonomy, $termArgs ) );
					}
				);

				if ( $duplicatedTermTaxonomyId ) {
					$this->sitepress->set_element_language_details(
						$duplicatedTermTaxonomyId,
						'tax_' . $termToDuplicate->taxonomy,
						$item->trid,
						$item->target_language_code,
						$item->source_language_code
					);
				}

				add_term_meta( $termToDuplicate->term_id, self::FIELD_TEMPORARY_DUPLICATED_TERM, 1, true );
			}
		}

		return count( $items );
	}

	/**
	 * @codeCoverageIgnore
	 *
	 * @param int|null $limit
	 *
	 * @return array
	 */
	private function getPendingItems( $limit = null ) {
		$translatableTaxTypes = Taxonomies::getTranslatableOnly( true );
		if ( empty( $translatableTaxTypes ) ) {
			return [];
		}

		return $this->getResultsWithLimit(
			"
			SELECT DISTINCT
			    term_rel.term_taxonomy_id AS ttid_to_duplicate,
			    term_tr.trid AS trid,
			    term_tr.language_code AS source_language_code,
			    post_tr.language_code AS target_language_code,
			    term_tax.parent
			FROM {$this->wpdb->term_relationships} AS term_rel
			LEFT JOIN {$this->wpdb->term_taxonomy} AS term_tax
				ON term_tax.term_taxonomy_id = term_rel.term_taxonomy_id
			LEFT JOIN {$this->wpdb->prefix}icl_translations AS term_tr
				ON term_tr.element_id = term_rel.term_taxonomy_id AND term_tr.element_type LIKE '" . $this->wpdb->esc_like( 'tax_' ) . "%'
			LEFT JOIN {$this->wpdb->prefix}icl_translations AS post_tr
				ON post_tr.element_id = term_rel.object_id AND post_tr.element_type LIKE '" . $this->wpdb->esc_like( 'post_' ) . "%'
			RIGHT JOIN {$this->wpdb->postmeta} AS postmeta
				ON postmeta.post_id = post_tr.element_id AND postmeta.meta_key = '" . Fields::TRANSLATION_GROUP . "'
			LEFT JOIN {$this->wpdb->prefix}icl_translations AS term_tr2
				ON term_tr2.trid = term_tr.trid AND term_tr2.language_code = post_tr.language_code
			WHERE term_tr.element_type IN(" . wpml_prepare_in( $translatableTaxTypes ) . ")
				AND term_tr.language_code <> post_tr.language_code
				AND term_tr2.element_id IS NULL
			ORDER BY term_tax.parent ASC
			",
			$limit
		);
	}

	/**
	 * @return string[]
	 */
	public static function getTemporaryTermFields() {
		return [
			self::FIELD_TEMPORARY_DUPLICATED_TERM,
		];
	}
}
// phpcs:enable
