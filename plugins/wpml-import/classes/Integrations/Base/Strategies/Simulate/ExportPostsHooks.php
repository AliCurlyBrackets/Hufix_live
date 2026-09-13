<?php

namespace WPML\Import\Integrations\Base\Strategies\Simulate;

use WPML\Import\Helper\PostTypes;

class ExportPostsHooks extends ExportObjectsHooks {

	const META_TYPE = 'post';

	/**
	 * @var \SitePress $sitepress
	 */
	private $sitepress;

	/**
	 * @var PostTypes
	 */
	private $postTypes;

	/**
	 * @param \SitePress $sitepress
	 * @param PostTypes  $postTypes
	 */
	public function __construct(
		\SitePress $sitepress,
		PostTypes $postTypes
	) {
		$this->sitepress = $sitepress;
		$this->postTypes = $postTypes;
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
		$postType = get_post_type( $objectId );

		if ( ! $this->postTypes->isTranslatable( $postType ) ) {
			return null;
		}

		$element = $this->sitepress->get_element_language_details( $objectId, 'post_' . $postType );
		if ( $element ) {
			return $element;
		}

		return null;
	}
}
