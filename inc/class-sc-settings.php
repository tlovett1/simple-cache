<?php
defined( 'ABSPATH' ) || exit;

class SC_Settings {

	//public $request_creds = false;

	public function __construct() { }

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
		add_action( 'admin_enqueue_scripts' , array( $this, 'action_admin_enqueue_scripts_styles' ) );
    }

    /**
     * Output turn on notice
     *
     * @since  1.0
     */
    public function setup_notice() {
    	$config = SC_Config::factory()->get();

    	if ( ! empty( $config['enable_page_caching'] ) || ! empty( $config['advanced_mode'] ) ) {
    		return;
    	}
    	?>
		<div class="notice notice-warning">
			<p>
				<?php esc_html_e( "Simple Cache won't work until you turn it on.", 'simple-cache' ); ?>
				<a href="options.php?wp_http_referer=<?php echo esc_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ); ?>&amp;action=sc_update&amp;sc_settings_nonce=<?php echo wp_create_nonce( 'sc_update_settings' ); ?>&amp;sc_simple_cache[enable_page_caching]=1" class="button button-primary" style="margin-left: 5px;"><?php esc_html_e( "Turn On Caching", 'simple-cache' ); ?></a>
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
	 * @since  1.0
	 */
	public function purge_cache() {
		if ( ! empty( $_REQUEST['action'] ) && 'sc_purge_cache' === $_REQUEST['action'] ) {
			if ( ! current_user_can ( 'manage_options' ) || empty( $_REQUEST['sc_cache_nonce'] ) || ! wp_verify_nonce( $_REQUEST['sc_cache_nonce'], 'sc_purge_cache' ) ) {
				wp_die( 'Cheatin, eh?' );
			}

			SC_Cron::factory()->purge_file_cache();

			if ( function_exists( 'wp_cache_flush' ) ) {
				wp_cache_flush();
			}

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

			ob_start();
			if ( ! SC_Config::factory()->verify_access() ) {
				return;
			}
			ob_get_clean();

			$defaults = SC_Config::factory()->defaults;
			$current_config = SC_Config::factory()->get();

			foreach ( $defaults as $key => $default ) {
				$clean_config[$key] = $current_config[$key];

				if ( isset( $_REQUEST['sc_simple_cache'][$key] ) ) {
					$clean_config[$key] = call_user_func( $default['sanitizer'], $_REQUEST['sc_simple_cache'][$key] );
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

			if ( ! empty( $_REQUEST['wp_http_referer'] ) ) {
				wp_redirect( $_REQUEST['wp_http_referer'] );
				exit;
			}
		}
	}

	/**
	 * Sanitize options
	 *
	 * @param array $option
	 * @since 1.0
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
		if ( ! empty( $_REQUEST['action'] ) && ! SC_Config::factory()->verify_access() ) {
			return;
		}

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
							<th scope="row"><label for="sc_manual_purge"><?php esc_html_e( 'Manually Delete Cache', 'simple-cache' ); ?></label></th>
							<td>
								<a class="button" href="options.php?wp_http_referer=<?php echo esc_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ); ?>&amp;action=sc_purge_cache&amp;sc_cache_nonce=<?php echo wp_create_nonce( 'sc_purge_cache' ); ?>"><?php esc_html_e( 'Purge', 'simple-cache' ); ?></a>
								<p class="description"><?php esc_html_e( 'This is helpful if you make a change to your site, and something is "stuck" in the cache.', 'simple-cache' ); ?></p>
							</td>
						</tr>
					</tbody>
				</table>

				<table class="form-table sc-advanced-mode-table <?php if ( ! empty( $config['advanced_mode'] ) ) : ?>show<?php endif; ?>">
					<tbody>
						<tr>
							<th scope="row"><label for="sc_enable_caching_advanced"><?php _e( 'Enable Page Caching', 'simple-cache' ); ?></label></th>
							<td>
								<select <?php if ( empty( $config['advanced_mode'] ) ) : ?>disabled<?php endif; ?> name="sc_simple_cache[enable_page_caching]" id="sc_enable_page_caching_advanced">
									<option value="0"><?php esc_html_e( 'No', 'simple-cache' ); ?></option>
									<option <?php selected( $config['enable_page_caching'], true ); ?> value="1"><?php esc_html_e( 'Yes', 'simple-cache' ); ?></option>
								</select>

								<p class="description"><?php esc_html_e( 'When enabled, entire front end pages will be cached.', 'simple-cache' ); ?></p>
							</td>
						</tr>

						<tr>
							<th scope="row"><label for="sc_enable_in_memory_object_caching"><?php _e( 'Enable In-Memory Object Caching', 'simple-cache' ); ?></label></th>
							<td>
								<select name="sc_simple_cache[enable_in_memory_object_caching]" id="sc_enable_in_memory_object_caching">
									<option value="0"><?php esc_html_e( 'No', 'simple-cache' ); ?></option>
									<option <?php selected( $config['enable_in_memory_object_caching'], true ); ?> value="1"><?php esc_html_e( 'Yes', 'simple-cache' ); ?></option>
								</select>

								<p class="description"><?php esc_html_e( 'When enabled, things like database query results will be stored in memory.', 'simple-cache' ); ?></p>
							</td>
						</tr>
						<tr>
							<th class="in-memory-cache <?php if ( ! empty( $config['enable_in_memory_object_caching'] ) ) : ?>show<?php endif; ?>" scope="row"><label for="sc_in_memory_cache"><?php _e( 'In Memory Cache', 'simple-cache' ); ?></label></th>
							<td class="in-memory-cache <?php if ( ! empty( $config['enable_in_memory_object_caching'] ) ) : ?>show<?php endif; ?>">
								<select name="sc_simple_cache[in_memory_cache]" id="sc_in_memory_cache">
									<option <?php selected( $config['in_memory_cache'], 'memcached' ); ?> value="memcached">Memcached</option>
									<option <?php selected( $config['in_memory_cache'], 'redis' ); ?> value="redis">Redis</option>
								</select>

								<p class="description"><?php esc_html_e( "If you aren't sure what these are, you probably don't have them available. Contact your host to inquire.", 'simple-cache' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="sc_manual_purge"><?php esc_html_e( 'Manually Delete Cache', 'simple-cache' ); ?></label></th>
							<td>
								<a class="button" href="options.php?wp_http_referer=<?php echo esc_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ); ?>&amp;action=sc_purge_cache&amp;sc_cache_nonce=<?php echo wp_create_nonce( 'sc_purge_cache' ); ?>"><?php esc_html_e( 'Purge', 'simple-cache' ); ?></a>

								<p class="description"><?php esc_html_e( "Purges object and page caches.", 'simple-cache' ); ?></p>
							</td>
						</tr>
					</tbody>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
	<?php
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
