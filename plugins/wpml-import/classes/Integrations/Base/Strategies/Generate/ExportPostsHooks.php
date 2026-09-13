<?php

namespace WPML\Import\Integrations\Base\Strategies\Generate;

use WPML\Import\Helper\PostTypes;

class ExportPostsHooks extends ExportObjectsHooks {

	/**
	 * @var \wpdb $wpdb
	 */
	protected $wpdb;

	/**
	 * @var \SitePress $sitepress
	 */
	protected $sitepress;

	/**
	 * @var PostTypes
	 */
	protected $postTypes;

	/**
	 * @param \wpdb      $wpdb
	 * @param \SitePress $sitepress
	 * @param PostTypes  $postTypes
	 */
	public function __construct(
		\wpdb $wpdb,
		\SitePress $sitepress,
		PostTypes $postTypes
	) {
		$this->wpdb      = $wpdb;
		$this->sitepress = $sitepress;
		$this->postTypes = $postTypes;
	}

	/**
	 * @return \wpdb $wpdb
	 */
	protected function getWpdb() {
		return $this->wpdb;
	}

	/**
	 * @return string
	 */
	protected function getMetaTable() {
		return $this->wpdb->postmeta;
	}

	/**
	 * @param int    $objectId
	 * @param string $metaKey
	 * @param mixed  $metaValue
	 */
	protected function setObjectMeta( $objectId, $metaKey, $metaValue ) {
		add_post_meta( $objectId, $metaKey, $metaValue, true );
	}

	/**
	 * @param  object $obj
	 *
	 * @return bool
	 */
	protected function isPostObject( $obj ) {
		return $obj instanceof \WP_Post;
	}

	/**
	 * @param  object $obj
	 *
	 * @return bool
	 */
	protected function isTranslatable( $obj ) {
		if ( $this->isPostObject( $obj ) ) {
			return $this->postTypes->isTranslatable( $obj->post_type );
		}
		return false;
	}

	/**
	 * @param  object $obj
	 *
	 * @return string
	 */
	protected function getObjectIdMetaKey( $obj ) {
		if ( $this->isPostObject( $obj ) ) {
			return 'post_id';
		}
		return '';
	}

	/**
	 * @param  object $obj
	 *
	 * @return int
	 */
	protected function getObjectId( $obj ) {
		if ( $this->isPostObject( $obj ) ) {
			return $obj->ID;
		}
		return 0;
	}

	/**
	 * @param  object $obj
	 *
	 * @return object|null
	 */
	protected function getElementLanguageDetails( $obj ) {
		if ( $this->isPostObject( $obj ) ) {
			return $this->sitepress->get_element_language_details( $obj->ID, 'post_' . $obj->post_type );
		}
		return null;
	}
}
