<?php
/**
 * Object cache functionality
 *
 * @package  simple-cache
 */

defined( 'ABSPATH' ) || exit;

/**
 * Wrap object caching functionality
 */
class SC_Object_Cache {
	/**
	 * Delete file for clean up
	 *
	 * @since  1.0
	 * @return bool
	 */
	public function clean_up() {

		$file = untrailingslashit( WP_CONTENT_DIR ) . '/object-cache.php';

		if ( ! @unlink( $file ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Write object-cache.php
	 *
	 * @since  1.0
	 * @return bool
	 */
	public function write() {

		$file = untrailingslashit( WP_CONTENT_DIR ) . '/object-cache.php';

		$config = SC_Config::factory()->get();

		$file_string = '';

		if ( ! empty( $config['enable_in_memory_object_caching'] ) && ! empty( $config['advanced_mode'] ) ) {
			$file_string = $this->get_file_code();
		}

		if ( ! file_put_contents( $file, $file_string ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get contents of object cache file
	 *
	 * @since  1.7
	 * @return string
	 */
	public function get_file_code() {
		$config = SC_Config::factory()->get();

		$cache_file = 'memcache-object-cache.php';

		if ( 'redis' === $config['in_memory_cache'] ) {
			$cache_file = 'redis-object-cache.php';
		} elseif ( 'memcachedd' === $config['in_memory_cache'] ) {
			$cache_file = 'memcached-object-cache.php';
		}

		/**
		 * Salt to be used with the cache keys.
		 *
		 * We need a random string not long as cache key size is limited and
		 * not with special characters as they cause issues with some caches.
		 *
		 * @var string
		 */
		$cache_key_salt = wp_generate_password( 10, false );

		// phpcs:disable
		return '<?php ' .
		"\r\n" . "defined( 'ABSPATH' ) || exit;" .
		"\r\n" . "define( 'SC_OBJECT_CACHE', true );" .
		"\r\n" . "defined( 'WP_CACHE_KEY_SALT' ) || define( 'WP_CACHE_KEY_SALT', '{$cache_key_salt}' );" .
		"\r\n" . "\$plugin_path = defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : WP_CONTENT_DIR . '/plugins/';" .
		"\r\n" . "include_once( \$plugin_path . '/simple-cache/inc/pre-wp-functions.php' );" .
		"\r\n" . "\$GLOBALS['sc_config'] = sc_load_config();" .
		"\r\n" . "if ( empty( \$GLOBALS['sc_config'] ) || empty( \$GLOBALS['sc_config']['enable_in_memory_object_caching'] ) ) { return; }" .
		"\r\n" . "if ( @file_exists( \$plugin_path . '/simple-cache/inc/dropins/" . $cache_file . "' ) ) { require_once( \$plugin_path . '/simple-cache/inc/dropins/" . $cache_file . "' ); }" . "\r\n";
		// phpcs:enable
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
