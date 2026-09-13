<?php

namespace WPML\Import\Commands;

use WPML\Collect\Support\Collection;
use WPML\Import\Commands\Base\HasItemsPerBatch;
use WPML\Import\Commands\Base\Query;
use WPML\Import\Commands\Base\Command;

// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
class SetUnassignedParentTermsLanguage implements Command {

	use Query;
	use HasItemsPerBatch;

	const DEFAULT_LIMIT = 50;

	/**
	 * @var \wpdb
	 */
	protected $wpdb;

	/**
	 * @var \SitePress
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
		return __( 'Setting Parent Terms\' Language', 'wpml-import' );
	}

	/**
	 * @return string
	 */
	public static function getDescription() {
		return __( 'Assigning language to parent taxonomy terms that don\'t have one, based on the language assigned to their children.', 'wpml-import' );
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
	 * @return int
	 */
	public function run( Collection $args = null ) {
		$items     = $this->getPendingItems( $this->filterNumberOfItemsPerBatch( self::DEFAULT_LIMIT ) );
		$processed = 0;

		foreach ( $items as $item ) {
			$term      = get_term( $item->parent, $item->taxonomy );
			$trid      = $this->getTridViaChild( $item->child_ttid, $item->taxonomy );
			$childLang = $this->getLanguageByTtid( $item->child_ttid, $item->taxonomy );

			if ( $childLang && $term instanceof \WP_Term ) {
				$this->sitepress->set_element_language_details(
					$term->term_taxonomy_id,
					'tax_' . $item->taxonomy,
					$trid,
					$childLang
				);
				++$processed;
			}

			delete_term_meta( $item->parent, MarkAncestorsForParentLanguageFix::META_MARK_PARENT );

		}

		return $processed;
	}

	/**
	 * @param int|null $limit
	 *
	 * @return array
	 */
	private function getPendingItems( $limit = null ) {
		$sql = "
			SELECT
				CAST( tm.meta_value AS UNSIGNED ) AS child_ttid,
				tt_parent.term_id               AS parent,
				tt_parent.taxonomy              AS taxonomy
			FROM {$this->wpdb->termmeta} AS tm
			INNER JOIN {$this->wpdb->term_taxonomy} AS tt_parent
				ON tt_parent.term_id = tm.term_id
			WHERE tm.meta_key = '" . MarkAncestorsForParentLanguageFix::META_MARK_PARENT . "'
				AND tm.meta_value <> ''
		";

		return $this->getResultsWithLimit( $sql, $limit );
	}

	/**
	 * @param int    $childTtid
	 * @param string $taxonomy
	 *
	 * @return int
	 */
	private function getTridViaChild( $childTtid, $taxonomy ) {
		$child = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"
					SELECT trid, source_language_code
					FROM {$this->wpdb->prefix}icl_translations
					WHERE element_id = %d AND element_type = %s
				",
				$childTtid,
				'tax_' . $taxonomy
			)
		);

		if ( ! $child || ! isset( $child->trid, $child->source_language_code ) ) {
			return 0;
		}

		$originalChildTtid = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"
					SELECT element_id
					FROM {$this->wpdb->prefix}icl_translations
					WHERE trid = %d AND language_code = %s
				",
				$child->trid,
				$child->source_language_code
			)
		);

		if ( ! $originalChildTtid ) {
			return 0;
		}

		$parentTermId = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"
					SELECT parent
					FROM {$this->wpdb->term_taxonomy}
					WHERE term_taxonomy_id = %d
				",
				$originalChildTtid
			)
		);

		if ( ! $parentTermId ) {
			return 0;
		}

		$parentTtid = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"
					SELECT term_taxonomy_id
					FROM {$this->wpdb->term_taxonomy}
					WHERE term_id = %d AND taxonomy = %s
				",
				$parentTermId,
				$taxonomy
			)
		);

		if ( ! $parentTtid ) {
			return 0;
		}

		$trid = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"
					SELECT trid
					FROM {$this->wpdb->prefix}icl_translations
					WHERE element_id = %d AND element_type = %s
				",
				$parentTtid,
				'tax_' . $taxonomy
			)
		);

		return (int) $trid;
	}

	/**
	 * @param int    $ttid
	 * @param string $taxonomy
	 *
	 * @return string
	 */
	private function getLanguageByTtid( $ttid, $taxonomy ) {
		return (string) apply_filters(
			'wpml_element_language_code',
			null,
			[
				'element_id'   => $ttid,
				'element_type' => 'tax_' . $taxonomy,
			]
		);
	}
}
// phpcs:enable
