<?php
/**
 * Single profile custom field element.
 * Displays a user custom field within elementor.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/
namespace Elementor;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPUM_Elementor_Single_Profile_Custom_Field extends Widget_Base {

	/**
	 * Retrieve widget name.
	 *
	 * @access public
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'wpum-single-profile-custom-field';
	}

	/**
	 * Retrieve widget title.
	 *
	 * @access public
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'Profile Custom Field' );
	}

	/**
	 * Retrieve the list of categories thewidget belongs to.
	 * Used to determine where to display the widget in the editor.
	 *
	 * @access public
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return [ 'wp-user-manager' ];
	}

	/**
	 * Get widget icon.
	 * Retrieve widget icon.
	 *
	 * @access public
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'wpum-logo-font-icon';
	}

	/**
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @access protected
	 */
	protected function _register_controls() {

		$this->start_controls_section(
			'custom_field_settings',
			[
				'label' => esc_html__( 'Custom field settings' ),
			]
		);

		$this->add_control(
			'custom_field',
			[
				'label'       => esc_html__( 'Select custom field' ),
				'label_block' => true,
				'description' => sprintf( __( 'Select the custom field you wish to display. More custom fields can be added through the <a href="%s" target="_blank">custom fields addon.</a>' ), 'https://wpusermanager.com/addons/custom-fields/' ),
				'type'        => Controls_Manager::SELECT2,
				'options'     => $this->get_registered_fields(),
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
            'section_style',
            [
                'label' => __( 'Custom field display', 'power-pack' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

		$this->add_control(
			'show_label',
			[
				'label' => esc_html__( 'Show custom field label', 'power-pack' ),
				'type'  => Controls_Manager::SWITCHER,
			]
		);

		$this->add_control(
			'show_inline',
			[
				'label'   => esc_html__( 'Display value on the same line', 'power-pack' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes'
			]
		);

		$this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'           => 'custom_field_label_typography',
                'label'          => esc_html__( 'Label typography' ),
				'selector'       => '{{WRAPPER}} .wpum-custom-field > p',
				'condition'      => [
                    'show_label' => 'yes',
                ],
            ]
		);

		$this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'           => 'custom_field_value_typography',
                'label'          => esc_html__( 'Value typography' ),
				'selector'       => '{{WRAPPER}} .wpum-custom-field .wpum-custom-field-value',
            ]
		);

		$this->add_control(
			'custom_field_value_color',
			[
				'label' => esc_html__( 'Value Color' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wpum-custom-field .wpum-custom-field-value' => 'color: {{VALUE}}',
				],
			]
		);

		$this->end_controls_section();

	}

	/**
	 * Retrieve a list of registered fields.
	 *
	 * @return array
	 */
	private function get_registered_fields() {

		$fields = [];

		$registered_fields = WPUM()->fields->get_fields(
			[
				'orderby' => 'fields_order',
				'order'   => 'ASC',
			]
		);

		$non_allowed_fields = [
			'user_avatar',
			'user_cover',
			'user_nickname',
			'user_displayname',
			'user_password',
		];

		if ( is_array( $registered_fields ) && ! empty( $registered_fields ) ) {
			foreach ( $registered_fields as $field ) {
				if ( $field->exists() ) {
					if ( ! empty( $field->get_primary_id() ) && in_array( $field->get_primary_id(), $non_allowed_fields ) ) {
						continue;
					}
					$fields[ $field->get_ID() ] = $field->get_name();
				}
			}
		}

		return $fields;

	}

	/**
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @access protected
	 */
	protected function render() {

		$settings = $this->get_settings_for_display();
		$user_id  = wpum_get_queried_user_id();
		$user     = get_user_by( 'id', $user_id );
		$field    = new \WPUM_Field( $settings['custom_field'] );
		$field->set_user_meta( $user_id );

		WPUM()->templates
			->set_template_data(
				[
					'user'            => $user,
					'current_user_id' => get_current_user_id(),
					'field'           => $field,
					'label'           => $settings['show_label'],
					'inline'          => $settings['show_inline']
				]
			)
			->get_template_part( 'elementor/custom-field' );

	}

}

Plugin::instance()->widgets_manager->register_widget_type( new WPUM_Elementor_Single_Profile_Custom_Field() );
