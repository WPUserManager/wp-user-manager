<?php

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

/**
 * A radio group only pre-selects an option when it has a default or a value.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/153
 */
class RadioFieldDefaultTest extends WPUMTestCase {

	public function _tearDown() {
		remove_all_filters( 'wpum_radio_field_select_first_option' );
		parent::_tearDown();
	}

	/**
	 * Render the radio field template.
	 *
	 * @param array $args Field data.
	 *
	 * @return string
	 */
	private function render( $args = array() ) {
		$data = (object) array_merge( array(
			'key'     => 'favourite',
			'options' => array(
				'red'   => 'Red',
				'green' => 'Green',
				'blue'  => 'Blue',
			),
			'default' => '',
			'value'   => '',
		), $args );

		ob_start();
		WPUM()->templates->set_template_data( $data )->get_template_part( 'form-fields/radio', 'field' );

		return ob_get_clean();
	}

	private function checked_values( $html ) {
		preg_match_all( '/value="([^"]*)"\s+checked=/', $html, $matches );

		return $matches[1];
	}

	public function test_nothing_is_selected_without_default_or_value() {
		$this->assertSame( array(), $this->checked_values( $this->render() ) );
	}

	public function test_default_is_selected() {
		$this->assertSame( array( 'green' ), $this->checked_values( $this->render( array( 'default' => 'green' ) ) ) );
	}

	public function test_saved_value_wins_over_default() {
		$this->assertSame( array( 'blue' ), $this->checked_values( $this->render( array( 'default' => 'green', 'value' => 'blue' ) ) ) );
	}

	public function test_filter_restores_first_option_selection() {
		add_filter( 'wpum_radio_field_select_first_option', '__return_true' );

		$this->assertSame( array( 'red' ), $this->checked_values( $this->render() ) );
	}
}
