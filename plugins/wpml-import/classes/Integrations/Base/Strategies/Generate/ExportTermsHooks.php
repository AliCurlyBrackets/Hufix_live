<?php

namespace WPML\Import\Integrations\Base\Strategies\Generate;

use WPML\Import\Helper\Taxonomies;

class ExportTermsHooks extends ExportObjectsHooks {

	/**
	 * @var \wpdb $wpdb
	 */
	protected $wpdb;

	/**
	 * @var \SitePress $sitepress
	 */
	protected $sitepress;

	/**
	 * @var Taxonomies
	 */
	protected $taxonomies;

	/**
	 * @param \wpdb      $wpdb
	 * @param \SitePress $sitepress
	 * @param Taxonomies $taxonomies
	 */
	public function __construct(
		\wpdb $wpdb,
		\SitePress $sitepress,
		Taxonomies $taxonomies
	) {
		$this->wpdb       = $wpdb;
		$this->sitepress  = $sitepress;
		$this->taxonomies = $taxonomies;
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
		return $this->wpdb->termmeta;
	}

	/**
	 * @param int    $objectId
	 * @param string $metaKey
	 * @param mixed  $metaValue
	 */
	protected function setObjectMeta( $objectId, $metaKey, $metaValue ) {
		add_term_meta( $objectId, $metaKey, $metaValue, true );
	}

	/**
	 * @param  object $obj
	 *
	 * @return bool
	 */
	protected function isTermObject( $obj ) {
		return $obj instanceof \WP_Term;
	}

	/**
	 * @param  object $obj
	 *
	 * @return bool
	 */
	protected function isTranslatable( $obj ) {
		if ( $this->isTermObject( $obj ) ) {
			return $this->taxonomies->isTranslatable( $obj->taxonomy );
		}
		return false;
	}

	/**
	 * @param  object $obj
	 *
	 * @return string
	 */
	protected function getObjectIdMetaKey( $obj ) {
		if ( $this->isTermObject( $obj ) ) {
			return 'term_id';
		}
		return '';
	}

	/**
	 * @param  object $obj
	 *
	 * @return int
	 */
	protected function getObjectId( $obj ) {
		if ( $this->isTermObject( $obj ) ) {
			return $obj->term_id;
		}
		return 0;
	}

	/**
	 * @param  object $obj
	 *
	 * @return object|null
	 */
	protected function getElementLanguageDetails( $obj ) {
		if ( $this->isTermObject( $obj ) ) {
			$element = $this->sitepress->get_element_language_details( $obj->term_taxonomy_id, 'tax_' . $obj->taxonomy );

			if ( ! is_object( $element ) ) {
				$element = $this->sitepress->get_element_language_details( $obj->term_id, 'tax_' . $obj->taxonomy );
			}

			if ( $element ) {
				return $element;
			}
		}
		return null;
	}
}
