<?php
/**
 * WP-Backend UI — loader.
 *
 * Bundle this directory into a plugin (e.g. lib/wp-backend-ui/) and require
 * this file from the plugin's main file at top level:
 *
 *     require_once __DIR__ . '/lib/wp-backend-ui/wp-backend-ui.php';
 *
 * Several plugins may ship different copies. Every copy registers itself as a
 * candidate; on plugins_loaded the newest version is loaded exactly once, so
 * all plugins share the same class and the same stylesheet.
 *
 * @package WP_Backend_UI
 * @version 1.0.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$GLOBALS['wpb_admin_ui_candidates']['1.0.2'] = __FILE__;

if ( ! function_exists( 'wpb_admin_ui_boot' ) ) {

	/**
	 * Load the newest registered copy of the library.
	 *
	 * @return void
	 */
	function wpb_admin_ui_boot() {
		if ( class_exists( 'WPB_Admin_UI', false ) || empty( $GLOBALS['wpb_admin_ui_candidates'] ) ) {
			return;
		}

		$candidates = $GLOBALS['wpb_admin_ui_candidates'];
		uksort( $candidates, 'version_compare' );
		$file = end( $candidates );

		require_once dirname( $file ) . '/includes/class-wpb-admin-ui.php';
		WPB_Admin_UI::init( $file );

		if ( ! empty( $GLOBALS['wpb_admin_ui_queue'] ) ) {
			foreach ( $GLOBALS['wpb_admin_ui_queue'] as $args ) {
				WPB_Admin_UI::register( $args );
			}
			$GLOBALS['wpb_admin_ui_queue'] = array();
		}
	}

	/**
	 * Register admin screens that should use the shared UI.
	 *
	 * Safe to call at any time (before or after the library booted).
	 *
	 * @param array $args See WPB_Admin_UI::register().
	 * @return void
	 */
	function wpb_admin_ui_register( array $args ) {
		if ( class_exists( 'WPB_Admin_UI', false ) ) {
			WPB_Admin_UI::register( $args );
			return;
		}
		$GLOBALS['wpb_admin_ui_queue'][] = $args;
	}

	add_action( 'plugins_loaded', 'wpb_admin_ui_boot', -1000 );
}

// Included late (e.g. from a theme or inside a plugins_loaded callback).
if ( did_action( 'plugins_loaded' ) ) {
	wpb_admin_ui_boot();
}
