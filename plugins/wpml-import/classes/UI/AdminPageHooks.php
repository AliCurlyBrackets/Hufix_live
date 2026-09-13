<?php

namespace WPML\Import\UI;

use WPML\FP\Lst;
use WPML\FP\Wrapper;
use WPML\Import\Commands\Provider;
use WPML\Import\Fields;
use WPML\Import\Helper\ImportedItems;
use WPML\Import\Helper\Resources;
use WPML\FP\Str;
use WPML\Import\Integrations\WooCommerce\HooksFactory as WooCommerceHooksFactory;
use WPML\Import\Integrations\WordPress\HooksFactory as WordPressHooksFactory;
use WPML\Import\Integrations\WPAllExport\HooksFactory as WPAllExportHooksFactory;
use WPML\Import\Integrations\WPAllImport\HooksFactory as WPAllImportHooksFactory;
use WPML\Import\Integrations\WPImportExport\HooksFactory as WPImportExportHooksFactory;
use WPML\LIB\WP\Hooks;

use function WPML\Container\make;
use function WPML\FP\spreadArgs;

class AdminPageHooks implements \IWPML_Backend_Action {

	const PAGE_SLUG = WPML_IMPORT_ADMIN_PAGE_SLUG;

	const CONTEXT = 'gui';

	public function add_hooks() {
		Hooks::onAction( 'wpml_admin_menu_configure' )
			->then( [ self::class, 'registerSubMenu' ] );

		Hooks::onAction( 'admin_enqueue_scripts' )
			->then( spreadArgs( [ self::class, 'enqueueApp' ] ) );

		Hooks::onFilter( 'wpml_posthog_allowed_pages' )
			->then( spreadArgs( Lst::append( self::PAGE_SLUG ) ) );
	}

	/**
	 * @return void
	 */
	public static function registerSubMenu() {
		do_action(
			'wpml_admin_menu_register_item',
			[
				'capability' => 'wpml_manage_languages',
				'menu_slug'  => self::PAGE_SLUG,
				'menu_title' => __( 'Export and Import', 'wpml-import' ),
				'page_title' => self::getTitle(),
				'function'   => [ self::class, 'render' ],
			]
		);
	}

	/**
	 * @return void
	 */
	public static function render() {
		?>
		<div class="wrap wrap-wpml-import">
			<h1><?php echo esc_html( self::getTitle() ); ?></h1>
			<div id="wpml-import-app"></div>
		</div>
		<?php
	}

	/**
	 * @return string
	 */
	private static function getTitle() {
		return __( 'WPML Export and Import', 'wpml-import' );
	}

	/**
	 * @param string $hook
	 *
	 * @return void
	 */
	public static function enqueueApp( $hook ) {
		if ( Str::endsWith( self::PAGE_SLUG, $hook ) ) {
			Wrapper::of( self::getData() )->map(
				Resources::enqueueApp(
					'import',
					[
						'react',
						'react-dom',
						'wp-data',
						'wp-i18n',
					]
				)
			);
		}
	}

	/**
	 * @return array
	 */
	private static function getData() {
		$importedItems = make( ImportedItems::class );
		$exampleData   = make( ExampleData::class )->get();
		$importedPosts = $importedItems->countPosts();
		$importedTerms = $importedItems->countTerms();

		return [
			'name' => 'wpmlImport',
			'data' => [
				'endpoints'        => [
					'command' => Endpoints\Command::class,
				],
				'commands'         => self::getCommandsData(),
				'hasImportedItems' => $importedPosts || $importedTerms,
				'exampleData'      => $exampleData,
				'strings'          => self::getStrings( $exampleData, $importedPosts, $importedTerms ),
			],
		];
	}

	/**
	 * @return array
	 */
	private static function getCommandsData() {
		return wpml_collect( Provider::get( self::CONTEXT ) )
			->map(
				function ( $className ) {
					/** @var class-string<\WPML\Import\Commands\Base\Command> $className */

					return [
						Endpoints\Command::KEY_COMMAND_CLASS => $className,
						'title'       => $className::getTitle(),
						'description' => $className::getDescription(),
					];
				}
			)->toArray();
	}

	/**
	 * @param array $exampleData
	 * @param int   $importedPosts
	 * @param int   $importedTerms
	 *
	 * @return array
	 */
	private static function getStrings( $exampleData, $importedPosts, $importedTerms ) {
		$fieldListItem = function ( $str, $field ) {
			return '<li>' . sprintf( $str, "<b>$field</b>" ) . '</li>';
		};

		$iAmUsingBuiltinTool = function ( $label ) {
			/* translators: %s is the label of the tool. */
			return sprintf( esc_html__( 'I’m using the built-in %s export/import tool', 'wpml-import' ), $label );
		};

		$iAmUsingPlugin = function ( $label ) {
			/* translators: %s is the label of the plugin. */
			return sprintf( esc_html__( 'I’m using %s plugin', 'wpml-import' ), $label );
		};

		return [
			/* translators: %1$s is the number of processed items and %2$s is the total number of items. */
			'itemsCount'              => esc_html__( '(%1$d/%2$d items)', 'wpml-import' ),
			/* translators: %1$s is the number of skipped items. */
			'skippedCount'            => esc_html__( '%d skipped', 'wpml-import' ),
			'noItems'                 => esc_html__( '(no items)', 'wpml-import' ),
			'countingItems'           => esc_html__( 'Counting items...', 'wpml-import' ),
			'quizz'                   => [
				'howExporting'              => esc_html__( 'First, tell us how you’re going to create the content that you’ll be importing:', 'wpml-import' ),
				'continue'                  => esc_html__( 'Continue', 'wpml-import' ),
				'tools'                     => [
					'wp'      => $iAmUsingBuiltinTool( WordPressHooksFactory::LABEL ),
					'wc'      => $iAmUsingBuiltinTool( WooCommerceHooksFactory::LABEL ),
					/* translators: %1$s and %2$s are plugin labels. */
					'wpAllIE' => sprintf( esc_html__( 'I’m using %1$s and %2$s plugins', 'wpml-import' ), WPAllExportHooksFactory::LABEL, WPAllImportHooksFactory::LABEL ),
					'wpIE'    => $iAmUsingPlugin( WPImportExportHooksFactory::LABEL ),
					'other'   => esc_html__( 'I’m using a different method to export from this site', 'wpml-import' ),
				],
				'thisSite'                  => esc_html__( 'I’m exporting the content from this site', 'wpml-import' ),
				'notThisSite'               => esc_html__( 'I’m exporting the content from a different website or system', 'wpml-import' ),
				'step1NotIntegratedTitle'   => esc_html__( 'Before importing, add language columns to your content', 'wpml-import' ),
				'step1NotIntegratedContent' => '<p>' . esc_html__( 'Make sure that your import file includes the following columns (custom fields) with language information:', 'wpml-import' ) . '</p>
					<ul class="ul-disc">' .
						/* translators: %s is the field key. */
						$fieldListItem( esc_html__( '%s: A value that’s the same for all the translations of each item. In e-commerce sites this will often be the SKU.', 'wpml-import' ), Fields::TRANSLATION_GROUP ) .
						/* translators: %s is the field key. */
						$fieldListItem( esc_html__( '%s: The language code for each item.', 'wpml-import' ), Fields::LANGUAGE_CODE ) .
					'</ul>
					<p>' . esc_html__( 'And, you can include these two columns if needed:', 'wpml-import' ) . '</p>
					<ul class="ul-disc">' .
						/* translators: %s is the field key. */
						$fieldListItem( esc_html__( '%s: If this item is a translation, set this column to the code of the source language. If it’s not a translation, keep it empty.', 'wpml-import' ), Fields::SOURCE_LANGUAGE_CODE ) .
						/* translators: %s is the field key. */
						$fieldListItem( esc_html__( '%s: Set to “draft” or “published” to determine the publishing status of the post after adjusting languages. Usually, this value will be “published”.', 'wpml-import' ), Fields::FINAL_POST_STATUS ) .
					'</ul>
					<p>'
						/* translators: %s is a comma-separated list of languages. */
						. sprintf( esc_html__( 'Here is an example of importing two items, into your site’s languages (%s):', 'wpml-import' ), $exampleData['languageList'] ) .
					'</p>',
				'step2NotIntegratedContent' => '<p>' . esc_html__( 'Once you’ve added language columns to your multilingual content file, import it to your site using whatever import plugin you prefer.', 'wpml-import' ) . '</p>',
				'step1IntegratedTitle'      => esc_html__( 'Before importing, your content needs language information', 'wpml-import' ),
				/* translators: %s is the label of the plugin. */
				'step1IntegratedContent'    => '<h4>' . sprintf( esc_html__( '%s will add the necessary language information to your export.', 'wpml-import' ), self::getTitle() ) . '</h4>',
				'step2IntegratedContent'    => '<p>' . esc_html__( 'Import your multilingual content file into the site using whatever import plugin you prefer.', 'wpml-import' ) . '</p>',
				'useYourFavoriteImportTool' => esc_html__( 'Use your favorite import plugin', 'wpml-import' ),
				'step2ComplementContent'    => '<p>' . esc_html__( 'After you import the content, it will still not show the correct language information. So, we recommend that you import new content as Drafts. If you’re importing updates, you can keep already-published content as published.', 'wpml-import' ) . '<p>',
				/* translators: %s is the label of the plugin. */
				'step3Title'                => sprintf( esc_html__( 'Return here, to %s, to set languages and connect translations', 'wpml-import' ), self::getTitle() ),
				/* translators: %s is the label of the plugin. */
				'step3Content'              => '<p>' . sprintf( esc_html__( 'When you visit this page after you’ve imported content with language information, you’ll be able to run %s to apply languages and connect translations.', 'wpml-import' ), self::getTitle() ) . '</p>
					<p>'
						. sprintf(
							/* translators: %1$s and %3$s are HTML link wrappers / %2$s is the plugin title. */
							esc_html__( 'Already imported and still seeing this screen? %1$sTroubleshooting %2$s%3$s.', 'wpml-import' ),
							'<a href="' . DocLinks::getMain() . '" target="_blank" class="wpml-external-link">',
							self::getTitle(),
							'</a>'
						) .
					'</p>',
			],
			'titleForEmptyImport'     => esc_html__( 'How to Export and Import Content in Multiple Languages', 'wpml-import' ),
			/* translators: %s is the label of the plugin. */
			'titleForImportedContent' => sprintf( esc_html__( '%s is ready to update your imported content', 'wpml-import' ), self::getTitle() ),
			'goodJob'                 => $importedPosts && $importedTerms
				/* translators: %1$d is the number of terms and %2$d is the number of posts. */
				? sprintf( esc_html__( 'Good job. We see %1$d terms and %2$d posts that are imported and need language information.', 'wpml-import' ), $importedTerms, $importedPosts )
				: ( $importedTerms
					/* translators: %d is the number of terms. */
					? sprintf( esc_html__( 'Good job. We see %d terms that are imported and need language information.', 'wpml-import' ), $importedTerms )
					/* translators: %d is the number of posts. */
					: sprintf( esc_html__( 'Good job. We see %d posts that are imported and need language information.', 'wpml-import' ), $importedPosts )
				),
			'runWpmlImport'           => esc_html__( 'Run WPML Import', 'wpml-import' ),
			'importComplete'          => esc_html__( 'Multilingual Import Complete', 'wpml-import' ),
		];
	}
}
