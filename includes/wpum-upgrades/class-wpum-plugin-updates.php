<?php
/**
 * Handles upgrade routines.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The class thand handles the upgrade.
 */
class WPUM_Plugin_Updates {

	/**
	 * Start things.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Hook into WP.
	 *
	 * @return void
	 */
	public function init() {
		require_once WPUM_PLUGIN_DIR . 'includes/wpum-upgrades/upgrade-functions.php';

		add_action( 'admin_init', [ $this, 'v2_upgrade_notice' ] );
		add_action( 'admin_init', [ $this, 'upgrade' ] );
	}

	/**
	 * Show an upgrade notice.
	 *
	 * @return void
	 */
	public function v2_upgrade_notice() {

		if ( get_option( 'v202_upgrade' ) ) {
			return;
		}

		$update_url = add_query_arg( [ 'wpum-plugin-updates' => 'v202' ], admin_url() );
		$message    = '<p><strong>WP User Manager</strong> needs to update your database to the latest version. The following process will make updates to your site\'s database. <strong><u>Please create a complete backup before proceeding.</u></strong></p>';
		$message   .= '<p><a href="' . $update_url . '" class="button-primary">' . esc_html__( 'Upgrade database', 'wp-user-manager' ) . '</a></p>';
		WPUM()->notices->register_notice( 'wpumv2_upgrade_required_notice', 'warning', $message, [ 'dismissible' => false ] );

	}

	/**
	 * Upgrade page options from a previous version.
	 *
	 * @return void
	 */
	private function upgrade_page_options() {

		// Get existing page options.
		$login_page             = wpum_get_option( 'login_page' );
		$password_recovery_page = wpum_get_option( 'password_recovery_page' );
		$registration_page      = wpum_get_option( 'registration_page' );
		$account_page           = wpum_get_option( 'account_page' );
		$profile_page           = wpum_get_option( 'profile_page' );
		$terms_page             = wpum_get_option( 'terms_page' );

		// Create an array for each of the page options.
		if ( ! is_array( $login_page ) && ! is_array( $password_recovery_page ) && ! is_array( $registration_page ) && ! is_array( $account_page ) && ! is_array( $profile_page ) && ! is_array( $terms_page ) ) {

			$login_page             = [ $login_page ];
			$password_recovery_page = [ $password_recovery_page ];
			$registration_page      = [ $registration_page ];
			$account_page           = [ $account_page ];
			$profile_page           = [ $profile_page ];
			$terms_page             = [ $terms_page ];

			// Now update the page options into the db with the newly generated array.
			wpum_update_option( 'login_page', $login_page );
			wpum_update_option( 'password_recovery_page', $password_recovery_page );
			wpum_update_option( 'registration_page', $registration_page );
			wpum_update_option( 'account_page', $account_page );
			wpum_update_option( 'profile_page', $profile_page );
			wpum_update_option( 'terms_page', $terms_page );

		}

		// Migrate frontend redirects options.
		$after_login        = wpum_get_option( 'login_redirect' );
		$after_logout       = wpum_get_option( 'logout_redirect' );
		$after_registration = wpum_get_option( 'registration_redirect' );

		if ( ! is_array( $after_login ) && ! empty( $after_login ) ) {
			$after_login = [ $after_login ];
			wpum_update_option( 'login_redirect', $after_login );
		}

		if ( ! is_array( $after_logout ) && ! empty( $after_logout ) ) {
			$after_logout = [ $after_logout ];
			wpum_update_option( 'logout_redirect', $after_logout );
		}

		if ( ! is_array( $after_registration ) && ! empty( $after_registration ) ) {
			$after_registration = [ $after_registration ];
			wpum_update_option( 'registration_redirect', $after_registration );
		}

		// Migrate backend redirects options.
		$wp_login_signup_redirect   = wpum_get_option( 'wp_login_signup_redirect' );
		$wp_login_password_redirect = wpum_get_option( 'wp_login_password_redirect' );
		$backend_profile_redirect   = wpum_get_option( 'backend_profile_redirect' );

		if ( ! is_array( $wp_login_signup_redirect ) && ! empty( $wp_login_signup_redirect ) ) {
			$wp_login_signup_redirect = [ $wp_login_signup_redirect ];
			wpum_update_option( 'wp_login_signup_redirect', $wp_login_signup_redirect );
		}

		if ( ! is_array( $wp_login_password_redirect ) && ! empty( $wp_login_password_redirect ) ) {
			$wp_login_password_redirect = [ $wp_login_password_redirect ];
			wpum_update_option( 'wp_login_password_redirect', $wp_login_password_redirect );
		}

		if ( ! is_array( $backend_profile_redirect ) && ! empty( $backend_profile_redirect ) ) {
			$backend_profile_redirect = [ $backend_profile_redirect ];
			wpum_update_option( 'backend_profile_redirect', $backend_profile_redirect );
		}

	}

	/**
	 * Check if the primary fields group exists.
	 *
	 * @return void
	 */
	private function check_primary_group() {

		//$primary_group = WPUM()->fields_groups->get_groups( [ 'primary' => true ] );

		//$group_exists = is_array( $primary_group ) && isset( $primary_group[0] ) && $primary_group[0] instanceof WPUM_Field_Group ? true : false;

		global $wpdb;

		$groups = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'wpum_fieldsgroups' );

		if ( ! isset( $groups[0] ) ) {
			$default_group = new WPUM_Field_Group();
			$default_group->add(
				[
					'id'   => 1,
					'name' => esc_html__( 'Primary fields', 'wp-user-manager' ),
					'is_primary' => true,
				]
			);
		}

		$default_group = new WPUM_Field_Group( 1 );
		$default_group->update( [ 'is_primary' => true ] );

	}

	/**
	 * Migrate fields groups from the existing table.
	 *
	 * @return void
	 */
	private function migrate_fields_groups() {

		global $wpdb;

		$table  = 'wpum_field_groups';
		$exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE '%s'", $table ) ) === $table;

		if ( $exists ) {
			$old_fields_groups = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'wpum_field_groups' );
			if ( ! empty( $old_fields_groups ) && is_array( $old_fields_groups ) ) {
				foreach ( $old_fields_groups as $field_group ) {
					$new_group = new WPUM_Field_Group();
					$new_group->add(
						[
							'id'          => $field_group->id,
							'name'        => esc_html( $field_group->name ),
							'description' => $field_group->description,
							'is_primary'  => $field_group->is_primary ? true : false,
							'group_order' => $field_group->group_order,
						]
					);
				}
				$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'wpum_field_groups' );
			}
		}

	}

	/**
	 * Migrate fields settings.
	 *
	 * @return void
	 */
	private function migrate_fields() {

		$fields = WPUM()->fields->get_fields();

		foreach ( $fields as $field ) {

			if ( $field->get_primary_id() == 'user_cover' ) {
				continue;
			}

			$field_id = $field->get_ID();

			// Update the assigned type of the field.
			// We need to check for the meta flag first.
			$existing_meta = WPUM()->fields->get_column( 'meta', $field_id );

			if ( $existing_meta ) {

				if ( $existing_meta == 'user_email' ) {
					$field->update( [ 'type' => 'user_email' ] );
				} elseif ( $existing_meta == 'password' ) {
					$field->update( [ 'type' => 'user_password' ] );
				} elseif ( $existing_meta == 'first_name' ) {
					$field->update( [ 'type' => 'user_firstname' ] );
				} elseif ( $existing_meta == 'last_name' ) {
					$field->update( [ 'type' => 'user_lastname' ] );
				} elseif ( $existing_meta == 'nickname' ) {
					$field->update( [ 'type' => 'user_nickname' ] );
				} elseif ( $existing_meta == 'display_name' ) {
					$field->update( [ 'type' => 'user_displayname' ] );
				} elseif ( $existing_meta == 'user_url' ) {
					$field->update( [ 'type' => 'user_website' ] );
				} elseif ( $existing_meta == 'description' ) {
					$field->update( [ 'type' => 'user_description' ] );
				} elseif ( $existing_meta == 'user_avatar' ) {
					$field->update( [ 'type' => 'user_avatar' ] );
				}
			}

			// Update the type of the field to match the new one.
			$existing_type = WPUM()->fields->get_column( 'type', $field_id );

			if ( $existing_type == 'select' ) {
				$field->update( [ 'type' => 'dropdown' ] );
			} elseif ( $existing_type == 'checkboxes' ) {
				$field->update( [ 'type' => 'multicheckbox' ] );
			}

			// Get previous required status and update the meta
			$is_required = WPUM()->fields->get_column( 'is_required', $field_id );
			if ( $is_required ) {
				$field->add_meta( 'required', true );
			}

			// Get the visibility setting.
			$default_visibility = WPUM()->fields->get_column( 'default_visibility', $field_id );
			$field->add_meta( 'visibility', $default_visibility );

			// Get the assigned user meta key.
			$meta = WPUM()->fields->get_column( 'meta', $field_id );
			if ( $meta == 'first_name' ) {
				$field->add_meta( 'user_meta_key', 'firstname' );
			} elseif ( $meta == 'last_name' ) {
				$field->add_meta( 'user_meta_key', 'lastname' );
			} elseif ( $meta == 'password' ) {
				$field->add_meta( 'user_meta_key', 'user_password' );
			} elseif ( $meta == 'user_avatar' ) {
				$field->add_meta( 'user_meta_key', 'current_user_avatar' );
			} else {
				$field->add_meta( 'user_meta_key', $meta );
			}

			// Grab all the options available.
			$options = WPUM()->fields->get_column( 'options', $field_id );

			if ( ! empty( $options ) ) {
				$options = maybe_unserialize( $options );
				if ( is_array( $options ) && ! empty( $options ) ) {
					if ( array_key_exists( 'can_edit', $options ) ) {
						unset( $options['can_edit'] );
					}
					foreach ( $options as $option_id => $option ) {
						if ( $option_id == 'selectable' ) {
							$dropdown_options = maybe_unserialize( $option );
							$new_opts         = [];
							if ( is_array( $dropdown_options ) && ! empty( $dropdown_options ) ) {
								foreach ( $dropdown_options as $dropdown_option ) {
									$new_opts[] = [
										'value' => $dropdown_option['option-value'],
										'label' => $dropdown_option['option-title'],
									];
								}
							}
							$field->add_meta( 'dropdown_options', maybe_unserialize( $new_opts ) );
						} else {
							$field->add_meta( $option_id, $option );
						}
					}
				}
			}

			// Mark username field as non editable.
			if ( $field->get_primary_id() == 'username' ) {
				$field->update_meta( 'editing', 'hidden' );
			}

			// Mark other fields as editable if needed.
			if ( $field->get_primary_id() !== 'username' ) {

				$editing_option_exists = WPUM()->fields->get_column( 'options', $field_id );
				$editing_option_exists = is_array( $editing_option_exists ) && ! empty( $editing_option_exists ) && array_key_exists( 'can_edit', maybe_unserialize( $editing_option_exists ) ) ? $editing_option_exists['can_edit'] : false;

				if ( $editing_option_exists ) {
					$field->update_meta( 'editing', $editing_option_exists );
				} else {
					$field->update_meta( 'editing', 'public' );
				}
			}
		}

	}

	/**
	 * Install cover field if it doesn't exist.
	 *
	 * @return void
	 */
	private function install_cover_field() {

		$primary_group  = WPUM()->fields_groups->get_groups( ['primary' => true ] );
		$primary_group  = $primary_group[0];
		$account_fields = WPUM()->fields->get_fields(
			[
				'group_id' => $primary_group->get_ID(),
				'orderby'  => 'field_order',
				'order'    => 'ASC',
			]
		);

		$cover_exists = false;

		foreach ( $account_fields as $field ) {
			if ( $field->get_primary_id() == 'user_cover' ) {
				$cover_exists = true;
			}
		}

		if ( ! $cover_exists ) {
			wpum_install_cover_image_field();
		}

	}

	/**
	 * Migrate emails.
	 *
	 * @return void
	 */
	private function migrate_emails() {

		$existing_emails = get_option( 'wpum_emails' );
		$new_emails      = '';

		if ( ! empty( $existing_emails ) && is_array( $existing_emails ) ) {

			// Grab the existing registration email and reformat it for the new emails.
			if ( array_key_exists( 'register', $existing_emails ) ) {

				$existing_registration_email = $existing_emails['register'];
				$existing_email_subject      = $existing_registration_email['subject'];
				$existing_email_message      = $existing_registration_email['message'];

				$new_emails['registration_confirmation'] = [
					'title'   => $existing_email_subject,
					'subject' => $existing_email_subject,
					'content' => $existing_email_message,
				];

			}

			if ( array_key_exists( 'password', $existing_emails ) ) {

				$existing_password_recovery_email = $existing_emails['password'];
				$existing_email_subject           = $existing_password_recovery_email['subject'];
				$existing_email_message           = $existing_password_recovery_email['message'];

				$new_emails['password_recovery_request'] = [
					'title'   => $existing_email_subject,
					'subject' => $existing_email_subject,
					'content' => $existing_email_message,
				];

			}
		}

		if ( is_array( $new_emails ) && ! empty( $new_emails ) ) {
			update_option( 'wpum_email', $new_emails );
		}

	}

	/**
	 * Install default search fields within the directory.
	 *
	 * @return void
	 */
	private function install_search_fields() {

		global $wpdb;

		$search_fields = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'wpum_search_fields' );

		if ( is_array( $search_fields ) && empty( $search_fields ) ) {
			wpum_setup_default_custom_search_fields();
		}

	}

	/**
	 * Install the default registration form.
	 *
	 * @return void
	 */
	private function install_registration_form() {

		global $wpdb;

		$reg_forms = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'wpum_registration_forms' );

		if ( is_array( $reg_forms ) && empty( $reg_forms ) ) {

			$default_form_id = WPUM()->registration_forms->insert(
				[
					'name' => esc_html__( 'Default registration form', 'wp-user-manager' ),
				]
			);

			$default_form = new WPUM_Registration_Form( $default_form_id );
			$default_form->add_meta( 'default', true );
			$default_form->add_meta( 'role', get_option( 'default_role' ) );
			$default_form->add_meta( 'fields', [] );

		}

	}

	/**
	 * Migrate directories.
	 *
	 * @return void
	 */
	function migrate_directories() {

		$directories = new WP_Query(
			array(
				'status'         => 'any',
				'order'          => 'ASC',
				'post_type'      => 'wpum_directory',
				'posts_per_page' => 20,
			)
		);

		if ( $directories->have_posts() ) {

			while ( $directories->have_posts() ) {

				$directories->the_post();

				$directory_id = get_the_ID();

				// Get all the existing custom fields.
				$directory_roles        = get_post_meta( $directory_id, 'directory_roles', true );
				$display_search_form    = get_post_meta( $directory_id, 'display_search_form', true );
				$excluded_ids           = get_post_meta( $directory_id, 'excluded_ids', true );
				$profiles_per_page      = get_post_meta( $directory_id, 'profiles_per_page', true );
				$directory_template     = get_post_meta( $directory_id, 'directory_template', true );
				$display_sorter         = get_post_meta( $directory_id, 'display_sorter', true );
				$display_amount         = get_post_meta( $directory_id, 'display_amount', true );
				$default_sorting_method = get_post_meta( $directory_id, 'default_sorting_method', true );

				carbon_set_post_meta( $directory_id, 'directory_assigned_roles', $directory_roles );

				if ( $display_search_form ) {
					carbon_set_post_meta( $directory_id, 'directory_search_form', 'yes' );
				}

				carbon_set_post_meta( $directory_id, 'directory_excluded_users', $excluded_ids );
				carbon_set_post_meta( $directory_id, 'directory_profiles_per_page', $profiles_per_page );

				if ( $display_sorter ) {
					carbon_set_post_meta( $directory_id, 'directory_display_sorter', 'yes' );
				}
				if ( $display_amount ) {
					carbon_set_post_meta( $directory_id, 'directory_display_amount_filter', 'yes' );
				}

				carbon_set_post_meta( $directory_id, 'directory_sorting_method', $default_sorting_method );

			}

			wp_reset_postdata();

		}

	}

	/**
	 * The actual function that runs the upgrade.
	 *
	 * @return void
	 */
	public function upgrade() {

		if ( isset( $_GET['wpum-plugin-updates'] ) && $_GET['wpum-plugin-updates'] == 'v202' && current_user_can( 'manage_options' ) && ! get_option( 'v202_upgrade' ) ) {

			delete_option( 'wpum_completed_upgrades' );
			delete_option( 'wpumv2_upgrade_completed' );

			// Check if all tables are there.
			$tables = array(
				'fields'                => new WPUM_DB_Table_Fields(),
				'fieldmeta'             => new WPUM_DB_Table_Field_Meta(),
				'fieldsgroups'          => new WPUM_DB_Table_Fields_Groups(),
				'registrationforms'     => new WPUM_DB_Table_Registration_Forms(),
				'registrationformsmeta' => new WPUM_DB_Table_Registration_Forms_Meta(),
				'searchfields'          => new WPUM_DB_Table_Search_Fields(),
			);

			foreach ( $tables as $key => $table ) {
				if ( ! $table->exists() ) {
					$table->create();
				}
			}

			update_option( 'wpum_version', WPUM_VERSION );

			$this->upgrade_page_options();
			$this->check_primary_group();
			$this->migrate_fields_groups();
			$this->migrate_fields();
			$this->install_cover_field();
			$this->install_search_fields();
			$this->install_registration_form();
			$this->migrate_emails();
			$this->migrate_directories();

			update_option( 'v202_upgrade', true );

			$message = __( 'Database upgrade completed. <a href="' . admin_url() . '">Go back to your admin panel.</a>', 'wp-user-manager' );

			wp_die( $message, 'WPUM DB Update' );

		}

	}

}

new WPUM_Plugin_Updates;
