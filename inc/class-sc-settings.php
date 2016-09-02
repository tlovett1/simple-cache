<?php
defined( 'ABSPATH' ) || exit;

class SC_Settings {

	public function __construct() {

	}

	/**
	 * Setup the plugin
	 *
	 * @since 1.0
	 */
	public function setup() {

		add_action( 'admin_menu', array( $this, 'action_admin_menu' ) );
		add_action( 'load-settings_page_simple-cache', array( $this, 'update' ) );
		add_action( 'load-settings_page_simple-cache', array( $this, 'purge_cache' ) );
		add_action( 'admin_notices', array( $this, 'setup_notice' ) );
		add_action( 'admin_notices', array( $this, 'cant_write_notice' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts_styles' ) );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ) );

	}

	/**
	 * Add purge cache button to admin bar
	 *
	 * @since 1,3
	 */
	public function admin_bar_menu() {
		global $wp_admin_bar;

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$wp_admin_bar->add_menu( array(
			'id'     => 'sc-purge-cache',
			'parent' => 'top-secondary',
			'href' => esc_url( admin_url( 'options-general.php?page=simple-cache&amp;wp_http_referer=' . esc_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ) . '&amp;action=sc_purge_cache&amp;sc_cache_nonce=' . wp_create_nonce( 'sc_purge_cache' ) ) ),
			'title'  => esc_html__( 'Purge Cache', 'simple-cache' ),
		) );
	}

	/**
	 * Output turn on notice
	 *
	 * @since 1.0
	 */
	public function setup_notice() {

		$cant_write = get_option( 'sc_cant_write', false );

		if ( $cant_write ) {
			return;
		}

		$config = SC_Config::factory()->get();

		if ( ! empty( $config['enable_page_caching'] ) || ! empty( $config['advanced_mode'] ) ) {
			return;
		}

		?>
		<div class="notice notice-warning">
			<p>
				<?php esc_html_e( "Simple Cache won't work until you turn it on.", 'simple-cache' ); ?>
				<a href="options-general.php?page=simple-cache&amp;wp_http_referer=<?php echo esc_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ); ?>&amp;action=sc_update&amp;sc_settings_nonce=<?php echo wp_create_nonce( 'sc_update_settings' ); ?>&amp;sc_simple_cache[enable_page_caching]=1" class="button button-primary" style="margin-left: 5px;"><?php esc_html_e( 'Turn On Caching', 'simple-cache' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Output can't write notice
	 *
	 * @since 1.0
	 */
	public function cant_write_notice() {

		global $pagenow;

		$cant_write = get_option( 'sc_cant_write', false );

		if ( ! $cant_write ) {
			return;
		}

		?>
		<div class="notice notice-error">
			<p>
				<?php esc_html_e( "Simple Cache can't create or modify needed files on your system. Specifically, Simple Cache needs to write to wp-config.php and /wp-content using PHP's fopen() function. Contact your host.", 'simple-cache' ); ?>
				<a href="options-general.php?page=simple-cache&amp;wp_http_referer=<?php echo esc_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ); ?>&amp;action=sc_update&amp;sc_settings_nonce=<?php echo wp_create_nonce( 'sc_update_settings' ); ?>" class="button button-primary" style="margin-left: 5px;"><?php esc_html_e( 'Try Again', 'simple-cache' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Enqueue settings screen js/css
	 *
	 * @since 1.0
	 */
	public function action_admin_enqueue_scripts_styles() {

		global $pagenow;

		if ( 'options-general.php' == $pagenow && ! empty( $_GET['page'] ) && 'simple-cache' == $_GET['page'] ) {

			if ( defined( WP_DEBUG ) && WP_DEBUG ) {
				$js_path = '/assets/js/src/settings.js';
				$css_path = '/assets/css/settings.css';
			} else {
				$js_path = '/assets/js/settings.min.js';
				$css_path = '/assets/css/settings.css';
			}

			wp_enqueue_script( 'sc-settings', plugins_url( $js_path, dirname( __FILE__ ) ), array( 'jquery' ), SC_VERSION, true );
			wp_enqueue_style( 'sc-settings', plugins_url( $css_path, dirname( __FILE__ ) ), array(), SC_VERSION );
		}
	}

	/**
	 * Add options page
	 *
	 * @since 1.0
	 */
	public function action_admin_menu() {

		add_submenu_page( 'options-general.php', esc_html__( 'Simple Cache', 'simple-cache' ), esc_html__( 'Simple Cache', 'simple-cache' ), 'manage_options', 'simple-cache', array( $this, 'screen_options' ) );
	}

	/**
	 * Purge cache manually
	 *
	 * @since 1.0
	 */
	public function purge_cache() {

		if ( ! empty( $_REQUEST['action'] ) && 'sc_purge_cache' === $_REQUEST['action'] ) {
			if ( ! current_user_can( 'manage_options' ) || empty( $_REQUEST['sc_cache_nonce'] ) || ! wp_verify_nonce( $_REQUEST['sc_cache_nonce'], 'sc_purge_cache' ) ) {
				wp_die( 'Cheatin, eh?' );
			}

			sc_cache_flush();

			if ( ! empty( $_REQUEST['wp_http_referer'] ) ) {
				wp_redirect( $_REQUEST['wp_http_referer'] );
				exit;
			}
		}
	}

	/**
	 * Handle setting changes
	 *
	 * @since 1.0
	 */
	public function update() {

		if ( ! empty( $_REQUEST['action'] ) && 'sc_update' === $_REQUEST['action'] ) {

			if ( ! current_user_can( 'manage_options' ) || empty( $_REQUEST['sc_settings_nonce'] ) || ! wp_verify_nonce( $_REQUEST['sc_settings_nonce'], 'sc_update_settings' ) ) {
				wp_die( esc_html__( 'Cheatin, eh?', 'simple-cache' ) );
			}

			if ( ! SC_Config::factory()->verify_file_access() ) {
				update_option( 'sc_cant_write', true );
				wp_redirect( $_REQUEST['wp_http_referer'] );
				exit;
			}

			delete_option( 'sc_cant_write' );

			$defaults = SC_Config::factory()->defaults;
			$current_config = SC_Config::factory()->get();

			foreach ( $defaults as $key => $default ) {
				$clean_config[ $key ] = $current_config[ $key ];

				if ( isset( $_REQUEST['sc_simple_cache'][ $key ] ) ) {
					$clean_config[ $key ] = call_user_func( $default['sanitizer'], $_REQUEST['sc_simple_cache'][ $key ] );
				}
			}

			// Back up configration in options
			update_option( 'sc_simple_cache', $clean_config );

			WP_Filesystem();

			SC_Config::factory()->write( $clean_config );

			SC_Advanced_Cache::factory()->write();
			SC_Object_Cache::factory()->write();

			if ( $clean_config['enable_page_caching'] ) {
				SC_Advanced_Cache::factory()->toggle_caching( true );
			} else {
				SC_Advanced_Cache::factory()->toggle_caching( false );
			}

			// Reschedule cron events
			SC_Cron::factory()->unschedule_events();
			SC_Cron::factory()->schedule_events();

			if ( ! empty( $_REQUEST['wp_http_referer'] ) ) {
				wp_redirect( $_REQUEST['wp_http_referer'] );
				exit;
			}
		}
	}

	/**
	 * Sanitize options
	 *
	 * @param  array $option
	 * @since  1.0
	 * @return array
	 */
	public function sanitize_options( $option ) {

		$new_option = array();

		if ( ! empty( $option['enable_page_caching'] ) ) {
			$new_option['enable_page_caching'] = true;
		} else {
			$new_option['enable_page_caching'] = false;
		}

		return $new_option;
	}

	/**
	 * Output settings
	 *
	 * @since 1.0
	 */
	public function screen_options() {

		$config = SC_Config::factory()->get();

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Simple Cache Settings', 'simple-cache' ); ?></h1>

			<form action="" method="post">
				<?php wp_nonce_field( 'sc_update_settings', 'sc_settings_nonce' ); ?>
				<input type="hidden" name="action" value="sc_update">
				<input type="hidden" name="wp_http_referer" value="<?php echo esc_attr( wp_unslash( $_SERVER['REQUEST_URI'] ) ); ?>'" />

				<div class="advanced-mode-wrapper">
					<label for="sc_advanced_mode"><?php esc_html_e( 'Enable Advanced Mode', 'simple-cache' ); ?></label>
					<select name="sc_simple_cache[advanced_mode]" id="sc_advanced_mode">
						<option value="0"><?php esc_html_e( 'No', 'simple-cache' ); ?></option>
						<option <?php selected( $config['advanced_mode'], true ); ?> value="1"><?php esc_html_e( 'Yes', 'simple-cache' ); ?></option>
					</select>
				</div>

				<table class="form-table sc-simple-mode-table <?php if ( empty( $config['advanced_mode'] ) ) : ?>show<?php endif; ?>">
					<tbody>
						<tr>
							<th scope="row"><label for="sc_enable_caching_simple"><span class="setting-highlight">*</span><?php _e( 'Enable Caching', 'simple-cache' ); ?></label></th>
							<td>
								<select <?php if ( ! empty( $config['advanced_mode'] ) ) : ?>disabled<?php endif; ?> name="sc_simple_cache[enable_page_caching]" id="sc_enable_page_caching_simple">
									<option value="0"><?php esc_html_e( 'No', 'simple-cache' ); ?></option>
									<option <?php selected( $config['enable_page_caching'], true ); ?> value="1"><?php esc_html_e( 'Yes', 'simple-cache' ); ?></option>
								</select>

								<p class="description"><?php esc_html_e( 'Turn this on to get started. This setting turns on caching and is really all you need.', 'simple-cache' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="sc_page_cache_length"><?php esc_html_e( 'Expire the cache after', 'simple-cache' ); ?></label></th>
							<td>
								<input <?php if ( ! empty( $config['advanced_mode'] ) ) : ?>disabled<?php endif; ?> size="5" id="sc_page_cache_length_simple" type="text" value="<?php echo (int) $config['page_cache_length']; ?>" name="sc_simple_cache[page_cache_length]"> <span class="description"><?php esc_html_e( 'minutes', 'simple-cache' ); ?></span>
							</td>
						</tr>

						<?php if ( function_exists( 'gzencode' ) ) : ?>
							<tr>
								<th scope="row"><label for="sc_enable_gzip_compression"><?php _e( 'Enable Compression', 'simple-cache' ); ?></label></th>
								<td>
									<select <?php if ( ! empty( $config['advanced_mode'] ) ) : ?>disabled<?php endif; ?> name="sc_simple_cache[enable_gzip_compression]" id="sc_enable_gzip_compression_simple">
										<option value="0"><?php esc_html_e( 'No', 'simple-cache' ); ?></option>
										<option <?php selected( $config['enable_gzip_compression'], true ); ?> value="1"><?php esc_html_e( 'Yes', 'simple-cache' ); ?></option>
									</select>

									<p class="description"><?php esc_html_e( 'When enabled, pages will be compressed. This is a good thing! This should always be enabled unless it causes issues.', 'simple-cache' ); ?></p>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>

				<table class="form-table sc-advanced-mode-table <?php if ( ! empty( $config['advanced_mode'] ) ) : ?>show<?php endif; ?>">
					<tbody>
						<tr>
							<th scope="row" colspan="2">
								<h2 class="cache-title"><?php esc_html_e( 'Page Cache', 'simple-cache' ); ?></h2>
							</th>
						</tr>

						<tr>
							<th scope="row"><label for="sc_enable_page_caching_advanced"><?php _e( 'Enable Page Caching', 'simple-cache' ); ?></label></th>
							<td>
								<select <?php if ( empty( $config['advanced_mode'] ) ) : ?>disabled<?php endif; ?> name="sc_simple_cache[enable_page_caching]" id="sc_enable_page_caching_advanced">
									<option value="0"><?php esc_html_e( 'No', 'simple-cache' ); ?></option>
									<option <?php selected( $config['enable_page_caching'], true ); ?> value="1"><?php esc_html_e( 'Yes', 'simple-cache' ); ?></option>
								</select>

								<p class="description"><?php esc_html_e( 'When enabled, entire front end pages will be cached.', 'simple-cache' ); ?></p>
							</td>
						</tr>

						<tr>
							<th scope="row"><label for="sc_cache_exception_urls"><?php _e( 'Exception URL(s)', 'simple-cache' ); ?></label></th>
							<td>
								<textarea name="sc_simple_cache[cache_exception_urls]" class="widefat" id="sc_cache_exception_urls"><?php echo esc_html( $config['cache_exception_urls'] ); ?></textarea>

								<p class="description"><?php esc_html_e( 'Allows you to add URL(s) to be exempt from page caching. One URL per line. URL(s) can be full URLs (http://google.com) or absolute paths (/my/url/).', 'simple-cache' ); ?></p>
							</td>
						</tr>

						<tr>
							<th scope="row"><label for="sc_page_cache_length_advanced"><?php esc_html_e( 'Expire page cache after', 'simple-cache' ); ?></label></th>
							<td>
								<input <?php if ( empty( $config['advanced_mode'] ) ) : ?>disabled<?php endif; ?> size="5" id="sc_page_cache_length_advanced" type="text" value="<?php echo (int) $config['page_cache_length']; ?>" name="sc_simple_cache[page_cache_length]"> <span class="description"><?php esc_html_e( 'minutes', 'simple-cache' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row" colspan="2">
								<h2 class="cache-title"><?php esc_html_e( 'Object Cache (Redis or Memcache)', 'simple-cache' ); ?></h2>
							</th>
						</tr>

						<?php if ( class_exists( 'Memcache' ) || class_exists( 'Redis' ) ) : ?>
							<tr>
								<th scope="row"><label for="sc_enable_in_memory_object_caching"><?php _e( 'Enable In-Memory Object Caching', 'simple-cache' ); ?></label></th>
								<td>
									<select name="sc_simple_cache[enable_in_memory_object_caching]" id="sc_enable_in_memory_object_caching">
										<option value="0"><?php esc_html_e( 'No', 'simple-cache' ); ?></option>
										<option <?php selected( $config['enable_in_memory_object_caching'], true ); ?> value="1"><?php esc_html_e( 'Yes', 'simple-cache' ); ?></option>
									</select>

									<p class="description"><?php _e( "When enabled, things like database query results will be stored in memory. Right now Memcache and Redis are suppported. Note that if the proper <a href='https://pecl.php.net/package/memcache'>Memcache</a> (NOT Memcached) or <a href='https://pecl.php.net/package/redis'>Redis</a> PHP extensions aren't loaded, they won't show as options below.", 'simple-cache' ); ?></p>
								</td>
							</tr>
							<tr>
								<th class="in-memory-cache <?php if ( ! empty( $config['enable_in_memory_object_caching'] ) ) : ?>show<?php endif; ?>" scope="row"><label for="sc_in_memory_cache"><?php _e( 'In Memory Cache', 'simple-cache' ); ?></label></th>
								<td class="in-memory-cache <?php if ( ! empty( $config['enable_in_memory_object_caching'] ) ) : ?>show<?php endif; ?>">
									<select name="sc_simple_cache[in_memory_cache]" id="sc_in_memory_cache">
										<?php if ( class_exists( 'Memcache' ) ) : ?>
											<option <?php selected( $config['in_memory_cache'], 'memcached' ); ?> value="memcached">Memcache</option>
										<?php endif; ?>
										<?php if ( class_exists( 'Redis' ) ) : ?>
											<option <?php selected( $config['in_memory_cache'], 'redis' ); ?> value="redis">Redis</option>
										<?php endif; ?>
									</select>
								</td>
							</tr>
						<?php else : ?>
							<tr>
								<td colspan="2">
									<?php _e( 'Neither <a href="https://pecl.php.net/package/memcache">Memcache</a> (NOT Memcached) nor <a href="https://pecl.php.net/package/redis">Redis</a> PHP extensions are set up on your server.', 'simple-cache' ); ?>
								</td>
							</tr>
						<?php endif; ?>

						<tr>
							<th scope="row" colspan="2">
								<h2 class="cache-title"><?php esc_html_e( 'Compression', 'simple-cache' ); ?></h2>
							</th>
						</tr>

						<?php if ( function_exists( 'gzencode' ) ) : ?>
							<tr>
								<th scope="row"><label for="sc_enable_gzip_compression_advanced"><?php _e( 'Enable gzip Compression', 'simple-cache' ); ?></label></th>
								<td>
									<select <?php if ( empty( $config['advanced_mode'] ) ) : ?>disabled<?php endif; ?> name="sc_simple_cache[enable_gzip_compression]" id="sc_enable_gzip_compression_advanced">
										<option value="0"><?php esc_html_e( 'No', 'simple-cache' ); ?></option>
										<option <?php selected( $config['enable_gzip_compression'], true ); ?> value="1"><?php esc_html_e( 'Yes', 'simple-cache' ); ?></option>
									</select>

									<p class="description"><?php esc_html_e( 'When enabled pages will be gzip compressed at the PHP level. Note many hosts set up gzip compression in Apache or nginx.', 'simple-cache' ); ?></p>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>

				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_html_e( 'Save Changes', 'simple-cache' ); ?>">
					<a class="button" style="margin-left: 10px;" href="?page=simple-cache&amp;wp_http_referer=<?php echo esc_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ); ?>&amp;action=sc_purge_cache&amp;sc_cache_nonce=<?php echo wp_create_nonce( 'sc_purge_cache' ); ?>"><?php esc_html_e( 'Purge Cache', 'simple-cache' ); ?></a>
				</p>
			</form>
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
