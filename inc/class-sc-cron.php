<?php
defined( 'ABSPATH' ) || exit;

class SC_Cron {

	public function __construct() {

	}

	/**
	 * Setup actions and filters
	 *
	 * @since 1.0
	 */
	private function setup() {

		add_action( 'sc_purge_file_cache', array( $this, 'purge_cache' ) );
		add_action( 'init', array( $this, 'schedule_events' ) );
		add_filter( 'cron_schedules', array( $this, 'filter_cron_schedules' ) );
	}

	/**
	 * Add custom cron schedule
	 *
	 * @param  array $schedules
	 * @since  1.0
	 * @return array
	 */
	public function filter_cron_schedules( $schedules ) {

		$schedules['simple_cache'] = array(
		'interval' => apply_filters( 'sc_cache_purge_interval', HOUR_IN_SECONDS ),
		'display' => esc_html__( 'Simple Cache Purge Interval', 'simple-cache' ),
		);
		return $schedules;
	}

	/**
	 * Setup cron jobs
	 *
	 * @since 1.0
	 */
	public function schedule_events() {

		$config = SC_Config::factory()->get();

		// Do nothing if we are using the object cache
		if ( ! empty( $config['advanced_mode'] ) && ! empty( $config['enable_in_memory_object_caching'] ) ) {
			return;
		}

		$timestamp = wp_next_scheduled( 'sc_purge_cache' );

		if ( ! $timestamp ) {
			wp_schedule_event( time(), 'simple_cache', 'sc_purge_cache' );
		}
	}

	/**
	 * Initiate a cache purge
	 *
	 * @since 1.0
	 */
	public function purge_file_cache() {

		global $wp_filesystem;

		$config = SC_Config::factory()->get();

		// Do nothing, caching is turned off
		if ( empty( $config['enable_page_caching'] ) ) {
			return;
		}

		// Do nothing if we are using the object cache
		if ( ! empty( $config['advanced_mode'] ) && ! empty( $config['enable_in_memory_object_caching'] ) ) {
			return;
		}

		WP_Filesystem();

		$wp_filesystem->rmdir( untrailingslashit( WP_CONTENT_DIR ) . '/cache/simple-cache', true );
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
