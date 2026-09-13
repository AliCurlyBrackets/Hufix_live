<?php

namespace WPML\Import\Integrations\WPAllImport;

use WPML\Import\Integrations\Base\Notice;

class ImportNotice extends Notice {

	const NOTICE_ID = 'wp-all-import';

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
		return [ HooksFactory::class, 'isOnImportPage' ];
	}

	/**
	 * @return string
	 */
	protected function getMessage() {
		if ( HooksFactory::hasWooCommerceAddon() ) {
			return $this->getShopImportMessage();
		}

		return $this->getImportMessage();
	}
}
