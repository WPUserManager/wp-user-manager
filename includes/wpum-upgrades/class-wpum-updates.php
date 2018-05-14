<?php
/**
 * Handles registration and display of the plugins related upgrade routines.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WPUM_Updates {

	/**
	 * Instance.
	 *
	 * @since
	 * @access static
	 * @var
	 */
	static private $instance;

	/**
	 * Instance.
	 *
	 * @since
	 * @access public
	 * @var WPUM_Background_Updater
	 */
	static public $background_updater;

	/**
	 * Updates
	 *
	 * @access private
	 * @var array
	 */
	private $updates = array();

	/**
	 * Current update percentage number
	 *
	 * @access private
	 * @var array
	 */
	public $percentage = 0;

	/**
	 * Current update step number
	 *
	 * @access private
	 * @var array
	 */
	public $step = 1;

	/**
	 * Current update number
	 *
	 * @access private
	 * @var array
	 */
	public $update = 1;

	/**
	 * Singleton pattern.
	 *
	 * @access private
	 *
	 * @param WPUM_Updates .
	 */
	private function __construct() {
	}

	/**
	 * Register updates
	 *
	 * @access public
	 *
	 * @param array $args
	 */
	public function register( $args ) {
		$args_default = array(
			'id'       => '',
			'version'  => '',
			'callback' => '',
		);

		$args = wp_parse_args( $args, $args_default );

		// You can only register database upgrade.
		$args['type'] = 'database';

		// Bailout.
		if (
			empty( $args['id'] ) ||
			empty( $args['version'] ) ||
			empty( $args['callback'] ) ||
			! is_callable( $args['callback'] )
		) {
			return;
		}

		// Change depend param to array.
		if ( isset( $args['depend'] ) && is_string( $args['depend'] ) ) {
			$args['depend'] = array( $args['depend'] );
		}

		$this->updates[ $args['type'] ][] = $args;
	}

	/**
	 * Get instance.
	 *
	 * @since
	 * @access static
	 * @return static
	 */
	static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Setup hook
	 *
	 * @access public
	 */
	public function setup() {
		/**
		 * Load file
		 */
		require_once WPUM_PLUGIN_DIR . 'includes/wpum-admin/class-wpum-background-updater.php';
		require_once WPUM_PLUGIN_DIR . 'includes/wpum-upgrades/upgrade-functions.php';

		self::$background_updater = new WPUM_Background_Updater();

		/**
		 * Setup hooks.
		 */
		add_action( 'init', array( $this, '__register_upgrade' ), 9999 );
		add_action( 'wpum_set_upgrade_completed', array( $this, '__flush_resume_updates' ), 9999 );
		add_action( 'wp_ajax_wpum_db_updates_info', array( $this, '__wpum_db_updates_info' ) );
		add_action( 'wp_ajax_wpum_run_db_updates', array( $this, '__wpum_start_updating' ) );
		add_action( 'admin_init', array( $this, '__redirect_admin' ) );
		add_action( 'admin_init', array( $this, '__pause_db_update' ), - 1 );
		add_action( 'admin_init', array( $this, '__restart_db_update' ), - 1 );
		add_action( 'admin_notices', array( $this, '__show_notice' ) );
		add_action( 'wpum_restart_db_upgrade', array( $this, '__health_background_update' ) );

		if ( is_admin() ) {
			add_action( 'admin_init', array( $this, '__change_users_label' ), 9999 );
			add_action( 'admin_menu', array( $this, '__register_menu' ), 9999 );
		}
	}

	/**
	 * Register plugin add-on updates.
	 *
	 * @access public
	 */
	public function __register_plugin_addon_updates() {
		$addons         = wpum_get_plugins();
		$plugin_updates = get_plugin_updates();

		foreach ( $addons as $key => $info ) {
			if ( 'active' != $info['Status'] || 'add-on' != $info['Type'] || empty( $plugin_updates[ $key ] ) ) {
				continue;
			}

			$this->updates['plugin'][] = array_merge( $info, (array) $plugin_updates[ $key ] );
		}
	}


	/**
	 * Fire custom action hook to register updates
	 *
	 * @access public
	 */
	public function __register_upgrade() {
		if ( ! is_admin() ) {
			return;
		}

		/**
		 * Fire the hook
		 */
		do_action( 'wpum_register_updates', $this );
	}

	/**
	 * Rename `Users` menu title if updates exists
	 *
	 * @access public
	 */
	function __change_users_label() {
		global $menu;

		// Bailout.
		if ( empty( $menu ) || ! $this->get_total_update_count() ) {
			return;
		}

		$is_update = ( $this->is_doing_updates() && ! self::$background_updater->is_paused_process() );

		foreach ( $menu as $index => $menu_item ) {
			if ( 'users.php' !== $menu_item[2] ) {
				continue;
			}

			$menu[ $index ][0] = sprintf(
				'%1$s <span class="update-plugins"><span class="plugin-count wpum-update-progress-count">%2$s%3$s</span></span>',
				__( 'Users', 'wp-user-manager' ),
				$is_update ?
					$this->get_db_update_processing_percentage() :
					$this->get_total_update_count(),
				$is_update ? '%' : ''
			);

			break;
		}
	}

	/**
	 * Register updates menu
	 *
	 * @access public
	 */
	public function __register_menu() {
		// Load plugin updates.
		$this->__register_plugin_addon_updates();

		// Bailout.
		if ( ! $this->get_total_update_count() ) {
			// Show complete update message if still on update setting page.
			if ( isset( $_GET['page'] ) && 'wpum-updates' === $_GET['page'] ) {
				add_users_page( 'WPUM Updates Complete', 'Updates', 'manage_options', 'wpum-updates', array( $this, 'render_complete_page' ) );
			}

			return;
		}

		$is_update = ( $this->is_doing_updates() && ! self::$background_updater->is_paused_process() );

		// Upgrades
		add_users_page(
			'WPUM Updates',
			sprintf(
				'%1$s <span class="update-plugins"%2$s><span class="plugin-count wpum-update-progress-count">%3$s%4$s</span></span>',
				__( 'Updates', 'wp-user-manager' ),
				isset( $_GET['wpum-pause-db-upgrades'] ) ? ' style="display:none;"' : '',
				$is_update ?
					$this->get_db_update_processing_percentage() :
					$this->get_total_update_count(),
				$is_update ? '%' : ''
			),
			'manage_options',
			'wpum-updates',
			array( $this, 'render_page' )
		);

	}

	/**
	 * Show update related notices
	 *
	 * @access public
	 */
	public function __redirect_admin() {
		// Show db upgrade completed notice.
		if (
			! wp_doing_ajax() &&
			current_user_can( 'manage_options' ) &&
			get_option( 'wpum_show_db_upgrade_complete_notice' ) &&
			! isset( $_GET['wpum-db-update-completed'] )
		) {
			delete_option( 'wpum_show_db_upgrade_complete_notice' );

			wp_redirect( admin_url( 'users.php?page=wpum-updates&wpum-db-update-completed=wpum_db_upgrade_completed' ) );
			exit();
		}
	}


	/**
	 * Pause db upgrade
	 *
	 * @access public
	 * @param bool $force
	 * @return bool
	 */
	public function __pause_db_update( $force = false ) {
		// Bailout.
		if (
			! $force &&
			(
				wp_doing_ajax() ||
				! isset( $_GET['page'] ) ||
				'wpum-updates' !== $_GET['page'] ||
				! isset( $_GET['wpum-pause-db-upgrades'] ) ||
				self::$background_updater->is_paused_process()
			)

		) {
			return false;
		}

		delete_option( 'wpum_upgrade_error' );

		$this->__health_background_update( $this );
		$batch = self::$background_updater->get_all_batch();

		// Bailout: if batch is empty
		if ( empty( $batch->data ) ) {
			return false;
		}

		// Remove cache.
		WPUM_Background_Updater::flush_cache();

		// Do not stop background process immediately if task running.
		// @see WPUM_Background_Updater::lock_process
		if ( ! $force && self::$background_updater->is_process_running() ) {
			update_option( 'wpum_pause_upgrade', 1 );

			return true;
		}

		update_option( 'wpum_paused_batches', $batch, 'no' );
		delete_option( $batch->key );
		delete_site_transient( self::$background_updater->get_identifier() . '_process_lock' );
		wp_clear_scheduled_hook( self::$background_updater->get_cron_identifier() );

		/**
		 * Fire action when pause db updates
		 */
		do_action( 'wpum_pause_db_upgrade', $this );

		return true;
	}

	/**
	 * Restart db upgrade
	 *
	 * @access public
	 * @return bool
	 */
	public function __restart_db_update() {
		// Bailout.
		if (
			wp_doing_ajax() ||
			! isset( $_GET['page'] ) ||
			'wpum-updates' !== $_GET['page'] ||
			! isset( $_GET['wpum-restart-db-upgrades'] ) ||
			! self::$background_updater->is_paused_process()
		) {
			return false;
		}

		WPUM_Background_Updater::flush_cache();
		$batch = get_option( 'wpum_paused_batches' );

		if ( ! empty( $batch ) ) {
			wp_cache_delete( $batch->key, 'options' );
			update_option( $batch->key, $batch->data );

			delete_option( 'wpum_paused_batches' );

			do_action( 'wpum_restart_db_upgrade', $this );

			self::$background_updater->dispatch();
		}

		return true;
	}

	/**
	 * Health check for updates.
	 *
	 * @access public
	 * @param WPUM_Updates $wpum_updates
	 */
	public function __health_background_update( $wpum_updates ) {
		if ( ! $this->is_doing_updates() ) {
			return;
		}

		WPUM_Background_Updater::flush_cache();

		$batch                = WPUM_Updates::$background_updater->get_all_batch();
		$batch_data_count     = count( $batch->data );
		$all_updates          = $wpum_updates->get_updates( 'database', 'all' );
		$all_update_ids       = wp_list_pluck( $all_updates, 'id' );
		$all_batch_update_ids = ! empty( $batch->data ) ? wp_list_pluck( $batch->data, 'id' ) : array();
		$log_data             = '';
		$doing_upgrade_args   = get_option( 'wpum_doing_upgrade' );

		if ( ! empty( $doing_upgrade_args ) ) {
			$log_data .= 'Doing update:' . "\n";
			$log_data .= print_r( $doing_upgrade_args, true ) . "\n";
		}

		/**
		 * Add remove upgrade from batch
		 */
		if ( ! empty( $batch->data ) ) {

			foreach ( $batch->data as $index => $update ) {
				$log_data = print_r( $update, true ) . "\n";

				if ( ! is_callable( $update['callback'] ) ) {
					$log_data .= 'Removing missing callback update: ' . "{$update['id']}\n";
					unset( $batch->data[ $index ] );
				} elseif ( wpum_has_upgrade_completed( $update['id'] ) ) {
					$log_data .= 'Removing already completed update: ' . "{$update['id']}\n";
					unset( $batch->data[ $index ] );
				}

				if ( ! empty( $update['depend'] ) ) {

					foreach ( $update['depend'] as $depend ) {
						if ( wpum_has_upgrade_completed( $depend ) ) {
							$log_data .= 'Completed update: ' . "{$depend}\n";
							continue;
						}

						if ( in_array( $depend, $all_update_ids ) && ! in_array( $depend, $all_batch_update_ids ) ) {
							$log_data .= 'Adding missing update: ' . "{$depend}\n";
							array_unshift( $batch->data, $all_updates[ array_search( $depend, $all_update_ids ) ] );
						}
					}
				}
			}
		}

		/**
		 * Add new upgrade to batch
		 */
		if ( $new_updates = $this->get_updates( 'database', 'new' ) ) {
			$all_batch_update_ids = ! empty( $batch->data ) ? wp_list_pluck( $batch->data, 'id' ) : array();

			foreach ( $new_updates as $index => $new_update ) {
				if ( wpum_has_upgrade_completed( $new_update['id'] ) || in_array( $new_update['id'], $all_batch_update_ids ) ) {
					unset( $new_updates[ $index ] );
				}
			}

			if ( ! empty( $new_updates ) ) {
				$log_data .= 'Adding new update: ' . "\n";
				$log_data .= print_r( $new_updates, true ) . "\n";

				$batch->data = array_merge( (array) $batch->data, $new_updates );
				update_option( 'wpum_db_update_count', ( absint( get_option( 'wpum_db_update_count' ) ) + count( $new_updates ) ) );
			}
		}

		/**
		 * Fix batch
		 */
		if ( empty( $batch->data ) ) {
			// Complete batch if do not have any data to process.
			self::$background_updater->delete( $batch->key );

			if ( self::$background_updater->has_queue() ) {
				$this->__health_background_update( $this );
			} else {
				delete_site_transient( self::$background_updater->get_identifier() . '_process_lock' );
				wp_clear_scheduled_hook( self::$background_updater->get_cron_identifier() );

				self::$background_updater->complete();
			}

		} elseif ( $batch_data_count !== count( $batch->data ) ) {

			$log_data .= 'Updating batch' . "\n";
			$log_data .= print_r( $batch, true );

			if ( ! empty( $batch->key ) ) {
				wp_cache_delete( $batch->key, 'options' );
				update_option( $batch->key, $batch->data );
			} else {

				foreach ( $batch->data as $data ) {
					WPUM_Updates::$background_updater->push_to_queue( $data );
				}

				WPUM_Updates::$background_updater->save();
			}
		}


		/**
		 * Fix wpum_doing_upgrade option
		 */
		if( $fresh_new_db_count = $this->get_total_new_db_update_count( true ) ) {
			update_option( 'wpum_db_update_count', $fresh_new_db_count );
		}

		$doing_upgrade_args['update']           = 1;
		$doing_upgrade_args['heading']          = sprintf( 'Update %s of %s', 1, $fresh_new_db_count );
		$doing_upgrade_args['total_percentage'] = $this->get_db_update_processing_percentage( true );

		// Remove already completed update from info.
		if (
			empty( $doing_upgrade_args['update_info'] )
			|| wpum_has_upgrade_completed( $doing_upgrade_args['update_info']['id'] )
		) {
			$doing_upgrade_args['update_info'] = current( array_values( $batch->data ) );
			$doing_upgrade_args['step']        = 1;
		}

		// Check if dependency completed or not.
		if ( isset( $doing_upgrade_args['update_info']['depend'] ) ) {
			foreach ( $doing_upgrade_args['update_info']['depend'] as $depend ) {
				if ( wpum_has_upgrade_completed( $depend ) ) {
					continue;
				}

				$doing_upgrade_args['update_info']      = $all_updates[ array_search( $depend, $all_update_ids ) ];
				$doing_upgrade_args['step']             = 1;
				$doing_upgrade_args['percentage']       = 0;
				$doing_upgrade_args['total_percentage'] = 0;

				break;
			}
		}

		if( ! empty( $doing_upgrade_args['update_info'] ) ) {
			update_option( 'wpum_doing_upgrade', $doing_upgrade_args );

			$log_data .= 'Updated doing update:' . "\n";
			$log_data .= print_r( $doing_upgrade_args, true ) . "\n";
		}

	}

	/**
	 * Show update related notices
	 *
	 * @access public
	 */
	public function __show_notice() {
		$current_screen = get_current_screen();

		// Bailout.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Run DB updates.
		if ( ! empty( $_GET['wpum-run-db-update'] ) ) {
			$this->run_db_update();
		}

		// Bailout.
		if ( in_array( $current_screen->base, array( 'wpum_forms_page_wpum-updates', 'update-core' ) ) ) {
			return;
		}

		// Show notice if upgrade paused.
		if ( self::$background_updater->is_paused_process() ) {
			ob_start();

			$upgrade_error = get_option( 'wpum_upgrade_error' );
			if ( ! $upgrade_error ) : ?>
				<strong><?php _e( 'Database Update', 'wp-user-manager' ); ?></strong>
				&nbsp;&#8211;&nbsp;<?php _e( 'WPUM needs to update your database to the latest version. The following process will make updates to your site\'s database. Please create a backup before proceeding.', 'wp-user-manager' ); ?>
				<br>
				<br>
				<a href="<?php echo esc_url( add_query_arg( array( 'wpum-restart-db-upgrades' => 1 ), admin_url( 'users.php?page=wpum-updates' ) ) ); ?>" class="button button-primary wpum-restart-updater-btn">
					<?php _e( 'Restart the updater', 'wp-user-manager' ); ?>
				</a>
			<?php else: ?>
				<strong><?php _e( 'Database Update', 'wp-user-manager' ); ?></strong>
				&nbsp;&#8211;&nbsp;<?php _e( 'An unexpected issue occurred during the database update which caused it to stop automatically. Please contact support for assistance.', 'wp-user-manager' ); ?>
			<?php
			endif;
			$desc_html = ob_get_clean();

			WPUM()->notices->register_notice( 'wpum_upgrade_db', 'error', $desc_html, [ 'dismissible' => false ] );

		}

		// Bailout if doing upgrades.
		if ( $this->is_doing_updates() ) {
			return;
		}

		// Show db upgrade completed notice.
		if ( ! empty( $_GET['wpum-db-update-completed'] ) ) {

			$message = __( 'WP User Manager database update successfully completed. Thank you for updating to the latest version!', 'wp-user-manager' );
			WPUM()->notices->register_notice( 'wpum_db_upgrade_completed', 'success', $message );

			// Start update.
		} elseif ( ! empty( $_GET['wpum-run-db-update'] ) ) {
			$this->run_db_update();

			// Show run the update notice.
		} elseif ( $this->get_total_new_db_update_count() ) {
			ob_start();
			?>
			<p>
				<?php _e( '<strong>WP User Manager</strong> needs to update your database to the latest version. The following process will make updates to your site\'s database. <strong><u>Please create a complete backup before proceeding.</u></strong>', 'wp-user-manager' ); ?>
			</p>
			<p>
				<a href="<?php echo esc_url( add_query_arg( array( 'wpum-run-db-update' => 1 ), admin_url( 'users.php?page=wpum-updates' ) ) ); ?>" class="button button-primary wpum-run-update-now">
					<?php _e( 'Update the database', 'wp-user-manager' ); ?>
				</a>
			</p>
			<?php
			$desc_html = ob_get_clean();

			WPUM()->notices->register_notice( 'wpum_upgrade_db', 'warning', $desc_html, [ 'dismissible' => false ] );

		}
	}

	/**
	 * Render completed page
	 *
	 * @access public
	 */
	public function render_complete_page() {
		include_once WPUM_PLUGIN_DIR . 'includes/wpum-upgrades/views/upgrades-complete.php';
	}

	/**
	 * Render updates page
	 *
	 * @access public
	 */
	public function render_page() {
		include_once WPUM_PLUGIN_DIR . 'includes/wpum-upgrades/views/upgrades.php';
	}

	/**
	 * Run database upgrades
	 *
	 * @access private
	 */
	private function run_db_update() {
		// Bailout.
		if ( $this->is_doing_updates() || ! $this->get_total_new_db_update_count() ) {
			return;
		}

		$updates = $this->get_updates( 'database', 'new' );

		foreach ( $updates as $update ) {
			self::$background_updater->push_to_queue( $update );
		}

		add_option( 'wpum_db_update_count', count( $updates ), '', 'no' );

		add_option( 'wpum_doing_upgrade', array(
			'update_info'      => $updates[0],
			'step'             => 1,
			'update'           => 1,
			'heading'          => sprintf( 'Update %s of %s', 1, count( $updates ) ),
			'percentage'       => 0,
			'total_percentage' => 0,
		), '', 'no' );

		self::$background_updater->save()->dispatch();
	}


	/**
	 * Delete resume updates
	 *
	 * @access public
	 */
	public function __flush_resume_updates() {
		//delete_option( 'wpum_doing_upgrade' );
		update_option( 'wpum_version', preg_replace( '/[^0-9.].*/', '', WPUM_VERSION ) );

		// Reset counter.
		$this->step = $this->percentage = 0;

		$this->update = ( $this->get_total_db_update_count() > $this->update ) ?
			( $this->update + 1 ) :
			$this->update;
	}


	/**
	 * Initialize updates
	 *
	 * @access public
	 * @return void
	 */
	public function __wpum_start_updating() {
		// Check permission.
		if (
			! current_user_can( 'manage_options' ) ||
			$this->is_doing_updates()
		) {
			wp_send_json_error();
		}

		// @todo: validate nonce
		// @todo: set http method to post
		if ( empty( $_POST['run_db_update'] ) ) {
			wp_send_json_error();
		}

		$this->run_db_update();

		wp_send_json_success();
	}


	/**
	 * This function handle ajax query for dn update status.
	 *
	 * @access public
	 * @return string
	 */
	public function __wpum_db_updates_info() {
		$update_info   = get_option( 'wpum_doing_upgrade' );
		$response_type = '';

		if ( self::$background_updater->is_paused_process() ) {
			$update_info = array(
				'message'    => __( 'The updates have been paused.', 'wp-user-manager' ),
				'heading'    => '',
				'percentage' => 0,
			);

			if ( get_option( 'wpum_upgrade_error' ) ) {
				$update_info['message'] = __( 'An unexpected issue occurred during the database update which caused it to stop automatically. Please contact support for assistance.', 'wp-user-manager' );
			}

			$response_type = 'error';

		} elseif ( empty( $update_info ) || ! $this->get_total_new_db_update_count( true ) ) {
			$update_info   = array(
				'message'    => __( 'WPUM database updates completed successfully. Thank you for updating to the latest version!', 'wp-user-manager' ),
				'heading'    => __( 'Updates Completed.', 'wp-user-manager' ),
				'percentage' => 100,
			);
			$response_type = 'success';

			delete_option( 'wpum_show_db_upgrade_complete_notice' );
		}

		$this->send_ajax_response( $update_info, $response_type );
	}

	/**
	 * Send ajax response
	 *
	 * @access public
	 * @param        $data
	 * @param string $type
	 */
	public function send_ajax_response( $data, $type = '' ) {
		$default = array(
			'message'    => '',
			'heading'    => '',
			'percentage' => 0,
			'step'       => 0,
			'update'     => 0,
		);

		// Set data.
		$data = wp_parse_args( $data, $default );

		switch ( $type ) {
			case 'success':
				wp_send_json_success( $data );
				break;

			case 'error':
				wp_send_json_error( $data );
				break;

			default:
				wp_send_json( array(
					'data' => $data,
				) );
				break;
		}
	}

	/**
	 * Set current update percentage.
	 *
	 * @access public
	 * @param $total
	 * @param $current_total
	 */
	public function set_percentage( $total, $current_total ) {
		// Set percentage.
		$this->percentage = $total ? ( ( $current_total ) / $total ) * 100 : 0;

		// Verify percentage.
		$this->percentage = ( 100 < $this->percentage ) ? 100 : $this->percentage;
	}

	/**
	 * Check if parent update completed or not.
	 *
	 * @access private
	 * @param array $update
	 * @return bool|null
	 */
	public function is_parent_updates_completed( $update ) {
		// Bailout.
		if ( empty( $update['depend'] ) ) {
			return true;
		}

		// Check if dependency is valid or not.
		if ( ! $this->has_valid_dependency( $update ) ) {
			return null;
		}

		$is_dependency_completed = true;

		foreach ( $update['depend'] as $depend ) {

			if ( ! wpum_has_upgrade_completed( $depend ) ) {
				$is_dependency_completed = false;
				break;
			}
		}

		return $is_dependency_completed;
	}

	/**
	 * Flag to check if DB updates running or not.
	 *
	 * @access public
	 * @return bool
	 */
	public function is_doing_updates() {
		return (bool) get_option( 'wpum_doing_upgrade' );
	}


	/**
	 * Check if update has valid dependency or not.
	 *
	 * @access public
	 * @param $update
	 * @return bool
	 */
	public function has_valid_dependency( $update ) {
		$is_valid_dependency = true;
		// $update_ids          = wp_list_pluck( $this->get_updates( 'database', 'all' ), 'id' );
		//
		// foreach ( $update['depend'] as $depend ) {
		// 	// Check if dependency is valid or not.
		// 	if ( ! in_array( $depend, $update_ids ) ) {
		// 		$is_valid_dependency = false;
		// 		break;
		// 	}
		// }

		return $is_valid_dependency;
	}

	/**
	 * Get updates.
	 *
	 * @access public
	 * @param string $update_type Tye of update.
	 * @param string $status      Tye of update.
	 *
	 * @return array
	 */
	public function get_updates( $update_type = '', $status = 'all' ) {
		// return all updates.
		if ( empty( $update_type ) ) {
			return $this->updates;
		}

		// Get specific update.
		$updates = ! empty( $this->updates[ $update_type ] ) ? $this->updates[ $update_type ] : array();

		// Bailout.
		if ( empty( $updates ) ) {
			return $updates;
		}

		switch ( $status ) {
			case 'new':
				// Remove already completed updates.
				wp_cache_delete( 'wpum_completed_upgrades', 'options' );
				$completed_updates = wpum_get_completed_upgrades();

				if ( ! empty( $completed_updates ) ) {
					foreach ( $updates as $index => $update ) {
						if ( in_array( $update['id'], $completed_updates ) ) {
							unset( $updates[ $index ] );
						}
					}
					$updates = array_values( $updates );
				}

				break;
		}

		return $updates;
	}

	/**
	 * Get addon update count.
	 *
	 * @access public
	 * @return int
	 */
	public function get_total_plugin_update_count() {
		return count( $this->get_updates( 'plugin' ) );
	}

	/**
	 * Get total update count
	 *
	 * @access public
	 * @return int
	 */
	public function get_total_update_count() {
		$db_update_count     = $this->get_pending_db_update_count();
		$plugin_update_count = $this->get_total_plugin_update_count();

		return ( $db_update_count + $plugin_update_count );
	}

	/**
	 * Get total pending updates count
	 *
	 * @access public
	 * @return int
	 */
	public function get_pending_db_update_count() {
		return count( $this->get_updates( 'database', 'new' ) );
	}

	/**
	 * Get total updates count
	 *
	 * @access public
	 * @return int
	 */
	public function get_total_db_update_count() {
		return count( $this->get_updates( 'database', 'all' ) );
	}

	/**
	 * Get total new updates count
	 *
	 * @access public
	 * @param bool $refresh
	 * @return int
	 */
	public function get_total_new_db_update_count( $refresh = false ) {
		$update_count = $this->is_doing_updates() && ! $refresh ?
			get_option( 'wpum_db_update_count' ) :
			$this->get_pending_db_update_count();

		return $update_count;
	}

	/**
	 * Get total new updates count
	 *
	 * @access public
	 * @param bool $refresh
	 * @return int
	 */
	public function get_running_db_update( $refresh = false ) {
		$current_update = 1;

		if ( $this->is_doing_updates() && ! $refresh ) {
			$current_update = get_option( 'wpum_doing_upgrade' );
			$current_update = $current_update['update'];
		}

		return $current_update;
	}

	/**
	 * Get database update processing percentage.
	 *
	 * @access public
	 * @param bool $refresh
	 * @return float|int
	 */
	public function get_db_update_processing_percentage( $refresh = false ) {
		// Bailout.
		if ( ! $this->get_total_new_db_update_count( $refresh ) ) {
			return 0;
		}

		$resume_update            = get_option( 'wpum_doing_upgrade' );
		$update_count_percentages = ( ( $this->get_running_db_update( $refresh ) - 1 ) / $this->get_total_new_db_update_count( $refresh ) ) * 100;
		$update_percentage_share  = ( 1 / $this->get_total_new_db_update_count() ) * 100;
		$upgrade_percentage       = ( ( $resume_update['percentage'] * $update_percentage_share ) / 100 );

		$final_percentage = $update_count_percentages + $upgrade_percentage;

		return $this->is_doing_updates() ?
			( absint( $final_percentage ) ?
				absint( $final_percentage ) :
				round( $final_percentage, 2 )
			) :
			0;
	}


	/**
	 * Get all update ids.
	 *
	 * @return array
	 */
	public function get_update_ids() {
		$all_updates    = $this->get_updates( 'database', 'all' );
		$all_update_ids = wp_list_pluck( $all_updates, 'id' );

		return $all_update_ids;
	}

	/**
	 * Get offset count
	 *
	 * @access public
	 * @param int $process_item_count
	 * @return float|int
	 */
	public function get_offset( $process_item_count ) {
		return ( 1 === $this->step ) ?
			0 :
			( $this->step - 1 ) * $process_item_count;
	}
}

WPUM_Updates::get_instance()->setup();
