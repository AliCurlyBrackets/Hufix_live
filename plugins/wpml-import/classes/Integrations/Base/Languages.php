<?php

namespace WPML\Import\Integrations\Base;

trait Languages {

	/**
	 * @param  array $queryArgs
	 *
	 * @return array
	 */
	public function includeAllLanguagesInQuery( $queryArgs ) {
		// Mostly for posts, but also for terms.
		do_action( 'wpml_switch_language', 'all' );
		// Mostly for terms.
		$queryArgs['wpml_skip_filters'] = true;
		return $queryArgs;
	}
}
