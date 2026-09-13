<?php

namespace WPML\Import\Integrations\Base;

use WPML\LIB\WP\Hooks;
use WPML_Notices;

abstract class Notice implements \IWPML_Backend_Action, \IWPML_DIC_Action {

	// Cascade of priorities before 10.
	// 7: WPML.
	// 8: WCML.
	// 9: WPML Export and Import.
	const PRIORITY = 9;
	const GROUP    = 'wpml-import-notices';

	const WPML_IMPORT_URL = 'https://wpml.org/documentation/related-projects/wpml-export-and-import/?utm_source=plugin&utm_medium=gui&utm_campaign=wpml-export-import&utm_term=admin-notice';
	const WCML_URL        = 'https://wpml.org/documentation/related-projects/woocommerce-multilingual/?utm_source=plugin&utm_medium=gui&utm_campaign=wcml&utm_term=admin-notice';

	const NOTICE_CLASSES = [
		'wpml-import-notice',
		'wpml-import-notice-from-wpml-import',
	];

	/** @var WPML_Notices $wpmlNotices */
	protected $wpmlNotices;

	/**
	 * @param WPML_Notices $wpmlNotices
	 */
	public function __construct( WPML_Notices $wpmlNotices ) {
		$this->wpmlNotices = $wpmlNotices;
	}

	public function add_hooks() {
		Hooks::onAction( 'admin_init', self::PRIORITY )->then( [ $this, 'manageNotice' ] );
	}

	public function manageNotice() {
		$notice = $this->wpmlNotices->get_new_notice(
			$this->getId(),
			$this->getMessage(),
			self::GROUP
		);
		$notice->set_css_class_types( 'info' );
		$notice->set_css_classes( self::NOTICE_CLASSES );
		$notice->add_display_callback( $this->getDisplayCallback() );
		if ( $this->requiresAdminLangActionButton() ) {
			$notice->add_action( $this->getAdminLangActionButton() );
		}
		$notice->set_dismissible( true );
		$this->wpmlNotices->add_notice( $notice, true );
	}

	/**
	 * @return string
	 */
	abstract protected function getId();

	/**
	 * @return callable
	 */
	abstract protected function getDisplayCallback();

	/**
	 * @return string
	 */
	abstract protected function getMessage();

	/**
	 * @return bool
	 */
	protected static function hasWcml() {
		return defined( 'WCML_VERSION' );
	}

	/**
	 * @return string
	 */
	protected function getExportMessage() {
		return sprintf(
			/* translators: %s is a link. */
			__( 'Migrating your multilingual site? Remember to also install %s on your new site before importing your content so we can restore all the translations.', 'wpml-import' ),
			$this->getSelfLink()
		);
	}

	/**
	 * @return string
	 */
	protected function getShopExportMessage() {
		if ( ! $this->hasWcml() ) {
			return sprintf(
				/* translators: %1$s and %2$s are both links. */
				__( 'Migrating your multilingual shop? With %1$s and %2$s you can transfer your translated content to a new site, including cross-sells, up-sells, and product attributes.', 'wpml-import' ),
				$this->getWcmlLink(),
				$this->getSelfLink()
			);
		}
		return sprintf(
			/* translators: %1$s and %2$s are both links. */
			__( 'Migrating your multilingual shop? Remember to install %1$s and %2$s on your new site before importing your content so we can restore all the translations.', 'wpml-import' ),
			$this->getWcmlLink(),
			$this->getSelfLink()
		);
	}

	/**
	 * @return string
	 */
	protected function getImportMessage() {
		return sprintf(
			/* translators: %s is a link. */
			__( 'Looking to import your multilingual content? Remember to install %s in your original site before exporting its content so we can import all the translations here.', 'wpml-import' ),
			$this->getSelfLink()
		);
	}

	/**
	 * @return string
	 */
	protected function getShopImportMessage() {
		if ( ! $this->hasWcml() ) {
			return sprintf(
				/* translators: %1$s is a link. */
				__( 'Looking to import your multilingual content? Install %1$s on this site.', 'wpml-import' )
					. '<br /><br />'
					/* translators: %2$s and %3$s are both links. */
					. __( 'Remember to also enable %2$s and %3$s in your original shop before exporting its content so we can import all the translations here.', 'wpml-import' ),
				$this->getWcmlLink(),
				$this->getWcmlLink(),
				$this->getSelfLink()
			);
		}
		return sprintf(
			/* translators: %1$s and %2$s are both links. */
			__( 'Looking to import your multilingual content? Remember to enable %1$s and %2$s in your original shop before exporting its content so we can import all the translations here.', 'wpml-import' ),
			$this->getWcmlLink(),
			$this->getSelfLink()
		);
	}

	/**
	 * @return string
	 */
	protected function getSelfLink() {
		$url   = self::WPML_IMPORT_URL;
		$title = __( 'WPML Export and Import', 'wpml-import' );
		return '<a class="wpml-external-link" href="' . esc_url( $url ) . '" title="' . esc_attr( $title ) . '" target="_blank">'
			. esc_html( $title )
			. '</a>';
	}

	/**
	 * @return string
	 */
	protected function getWcmlLink() {
		$url   = self::WCML_URL;
		$title = __( 'WooCommerce Multilingual', 'wpml-import' );
		return '<a class="wpml-external-link" href="' . esc_url( $url ) . '" title="' . esc_attr( $title ) . '" target="_blank">'
			. esc_html( $title )
			. '</a>';
	}

	/**
	 * @return bool
	 */
	protected function requiresAdminLangActionButton() {
		return false;
	}

	/**
	 * @return \WPML_Notice_Action
	 */
	protected function getAdminLangActionButton() {
		$action = new \WPML_Notice_Action(
			__( 'Switch to all languages', 'wpml-import' ),
			add_query_arg( 'lang', 'all' ),
			false,
			false,
			true,
			false
		);
		return $action;
	}
}
