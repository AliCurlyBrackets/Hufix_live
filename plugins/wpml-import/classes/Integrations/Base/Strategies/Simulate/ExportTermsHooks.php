<?php

namespace WPML\Import\Integrations\Base\Strategies\Simulate;

use WPML\Import\Helper\Taxonomies;

class ExportTermsHooks extends ExportObjectsHooks {

	const META_TYPE = 'term';

	/**
	 * @var \SitePress $sitepress
	 */
	private $sitepress;

	/**
	 * @var Taxonomies
	 */
	private $taxonomies;

	/**
	 * @param \SitePress $sitepress
	 * @param Taxonomies $taxonomies
	 */
	public function __construct(
		\SitePress $sitepress,
		Taxonomies $taxonomies
	) {
		$this->sitepress  = $sitepress;
		$this->taxonomies = $taxonomies;
	}

	/**
	 * @return string
	 */
	protected function getMetaType() {
		return self::META_TYPE;
	}

	/**
	 * @param  int $objectId
	 *
	 * @return \stdClass|null
	 */
	protected function getElementLanguageDetails( $objectId ) {
		$term = get_term( $objectId );

		if ( ! $term ) {
			return null;
		}

		if ( ! $this->taxonomies->isTranslatable( $term->taxonomy ) ) {
			return null;
		}

		$element = $this->sitepress->get_element_language_details( $term->term_taxonomy_id, 'tax_' . $term->taxonomy );

		if ( ! is_object( $element ) ) {
			$element = $this->sitepress->get_element_language_details( $term->term_id, 'tax_' . $term->taxonomy );
		}

		if ( $element ) {
			return $element;
		}

		return null;
	}
}
