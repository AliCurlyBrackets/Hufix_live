<?php

namespace WPML\Import\Integrations\WordPress;

use WPML\Import\Integrations\Base\Notice;

class ImportNotice extends Notice {

	const NOTICE_ID = 'wordpress-import';

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

	protected function getMessage() {
		return $this->getImportMessage();
	}
}
