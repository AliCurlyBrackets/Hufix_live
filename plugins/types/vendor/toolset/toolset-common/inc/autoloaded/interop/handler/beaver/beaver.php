<?php

namespace OTGS\Toolset\Common\Interop\Handler\BeaverBuilder;

use OTGS\Toolset\Common\Interop\HandlerInterface;

/**
 * Class MainIntegration
 *
 * Handles the first layer of the Beaver Builder integration.
 *
 * @since 3.0.8
 */
class MainIntegration implements HandlerInterface {
	/**
	 * Initializes the Beaver Builder integration.
	 */
	public function initialize() {
		add_filter( 'toolset_filter_shortcode_script_i18n', array( $this, 'add_shortcodes_modal_button_to_beaver_inputs' ) );
	}

	public function add_shortcodes_modal_button_to_beaver_inputs( $shortcodes_gui_translations ) {
		// Exclude BB's autoSuggest fields (.fl-suggest-field / .as-input): wrapping them
		// breaks BB's sibling-based .as-values lookup, which silently drops the selected
		// term/post/user IDs on save (e.g. Posts module Categories/Tags/Authors filters).
		$shortcodes_gui_translations['integrated_inputs'][] = '.fl-lightbox-content input:text:not(.fl-suggest-field):not(.as-input)';
		return $shortcodes_gui_translations;
	}
}