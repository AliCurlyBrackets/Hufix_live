<?php

namespace WPML\Forms\WPForms\SharedAPI;

class SharedBaseHooks implements \IWPML_Backend_Action, \IWPML_Frontend_Action {

	public function add_hooks() {
		$forms = new \WPML\Forms(
			WPML_WP_FORMS_FILE,
			WpForms::class,
			new Status()
		);
		$forms->addHooks();

	}
}