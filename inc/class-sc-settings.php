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

			$creds = $this->request_filesystem_credentials( admin_url('options-general.php?page=simple-cache'), '', false, false, false, false );

			if ( false === $creds ) {
				return false;
			}

			if ( ! WP_Filesystem( $creds ) ) {
				return false;
			}

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
	 * :'( Unfortunately have to rewrite this method
	 *
	 * @param  string  $form_post
	 * @param  string  $type
	 * @param  boolean $error
	 * @param  boolean $context
	 * @param  boolean $allow_relaxed_file_ownership
	 * @param  boolean $print_form
	 * @since  1.0
	 * @return boolean
	 */
	function request_filesystem_credentials( $form_post, $type = '', $error = false, $context = false, $allow_relaxed_file_ownership = false, $print_form = true ) {
		global $pagenow;

		$req_cred = apply_filters( 'request_filesystem_credentials', '', $form_post, $type, $error, $context, null, $allow_relaxed_file_ownership );
		if ( '' !== $req_cred )
			return $req_cred;

		if ( empty($type) ) {
			$type = get_filesystem_method( array(), $context, $allow_relaxed_file_ownership );
		}

		if ( 'direct' == $type )
			return true;

		$credentials = get_option('ftp_credentials', array( 'hostname' => '', 'username' => ''));

		// If defined, set it to that, Else, If POST'd, set it to that, If not, Set it to whatever it previously was(saved details in option)
		$credentials['hostname'] = defined('FTP_HOST') ? FTP_HOST : (!empty($_POST['hostname']) ? wp_unslash( $_POST['hostname'] ) : $credentials['hostname']);
		$credentials['username'] = defined('FTP_USER') ? FTP_USER : (!empty($_POST['username']) ? wp_unslash( $_POST['username'] ) : $credentials['username']);
		$credentials['password'] = defined('FTP_PASS') ? FTP_PASS : (!empty($_POST['password']) ? wp_unslash( $_POST['password'] ) : '');

		// Check to see if we are setting the public/private keys for ssh
		$credentials['public_key'] = defined('FTP_PUBKEY') ? FTP_PUBKEY : (!empty($_POST['public_key']) ? wp_unslash( $_POST['public_key'] ) : '');
		$credentials['private_key'] = defined('FTP_PRIKEY') ? FTP_PRIKEY : (!empty($_POST['private_key']) ? wp_unslash( $_POST['private_key'] ) : '');

		// Sanitize the hostname, Some people might pass in odd-data:
		$credentials['hostname'] = preg_replace('|\w+://|', '', $credentials['hostname']); //Strip any schemes off

		if ( strpos($credentials['hostname'], ':') ) {
			list( $credentials['hostname'], $credentials['port'] ) = explode(':', $credentials['hostname'], 2);
			if ( ! is_numeric($credentials['port']) )
				unset($credentials['port']);
		} else {
			unset($credentials['port']);
		}

		if ( ( defined( 'FTP_SSH' ) && FTP_SSH ) || ( defined( 'FS_METHOD' ) && 'ssh2' == FS_METHOD ) ) {
			$credentials['connection_type'] = 'ssh';
		} elseif ( ( defined( 'FTP_SSL' ) && FTP_SSL ) && 'ftpext' == $type ) { //Only the FTP Extension understands SSL
			$credentials['connection_type'] = 'ftps';
		} elseif ( ! empty( $_POST['connection_type'] ) ) {
			$credentials['connection_type'] = wp_unslash( $_POST['connection_type'] );
		} elseif ( ! isset( $credentials['connection_type'] ) ) { //All else fails (And it's not defaulted to something else saved), Default to FTP
			$credentials['connection_type'] = 'ftp';
		}

		if ( ! $error &&
				(
					( !empty($credentials['password']) && !empty($credentials['username']) && !empty($credentials['hostname']) ) ||
					( 'ssh' == $credentials['connection_type'] && !empty($credentials['public_key']) && !empty($credentials['private_key']) )
				) ) {
			$stored_credentials = $credentials;
			if ( !empty($stored_credentials['port']) ) //save port as part of hostname to simplify above code.
				$stored_credentials['hostname'] .= ':' . $stored_credentials['port'];

			unset($stored_credentials['password'], $stored_credentials['port'], $stored_credentials['private_key'], $stored_credentials['public_key']);
			if ( ! wp_installing() ) {
				update_option( 'ftp_credentials', $stored_credentials );
			}
			return $credentials;
		}

		if ( ! $print_form ) {
			return false;
		}

		$hostname = isset( $credentials['hostname'] ) ? $credentials['hostname'] : '';
		$username = isset( $credentials['username'] ) ? $credentials['username'] : '';
		$public_key = isset( $credentials['public_key'] ) ? $credentials['public_key'] : '';
		$private_key = isset( $credentials['private_key'] ) ? $credentials['private_key'] : '';
		$port = isset( $credentials['port'] ) ? $credentials['port'] : '';
		$connection_type = isset( $credentials['connection_type'] ) ? $credentials['connection_type'] : '';

		if ( $error ) {
			$error_string = __('<strong>ERROR:</strong> There was an error connecting to the server, Please verify the settings are correct.');
			if ( is_wp_error($error) )
				$error_string = esc_html( $error->get_error_message() );
			echo '<div id="message" class="error"><p>' . $error_string . '</p></div>';
		}

		$types = array();
		if ( extension_loaded('ftp') || extension_loaded('sockets') || function_exists('fsockopen') )
			$types[ 'ftp' ] = __('FTP');
		if ( extension_loaded('ftp') ) //Only this supports FTPS
			$types[ 'ftps' ] = __('FTPS (SSL)');
		if ( extension_loaded('ssh2') && function_exists('stream_get_contents') )
			$types[ 'ssh' ] = __('SSH2');

		$types = apply_filters( 'fs_ftp_connection_types', $types, $credentials, $type, $error, $context );

	?>
	<script type="text/javascript">
	<!--
	jQuery(function($){
		jQuery("#ssh").click(function () {
			jQuery("#ssh_keys").show();
		});
		jQuery("#ftp, #ftps").click(function () {
			jQuery("#ssh_keys").hide();
		});
		jQuery('#request-filesystem-credentials-form input[value=""]:first').focus();
	});
	-->
	</script>
	<form action="<?php echo esc_url( $form_post ) ?>" method="post">
	<div id="request-filesystem-credentials-form" class="request-filesystem-credentials-form">
	<?php
	// Print a H1 heading in the FTP credentials modal dialog, default is a H2.
	$heading_tag = 'h2';
	if ( 'plugins.php' === $pagenow || 'plugin-install.php' === $pagenow ) {
		$heading_tag = 'h1';
	}
	echo "<$heading_tag id='request-filesystem-credentials-title'>" . __( 'Connection Information' ) . "</$heading_tag>";
	?>
	<p id="request-filesystem-credentials-desc"><?php
		$label_user = __('Username');
		$label_pass = __('Password');
		_e('To perform the requested action, WordPress needs to access your web server.');
		echo ' ';
		if ( ( isset( $types['ftp'] ) || isset( $types['ftps'] ) ) ) {
			if ( isset( $types['ssh'] ) ) {
				_e('Please enter your FTP or SSH credentials to proceed.');
				$label_user = __('FTP/SSH Username');
				$label_pass = __('FTP/SSH Password');
			} else {
				_e('Please enter your FTP credentials to proceed.');
				$label_user = __('FTP Username');
				$label_pass = __('FTP Password');
			}
			echo ' ';
		}
		_e('If you do not remember your credentials, you should contact your web host.');
	?></p>
	<label for="hostname">
		<span class="field-title"><?php _e( 'Hostname' ) ?></span>
		<input name="hostname" type="text" id="hostname" aria-describedby="request-filesystem-credentials-desc" class="code" placeholder="<?php esc_attr_e( 'example: www.wordpress.org' ) ?>" value="<?php echo esc_attr($hostname); if ( !empty($port) ) echo ":$port"; ?>"<?php disabled( defined('FTP_HOST') ); ?> />
	</label>
	<div class="ftp-username">
		<label for="username">
			<span class="field-title"><?php echo $label_user; ?></span>
			<input name="username" type="text" id="username" value="<?php echo esc_attr($username) ?>"<?php disabled( defined('FTP_USER') ); ?> />
		</label>
	</div>
	<div class="ftp-password">
		<label for="password">
			<span class="field-title"><?php echo $label_pass; ?></span>
			<input name="password" type="password" id="password" value="<?php if ( defined('FTP_PASS') ) echo '*****'; ?>"<?php disabled( defined('FTP_PASS') ); ?> />
			<em><?php if ( ! defined('FTP_PASS') ) _e( 'This password will not be stored on the server.' ); ?></em>
		</label>
	</div>
	<?php if ( isset($types['ssh']) ) : ?>
	<fieldset>
	<legend><?php _e( 'Authentication Keys' ); ?></legend>
	<label for="public_key">
		<span class="field-title"><?php _e('Public Key:') ?></span>
		<input name="public_key" type="text" id="public_key" aria-describedby="auth-keys-desc" value="<?php echo esc_attr($public_key) ?>"<?php disabled( defined('FTP_PUBKEY') ); ?> />
	</label>
	<label for="private_key">
		<span class="field-title"><?php _e('Private Key:') ?></span>
		<input name="private_key" type="text" id="private_key" value="<?php echo esc_attr($private_key) ?>"<?php disabled( defined('FTP_PRIKEY') ); ?> />
	</label>
	</fieldset>
	<span id="auth-keys-desc"><?php _e('Enter the location on the server where the public and private keys are located. If a passphrase is needed, enter that in the password field above.') ?></span>
	<?php endif; ?>
	<fieldset>
	<legend><?php _e( 'Connection Type' ); ?></legend>
	<?php
		$disabled = disabled( (defined('FTP_SSL') && FTP_SSL) || (defined('FTP_SSH') && FTP_SSH), true, false );
		foreach ( $types as $name => $text ) : ?>
		<label for="<?php echo esc_attr($name) ?>">
			<input type="radio" name="connection_type" id="<?php echo esc_attr($name) ?>" value="<?php echo esc_attr($name) ?>"<?php checked($name, $connection_type); echo $disabled; ?> />
			<?php echo $text ?>
		</label>
		<?php endforeach; ?>
	</fieldset>
	<input name="action" value="<?php echo esc_attr( $_REQUEST['action'] ); ?>" type="hidden">
	<input name="sc_settings_nonce" value="<?php echo esc_attr( $_REQUEST['sc_settings_nonce'] ); ?>" type="hidden">
	<input name="wp_http_referer" value="<?php echo esc_attr( $_REQUEST['wp_http_referer'] ); ?>" type="hidden">
	<?php foreach ( $_REQUEST['sc_simple_cache'] as $key => $value ) : ?>
		<input name="sc_simple_cache[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $value ); ?>" type="hidden">
	<?php endforeach; ?>
		<p class="request-filesystem-credentials-action-buttons">
			<button class="button cancel-button" data-js-action="close" type="button"><?php _e( 'Cancel' ); ?></button>
			<?php submit_button( __( 'Proceed' ), 'button', 'upgrade', false ); ?>
		</p>
	</div>
	</form>
	<?php
		return false;
	}

	/**
	 * Output settings
	 *
	 * @since 1.0
	 */
	public function screen_options() {
		if ( ! empty( $_REQUEST['action'] ) ) {
			$creds = $this->request_filesystem_credentials( admin_url('options-general.php?page=simple-cache'), '', false, false, false, true );

			if ( false === $creds ) {
				return false;
			}

			if ( ! WP_Filesystem( $creds ) ) {
				return false;
			}
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
