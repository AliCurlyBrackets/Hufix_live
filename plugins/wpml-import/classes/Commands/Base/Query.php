<?php

namespace WPML\Import\Commands\Base;

trait Query {

	/**
	 * @var \wpdb $wpdb
	 */
	protected $wpdb;

	/**
	 * @param string   $query
	 * @param int|null $limit
	 *
	 * @return array
	 */
	protected function getResultsWithLimit( $query, $limit ) {
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		if ( $limit ) {
			$query = $this->wpdb->prepare(
				$query . PHP_EOL . 'LIMIT %d',
				$limit
			);
		}

		return (array) $this->wpdb->get_results( $query );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
	}
}
