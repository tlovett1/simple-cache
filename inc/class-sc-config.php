<?php
defined( 'ABSPATH' ) || exit;

class SC_Config {

	/**
	 * Setup object
	 *
	 * @since 1.0.1
	 */
	public $defaults = array();


	public function __construct() {

		$this->defaults = array(
			'enable_page_caching' => array(
				'default'         => false,
				'sanitizer'       => array( $this, 'boolval' ),
			),
			'advanced_mode' => array(
				'default'   => false,
				'sanitizer' => array( $this, 'boolval' ),
			),
			'enable_in_memory_object_caching' => array(
				'default'                     => false,
				'sanitizer'                   => array( $this, 'boolval' ),
			),
			'enable_gzip_compression' => array(
				'default'             => false,
				'sanitizer'           => array( $this, 'boolval' ),
			),
			'in_memory_cache' => array(
				'default'     => 'memcached',
				'sanitizer'   => 'sanitize_text_field',
			),
			'page_cache_length' => array(
				'default'       => 1440, // One day
				'sanitizer'     => 'intval',
			),
			'cache_exception_urls' => array(
				'default'       => '',
				'sanitizer'     => 'wp_kses_post',
			),
		);
	}

	/**
	 * Make sure we support old PHP with boolval
	 *
	 * @param  string $value
	 * @since  1.0
	 * @return boolean
	 */
	public function boolval( $value ) {

		return (bool) $value;
	}

	/**
	 * Return defaults
	 *
	 * @since  1.0
	 * @return array
	 */
	public function get_defaults() {

		$defaults = array();

		foreach ( $this->defaults as $key => $default ) {
			$defaults[ $key ] = $default['default'];
		}

		return $defaults;
	}

	/**
	 * Write config to file
	 *
	 * @since  1.0
	 * @param  array $config
	 * @return bool
	 */
	public function write( $config ) {

		global $wp_filesystem;

		$config_dir = WP_CONTENT_DIR  . '/sc-config';

		$site_url_parts = parse_url( site_url() );

		$config_file = $config_dir  . '/config-' . $site_url_parts['host'] . '.php';

		$this->config = wp_parse_args( $config, $this->get_defaults() );

		$wp_filesystem->mkdir( $config_dir );

		$config_file_string = '<?php ' . "\n\r" . "defined( 'ABSPATH' ) || exit;" . "\n\r" . 'return ' . var_export( $this->config, true ) . '; ' . "\n\r";

		if ( ! $wp_filesystem->put_contents( $config_file, $config_file_string, FS_CHMOD_FILE ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get config from file or cache
	 *
	 * @since  1.0
	 * @return array
	 */
	public function get() {

		$config = get_option( 'sc_simple_cache', $this->get_defaults() );

		return wp_parse_args( $config, $this->get_defaults() );
	}

	/**
	 * Check if a directory is writable and we can create files as the same user as the current file
	 *
	 * @param  string  $dir
	 * @since  1.2.3
	 * @return boolean
	 */
	private function _is_dir_writable( $dir ) {
		$temp_file_name = untrailingslashit( $dir ) .  '/temp-write-test-' . time();
		$temp_handle = fopen( $temp_file_name, 'w' );

		if ( $temp_handle ) {

			// Attempt to determine the file owner of the WordPress files, and that of newly created files
			$wp_file_owner = $temp_file_owner = false;

			if ( function_exists( 'fileowner' ) ) {
				$wp_file_owner = @fileowner( __FILE__ );
				// Pass in the temporary handle to determine the file owner.
				$temp_file_owner = @fileowner( $temp_file_name );

				// Close and remove the temporary file.
				@fclose( $temp_file_name );
				@unlink( $temp_file_name );

				// Return if we cannot determine the file owner, or if the owner IDs do not match.
				if ( $wp_file_owner === false || $wp_file_owner !== $temp_file_owner ) {
					return false;
				}
			} else {
				if ( ! @is_writable( $dir ) ) {
					return false;
				}
			}
		} else {
			return false;
		}

		return true;
	}

	/**
	 * Verify we can write to the file system
	 *
	 * @since  1.0
	 * @return boolean
	 */
	public function verify_file_access() {
		if ( function_exists( 'clearstatcache' ) ) {
			@clearstatcache();
		}

		// First check wp-config.php
		if ( ! @is_writable( ABSPATH . 'wp-config.php' ) && ! @is_writable( ABSPATH . '../wp-config.php' ) ) {
			return false;
		}

		// Now check wp-content. We need to be able to create files of the same user as this file
		if ( ! $this->_is_dir_writable( untrailingslashit( WP_CONTENT_DIR ) ) ) {
			return false;
		}

		// If the cache and/or cache/simple-cache directories exist, make sure it's writeable
		if ( @file_exists( untrailingslashit( WP_CONTENT_DIR ) . '/cache' ) ) {
			if ( ! $this->_is_dir_writable( untrailingslashit( WP_CONTENT_DIR ) . '/cache' ) ) {
				return false;
			}

			if ( @file_exists( untrailingslashit( WP_CONTENT_DIR ) . '/cache/simple-cache' ) ) {
				if ( ! $this->_is_dir_writable( untrailingslashit( WP_CONTENT_DIR ) . '/cache/simple-cache' ) ) {
					return false;
				}
			}
		}

		// If the sc-config directory exists, make sure it's writeable
		if ( @file_exists( untrailingslashit( WP_CONTENT_DIR ) . '/sc-config' ) ) {
			if ( ! $this->_is_dir_writable( untrailingslashit( WP_CONTENT_DIR ) . '/sc-config' ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Delete files and option for clean up
	 *
	 * @since  1.2.2
	 * @return bool
	 */
	public function clean_up() {

		global $wp_filesystem;

		$folder = untrailingslashit( WP_CONTENT_DIR )  . '/sc-config';

		delete_option( 'sc_simple_cache' );

		if ( ! $wp_filesystem->delete( $folder, true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Return an instance of the current class, create one if it doesn't exist
	 *
	 * @since  1.0
	 * @return SC_Config
	 */
	public static function factory() {

		static $instance;

		if ( ! $instance ) {
			$instance = new self();
		}

		return $instance;
	}
}
