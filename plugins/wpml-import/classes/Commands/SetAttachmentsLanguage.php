<?php

namespace WPML\Import\Commands;

use WPML\Collect\Support\Collection;
use WPML\Import\Fields;

// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
class SetAttachmentsLanguage implements Base\Command {

	use Base\Query;
	use Base\HasItemsPerBatch;

	const DEFAULT_LIMIT = 5;

	/** @var \SitePress */
	private $sitepress;

	/**
	 * @param \wpdb      $wpdb
	 * @param \SitePress $sitepress
	 */
	public function __construct( \wpdb $wpdb, \SitePress $sitepress ) {
		$this->wpdb      = $wpdb;
		$this->sitepress = $sitepress;
	}

	/**
	 * @return string
	 */
	public static function getTitle() {
		return __( 'Setting Attachments\' Language', 'wpml-import' );
	}

	/**
	 * @return string
	 */
	public static function getDescription() {
		return __( 'Assigning a language to imported attachments.', 'wpml-import' );
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
		$items = $this->getPendingItems( $this->filterNumberOfItemsPerBatch( self::DEFAULT_LIMIT ) );

		foreach ( $items as $item ) {
			$languageCode = $item->language_code ?: $this->sitepress->get_default_language();

			$this->sitepress->set_element_language_details( $item->attachment_id, 'post_attachment', 0, $languageCode );
		}

		return count( $items );
	}

	/**
	 * @param int|null $limit
	 *
	 * @return object[]
	 */
	private function getPendingItems( $limit = null ) {
		return $this->getResultsWithLimit(
			"
			SELECT
				p.ID AS attachment_id,
				parent_t.language_code
			FROM {$this->wpdb->posts} p
			LEFT JOIN {$this->wpdb->prefix}icl_translations t
				ON t.element_id = p.ID
				AND t.element_type = 'post_attachment'
			LEFT JOIN {$this->wpdb->postmeta} pm
				ON pm.post_id = p.post_parent
				AND pm.meta_key = '" . Fields::TRANSLATION_GROUP . "'
			LEFT JOIN {$this->wpdb->prefix}icl_translations parent_t
				ON parent_t.element_id = p.post_parent
				AND parent_t.element_type LIKE 'post_%'
			WHERE p.post_type = 'attachment'
				AND p.post_status != 'auto-draft'
				AND t.trid IS NULL
				AND pm.meta_id IS NOT NULL
			ORDER BY p.ID ASC
			",
			$limit
		);
	}
}
// phpcs:enable
