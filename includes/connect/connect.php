<?php
/**
 * "Plugins by Sanjay Shankar" newsletter card: loader.
 *
 * Shared by every plugin by Sanjay Shankar (Shopnook, Seeknook, Likenook,
 * Anchormap, Seasonal Effects & Notice Bar, Advanced Preloader...). Each
 * plugin ships an identical copy of this folder; only the text domain in
 * class-sanjayshankar-connect.php differs. When several copies are active,
 * the newest version runs and the others stay silent, so a site sees one card.
 *
 * Host plugin usage:
 *   require_once __DIR__ . '/includes/connect/connect.php';
 *   add_filter( 'sanjayshankar_connect_plugins', function ( $plugins ) {
 *       $plugins['shopnook'] = 'Shopnook';
 *       return $plugins;
 *   } );
 *   // In the plugin's own admin screen, where the card may appear:
 *   do_action( 'sanjayshankar_connect_card', 'shopnook' );
 *
 * @package SanjayShankar_Connect
 */

defined( 'ABSPATH' ) || exit;

// Bump this, and the class file, whenever the module changes.
$GLOBALS['sanjayshankar_connect_versions']['1.0.0'] = __DIR__;

if ( ! function_exists( 'sanjayshankar_connect_boot' ) ) {
	/**
	 * Loads the newest copy of the module among the active plugins.
	 */
	function sanjayshankar_connect_boot() {
		if ( class_exists( 'SanjayShankar_Connect' ) ) {
			return;
		}
		$versions = $GLOBALS['sanjayshankar_connect_versions'];
		uksort( $versions, 'version_compare' );
		$dir = end( $versions );
		require_once $dir . '/class-sanjayshankar-connect.php';
		SanjayShankar_Connect::init( $dir );
	}
	add_action( 'plugins_loaded', 'sanjayshankar_connect_boot', 0 );
}
