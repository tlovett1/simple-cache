<?php
/**
 * Handle all admin notices
 *
 * @package  simple-cache
 */

defined( 'ABSPATH' ) || exit;

/**
 * Wrap notices functionality
 */
class SC_Notices {

	/**
	 * Setup actions and filters
	 *
	 * @since 1.0
	 */
	private function setup() {
		if ( SC_IS_NETWORK ) {
			add_action( 'network_admin_notices', array( $this, 'error_notice' ) );
			add_action( 'network_admin_notices', array( $this, 'setup_notice' ) );
		} else {
			add_action( 'admin_notices', array( $this, 'error_notice' ) );
			add_action( 'admin_notices', array( $this, 'setup_notice' ) );
		}
	}

	/**
	 * Output turn on notice
	 *
	 * @since 1.0
	 */
	public function setup_notice() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( SC_IS_NETWORK ) {
			$cant_write = get_site_option( 'sc_cant_write', false );
		} else {
			$cant_write = get_option( 'sc_cant_write', false );
		}

		if ( $cant_write ) {
			return;
		}

		$config = SC_Config::factory()->get();

		if ( ! empty( $config['enable_page_caching'] ) || ! empty( $config['advanced_mode'] ) ) {
			return;
		}

		$file_name = ( SC_IS_NETWORK ) ? 'settings.php' : 'options-general.php';

		?>
		<div class="notice notice-warning">
			<p>
				<?php esc_html_e( "Simple Cache won't work until you turn it on.", 'simple-cache' ); ?>
				<a href="<?php echo esc_attr( $file_name ); ?>?page=simple-cache&amp;wp_http_referer=<?php echo esc_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ); ?>&amp;action=sc_update&amp;sc_settings_nonce=<?php echo esc_attr( wp_create_nonce( 'sc_update_settings' ) ); ?>&amp;sc_simple_cache[enable_page_caching]=1" class="button button-primary" style="margin-left: 5px;"><?php esc_html_e( 'Turn On Caching', 'simple-cache' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Output error notices
	 *
	 * @since  1.7
	 */
	public function error_notice() {
		if ( SC_IS_NETWORK ) {
			$setting_file = 'settings.php';
			$cant_write   = get_site_option( 'sc_cant_write', array() );
		} else {
			$setting_file = 'options-general.php';
			$cant_write   = get_option( 'sc_cant_write', array() );
		}

		$config = SC_Config::factory()->get();

		$object_cache_broken = ! empty( $config['enable_in_memory_object_caching'] ) && ! empty( $config['advanced_mode'] ) && ! empty( $config['enable_in_memory_object_caching'] ) && ( ! defined( 'SC_OBJECT_CACHE' ) || ! SC_OBJECT_CACHE );

		$wp_cache_broken = ! empty( $config['enable_page_caching'] ) && ( ! defined( 'WP_CACHE' ) || ! WP_CACHE );

		$advanced_cache_broken = ! $wp_cache_broken && ! empty( $config['enable_page_caching'] ) && ( ! defined( 'SC_ADVANCED_CACHE' ) || ! SC_ADVANCED_CACHE );

		if ( empty( $cant_write ) && ! $object_cache_broken && ! $wp_cache_broken && ! $advanced_cache_broken ) {
			return;
		}

		?>
		<div class="error sc-notice">
			<p><?php esc_html_e( 'Simple Cache has encountered the following error(s):', 'simple-cache' ); ?></p>
			<ol>
				<?php if ( in_array( 'cache', $cant_write, true ) && ! $config['enable_in_memory_object_caching'] ) : ?>
					<li>
						<?php esc_html_e( 'Simple Cache is not able to write data to the cache directory.', 'simple-cache' ); ?>
					</li>
				<?php endif; ?>

				<?php if ( in_array( 'wp-config', $cant_write, true ) || $wp_cache_broken ) : ?>
					<li>
						<?php echo wp_kses_post( __( '<code>define( "WP_CACHE", true );</code> is not in wp-config.php. Either click "Attempt Fix" or add the code manually.', 'simple-cache' ) ); ?>
					</li>
				<?php endif; ?>

				<?php if ( in_array( 'config', $cant_write, true ) ) : ?>
					<li>
						<?php echo wp_kses_post( sprintf( __( 'Simple Cache could not create the necessary config file. Either click "Attempt Fix" or add the following code to <code>%s</code>:', 'simple-cache' ), esc_html( sc_get_config_dir() . '/' . sc_get_config_file_name() ) ) ); ?>

						<pre><?php echo esc_html( SC_Config::factory()->get_file_code() ); ?></pre>
					</li>
				<?php endif; ?>

				<?php if ( in_array( 'wp-content', $cant_write, true ) || $object_cache_broken ) : ?>
					<li>
						<?php echo wp_kses_post( sprintf( __( 'Simple Cache could not write object-cache.php to your wp-content directory or the file has been tampered with. Either click "Attempt Fix" or add the following code manually to <code>wp-content/object-cache.php</code>:', 'simple-cache' ), esc_html( sc_get_config_dir() . '/' . sc_get_config_file_name() ) ) ); ?>

						<pre><?php echo esc_html( SC_Object_Cache::factory()->get_file_code() ); ?></pre>
					</li>
				<?php endif; ?>

				<?php if ( in_array( 'wp-content', $cant_write, true ) || $advanced_cache_broken ) : ?>
					<li>
						<?php echo wp_kses_post( sprintf( __( 'Simple Cache could not write advanced-cache.php to your wp-content directory or the file has been tampered with. Either click "Attempt Fix" or add the following code manually to <code>wp-content/advanced-cache.php</code>:', 'simple-cache' ), esc_html( sc_get_config_dir() . '/' . sc_get_config_file_name() ) ) ); ?>

						<pre><?php echo esc_html( SC_Advanced_Cache::factory()->get_file_code() ); ?></pre>
					</li>
				<?php endif; ?>
			</ol>

			<p>
				<a href="<?php echo esc_attr( $setting_file ); ?>?page=simple-cache&amp;wp_http_referer=<?php echo esc_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ); ?>&amp;action=sc_update&amp;sc_settings_nonce=<?php echo esc_attr( wp_create_nonce( 'sc_update_settings' ) ); ?>" class="button button-primary" style="margin-left: 5px;"><?php esc_html_e( 'Attempt Fix', 'simple-cache' ); ?></a>
			</p>
		</div>
		<?php
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
