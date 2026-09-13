<?php

namespace WPML\Import\Commands;

use WPML\Collect\Support\Collection;
use WPML\FP\Fns;
use WPML\Import\Commands\Base\HasItemsPerBatch;
use WPML\Import\Commands\Base\Query;
use WPML\Import\Fields;

// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.LikeWithoutWildcards
// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnescapedLiteral
class ConnectTermTranslationsByPostsWithOnlyOneAssignment implements Base\Command, Base\TemporaryTermFields {

	use Query;
	use HasItemsPerBatch;

	const DEFAULT_LIMIT = 20;


	const FIELD_TEMPORARY_ATTEMPT_RECONNECT_TERM = '_wpml_import_attempt_reconnect_term';

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
		return __( 'Connecting Inline Term Translations', 'wpml-import' );
	}

	/**
	 * @return string
	 */
	public static function getDescription() {
		return __( 'Scanning posts with a single taxonomy term in order to identify and connect the terms assigned to these posts with their translations.', 'wpml-import' );
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
		$items = $this->getPendingItems( $this->filterNumberOfItemsPerBatch( self::DEFAULT_LIMIT ) );

		foreach ( $items as $item ) {
			// Prevent processing the same term over and over.
			add_term_meta( $item->term_id, self::FIELD_TEMPORARY_ATTEMPT_RECONNECT_TERM, 1 );

			$originalPost = $this->sitepress->get_original_element_translation( $item->post_trid, $item->wpml_post_type );

			if ( $originalPost ) {
				$originalTerms = $this->getTaxonomyTermsForOriginalPost( $originalPost->element_id, $item->taxonomy, $item->term_language_code );

				if ( count( $originalTerms ) === 1 ) {
					$originalTerm = reset( $originalTerms );

					$this->connectOldTranslationGroupWithNewOne( $item, $originalTerm );
				}
			}
		}

		return count( $items );
	}

	/**
	 * If the moved ttid was already connected to another translation,
	 * we also need to move other translations from the old to the new group.
	 *
	 * @param object $item
	 * @param object $originalTerm
	 *
	 * @return void
	 */
	private function connectOldTranslationGroupWithNewOne( $item, $originalTerm ) {
		$elementType = 'tax_' . $item->taxonomy;

		/**
		 * There is old language information cached from a previous call.
		 * Leverage the $skip_cache argument to avoid problems.
		 *
		 * @see https://onthegosystems.myjetbrains.com/youtrack/issue/wpmldev-5640/
		 */
		$oldGroupTranslations = $this->sitepress->get_element_translations( $item->term_trid, $elementType, false, false, true );
		$newGroupTranslations = $this->sitepress->get_element_translations( $originalTerm->trid, $elementType );

		foreach ( $oldGroupTranslations as $lang => $oldGroupTranslation ) {
			if ( ! isset( $newGroupTranslations[ $lang ] ) ) {
				$this->sitepress->set_element_language_details(
					$oldGroupTranslation->element_id,
					$elementType,
					$originalTerm->trid,
					$oldGroupTranslation->language_code,
					$originalTerm->language_code
				);
			}
		}
	}

	/**
	 * @param int|null $limit
	 *
	 * @return array
	 */
	private function getPendingItems( $limit = null ) {
		$defaultLanguage = $this->sitepress->get_default_language();

		$items = $this->getResultsWithLimit(
			$this->wpdb->prepare(
				"
				SELECT
					pm.post_id AS post_id,
					MIN(iclptr.trid) AS post_trid,
					MIN(iclptr.element_type) AS wpml_post_type,
					tt.taxonomy AS taxonomy,
					icltr.language_code AS term_language_code,
					MIN(tt.term_taxonomy_id) AS ttid,
					MIN(tt.term_id) AS term_id,
					MIN(icltr.trid) AS term_trid
				FROM {$this->wpdb->postmeta} AS pm
				LEFT JOIN {$this->wpdb->term_relationships} AS tr
					ON tr.object_id = pm.post_id
				LEFT JOIN {$this->wpdb->term_taxonomy} AS tt
					ON tt.term_taxonomy_id = tr.term_taxonomy_id
				LEFT JOIN {$this->wpdb->termmeta} AS tm
				    ON tm.term_id = tt.term_id AND tm.meta_key = '" . self::FIELD_TEMPORARY_ATTEMPT_RECONNECT_TERM . "'
				LEFT JOIN {$this->wpdb->prefix}icl_translations AS icltr
					ON icltr.element_id = tt.term_taxonomy_id AND icltr.element_type LIKE '" . $this->wpdb->esc_like( 'tax_' ) . "%'
				LEFT JOIN {$this->wpdb->prefix}icl_translations AS iclptr
					ON iclptr.element_id = pm.post_id AND iclptr.element_type LIKE '" . $this->wpdb->esc_like( 'post_' ) . "%'
				WHERE pm.meta_key = '" . Fields::TRANSLATION_GROUP . "'
					AND icltr.language_code != %s
					AND icltr.language_code = iclptr.language_code
					AND icltr.trid IS NOT NULL
					AND icltr.source_language_code IS NULL
				GROUP BY post_id, taxonomy, term_language_code
				HAVING COUNT(pm.post_id) = 1
					AND MAX(tm.meta_value) IS NULL
				ORDER BY post_id ASC
				",
				$defaultLanguage
			),
			$limit
		);

		/**
		 * @param object[] $carry
		 * @param object   $item
		 *
		 * @return object[]
		 */
		$keepOnlyTheFirstOccurrenceOfAssignment = Fns::reduce(
			function ( $carry, $item ) {
				if ( ! isset( $carry[ $item->ttid ] ) ) {
					$carry[ $item->ttid ] = $item;
				}

				return $carry;
			},
			[]
		);

		return $keepOnlyTheFirstOccurrenceOfAssignment( $items );
	}

	/**
	 * @param int    $postId
	 * @param string $taxonomy
	 * @param string $lang
	 *
	 * @return array
	 */
	private function getTaxonomyTermsForOriginalPost( $postId, $taxonomy, $lang ) {
		return (array) $this->wpdb->get_results(
			$this->wpdb->prepare(
				"
				SELECT
					tr.term_taxonomy_id AS ttid,
					icltr.trid AS trid,
					icltr.language_code AS language_code
				FROM {$this->wpdb->term_relationships} AS tr
				LEFT JOIN {$this->wpdb->term_taxonomy} AS tt
					ON tt.term_taxonomy_id = tr.term_taxonomy_id
				LEFT JOIN {$this->wpdb->prefix}icl_translations AS icltr
					ON icltr.element_id = tt.term_taxonomy_id AND icltr.element_type LIKE '" . $this->wpdb->esc_like( 'tax_' ) . "%'
				LEFT JOIN {$this->wpdb->prefix}icl_translations AS icltr2
					ON icltr2.trid = icltr.trid AND icltr2.language_code = %s
				LEFT JOIN {$this->wpdb->prefix}icl_translations AS iclptr
					ON iclptr.element_id = tr.object_id AND iclptr.element_type LIKE '" . $this->wpdb->esc_like( 'post_' ) . "%'
				WHERE tr.object_id = %d
					AND icltr.source_language_code IS NULL
					AND icltr2.language_code IS NULL
					AND icltr.language_code = iclptr.language_code
					AND tt.taxonomy = %s
				",
				$lang,
				$postId,
				$taxonomy
			)
		);
	}

	/**
	 * @return string[]
	 */
	public static function getTemporaryTermFields() {
		return [
			self::FIELD_TEMPORARY_ATTEMPT_RECONNECT_TERM,
		];
	}
}
// phpcs:enable
