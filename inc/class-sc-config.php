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
				'default'       => 3600,
				'sanitizer'     => 'intval',
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

		$config_file_string = '<?php ' . PHP_EOL . "defined( 'ABSPATH' ) || exit;" . PHP_EOL . 'return ' . var_export( $this->config, true ) . '; ' . PHP_EOL;

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
	 * Verify we can write to the file system
	 *
	 * @since  1.0
	 * @return boolean
	 */
	public function verify_file_access() {

		ob_start();
		$creds = request_filesystem_credentials( admin_url( 'options-general.php?page=simple-cache' ), '', false, false, null );

		if ( false === $creds ) {
			ob_get_clean();
			return false;
		}

		if ( ! WP_Filesystem( $creds ) ) {
			ob_get_clean();
			return false;
		}

		ob_get_clean();

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
