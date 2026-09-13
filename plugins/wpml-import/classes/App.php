<?php

namespace WPML\Import;

use WPML\FP\Logic;

use function WPML\Container\make;

class App {

	/**
	 * @return void
	 *
	 * @codeCoverageIgnore
	 */
	public static function run() {
		make( \WPML_Action_Filter_Loader::class )->load( self::getHooksClasses() );
	}

	/**
	 * @return string[]
	 *
	 * @codeCoverageIgnore
	 */
	private static function getHooksClasses() {
		return array_merge(
			self::getCoreHooksClasses(),
			self::getIntegrationHooksClasses()
		);
	}

	/**
	 * @return string[]
	 *
	 * @codeCoverageIgnore
	 */
	private static function getCoreHooksClasses() {
		return [
			CLI\Hooks::class,
			UI\AdminPageHooks::class,
			API\Hooks::class,
			Core\BeforeProcessHooks::class,
		];
	}

	/**
	 * @return string[]
	 *
	 * @codeCoverageIgnore
	 */
	private static function getIntegrationHooksClasses() {
		return wpml_collect(
			[
				Integrations\WooCommerce\HooksFactory::class => defined( 'WC_VERSION' ),
				Integrations\WooCommerce\WCML\HooksFactory::class => defined( 'WC_VERSION' ) && defined( 'WCML_VERSION' ),
				Integrations\WordPress\HooksFactory::class => true,
				Integrations\WPAllExport\HooksFactory::class => defined( 'PMXE_VERSION' ),
				Integrations\WPAllImport\HooksFactory::class => defined( 'PMXI_VERSION' ),
				Integrations\WPImportExport\HooksFactory::class => defined( 'WPIE_PLUGIN_VERSION' ),
			]
		)->filter( Logic::isTruthy() )
			->keys()
			->toArray();
	}
}
