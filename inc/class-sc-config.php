<?php
/**
 * Handle plugin config
 *
 * @package  simple-cache
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class wrapping config functionality
 */
class SC_Config {

	/**
	 * Setup object
	 *
	 * @since 1.0.1
	 * @var   array
	 */
	public $defaults = array();


	/**
	 * Set config defaults
	 *
	 * @since 1.0
	 */
	public function __construct() {

		$this->defaults = array(
			'enable_page_caching'              => array(
				'default'   => false,
				'sanitizer' => array( $this, 'boolval' ),
			),
			'advanced_mode'                    => array(
				'default'   => false,
				'sanitizer' => array( $this, 'boolval' ),
			),
			'enable_in_memory_object_caching'  => array(
				'default'   => false,
				'sanitizer' => array( $this, 'boolval' ),
			),
			'enable_gzip_compression'          => array(
				'default'   => false,
				'sanitizer' => array( $this, 'boolval' ),
			),
			'in_memory_cache'                  => array(
				'default'   => 'memcached',
				'sanitizer' => 'sanitize_text_field',
			),
			'page_cache_length'                => array(
				'default'   => 24,
				'sanitizer' => 'absint',
			),
			'page_cache_length_unit'           => array(
				'default'   => 'hours',
				'sanitizer' => array( $this, 'sanitize_length_unit' ),
			),
			'cache_exception_urls'             => array(
				'default'   => '',
				'sanitizer' => 'wp_kses_post',
			),
			'enable_url_exemption_regex'       => array(
				'default'   => false,
				'sanitizer' => array( $this, 'boolval' ),
			),
			'page_cache_enable_rest_api_cache' => array(
				'default'   => false,
				'sanitizer' => array( $this, 'boolval' ),
			),
			'page_cache_restore_headers'       => array(
				'default'   => false,
				'sanitizer' => array( $this, 'boolval' ),
			),
		);
	}

	/**
	 * Make sure we support old PHP with boolval
	 *
	 * @param  string $value Value to check.
	 * @since  1.0
	 * @return boolean
	 */
	public function boolval( $value ) {
		return (bool) $value;
	}

	/**
	 * Make sure the length unit has an expected value
	 *
	 * @param  string $value Value to sanitize.
	 * @return string
	 */
	public function sanitize_length_unit( $value ) {
		$accepted_values = array( 'minutes', 'hours', 'days', 'weeks' );

		if ( in_array( $value, $accepted_values, true ) ) {
			return $value;
		}

		return 'minutes';
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
	 * Get config file name
	 *
	 * @since  1.7
	 * @return string
	 */
	private function get_config_file_name() {
		if ( SC_IS_NETWORK ) {
			return 'config-network.php';
		} else {
			$home_url_parts = wp_parse_url( home_url() );

			return 'config-' . $home_url_parts['host'] . '.php';
		}
	}

	/**
	 * Get contents of config file
	 *
	 * @since  1.7
	 * @param  array $config Config array to use
	 * @return string
	 */
	public function get_file_code( $config = null ) {
		if ( empty( $config ) ) {
			$config = $this->get();
		}

		// phpcs:disable
		return '<?php ' . "\r\n" . "defined( 'ABSPATH' ) || exit;" . "\r\n" . 'return ' . var_export( wp_parse_args( $config, $this->get_defaults() ), true ) . '; ' . "\r\n";
		// phpcs:enable
	}

	/**
	 * Write config to file
	 *
	 * @since  1.0
	 * @param  array $config Configuration array.
	 * @param  bool  $force_network Force network wide style write
	 * @return bool
	 */
	public function write( $config, $force_network = false ) {

		$config_dir = sc_get_config_dir();

		$file_name = ( $force_network ) ? 'config-network.php' : $this->get_config_file_name();

		$config = wp_parse_args( $config, $this->get_defaults() );

		@mkdir( $config_dir );

		$config_file_string = $this->get_file_code( $config );

		if ( ! file_put_contents( $config_dir . '/' . $file_name, $config_file_string ) ) {
			return false;
		}

		// Delete network config if not network activated
		if ( 'config-network.php' !== $file_name ) {
			@unlink( $config_dir . '/config-network.php', true );
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
		if ( SC_IS_NETWORK ) {
			$config = get_site_option( 'sc_simple_cache', $this->get_defaults() );
		} else {
			$config = get_option( 'sc_simple_cache', $this->get_defaults() );
		}

		return wp_parse_args( $config, $this->get_defaults() );
	}

	/**
	 * Delete files and option for clean up
	 *
	 * @since  1.2.2
	 * @return bool
	 */
	public function clean_up() {

		$config_dir = sc_get_config_dir();

		if ( SC_IS_NETWORK ) {
			delete_site_option( 'sc_simple_cache' );
		} else {
			delete_option( 'sc_simple_cache' );
		}

		@unlink( $config_dir . '/config-network.php', true );

		if ( ! @unlink( $config_dir . '/' . $this->get_config_file_name() ) ) {
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
