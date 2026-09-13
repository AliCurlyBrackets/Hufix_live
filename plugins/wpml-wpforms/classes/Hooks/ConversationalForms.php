<?php

namespace WPML\Forms\WPForms\Hooks;

use WPML\Forms\WPForms\SharedAPI\Strings;

class ConversationalForms {

	/** @var Strings */
	private $strings;

	/**
	 * @param Strings $strings
	 */
	public function __construct( Strings $strings ) {
		$this->strings = $strings;
	}

	public function addHooks() {
		add_filter(
			'wpforms_conversational_forms_frontend_handle_request_form_data',
			[ $this->strings, 'applyTranslations' ]
		);
	}
}
