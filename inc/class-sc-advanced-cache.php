<?php
defined( 'ABSPATH' ) || exit;

class SC_Advanced_Cache {

	/**
	 * Setup hooks/filters
	 *
	 * @since  1.0
	 */
	public function setup() {
		add_action( 'admin_notices', array( $this, 'print_notice' ) );
	}

	/**
	 * Print out a warning if WP_CACHE is off when it should be on or if advanced-cache.php is messed up
	 *
	 * @since  1.0
	 */
	public function print_notice() {
		$config = SC_Config::factory()->get();

		if ( empty( $config['enable_page_caching'] ) ) {
			return;
		}

		if ( defined( 'SC_ADVANCED_CACHE' ) && SC_ADVANCED_CACHE ) {
			return;
		}

		?>
		<div class="error">
			<p>
				<?php if ( empty( $config['advanced_mode'] ) ) : ?>
					<?php esc_html_e( 'Woops! Caching was turned off in wp-config.php, or an important file that Simple Cache uses was edited or deleted.' ); ?>
				<?php else : ?>
					<?php esc_html_e( 'Woops! Caching was turned off in wp-config.php, or advanced-cache.php was edited or deleted.' ); ?>
				<?php endif; ?>

				<a href="options.php?wp_http_referer=<?php echo esc_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ); ?>&amp;action=sc_update&amp;sc_settings_nonce=<?php echo wp_create_nonce( 'sc_update_settings' ); ?>" class="button button-primary" style="margin-left: 5px;"><?php esc_html_e( "Fix", 'simple-cache' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Write advanced-cache.php
	 *
	 * @since  1.0
	 * @return bool
	 */
	public function write() {
		global $wp_filesystem;

		$file = untrailingslashit( WP_CONTENT_DIR )  . '/advanced-cache.php';

		$config = SC_Config::factory()->get();

		$file_string = '';

		if ( ! empty( $config['enable_page_caching'] ) ) {
			$cache_file = 'file-based-page-cache.php';

			if ( ! empty( $config['enable_in_memory_object_caching'] ) ) {
				$cache_file = 'batcache.php';
			}

			$file_string = '<?php ' .
				PHP_EOL . "defined( 'ABSPATH' ) || exit;" .
				PHP_EOL . "define( 'SC_ADVANCED_CACHE', true );" .
				PHP_EOL . "if ( is_admin() ) { return; }" .
				PHP_EOL . "if ( ! @file_exists( WP_CONTENT_DIR . '/sc-config/config-' . \$_SERVER['HTTP_HOST'] . '.php' ) ) { return; }" .
				PHP_EOL . "global \$sc_config;" .
				PHP_EOL . "\$sc_config = include( WP_CONTENT_DIR . '/sc-config/config-' . \$_SERVER['HTTP_HOST'] . '.php' );" .
				PHP_EOL . "if ( empty( \$sc_config ) || empty( \$sc_config['enable_page_caching'] ) ) { return; }" .
				PHP_EOL . "require_once( '" . untrailingslashit( plugin_dir_path( __FILE__ ) ) . "/dropins/" . $cache_file . "' ); " . PHP_EOL;

		}

		if ( ! $wp_filesystem->put_contents( $file, $file_string, FS_CHMOD_FILE ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Toggle WP_CACHE on or off in wp-config.php
	 *
	 * @param  boolean $status
	 * @since  1.0
	 * @return boolean
	 */
	public function toggle_caching( $status ) {
		global $wp_filesystem;

		if ( defined( 'WP_CACHE' ) && WP_CACHE === $status ) {
			return;
		}

		// Lets look 4 levels deep for wp-config.php
		$levels = 4;

		$file = '/wp-config.php';
		$config_path = false;

		for ( $i = 1; $i <= 3; $i++ ) {
			if ( $i > 1 ) {
				$file = '/..' . $file;
			}

			if ( $wp_filesystem->exists( untrailingslashit( ABSPATH )  . $file ) ) {
				$config_path = untrailingslashit( ABSPATH )  . $file;
				break;
			}
		}

		// Couldn't find wp-config.php
		if ( ! $config_path ) {
			return false;
		}

		$config_file = explode( PHP_EOL, $wp_filesystem->get_contents( $config_path ) );
		$line_key = false;

		foreach ( $config_file as $key => $line ) {
			if ( ! preg_match( '/^\s*define\(\s*(\'|")([A-Z_]+)(\'|")(.*)/', $line, $match ) ) {
				continue;
			}

			if ( $match[2] == 'WP_CACHE' ) {
				$line_key = $key;
			}
		}

		if ( $line_key !== false ) {
			unset( $config_file[$line_key] );
		}

		$status_string = ( $status ) ? 'true' : 'false';

		array_shift( $config_file );
		array_unshift( $config_file, '<?php', "define( 'WP_CACHE', $status_string ); // Simple Cache" );

		if ( ! $wp_filesystem->put_contents( $config_path, implode( PHP_EOL, $config_file ), FS_CHMOD_FILE ) ) {
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
			$instance->setup();
		}

		return $instance;
	}
}
