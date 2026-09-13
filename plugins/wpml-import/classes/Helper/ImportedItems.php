<?php

namespace WPML\Import\Helper;

use WPML\Import\Fields;

class ImportedItems {

	/**
	 * @var \wpdb $wpdb
	 */
	private $wpdb;

	public function __construct( \wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * @return int
	 */
	public function countPosts() {
		return $this->countItems( $this->wpdb->postmeta );
	}

	/**
	 * @return int
	 */
	public function countTerms() {
		return $this->countItems( $this->wpdb->termmeta );
	}

	/**
	 * @param string $table
	 *
	 * @return int
	 */
	private function countItems( $table ) {
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $this->wpdb->get_var(
			"
			SELECT COUNT(*) FROM {$table}
			WHERE meta_key = '" . Fields::TRANSLATION_GROUP . "'
			"
		);
		// phpcs:enable
	}
}
