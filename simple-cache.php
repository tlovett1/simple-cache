<?php
/**
 * Plugin Name: Simple Cache
 * Plugin URI: https://taylorlovett.com
 * Description: A simple caching plugin that just works.
 * Author: Taylor Lovett
 * Version: 2.0.0
 * Text Domain: simple-cache
 * Domain Path: /languages
 * Author URI: https://taylorlovett.com
 *
 * @package  simple-cache
 */

defined( 'ABSPATH' ) || exit;

define( 'SC_VERSION', '2.0.0' );
define( 'SC_PATH', dirname( __FILE__ ) );

$active_plugins = get_site_option( 'active_sitewide_plugins' );

if ( is_multisite() && isset( $active_plugins[ plugin_basename( __FILE__ ) ] ) ) {
	define( 'SC_IS_NETWORK', true );
} else {
	define( 'SC_IS_NETWORK', false );
}

require_once SC_PATH . '/inc/pre-wp-functions.php';
require_once SC_PATH . '/inc/functions.php';
require_once SC_PATH . '/inc/class-sc-notices.php';
require_once SC_PATH . '/inc/class-sc-settings.php';
require_once SC_PATH . '/inc/class-sc-config.php';
require_once SC_PATH . '/inc/class-sc-advanced-cache.php';
require_once SC_PATH . '/inc/class-sc-object-cache.php';
require_once SC_PATH . '/inc/class-sc-cron.php';

SC_Settings::factory();
SC_Advanced_Cache::factory();
SC_Object_Cache::factory();
SC_Cron::factory();
SC_Notices::factory();

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
		$new_actions['sc_settings'] = '<a href="' . esc_url( admin_url( 'options-general.php?page=simple-cache' ) ) . '">' . esc_html__( 'Settings', 'simple-cache' ) . '</a>';
	}

	return array_merge( $new_actions, $plugin_actions );
}
add_filter( 'plugin_action_links', 'sc_filter_plugin_action_links', 10, 2 );

/**
 * Clean up necessary files
 *
 * @param  bool $network Whether the plugin is network wide
 * @since 1.0
 */
function sc_deactivate( $network ) {
	if ( ! apply_filters( 'sc_disable_auto_edits', false ) ) {
		SC_Advanced_Cache::factory()->clean_up();
		SC_Advanced_Cache::factory()->toggle_caching( false );
		SC_Object_Cache::factory()->clean_up();
	}

	SC_Config::factory()->clean_up();

	sc_cache_flush( $network );
}
add_action( 'deactivate_' . plugin_basename( __FILE__ ), 'sc_deactivate' );

/**
 * Create config file
 *
 * @param  bool $network Whether the plugin is network wide
 * @since 1.0
 */
function sc_activate( $network ) {
	if ( $network ) {
		SC_Config::factory()->write( array(), true );
	} else {
		SC_Config::factory()->write( array() );
	}
}
add_action( 'activate_' . plugin_basename( __FILE__ ), 'sc_activate' );


