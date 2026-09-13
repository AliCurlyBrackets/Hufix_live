<?php

namespace WPML\Import\Integrations\WooCommerce\WCML\Commands;

use WPML\Collect\Support\Collection;
use WPML\FP\Obj;
use WPML\Import\Commands\Base\Command;

class RegisterAttributesAsTranslatableTaxonomies implements Command {

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
		return __( 'Registering Product Attributes', 'wpml-import' );
	}

	/**
	 * @return string
	 */
	public static function getDescription() {
		return __( 'Identifying and registering attributes created during product imports for translation.', 'wpml-import' );
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
	 * @codeCoverageIgnore
	 *
	 * @param Collection|null $args
	 *
	 * @return int
	 */
	public function run( Collection $args = null ) {
		$wpmlSettings = (array) $this->sitepress->get_settings();
		$syncSettings = (array) $this->sitepress->get_setting( 'taxonomies_sync_option', [] );

		$items = $this->getPendingItems();
		foreach ( $items as $taxonomyName ) {
			$syncSettings[ $taxonomyName ] = 1;
		}

		$wpmlSettings['taxonomies_sync_option'] = $syncSettings;
		$this->sitepress->save_settings( $wpmlSettings );

		return count( $items );
	}

	/**
	 * @codeCoverageIgnore
	 *
	 * @return array
	 */
	private function getPendingItems() {
		$attributes   = wc_get_attribute_taxonomies();
		$syncSettings = $this->sitepress->get_setting( 'taxonomies_sync_option', [] );

		$getAttributeName = Obj::prop( 'attribute_name' );
		$getTaxonomyName  = 'wc_attribute_taxonomy_name';
		$isNotRegistered  = function ( $attributeName ) use ( $syncSettings ) {
			return ! Obj::has( $attributeName, $syncSettings );
		};

		return wpml_collect( $attributes )
			->map( $getAttributeName )
			->map( $getTaxonomyName )
			->filter( $isNotRegistered )
			->values()
			->toArray();
	}
}
