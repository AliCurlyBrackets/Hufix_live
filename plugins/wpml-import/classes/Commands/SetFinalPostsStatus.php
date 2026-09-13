<?php

namespace WPML\Import\Commands;

use WPML\Collect\Support\Collection;
use WPML\Import\Commands\Base\HasItemsPerBatch;
use WPML\Import\Commands\Base\Query;
use WPML\Import\Fields;

// phpcs:disable Squiz.Strings.DoubleQuoteUsage.NotRequired

class SetFinalPostsStatus implements Base\Command {

	use Query;
	use HasItemsPerBatch;

	const DEFAULT_LIMIT = 10;

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
		return __( 'Updating Final Post Status', 'wpml-import' );
	}

	/**
	 * @return string
	 */
	public static function getDescription() {
		return __( 'Setting the post status based on the "_wpml_import_after_process_post_status" field from the import file (if provided).', 'wpml-import' );
	}

	/**
	 * @codeCoverageIgnore
	 *
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
			$post = get_post( $item->post_id, ARRAY_A );

			if ( $post ) {
				$post['post_status'] = $item->new_status;
				wp_update_post( $post );
			}
			delete_post_meta( $item->post_id, Fields::FINAL_POST_STATUS );
		}

		return count( $items );
	}

	/**
	 * @codeCoverageIgnore
	 *
	 * @param int $limit
	 *
	 * @return array
	 */
	private function getPendingItems( $limit = null ) {
		$validStatuses = array_keys( get_post_statuses() );

		return $this->getResultsWithLimit(
			"
			SELECT
				pm.post_id AS post_id,
				pm2.meta_value AS new_status
			FROM {$this->wpdb->postmeta} AS pm
			RIGHT JOIN {$this->wpdb->postmeta} AS pm2
				ON pm2.post_id = pm.post_id
					AND pm2.meta_key = '" . Fields::FINAL_POST_STATUS . "'
			LEFT JOIN {$this->wpdb->posts} AS p
				ON p.ID = pm.post_id
			WHERE pm.meta_key = '" . Fields::TRANSLATION_GROUP . "'
				AND pm2.meta_value IN(" . wpml_prepare_in( $validStatuses ) . ")
				AND pm2.meta_value != p.post_status
			",
			$limit
		);
	}
}
// phpcs:enable Squiz.Strings.DoubleQuoteUsage.NotRequired
