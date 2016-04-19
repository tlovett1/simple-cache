<?php

/**
 * Clear the cache
 *
 * @since  1.4
 */
function sc_cache_flush() {
	global $wp_filesystem;

	require_once( ABSPATH . 'wp-admin/includes/file.php');

	WP_Filesystem();

	$wp_filesystem->rmdir( untrailingslashit( WP_CONTENT_DIR ) . '/cache/simple-cache', true );

	if ( function_exists( 'wp_cache_flush' ) ) {
		wp_cache_flush();
	}
}
