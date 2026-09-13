<?php

namespace WPML\Import\Commands;

use WPML\Collect\Support\Collection;
use WPML\Import\Fields;

class ConnectPostImageAttachments implements Base\Command, Base\TemporaryPostFields {

	use Base\Query;
	use Base\HasItemsPerBatch;

	const DEFAULT_LIMIT = 100;

	const FIELD_TEMPORARY    = '_wpml_import_connect_post_image_attachments';
	const THUMBNAIL_META_KEY = '_thumbnail_id';
	const GALLERY_META_KEY   = '_product_image_gallery';

	/**
	 * @var \wpdb
	 */
	protected $wpdb;

	/**
	 * @var \SitePress
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
		return __( 'Connecting Post Image Attachments', 'wpml-import' );
	}

	/**
	 * @return string
	 */
	public static function getDescription() {
		return __( 'Updating thumbnail and gallery attachment IDs on translated posts, products, and CPTs to point to their translated equivalents.', 'wpml-import' );
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
			if ( $item->thumbnail_id ) {
				$translated = $this->translateAttachmentId( (int) $item->thumbnail_id, $item->language_code );
				if ( $translated !== (int) $item->thumbnail_id ) {
					$this->wpdb->update(
						$this->wpdb->postmeta,
						[ 'meta_value' => $translated ],
						[
							'post_id'  => $item->element_id,
							'meta_key' => self::THUMBNAIL_META_KEY,
						]
					);
				}
			}

			if ( $item->gallery ) {
				$ids            = array_filter( array_map( 'intval', explode( ',', $item->gallery ) ) );
				$translated_ids = array_map(
					fn( $id ) => $this->translateAttachmentId( $id, $item->language_code ),
					$ids
				);
				if ( $translated_ids !== $ids ) {
					$this->wpdb->update(
						$this->wpdb->postmeta,
						[ 'meta_value' => implode( ',', $translated_ids ) ],
						[
							'post_id'  => $item->element_id,
							'meta_key' => self::GALLERY_META_KEY,
						]
					);
				}
			}

			add_post_meta( $item->element_id, self::FIELD_TEMPORARY, 1 );
		}

		return count( $items );
	}

	/**
	 * @param int    $id
	 * @param string $languageCode
	 *
	 * @return int
	 */
	private function translateAttachmentId( $id, $languageCode ) {
		return (int) $this->sitepress->get_object_id( $id, 'attachment', true, $languageCode );
	}

	/**
	 * @param int|null $limit
	 *
	 * @return array
	 */
	private function getPendingItems( $limit = null ) {
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $this->getResultsWithLimit(
			"
			SELECT t.element_id AS element_id,
				t.language_code AS language_code,
				pm_thumb.meta_value AS thumbnail_id,
				pm_gallery.meta_value AS gallery
			FROM {$this->wpdb->prefix}icl_translations AS t
			-- Restrict to imported content.
			INNER JOIN {$this->wpdb->postmeta} AS import_pm
				ON import_pm.post_id = t.element_id
				AND import_pm.meta_key = '" . Fields::TRANSLATION_GROUP . "'
			-- Skip already-processed items.
			LEFT JOIN {$this->wpdb->postmeta} AS tpm
				ON tpm.post_id = t.element_id
				AND tpm.meta_key = '" . self::FIELD_TEMPORARY . "'
			-- Fetch thumbnail ID.
			LEFT JOIN {$this->wpdb->postmeta} AS pm_thumb
				ON pm_thumb.post_id = t.element_id
				AND pm_thumb.meta_key = '" . self::THUMBNAIL_META_KEY . "'
			-- Fetch gallery IDs.
			LEFT JOIN {$this->wpdb->postmeta} AS pm_gallery
				ON pm_gallery.post_id = t.element_id
				AND pm_gallery.meta_key = '" . self::GALLERY_META_KEY . "'
			WHERE t.element_type LIKE 'post_%'
				AND t.element_type != 'post_attachment'
				AND tpm.meta_id IS NULL
				AND ( pm_thumb.meta_id IS NOT NULL OR pm_gallery.meta_id IS NOT NULL )
			ORDER BY t.element_id ASC
			",
			$limit
		);
		// phpcs:enable
	}

	/**
	 * @return string[]
	 */
	public static function getTemporaryPostFields() {
		return [
			self::FIELD_TEMPORARY,
		];
	}
}
