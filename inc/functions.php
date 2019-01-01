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

	$url_parts = parse_url( home_url() );

	$path = sc_get_cache_dir() . '/' . untrailingslashit( $url_parts['host'] );

	if ( ! empty( $url_parts['path'] ) && '/' !== $url_parts['path'] ) {
		$path .= trim( $url_parts['path'], '/' );
	}

	$wp_filesystem->rmdir( $path, true );

	if ( function_exists( 'wp_cache_flush' ) ) {
		wp_cache_flush();
	}
}
