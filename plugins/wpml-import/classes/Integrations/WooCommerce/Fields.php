<?php

namespace WPML\Import\Integrations\WooCommerce;

/**
 * WooCommerce-specific fields for the WPML Import process.
 */
class Fields {

	/**
	 * Custom meta key for local attribute labels export/import.
	 * Format: JSON string like {"attribute-slug":"Translated Label"}
	 */
	const LOCAL_ATTRIBUTE_LABELS = '_wpml_import_wc_local_attribute_labels';
}
