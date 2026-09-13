<?php

namespace WPML\Import\Helper;

class Page {

	/**
	 * @param  string $path
	 *
	 * @return bool
	 */
	public static function isOn( $path ) {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		if (
			defined( 'DOING_AJAX' )
			&& DOING_AJAX
		) {
			return false;
		}

		if ( false === strpos( $_SERVER['REQUEST_URI'], $path ) ) {
			return false;
		}

		return true;
	}
}
