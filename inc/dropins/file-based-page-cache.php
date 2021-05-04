<?php
/**
 * File based page cache drop in
 *
 * @package  simple-cache
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'sc_serve_file_cache' ) ) {
	return;
}

// Don't cache robots.txt or htacesss.
if ( strpos( $_SERVER['REQUEST_URI'], 'robots.txt' ) !== false || strpos( $_SERVER['REQUEST_URI'], '.htaccess' ) !== false ) {
	return;
}

// Don't cache non-GET requests.
if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'GET' !== $_SERVER['REQUEST_METHOD'] ) {
	return;
}

$file_extension = $_SERVER['REQUEST_URI'];
$file_extension = preg_replace( '#^(.*?)\?.*$#', '$1', $file_extension );
$file_extension = trim( preg_replace( '#^.*\.(.*)$#', '$1', $file_extension ) );

// Don't cache disallowed extensions. Prevents wp-cron.php, xmlrpc.php, etc.
if ( ! preg_match( '#index\.php$#i', $_SERVER['REQUEST_URI'] ) && in_array( $file_extension, array( 'php', 'xml', 'xsl' ), true ) ) {
	return;
}

// Don't cache if logged in.
if ( ! empty( $_COOKIE ) ) {
	$wp_cookies = array( 'wordpressuser_', 'wordpresspass_', 'wordpress_sec_', 'wordpress_logged_in_' );

	foreach ( $_COOKIE as $key => $value ) {
		foreach ( $wp_cookies as $cookie ) {
			if ( strpos( $key, $cookie ) !== false ) {
				// Logged in!
				return;
			}
		}
	}

	if ( ! empty( $_COOKIE['sc_commented_posts'] ) ) {
		foreach ( $_COOKIE['sc_commented_posts'] as $path ) {
			if ( rtrim( $path, '/' ) === rtrim( $_SERVER['REQUEST_URI'], '/' ) ) {
				// User commented on this post.
				return;
			}
		}
	}
}

// Deal with optional cache exceptions.
if ( ! empty( $GLOBALS['sc_config']['advanced_mode'] ) && ! empty( $GLOBALS['sc_config']['cache_exception_urls'] ) ) {
	$exceptions = preg_split( '#(\n|\r)#', $GLOBALS['sc_config']['cache_exception_urls'] );

	$regex = ( ! empty( $GLOBALS['sc_config']['enable_url_exemption_regex'] ) ) ? true : false;

	foreach ( $exceptions as $exception ) {
		if ( sc_url_exception_match( $exception, $regex ) ) {
			// Exception match.
			return;
		}
	}
}

sc_serve_file_cache();

ob_start( 'sc_file_cache' );
