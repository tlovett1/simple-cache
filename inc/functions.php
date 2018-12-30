<?php
/**
 * Utility functions for plugin
 *
 * @package  simple-cache
 */

/**
 * Clear the cache
 *
 * @since  1.4
 */
function sc_cache_flush() {
	global $wp_filesystem;

	require_once ABSPATH . 'wp-admin/includes/file.php';

	WP_Filesystem();

	$wp_filesystem->rmdir( untrailingslashit( WP_CONTENT_DIR ) . '/cache/simple-cache', true );

	if ( function_exists( 'wp_cache_flush' ) ) {
		wp_cache_flush();
	}
}

/**
 * Get cache directory
 *
 * @return string
 */
function sc_get_cache_dir() {
	return ( defined( 'SC_CACHE_DIR') ) ? rtrim( SC_CACHE_DIR, '/' ) : rtrim( WP_CONTENT_DIR, '/' ) . '/cache/simple-cache';
}

/**
 * Get config directory
 *
 * @return string
 */
function sc_get_config_dir() {

}
