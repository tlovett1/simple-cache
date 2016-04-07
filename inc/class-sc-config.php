<?php
defined( 'ABSPATH' ) || exit;

class SC_Config {

	/**
	 * Store default configuration
	 *
	 * @var array
	 */
	public $defaults = array(
		'enable_page_caching'             => array(
			'default'   => false,
			'sanitizer' => 'boolval',
		),
		'advanced_mode'                   => array(
			'default'   => false,
			'sanitizer' => 'boolval',
		),
		'enable_in_memory_object_caching' => array(
			'default'   => false,
			'sanitizer' => 'boolval',
		),
		'in_memory_cache'                 => array(
			'default'   => 'memcached',
			'sanitizer' => 'sanitize_text_field',
		),
	);

	/**
	 * Return defaults
	 *
	 * @since 1.0
	 * @return array
	 */
	public function get_defaults() {
		$defaults = array();

		foreach ( $this->defaults as $key => $default ) {
			$defaults[$key] = $default['default'];
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

		$config_file_string = '<?php ' . PHP_EOL . "defined( 'ABSPATH' ) || exit;" . PHP_EOL . "return " . var_export( $this->config, true ) . "; " . PHP_EOL;

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
		return get_option( 'sc_simple_cache', $this->get_defaults() );
	}

	/**
	 * Verify we have access to the file system
	 *
	 * @since  1.0
	 * @return bool
	 */
	public function verify_access() {
		$creds = request_filesystem_credentials( site_url() . '/wp-admin/', '', false, false, array() );

		if ( ! $creds || ! WP_Filesystem( $creds ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Return an instance of the current class, create one if it doesn't exist
	 *
	 * @since 1.0
	 * @return object
	 */
	public static function factory() {
		static $instance;

		if ( ! $instance ) {
			$instance = new self();
		}

		return $instance;
	}
}
