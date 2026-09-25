<?php
/**
 * Tests for the Image field type.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/419
 * @see https://github.com/WPUserManager/wp-user-manager/pull/441
 */

require_once __DIR__ . '/FieldsTestCase.php';

/**
 * Concrete form so the protected validate_fields() can be exercised.
 */
class WPUM_Image_Field_Test_Form extends WPUM_Form {

	/**
	 * @param array $fields
	 * @param array $values
	 *
	 * @return bool|WP_Error
	 */
	public function run_validation( $fields, $values ) {
		$this->fields = $fields;

		return $this->validate_fields( $values );
	}
}

class ImageFieldTest extends FieldsTestCase {

	/**
	 * Smallest valid PNG: 1x1 transparent pixel.
	 */
	const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

	/**
	 * @var string[] Temporary files to remove.
	 */
	private $tmp_files = array();

	public function _tearDown() {
		foreach ( $this->tmp_files as $file ) {
			if ( file_exists( $file ) ) {
				unlink( $file );
			}
		}
		$_FILES = array();

		parent::_tearDown();
	}

	/**
	 * Build a field config array as the forms pass it to get_posted_field().
	 *
	 * @param array $overrides
	 *
	 * @return array
	 */
	private function field_config( $overrides = array() ) {
		return array_merge(
			array(
				'label'              => 'Photo',
				'type'               => 'image',
				'template'           => 'image',
				'required'           => false,
				'allowed_mime_types' => '',
				'max_file_size'      => '',
			),
			$overrides
		);
	}

	/**
	 * Put a file into $_FILES for the given key.
	 *
	 * @param string $key
	 * @param string $name     Client file name.
	 * @param string $contents File contents.
	 * @param string $type     Client (browser supplied) mime type.
	 */
	private function fake_upload( $key, $name, $contents, $type ) {
		$tmp = wp_tempnam( $name );
		file_put_contents( $tmp, $contents );
		$this->tmp_files[] = $tmp;

		$_FILES[ $key ] = array(
			'name'     => $name,
			'type'     => $type,
			'tmp_name' => $tmp,
			'error'    => 0,
			'size'     => strlen( $contents ),
		);
	}

	/**
	 * Run a posted upload through the field and return the exception message, or '' if none.
	 *
	 * @param array $field
	 *
	 * @return string
	 */
	private function upload_error( $field ) {
		try {
			( new WPUM_Field_Image() )->get_posted_field( 'wpum_photo', $field );
		} catch ( Exception $e ) {
			return $e->getMessage();
		}

		return '';
	}

	// ─── Registration ───────────────────────────────────────────────

	public function test_image_field_type_is_registered_in_advanced_group() {
		$types = wpum_get_registered_field_types();

		$this->assertArrayHasKey( 'advanced', $types );
		$registered = wp_list_pluck( $types['advanced']['fields'], 'type' );
		$this->assertContains( 'image', $registered );
	}

	public function test_image_field_type_uses_image_template_and_file_settings() {
		$field_type = new WPUM_Field_Image();

		$this->assertSame( 'image', $field_type->type );
		$this->assertSame( 'image', $field_type->template() );
		$this->assertInstanceOf( 'WPUM_Field_File', $field_type );

		$settings = $field_type->get_editor_settings();
		$this->assertArrayHasKey( 'max_file_size', $settings['validation'] );
		$this->assertArrayHasKey( 'allowed_mime_types', $settings['validation'] );
	}

	// ─── Allowed mime types ─────────────────────────────────────────

	public function test_default_allowed_types_are_raster_images_only() {
		$types = ( new WPUM_Field_Image() )->get_allowed_upload_mime_types( $this->field_config() );

		$this->assertNotEmpty( $types );
		foreach ( $types as $mime ) {
			$this->assertStringStartsWith( 'image/', $mime );
		}
		$this->assertContains( 'image/jpeg', $types );
		$this->assertContains( 'image/png', $types );
		$this->assertNotContains( 'application/pdf', $types );
	}

	public function test_non_image_types_in_field_setting_are_dropped() {
		$types = ( new WPUM_Field_Image() )->get_allowed_upload_mime_types( $this->field_config( array( 'allowed_mime_types' => 'png, pdf, docx' ) ) );

		$this->assertSame( array( 'png' => 'image/png' ), $types );
	}

	public function test_svg_is_never_allowed_even_when_the_site_allows_it() {
		$allow_svg = function ( $mimes ) {
			$mimes['svg'] = 'image/svg+xml';
			return $mimes;
		};
		add_filter( 'upload_mimes', $allow_svg );

		$types = ( new WPUM_Field_Image() )->get_allowed_upload_mime_types( $this->field_config( array( 'allowed_mime_types' => 'svg,png' ) ) );

		remove_filter( 'upload_mimes', $allow_svg );

		$this->assertNotContains( 'image/svg+xml', $types );
		$this->assertContains( 'image/png', $types );
	}

	// ─── Upload validation ──────────────────────────────────────────

	public function test_pdf_is_rejected_by_default() {
		$this->fake_upload( 'wpum_photo', 'cv.pdf', "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF", 'application/pdf' );

		$this->assertStringContainsString( 'needs to be one of the following file types', $this->upload_error( $this->field_config() ) );
	}

	public function test_non_image_with_spoofed_browser_mime_type_is_rejected() {
		// The browser-supplied type claims PNG, but the file is a PDF. The check must use
		// the type WordPress detects, not $_FILES['type'].
		$this->fake_upload( 'wpum_photo', 'cv.pdf', "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF", 'image/png' );

		$this->assertStringContainsString( 'needs to be one of the following file types', $this->upload_error( $this->field_config() ) );
	}

	public function test_file_over_max_size_is_rejected() {
		$this->fake_upload( 'wpum_photo', 'pixel.png', base64_decode( self::PNG_1X1 ), 'image/png' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

		$this->assertStringContainsString( 'too big', $this->upload_error( $this->field_config( array( 'max_file_size' => '10' ) ) ) );
	}

	public function test_allowed_image_passes_wpum_validation() {
		$this->fake_upload( 'wpum_photo', 'pixel.png', base64_decode( self::PNG_1X1 ), 'image/png' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

		$error = $this->upload_error( $this->field_config( array( 'max_file_size' => '100000' ) ) );

		// A file that is not a real HTTP upload always fails WordPress's own
		// is_uploaded_file() test inside wp_handle_upload(). Reaching that test means
		// WPUM's type and size checks accepted the image.
		$this->assertStringNotContainsString( 'needs to be one of the following file types', $error );
		$this->assertStringNotContainsString( 'too big', $error );
		$this->assertStringContainsString( 'failed upload test', $error );
	}

	public function test_no_upload_falls_back_to_current_value() {
		$_POST['current_wpum_photo'] = 'http://example.org/wp-content/uploads/photo.png';

		$value = ( new WPUM_Field_Image() )->get_posted_field( 'wpum_photo', $this->field_config() );

		$this->assertSame( 'http://example.org/wp-content/uploads/photo.png', $value );
	}

	// ─── Form validation of posted current_ values ──────────────────

	public function test_form_rejects_non_image_current_url_for_image_field() {
		remove_all_filters( 'submit_wpum_form_validate_fields' );
		$form   = new WPUM_Image_Field_Test_Form();
		$fields = array( 'account' => array( 'wpum_photo' => $this->field_config() ) );

		$result = $form->run_validation( $fields, array( 'account' => array( 'wpum_photo' => 'http://example.org/evil.php' ) ) );

		$this->assertWPError( $result );
		$this->assertStringContainsString( 'jpg, jpeg, png, gif, webp', $result->get_error_message() );
	}

	public function test_form_accepts_image_current_url_for_image_field() {
		remove_all_filters( 'submit_wpum_form_validate_fields' );
		$form   = new WPUM_Image_Field_Test_Form();
		$fields = array( 'account' => array( 'wpum_photo' => $this->field_config() ) );

		$result = $form->run_validation( $fields, array( 'account' => array( 'wpum_photo' => 'http://example.org/photo.webp' ) ) );

		$this->assertTrue( $result );
	}

	// ─── Output ─────────────────────────────────────────────────────

	public function test_formatted_output_renders_img_tag() {
		$output = ( new WPUM_Field_Image() )->get_formatted_output( null, 'http://example.org/photo.png' );

		$this->assertStringContainsString( '<img src="http://example.org/photo.png"', $output );
	}

	public function test_formatted_output_escapes_url() {
		$output = ( new WPUM_Field_Image() )->get_formatted_output( null, 'http://example.org/x.png" onerror="alert(1)' );

		$this->assertStringNotContainsString( 'onerror="', $output );
	}

	public function test_formatted_output_drops_javascript_url() {
		$output = ( new WPUM_Field_Image() )->get_formatted_output( null, 'javascript:alert(1)' );

		$this->assertSame( '', $output );
	}

	public function test_formatted_output_is_empty_for_empty_values() {
		$field_type = new WPUM_Field_Image();

		$this->assertSame( '', $field_type->get_formatted_output( null, '' ) );
		$this->assertSame( '', $field_type->get_formatted_output( null, false ) );
		$this->assertSame( '', $field_type->get_formatted_output( null, array() ) );
		$this->assertSame( '', $field_type->get_formatted_output( null, 999999 ) );
	}

	public function test_formatted_output_accepts_uploaded_file_array() {
		$output = ( new WPUM_Field_Image() )->get_formatted_output( null, array(
			'url'  => 'http://example.org/photo.png',
			'path' => '/tmp/photo.png',
		) );

		$this->assertStringContainsString( 'src="http://example.org/photo.png"', $output );
	}

	public function test_formatted_output_accepts_attachment_id() {
		$attachment_id = $this->factory()->attachment->create_object( array(
			'file'           => 'photo.png',
			'post_mime_type' => 'image/png',
		) );

		$output = ( new WPUM_Field_Image() )->get_formatted_output( null, $attachment_id );

		$this->assertStringContainsString( 'photo.png', $output );
	}

	// ─── Template and assets ────────────────────────────────────────

	/**
	 * @param array $data
	 *
	 * @return string
	 */
	private function render_template( $data ) {
		ob_start();
		WPUM()->templates->set_template_data( array_merge( array(
			'key'                => 'wpum_photo',
			'name'               => 'wpum_photo',
			'value'              => '',
			'allowed_mime_types' => '',
			'max_file_size'      => '',
		), $data ) )->get_template_part( 'form-fields/image', 'field' );

		return ob_get_clean();
	}

	public function test_template_only_accepts_image_mime_types() {
		$html = $this->render_template( array( 'allowed_mime_types' => 'png,pdf' ) );

		$this->assertMatchesRegularExpression( '/data-file_types="image\/png"/', $html );
		$this->assertStringNotContainsString( 'application/pdf', $html );
	}

	public function test_template_passes_max_file_size_in_bytes() {
		$html = $this->render_template( array( 'max_file_size' => '2048' ) );

		$this->assertStringContainsString( 'data-file_size="2048"', $html );
	}

	public function test_template_outputs_current_image_input_only_when_set() {
		$this->assertStringNotContainsString( 'current_wpum_photo', $this->render_template( array() ) );

		$html = $this->render_template( array( 'value' => 'http://example.org/photo.png' ) );
		$this->assertStringContainsString( 'name="current_wpum_photo" value="http://example.org/photo.png"', $html );
	}

	public function test_filepond_assets_load_only_when_image_field_renders() {
		$GLOBALS['wp_scripts'] = null;
		$GLOBALS['wp_styles']  = null;

		wpum_load_scripts();

		$this->assertTrue( wp_script_is( 'wpum-filepond-init', 'registered' ) );
		$this->assertFalse( wp_script_is( 'wpum-filepond-init', 'enqueued' ) );

		$this->render_template( array() );

		$this->assertTrue( wp_script_is( 'wpum-filepond-init', 'enqueued' ) );
		$this->assertTrue( wp_style_is( 'wpum-filepond', 'enqueued' ) );
	}

	public function test_filepond_bundle_keeps_mit_licence_headers() {
		$bundle = file_get_contents( WPUM_PLUGIN_DIR . 'assets/js/vendor/filepond-bundle.js' );

		foreach ( array( 'FilePond ', 'FilePondPluginFileValidateType', 'FilePondPluginFileValidateSize', 'FilePondPluginImagePreview', 'jquery-filepond' ) as $package ) {
			$this->assertMatchesRegularExpression( '/' . preg_quote( $package, '/' ) . '.*\n \* Licensed under MIT/', $bundle, "{$package} licence header missing from the bundle." );
		}
	}
}
