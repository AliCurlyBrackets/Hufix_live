<?php
// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.LikeWildcardsInQuery

namespace WPML\Import\Commands;

use WPML\Collect\Support\Collection;
use WPML\Import\Commands\Base\HasItemsPerBatch;
use WPML\Import\Commands\Base\Query;
use WPML\Import\Fields;
use WPML\TM\AutomaticTranslation\Actions\Actions;

use function WPML\Container\make;


class SendPostsToAutomaticTranslation implements Base\Command, Base\TemporaryPostFields {

	use Query;
	use HasItemsPerBatch;

	const DEFAULT_LIMIT = 3;

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
		return __( 'Sending Posts for Automatic Translation', 'wpml-import' );
	}

	/**
	 * @return string
	 */
	public static function getDescription() {
		return __( 'Creating automatic translation jobs for posts flagged for auto-translation in the exported data.', 'wpml-import' );
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

		$itemsParam = $this->groupDataBySourceLanguageAndElementType( $items );

		/** @var Actions $actions */
		$actions = make( Actions::class );

		$result = [];
		foreach ( $itemsParam as $sourceLangCode => $slcJobs ) {
			foreach ( $slcJobs as $elementType => $jobs ) {
				$result = array_merge(
					$result,
					$actions->createNewTranslationJobs( $sourceLangCode, $jobs, $elementType )
				);
			}
		}

		return count( $result );
	}


	/**
	 * @param int|null $limit
	 *
	 * @return array
	 *
	 * Test edge case: original article not in default language
	 */
	private function getPendingItems( $limit = null ) {
		if ( ! \WPML\Setup\Option::shouldTranslateEverything() ) {
			return [];
		}

		$items = $this->getResultsWithLimit(
			$this->wpdb->prepare(
				"
				SELECT DISTINCT pm.post_id,
					tr.trid,
					tr.element_type,
					tr.language_code AS source_language_code,
					lang.code AS target_language_code
				FROM {$this->wpdb->postmeta} AS pm
				INNER JOIN {$this->wpdb->prefix}icl_translations AS tr ON tr.element_id = pm.post_id
					AND tr.element_type LIKE 'post_%'
					AND tr.source_language_code IS NULL
				INNER JOIN {$this->wpdb->prefix}icl_languages AS lang ON lang.active = 1
					AND lang.code != tr.language_code
				LEFT JOIN {$this->wpdb->prefix}icl_translations AS tr2 ON tr2.trid = tr.trid
					AND tr2.language_code = lang.code
				WHERE pm.meta_key = '" . Fields::DO_APPLY_ATE_ON_POST . "'
					AND pm.meta_value = '1'
					AND tr2.element_id IS NULL
					# during iterative imports, this helps to only take pending items, and not get stuck on the first LIMIT items
					AND tr2.trid is NULL
				ORDER BY pm.post_id ASC, lang.code ASC
				"
			),
			$limit
		);

		return $items;
	}


	/**
	 * Preparing an intermediary data structure to facilitate calling createNewTranslationJobs
	 * as few times as possible (batch creation of translation jobs heavily reduces execution time).
	 *
	 * @param array $items
	 *
	 * @return array
	 */
	private function groupDataBySourceLanguageAndElementType( $items ) {

		$newItems = [];

		foreach ( $items as $item ) {
			$slc = $item->source_language_code;
			if ( ! isset( $newItems[ $slc ] ) ) {
				$newItems[ $slc ] = [];
			}
			$et = $item->element_type;
			if ( ! isset( $newItems[ $slc ][ $et ] ) ) {
				$newItems[ $slc ][ $et ] = [];
			}
			$newItems[ $slc ][ $et ][] = [ (int) $item->post_id, $item->target_language_code ];
		}

		return $newItems;
	}

	/**
	 * @return string[]
	 */
	public static function getTemporaryPostFields() {
		return [
			Fields::DO_APPLY_ATE_ON_POST,
		];
	}
}
