<?php

namespace GFML\Container;

class Config {

	/**
	 * @return array
	 */
	public static function getSharedClasses() {
		return [
			\GFML_TM_API::class,
			\GFML_String_Name_Helper::class,
		];
	}
}
