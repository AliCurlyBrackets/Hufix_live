<?php

namespace WPML\Import;

/**
 * These are the reserved fields for the WPML Import process.
 */
class Fields {

	// Expected fields in the imported content.
	const LANGUAGE_CODE        = '_wpml_import_language_code';
	const SOURCE_LANGUAGE_CODE = '_wpml_import_source_language_code';
	const TRANSLATION_GROUP    = '_wpml_import_translation_group';
	const FINAL_POST_STATUS    = '_wpml_import_after_process_post_status';
	const DO_APPLY_ATE_ON_POST = '_wpml_import_tea';
}
