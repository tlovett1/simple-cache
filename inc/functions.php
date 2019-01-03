<?php
/**
 * Utility functions for plugin
 *
 * @package  simple-cache
 */

/**
 * Clear the cache
 *
 * @param  bool $network_wide Flush all site caches
 * @since  1.4
 */
function sc_cache_flush( $network_wide = false ) {
	global $wp_filesystem;

	require_once ABSPATH . 'wp-admin/includes/file.php';

	WP_Filesystem();

	$paths = array();

	if ( $network_wide && SC_IS_NETWORK ) {
		$sites = get_sites();

		foreach ( $sites as $site ) {
			switch_to_blog( $site->blog_id );

			$url_parts = parse_url( home_url() );

			$path = sc_get_cache_dir() . '/' . untrailingslashit( $url_parts['host'] );

			if ( ! empty( $url_parts['path'] ) && '/' !== $url_parts['path'] ) {
				$path .= trim( $url_parts['path'], '/' );
			}

			$paths[] = $path;

			restore_current_blog();
		}

	} else {
		$url_parts = parse_url( home_url() );

		$path = sc_get_cache_dir() . '/' . untrailingslashit( $url_parts['host'] );

		if ( ! empty( $url_parts['path'] ) && '/' !== $url_parts['path'] ) {
			$path .= trim( $url_parts['path'], '/' );
		}

		$paths[] = $path;
	}

	foreach ( $paths as $rm_path ) {
		$wp_filesystem->rmdir( $rm_path, true );
	}

	if ( function_exists( 'wp_cache_flush' ) ) {
		wp_cache_flush();
	}
}
