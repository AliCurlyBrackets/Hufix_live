<?php

namespace WPML\Import\Commands;

use WPML\Collect\Support\Collection;
use WPML\Import\Fields;
use WPML\Import\Helper\Taxonomies;

// phpcs:disable Squiz.Strings.DoubleQuoteUsage.NotRequired
// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.LikeWithoutWildcards
// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnescapedLiteral

/**
 * Replaces post/term assignments when the languages are
 * not matching and a term in the same language exists.
 *
 * This applies only to translatable terms.
 */
class ReassignPostsToTranslatedTerms implements Base\Command {

	use Base\Query;
	use Base\HasItemsPerBatch;

	const DEFAULT_LIMIT = 20;

	/**
	 * @var \wpdb $wpdb
	 */
	protected $wpdb;

	public function __construct( \wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * @return string
	 */
	public static function getTitle() {
		return __( 'Connecting Posts with Correct Term Translations', 'wpml-import' );
	}

	/**
	 * @return string
	 */
	public static function getDescription() {
		return __( 'Ensuring posts are connected to terms in the correct language, especially if you have the same term names across languages.', 'wpml-import' );
	}

	/**
	 * @codeCoverageIgnore
	 *
	 * @param Collection|null $args
	 *
	 * @return int
	 */
	public function countPendingItems( Collection $args = null ) {
		return (int) count( $this->getPendingItems( PHP_INT_MAX ) );
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

		$termsByTax = [];

		foreach ( $items as $item ) {
			// First remove possible duplicate already existing in the right language.
			$this->wpdb->delete(
				$this->wpdb->term_relationships,
				[
					'object_id'        => $item->post_id,
					'term_taxonomy_id' => $item->ttid_in_post_lang,
				]
			);

			$this->wpdb->update(
				$this->wpdb->term_relationships,
				[
					'term_taxonomy_id' => $item->ttid_in_post_lang,
				],
				[
					'object_id'        => $item->post_id,
					'term_taxonomy_id' => $item->ttid_in_wrong_lang,
				]
			);

			if ( ! isset( $termsByTax[ $item->taxonomy ] ) ) {
				$termsByTax[ $item->taxonomy ] = [];
			}

			$termsByTax[ $item->taxonomy ][] = $item->ttid_in_wrong_lang;
			$termsByTax[ $item->taxonomy ][] = $item->ttid_in_post_lang;
		}

		foreach ( $termsByTax as $taxonomy => $ttids ) {
			wp_update_term_count_now( $ttids, $taxonomy );
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
			    term_rel.object_id AS post_id,
				term_rel.term_taxonomy_id AS ttid_in_wrong_lang,
				term_tr2.element_id AS ttid_in_post_lang,
				term_tax.taxonomy AS taxonomy
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
				AND term_tr2.element_id IS NOT NULL
			",
			$limit
		);
	}
}
// phpcs:enable
