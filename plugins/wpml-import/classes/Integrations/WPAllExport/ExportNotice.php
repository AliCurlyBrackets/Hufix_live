<?php

namespace WPML\Import\Integrations\WPAllExport;

use WPML\Import\Fields;
use WPML\FP\Lst;
use WPML\LIB\WP\Hooks;
use WPML\Import\Integrations\Base\Notice;
use function WPML\FP\spreadArgs;

class ExportNotice extends Notice {

	const NOTICE_ID = 'wp-all-export';

	/**
	 * @return string
	 */
	protected function getId() {
		return self::NOTICE_ID;
	}

	/**
	 * @return callable
	 */
	protected function getDisplayCallback() {
		return [ HooksFactory::class, 'isOnExportPage' ];
	}

	/**
	 * @return string
	 */
	protected function getMessage() {
		if ( HooksFactory::hasWooCommerceAddon() ) {
			return $this->getShopExportMessage();
		}

		return $this->getExportMessage();
	}
}
