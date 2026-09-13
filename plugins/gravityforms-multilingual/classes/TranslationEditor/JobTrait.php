<?php

namespace GFML\TranslationEditor;

use WPML\FP\Obj;

trait JobTrait {

	/**
	 * @param \stdClass $job
	 *
	 * @return bool
	 */
	protected function isOurJob( $job ) {
		return 'package_' . ICL_GRAVITY_FORM_ELEMENT_TYPE === Obj::prop( 'original_post_type', $job );
	}

}
