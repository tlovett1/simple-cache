<?php
/**
 * Plugin Name: Simple Cache
 * Plugin URI: http://taylorlovett.com
 * Description: A simple caching plugin that just works.
 * Author: Taylor Lovett
 * Version: 1.6.4
 * Text Domain: simple-cache
 * Domain Path: /languages
 * Author URI: http://taylorlovett.com
 *
 * @package  simple-cache
 */

defined( 'ABSPATH' ) || exit;

define( 'SC_VERSION', '1.6.4' );

define( 'SC_DIR_PATH', plugin_dir_path( __FILE__ ) );

require_once SC_DIR_PATH . 'inc/functions.php';
require_once SC_DIR_PATH . 'inc/class-sc-settings.php';
require_once SC_DIR_PATH . 'inc/class-sc-config.php';
require_once SC_DIR_PATH . 'inc/class-sc-advanced-cache.php';
require_once SC_DIR_PATH . 'inc/class-sc-object-cache.php';
require_once SC_DIR_PATH . 'inc/class-sc-cron.php';

SC_Settings::factory();
SC_Advanced_Cache::factory();
SC_Object_Cache::factory();
SC_Cron::factory();


/**
 * Load text domain
 *
 * @since 1.0
 */
function sc_load_textdomain() {

	load_plugin_textdomain( 'simple-cache', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'sc_load_textdomain' );


/**
 * Add settings link to plugin actions
 *
 * @param  array  $plugin_actions Each action is HTML.
 * @param  string $plugin_file Path to plugin file.
 * @since  1.0
 * @return array
 */
function sc_filter_plugin_action_links( $plugin_actions, $plugin_file ) {

	$new_actions = array();

	if ( basename( dirname( __FILE__ ) ) . '/simple-cache.php' === $plugin_file ) {
		/* translators: Param 1 is link to settings page. */
		$new_actions['sc_settings'] = sprintf( __( '<a href="%s">Settings</a>', 'simple-cache' ), esc_url( admin_url( 'options-general.php?page=simple-cache' ) ) );
	}

	return array_merge( $new_actions, $plugin_actions );
}
add_filter( 'plugin_action_links', 'sc_filter_plugin_action_links', 10, 2 );

/**
 * Clean up necessary files
 *
 * @since 1.0
 */
function sc_clean_up() {

	WP_Filesystem();

	SC_Advanced_Cache::factory()->clean_up();
	SC_Advanced_Cache::factory()->toggle_caching( false );
	SC_Object_Cache::factory()->clean_up();
	SC_Config::factory()->clean_up();
}
register_deactivation_hook( __FILE__, 'sc_clean_up' );


