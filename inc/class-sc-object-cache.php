<?php
defined( 'ABSPATH' ) || exit;

class SC_Object_Cache {

	/**
	 * Setup hooks/filters
	 *
	 * @since 1.0
	 */
	public function setup() {

		add_action( 'admin_notices', array( $this, 'print_notice' ) );
	}

	/**
	 * Print out a warning if object-cache.php is messed up
	 *
	 * @since 1.0
	 */
	public function print_notice() {

		$cant_write = get_option( 'sc_cant_write', false );

		if ( $cant_write ) {
			return;
		}

		$config = SC_Config::factory()->get();

		if ( empty( $config['enable_in_memory_object_caching'] ) || empty( $config['advanced_mode'] ) ) {
			return;
		}

		if ( defined( 'SC_OBJECT_CACHE' ) && SC_OBJECT_CACHE ) {
			return;
		}

		?>
	 <div class="error">
	  <p>
		<?php esc_html_e( 'wp-content/object-cache.php was edited or deleted. Simple Cache is not able to utilize object caching.' ); ?>

				<a href="options-general.php?page=simple-cache&amp;wp_http_referer=<?php echo esc_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ); ?>&amp;action=sc_update&amp;sc_settings_nonce=<?php echo wp_create_nonce( 'sc_update_settings' ); ?>" class="button button-primary" style="margin-left: 5px;"><?php esc_html_e( 'Fix', 'simple-cache' ); ?></a>
	  </p>
	 </div>
		<?php
	}

	/**
	 * Delete file for clean up
	 *
	 * @since  1.0
	 * @return bool
	 */
	public function clean_up() {

		global $wp_filesystem;

		$file = untrailingslashit( WP_CONTENT_DIR )  . '/object-cache.php';

		if ( ! $wp_filesystem->delete( $file ) ) {
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

		global $wp_filesystem;

		$file = untrailingslashit( WP_CONTENT_DIR )  . '/object-cache.php';

		$config = SC_Config::factory()->get();

		$file_string = '';

		if ( ! empty( $config['enable_in_memory_object_caching'] ) && ! empty( $config['advanced_mode'] ) ) {
			$cache_file = 'memcached-object-cache.php';

			if ( 'redis' === $config['in_memory_cache'] ) {
				$cache_file = 'redis-object-cache.php';
			}

			$file_string = '<?php ' .
			"\n\r" . "defined( 'ABSPATH' ) || exit;" .
			"\n\r" . "define( 'SC_OBJECT_CACHE', true );" .
			"\n\r" . "if ( ! @file_exists( WP_CONTENT_DIR . '/sc-config/config-' . \$_SERVER['HTTP_HOST'] . '.php' ) ) { return; }" .
			"\n\r" . "\$GLOBALS['sc_config'] = include( WP_CONTENT_DIR . '/sc-config/config-' . \$_SERVER['HTTP_HOST'] . '.php' );" .
			"\n\r" . "if ( empty( \$GLOBALS['sc_config'] ) || empty( \$GLOBALS['sc_config']['enable_in_memory_object_caching'] ) ) { return; }" .
			"\n\r" . "if ( @file_exists( '" . untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/dropins/' . $cache_file . "' ) ) { require_once( '" . untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/dropins/' . $cache_file . "' ); }" . "\n\r";

		}

		if ( ! $wp_filesystem->put_contents( $file, $file_string, FS_CHMOD_FILE ) ) {
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
			$instance->setup();
		}

		return $instance;
	}
}
