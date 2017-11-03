<?php
/**
 * The simple-cache-wp-cli.php file.
 *
 * Adds some WP-CLI commands to perform certain operations with the
 * simple-cache plugin options.
 */

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}


/**
 * Implements example command.
 */
class Simple_Cache_WP_CLI extends WP_CLI_Command {

	/**
	 * Used to toggle either the cache or gzip compression options for
	 * simple-cache to '1' - IE 'ON'.
	 *
	 * ## OPTIONS
	 *
	 * [--option=<option>]
	 * : (optional) Option to toggle, either 'cache' or 'compression'.
	 * ---
	 * default: cache
	 * options:
	 *   - cache
	 *   - compression
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp simple-cache on
	 *     wp simple-cache on --option=compression
	 *
	 * @when before_wp_load
	 */
	function on( $args, $assoc_args ) {

		// get the options array and assosiative args.
		$sc_options = get_option( 'sc_simple_cache' );
		$option = $assoc_args['option'];
		// an empty string to start as a message.
		$message = '';
		switch ( $option ) {
			case 'cache':
				if ( 1 === $sc_options['enable_page_caching'] ) {
					// cache is enabled aready set a message and do nothing.
					$message = 'Cache already on';
				} else {
					// set the new value and update the options array.
					$sc_options['enable_page_caching'] = 1;
					$updated = update_option( 'sc_simple_cache', $sc_options );
					SC_Advanced_Cache::factory()->toggle_caching( true );
					$message = 'Cache toggled on';
				}
				break;

			case 'compression':
				if ( 1 === $sc_options['enable_gzip_compression'] ) {
					// compression is enabled aready set a message and do nothing.
					$message = 'Compression already on';
				} else {
					// set the new value and update the options array.
					$sc_options['enable_gzip_compression'] = 1;
					$updated = update_option( 'sc_simple_cache', $sc_options );
					$message = 'Compression toggled on';
				}

				break;

			default:
		}
		// if we have a successful $updated value then success... else error.
		if ( isset( $updated ) && $updated ) {
			WP_CLI::success( "$message" );
		} else {
			WP_CLI::error( "$message" );
		}

	}

	/**
	 * Used to toggle either the cache or gzip compression options for
	 * simple-cache to '0' - IE 'OFF'.
	 * ## OPTIONS
	 *
	 * [--option=<option>]
	 * : (optional) Option to toggle, either 'cache' or 'compression'.
	 * ---
	 * default: cache
	 * options:
	 *   - cache
	 *   - compression
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp simple-cache off
	 *     wp simple-cache off --option=compression
	 *
	 * @when before_wp_load
	 */
	function off( $args, $assoc_args ) {

		// get the options array and assosiative args.
		$sc_options = get_option( 'sc_simple_cache' );
		$option = $assoc_args['option'];
		// an empty string to start as a message.
		$message = '';
		switch ( $option ) {
			case 'cache':
				if ( 0 === $sc_options['enable_page_caching'] ) {
					// cache is disabled aready set a message and do nothing.
					$message = 'Cache already off';
				} else {
					// set the new value and update the options array.
					$sc_options['enable_page_caching'] = 0;
					$updated = update_option( 'sc_simple_cache', $sc_options );
					SC_Advanced_Cache::factory()->toggle_caching( false );
					$message = 'Cache toggled off';
				}
				break;

			case 'compression':
				if ( 0 === $sc_options['enable_gzip_compression'] ) {
					// compression is disabled aready set a message and do nothing.
					$message = 'Compression already off';
				} else {
					// set the new value and update the options array.
					$sc_options['enable_gzip_compression'] = 0;
					$updated = update_option( 'sc_simple_cache', $sc_options );
					$message = 'Compression toggled off';
				}

				break;

			default:
		}
		// if we have a successful $updated value then success... else error.
		if ( isset( $updated ) && $updated ) {
			WP_CLI::success( "$message" );
		} else {
			WP_CLI::error( "$message" );
		}
	}
}

WP_CLI::add_command( 'simple-cache', 'Simple_Cache_WP_CLI' );
