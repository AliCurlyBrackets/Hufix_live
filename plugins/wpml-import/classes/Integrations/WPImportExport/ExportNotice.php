<?php

namespace WPML\Import\Integrations\WPImportExport;

use WPML\Import\Fields;
use WPML\FP\Lst;
use WPML\LIB\WP\Hooks;
use WPML\Import\Integrations\Base\Notice;
use function WPML\FP\spreadArgs;

class ExportNotice extends Notice {

	const NOTICE_ID = 'wp-import-export-export';

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
		return $this->getExportMessage();
	}
}
