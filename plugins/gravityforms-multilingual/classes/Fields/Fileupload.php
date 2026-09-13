<?php

namespace GFML\Fields;

use WPML\FP\Obj;

class Fileupload implements \IWPML_Backend_Action, \IWPML_Frontend_Action {

	public function add_hooks() {
		add_filter( 'gform_plupload_settings', [ $this, 'convertUrl' ] );
	}

	/**
	 * @param array $settings
	 *
	 * @return array
	 *
	 * @see https://onthegosystems.myjetbrains.com/youtrack/issue/gfml-124
	 */
	public function convertUrl( $settings ) {
		if ( Obj::prop( 'url', $settings ) ) {
			$settings['url'] = apply_filters( 'wpml_permalink', $settings['url'], null, false );
		}

		return $settings;
	}

}
