<?php

namespace WPML\Import\Commands;

use WPML\API\Sanitize;
use WPML\FP\Fns;
use WPML\FP\Maybe;

class Provider {

	/**
	 * @param string $context
	 *
	 * @return array
	 */
	public static function get( $context ) {
		return wpml_collect()
			->merge( self::getProcessCommands( $context ) )
			->merge( self::getCleanupCommands( $context ) )
			->filter( [ self::class, 'isCommandClass' ] )
			->values()
			->toArray();
	}

	/**
	 * @param string $context
	 *
	 * @return array
	 */
	public static function getProcessCommands( $context ) {
		$processCommands = [
			SetTermsLanguage::class,
			SetPostsLanguage::class,
			SetAttachmentsLanguage::class,
			ConnectPostImageAttachments::class,
			FixImportedPostSlugs::class,
			SetFinalPostsStatus::class,
			SetInlineTermsLanguage::class,
			DuplicateTermsAssignedToPostsInDifferentLanguage::class,
			ReassignPostsToTranslatedTerms::class,
			DeleteOriginalsOfDuplicatedTerms::class,
			ConnectTermTranslationsByPostsWithOnlyOneAssignment::class,
			MarkAncestorsForParentLanguageFix::class,
			SetUnassignedParentTermsLanguage::class,
			SendPostsToAutomaticTranslation::class,
		];

		/**
		 * This filter allows to alter the process commands to run (add/remove/change-order).
		 *
		 * @param class-string<Base\Command>[] $processCommands The process command class names.
		 * @param string                       $context         The context in which the commands are ran.
		 */
		return (array) apply_filters( 'wpml_import_process_commands', $processCommands, $context );
	}

	/**
	 * @param string $context
	 *
	 * @return array
	 */
	public static function getCleanupCommands( $context ) {
		$cleanupCommands = [
			CleanupTermFields::class,
			CleanupPostFields::class,
			FlushTranslationsCache::class,
		];

		/**
		 * This filter allows to alter the cleanup commands to run (add/remove/change-order).
		 *
		 * @param class-string<Base\Command>[] $cleanupCommands The cleanup command class names.
		 * @param string                       $context         The context in which the commands are ran.
		 */
		return (array) apply_filters( 'wpml_import_cleanup_commands', $cleanupCommands, $context );
	}

	/**
	 * @param class-string<Base\Command|mixed> $className
	 *
	 * @return bool
	 */
	public static function isCommandClass( $className ) {
		return in_array( Base\Command::class, class_implements( $className ), true );
	}

	/**
	 * @param string $commandClass
	 *
	 * @return Base\Command|null
	 */
	public static function getCommandInstance( $commandClass ) {
		$command = null;

		try {
			$command = Maybe::fromNullable( $commandClass )
				->map( [ Sanitize::class, 'string' ] )
				->filter( [ self::class, 'isCommandClass' ] )
				->map( Fns::make() )
				->getOrElse( null );
		} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Silently fail.
		}

		return $command;
	}
}
