<?php
/**
 * Handles the background updater integration.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WPUM_Background_Updater Class.
 */
class WPUM_Background_Updater extends WP_Background_Process {

	/**
	 * @var string
	 */
	protected $action = 'wpum_db_updater';

	/**
	 * Dispatch updater.
	 * Updater will still run via cron job if this fails for any reason.
	 */
	public function dispatch() {
		/* @var WP_Background_Process $dispatched */
		parent::dispatch();
	}


	/**
	 * Get all batches.
	 *
	 * @access public
	 * @return stdClass
	 */
	public function get_all_batch() {
		return parent::get_batch();
	}

	/**
	 * Is queue empty
	 *
	 * @return bool
	 */
	public function has_queue() {
		return ( ! parent::is_queue_empty() );
	}


	/**
	 * Lock process
	 *
	 * Lock the process so that multiple instances can't run simultaneously.
	 * Override if applicable, but the duration should be greater than that
	 * defined in the time_exceeded() method.
	 */
	protected function lock_process() {
		// Check if admin want to pause upgrade.
		if( get_option('wpum_pause_upgrade') ) {
			self::flush_cache();

			delete_option( 'wpum_paused_batches' );

			WPUM_Updates::get_instance()->__pause_db_update( true );

			delete_option('wpum_pause_upgrade');

			/**
			 * Fire action when pause db updates
			 */
			do_action( 'wpum_pause_db_upgrade', WPUM_Updates::get_instance() );

			wp_die();
		}


		$this->start_time = time(); // Set start time of current process.

		$lock_duration = ( property_exists( $this, 'queue_lock_time' ) ) ? $this->queue_lock_time : 60; // 1 minute
		$lock_duration = apply_filters( $this->identifier . '_queue_lock_time', $lock_duration );

		set_site_transient( $this->identifier . '_process_lock', microtime(), $lock_duration );
	}

	/**
	 * Handle cron healthcheck
	 *
	 * Restart the background process if not already running
	 * and data exists in the queue.
	 */
	public function handle_cron_healthcheck() {
		if ( $this->is_process_running() || $this->is_paused_process()  ) {
			// Background process already running.
			return;
		}

		if ( $this->is_queue_empty() ) {
			// No data to process.
			$this->clear_scheduled_event();

			return;
		}

		$this->handle();
	}

	/**
	 * Schedule fallback event.
	 */
	protected function schedule_event() {
		if ( ! wp_next_scheduled( $this->cron_hook_identifier ) && ! $this->is_paused_process() ) {
			wp_schedule_event( time() + 10, $this->cron_interval_identifier, $this->cron_hook_identifier );
		}
	}

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param array $update Update info
	 * @return mixed
	 */
	protected function task( $update ) {
		// Pause upgrade immediately if admin pausing upgrades.
		if( $this->is_paused_process() ) {
			wp_die();
		}

		if ( empty( $update ) ) {
			return false;
		}

		// Delete cache.
		self::flush_cache();

		/* @var  WPUM_Updates $wpum_updates */
		$wpum_updates  = WPUM_Updates::get_instance();
		$resume_update = get_option(
			'wpum_doing_upgrade',

			// Default update.
			array(
				'update_info'      => $update,
				'step'             => 1,
				'update'           => 1,
				'heading'          => sprintf( 'Update %s of {update_count}', 1 ),
				'percentage'       => $wpum_updates->percentage,
				'total_percentage' => 0,
			)
		);

		// Continuously skip update if previous update does not complete yet.
		if (
			$resume_update['update_info']['id'] !== $update['id'] &&
			! wpum_has_upgrade_completed( $resume_update['update_info']['id'] )
		) {
			return $update;
		}

		// Set params.
		$resume_update['update_info'] = $update;
		$wpum_updates->step           = absint( $resume_update['step'] );
		$wpum_updates->update         = absint( $resume_update['update'] );
		$is_parent_update_completed   = $wpum_updates->is_parent_updates_completed( $update );


		// Skip update if dependency update does not complete yet.
		if ( empty( $is_parent_update_completed ) ) {
			// @todo: set error when you have only one update with invalid dependency
			if ( ! is_null( $is_parent_update_completed ) ) {
				return $update;
			}

			return false;
		}


		// Pause upgrade immediately if found following:
		// 1. Running update number greater then total update count
		// 2. Processing percentage greater then 100%
		if( (
			101 < $resume_update['total_percentage'] ) ||
		    ( $wpum_updates->get_total_db_update_count() < $resume_update['update'] ) ||
		    ! in_array( $resume_update['update_info']['id'], $wpum_updates->get_update_ids() )
		) {
			if( ! $this->is_paused_process() ){
				$wpum_updates->__pause_db_update(true);
			}

			update_option( 'wpum_upgrade_error', 1 );

			$log_data = 'Update Task' . "\n";
			$log_data .= "Total update count: {$wpum_updates->get_total_db_update_count()}\n";
			$log_data .= 'Update IDs: ' . print_r( $wpum_updates->get_update_ids() , true );
			$log_data .= 'Update: ' . print_r( $resume_update , true );

			wp_die();
		}

		try{
			// Run update.
			if ( is_array( $update['callback'] ) ) {
				$update['callback'][0]->$update['callback'][1]();
			} else {
				$update['callback']();
			}
		} catch ( Exception $e ){

			if( ! $this->is_paused_process() ){
				$wpum_updates->__pause_db_update(true);
			}

			$log_data = 'Update Task' . "\n";
			$log_data .= print_r( $resume_update, true ) . "\n\n";
			$log_data .= "Error\n {$e->getMessage()}";

			update_option( 'wpum_upgrade_error', 1 );

			wp_die();
		}

		// Set update info.
		$doing_upgrade_args = array(
			'update_info'      => $update,
			'step'             => ++ $wpum_updates->step,
			'update'           => $wpum_updates->update,
			'heading'          => sprintf( 'Update %s of %s', $wpum_updates->update, get_option( 'wpum_db_update_count' ) ),
			'percentage'       => $wpum_updates->percentage,
			'total_percentage' => $wpum_updates->get_db_update_processing_percentage(),
		);

		// Cache upgrade.
		update_option( 'wpum_doing_upgrade', $doing_upgrade_args );

		// Check if current update completed or not.
		if ( wpum_has_upgrade_completed( $update['id'] ) ) {
			return false;
		}

		return $update;
	}

	/**
	 * Complete
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	public function complete() {
		if ( $this->is_paused_process() ) {
			return false;
		}

		parent::complete();

		delete_option( 'wpum_pause_upgrade' );
		delete_option( 'wpum_upgrade_error' );
		delete_option( 'wpum_db_update_count' );
		delete_option( 'wpum_doing_upgrade' );
		add_option( 'wpum_show_db_upgrade_complete_notice', 1, '', 'no' );

	}

	/**
	 * Get memory limit
	 *
	 * @return int
	 */
	protected function get_memory_limit() {
		if ( function_exists( 'ini_get' ) ) {
			$memory_limit = ini_get( 'memory_limit' );
		} else {
			// Sensible default.
			$memory_limit = '128M';
		}

		if ( ! $memory_limit || '-1' === $memory_limit ) {
			// Unlimited, set to 32GB.
			$memory_limit = '32000M';
		}

		return intval( $memory_limit ) * 1024 * 1024;
	}

	/**
	 * Maybe process queue
	 *
	 * Checks whether data exists within the queue and that
	 * the process is not already running.
	 */
	public function maybe_handle() {
		// Don't lock up other requests while processing
		session_write_close();

		if ( $this->is_process_running() || $this->is_paused_process() ) {
			// Background process already running.
			wp_die();
		}

		if ( $this->is_queue_empty() ) {
			// No data to process.
			wp_die();
		}

		check_ajax_referer( $this->identifier, 'nonce' );

		$this->handle();

		wp_die();
	}


	/**
	 * Check if backgound upgrade paused or not.
	 *
	 * @access public
	 * @return bool
	 */
	public function is_paused_process(){
		// Delete cache.
		wp_cache_delete( 'wpum_paused_batches', 'options' );

		$paused_batches = get_option('wpum_paused_batches');

		return ! empty( $paused_batches );
	}


	/**
	 * Get identifier
	 *
	 * @access public
	 * @return mixed|string
	 */
	public function get_identifier() {
		return $this->identifier;
	}

	/**
	 * Get cron identifier
	 *
	 * @access public
	 * @return mixed|string
	 */
	public function get_cron_identifier() {
		return $this->cron_hook_identifier;
	}


	/**
	 * Flush background update related cache to prevent task to go to stalled state.
	 *
	 */
	public static function flush_cache() {

		$options = array(
			'wpum_completed_upgrades',
			'wpum_doing_upgrade',
			'wpum_paused_batches',
			'wpum_upgrade_error',
			'wpum_db_update_count',
			'wpum_doing_upgrade',
			'wpum_pause_upgrade',
			'wpum_show_db_upgrade_complete_notice',
		);


		foreach ( $options as $option ) {
			wp_cache_delete( $option, 'options' );
		}
	}
}
