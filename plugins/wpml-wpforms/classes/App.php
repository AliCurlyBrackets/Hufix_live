<?php

namespace WPML\Forms\WPForms;

use WPML\Forms\WPForms\SharedAPI\SharedBaseHooks;

final class App {

	public static function init() {
		( new \WPML_Action_Filter_Loader() )->load( [
			SharedBaseHooks::class,
		] );
	}
}