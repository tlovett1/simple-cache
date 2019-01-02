<?php
/**
 * Holds functions that can be loaded in advanced-cache.php
 *
 * @since  1.7
 * @package  simple-cache
 */

/**
 * Cache output before it goes to the browser
 *
 * @param  string $buffer Page HTML.
 * @param  int    $flags OB flags to be passed through.
 * @since  1.7
 * @return string
 */
function sc_file_cache( $buffer, $flags ) {
	global $post;

	$cache_dir = sc_get_cache_dir();

	if ( strlen( $buffer ) < 255 ) {
		return $buffer;
	}

	// Don't cache search, 404, or password protected.
	if ( is_404() || is_search() || ! empty( $post->post_password ) ) {
		return $buffer;
	}

	/**
	 * Set the permission constants if not already set. Normally, this is taken care of in
	 * WP_Filesystem constructor, but it is not invoked here, because WP_Filesystem_Direct
	 * is instantiated directly.
	 */
	if ( ! defined( 'FS_CHMOD_DIR' ) ) {
		define( 'FS_CHMOD_DIR', ( fileperms( ABSPATH ) & 0777 | 0755 ) );
	}
	if ( ! defined( 'FS_CHMOD_FILE' ) ) {
		define( 'FS_CHMOD_FILE', ( fileperms( ABSPATH . 'index.php' ) & 0777 | 0644 ) );
	}

	include_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
	include_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';

	$filesystem = new WP_Filesystem_Direct( new StdClass() );

	// Make sure we can read/write files to wp-content/cache/
	if ( ! $filesystem->exists( dirname( $cache_dir ) ) ) {
		if ( ! $filesystem->mkdir( dirname( $cache_dir ) ) ) {
			// Can not cache!
			return $buffer;
		}
	}

	// Make sure we can read/write files to cache dir
	if ( ! $filesystem->exists( $cache_dir ) ) {
		if ( ! $filesystem->mkdir( $cache_dir ) ) {
			// Can not cache!
			return $buffer;
		}
	}

	$buffer = apply_filters( 'sc_pre_cache_buffer', $buffer );

	$url_path = sc_get_url_path();

	$dirs = explode( '/', $url_path );

	$path = $cache_dir;

	foreach ( $dirs as $dir ) {
		if ( ! empty( $dir ) ) {
			$path .= '/' . $dir;

			if ( ! $filesystem->exists( $path ) ) {
				if ( ! $filesystem->mkdir( $path ) ) {
					// Can not cache!
					return $buffer;
				}
			}
		}
	}

	$modified_time = time(); // Make sure modified time is consistent.

	// Prevent mixed content when there's an http request but the site URL uses https.
	$home_url = get_home_url();
	if ( ! is_ssl() && 'https' === strtolower( parse_url( $home_url, PHP_URL_SCHEME ) ) ) {
		$https_home_url = $home_url;
		$http_home_url  = str_replace( 'https://', 'http://', $https_home_url );
		$buffer         = str_replace( esc_url( $http_home_url ), esc_url( $https_home_url ), $buffer );
	}

	if ( preg_match( '#</html>#i', $buffer ) ) {
		$buffer .= "\n<!-- Cache served by Simple Cache - Last modified: " . gmdate( 'D, d M Y H:i:s', $modified_time ) . " GMT -->\n";
	}

	if ( ! empty( $GLOBALS['sc_config']['enable_gzip_compression'] ) && function_exists( 'gzencode' ) ) {
		$filesystem->put_contents( $path . '/index.gzip.html', gzencode( $buffer, 3 ), FS_CHMOD_FILE );
		$filesystem->touch( $path . '/index.gzip.html', $modified_time );
	} else {
		$filesystem->put_contents( $path . '/index.html', $buffer, FS_CHMOD_FILE );
		$filesystem->touch( $path . '/index.html', $modified_time );
	}

	header( 'Cache-Control: no-cache' ); // Check back every time to see if re-download is necessary.

	header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $modified_time ) . ' GMT' );

	if ( function_exists( 'ob_gzhandler' ) && ! empty( $GLOBALS['sc_config']['enable_gzip_compression'] ) ) {
		return ob_gzhandler( $buffer, $flags );
	} else {
		return $buffer;
	}
}

/**
 * Get URL path for caching
 *
 * @since  1.0
 * @return string
 */
function sc_get_url_path() {

	$host = ( isset( $_SERVER['HTTP_HOST'] ) ) ? $_SERVER['HTTP_HOST'] : '';

	return rtrim( $host, '/' ) . $_SERVER['REQUEST_URI'];
}

/**
 * Optionally serve cache and exit
 *
 * @since 1.0
 */
function sc_serve_file_cache() {
	$cache_dir = ( defined( 'SC_CACHE_DIR') ) ? rtrim( SC_CACHE_DIR, '/' ) : rtrim( WP_CONTENT_DIR, '/' ) . '/cache/simple-cache';

	$file_name = 'index.html';

	if ( function_exists( 'gzencode' ) && ! empty( $GLOBALS['sc_config']['enable_gzip_compression'] ) ) {
		$file_name = 'index.gzip.html';
	}

	$path = $cache_dir . '/' . rtrim( sc_get_url_path(), '/' ) . '/' . $file_name;

	$modified_time = (int) @filemtime( $path );

	header( 'Cache-Control: no-cache' ); // Check back in an hour.

	if ( ! empty( $modified_time ) && ! empty( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) && strtotime( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) === $modified_time ) {
		if ( function_exists( 'gzencode' ) && ! empty( $GLOBALS['sc_config']['enable_gzip_compression'] ) ) {
			header( 'Content-Encoding: gzip' );
		}

		header( $_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified', true, 304 );
		exit;
	}

	if ( @file_exists( $path ) && @is_readable( $path ) ) {
		if ( function_exists( 'gzencode' ) && ! empty( $GLOBALS['sc_config']['enable_gzip_compression'] ) ) {
			header( 'Content-Encoding: gzip' );
		}

		@readfile( $path );

		exit;
	}
}

/**
 * Get cache directory
 *
 * @since  1.7
 * @return string
 */
function sc_get_cache_dir() {
	return ( defined( 'SC_CACHE_DIR') ) ? rtrim( SC_CACHE_DIR, '/' ) : rtrim( WP_CONTENT_DIR, '/' ) . '/cache/simple-cache';
}

/**
 * Get config directory
 *
 * @since 1.7
 * @return string
 */
function sc_get_config_dir() {
	return ( defined( 'SC_CONFIG_DIR') ) ? rtrim( SC_CONFIG_DIR, '/' ) : rtrim( WP_CONTENT_DIR, '/' ) . '/sc-config';
}

/**
 * Gets name of the config file.
 *
 * @since  1.7
 * @return string
 */
function sc_get_config_file_name() {
	return 'config-' . $_SERVER['HTTP_HOST'] . '.php';
}

/**
 * Load config. Use network if it exists. Only intended to be used pre-wp.
 *
 * @since  1.7
 * @return bool|array
 */
function sc_load_config() {
	if ( @file_exists( sc_get_config_dir() . '/config-network.php' ) ) {
		return  include( sc_get_config_dir() . '/config-network.php' );
	} elseif ( @file_exists( sc_get_config_dir() . '/' . sc_get_config_file_name() ) ) {
		return  include( sc_get_config_dir() . '/' . sc_get_config_file_name() );
	}

	return false;
}

/**
 * Return true of exception url matches current url
 *
 * @param  string $exception Exceptions to check URL against.
 * @param  bool   $regex Whether to check with regex or not.
 * @since  1.6
 * @return boolean
 */
function sc_url_exception_match( $exception, $regex = false ) {
	if ( preg_match( '#^[\s]*$#', $exception ) ) {
		return false;
	}

	$exception = trim( $exception );

	if ( ! preg_match( '#^/#', $exception ) ) {

		$url = rtrim( 'http' . ( isset( $_SERVER['HTTPS'] ) ? 's' : '' ) . '://' . "{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}", '/' );

		if ( $regex ) {
			if ( preg_match( '#^' . $exception . '$#', $url ) ) {
				// Exception match!
				return true;
			}
		} elseif ( preg_match( '#\*$#', $exception ) ) {
			$filtered_exception = str_replace( '*', '', $exception );

			if ( preg_match( '#^' . $filtered_exception . '#', $url ) ) {
				// Exception match!
				return true;
			}
		} else {
			$exception = rtrim( $exception, '/' );

			if ( strtolower( $exception ) === strtolower( $url ) ) {
				// Exception match!
				return true;
			}
		}
	} else {
		$path = $_SERVER['REQUEST_URI'];

		if ( $regex ) {
			if ( preg_match( '#^' . $exception . '$#', $path ) ) {
				// Exception match!
				return true;
			}
		} elseif ( preg_match( '#\*$#', $exception ) ) {
			$filtered_exception = preg_replace( '#/?\*#', '', $exception );

			if ( preg_match( '#^' . $filtered_exception . '#i', $path ) ) {
				// Exception match!
				return true;
			}
		} else {
			if ( '/' !== $path ) {
				$path = rtrim( $path, '/' );
			}

			if ( '/' !== $exception ) {
				$exception = rtrim( $exception, '/' );
			}

			if ( strtolower( $exception ) === strtolower( $path ) ) {
				// Exception match!
				return true;
			}
		}
	}

	return false;
}
