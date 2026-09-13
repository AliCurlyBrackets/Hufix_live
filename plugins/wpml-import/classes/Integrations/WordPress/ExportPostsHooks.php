<?php

namespace WPML\Import\Integrations\WordPress;

use WPML\LIB\WP\Hooks;

use function WPML\FP\spreadArgs;

class ExportPostsHooks extends \WPML\Import\Integrations\Base\Strategies\Generate\ExportPostsHooks {

	public function add_hooks() {
		Hooks::onAction( 'the_post' )->then( spreadArgs( [ $this, 'setMetaFields' ] ) );
		parent::add_hooks();
	}
}
