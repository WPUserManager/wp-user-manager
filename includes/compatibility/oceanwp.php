<?php

/**
 * Ensure the email customizer works when using the OceanWP theme
 */
add_action( 'after_setup_theme', function () {
	if ( ! class_exists( 'OCEANWP_Theme_Class' ) ) {
		return;
	}

	if ( isset( $_GET['wpum_email_customizer'] ) ) {
		class OceanWP_Customizer {
		}

		class OceanWP_Customizer_Slider_Control extends \WP_Customize_Control {
		}
	}
}, 3 );
