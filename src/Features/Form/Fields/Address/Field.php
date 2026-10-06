<?php
/**
 * A plain single-line Address field using Formidable's native text lifecycle.
 *
 * @package FormidableGeolocationAutocomplete\Features\Form\Fields\Address
 * @since 1.0.0
 */

namespace FormidableGeolocationAutocomplete\Features\Form\Fields\Address;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keep the premium saved type without loading its geolocation field engine.
 */
class Field extends \FrmFieldText {
	/**
	 * Saved field identity.
	 *
	 * @var string Saved field identity.
	 */
	protected $type = 'frmgeo_address';

	/**
	 * Valid browser input type independent of the saved field type.
	 *
	 * @return string Valid browser input type independent of the saved field type.
	 */
	protected function html5_input_type() {
		return 'text';
	}

	/**
	 * Autocomplete defaults alongside native text options.
	 *
	 * @return array Autocomplete defaults alongside native text options.
	 */
	protected function extra_field_opts() {
		return array_merge( parent::extra_field_opts(), Settings::defaults() );
	}

	/**
	 * Configure get value to save.
	 *
	 * @param mixed $value Submitted value.
	 * @param array $atts Host arguments.
	 * @return string
	 */
	public function get_value_to_save( $value, $atts ) {
		return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
	}

	/**
	 * Add a map link only to the administrator's entry detail display.
	 *
	 * @param mixed $value Stored address.
	 * @param array $atts Display options.
	 * @return string Escaped address, optionally with a map link.
	 */
	public function prepare_display_value( $value, $atts = array() ) {
		$value   = is_scalar( $value ) ? (string) $value : '';
		$display = esc_html( $value );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = isset( $_GET['frm_action'] ) && is_string( $_GET['frm_action'] ) ? sanitize_key( wp_unslash( $_GET['frm_action'] ) ) : '';
		if ( '' !== $value && is_admin() && current_user_can( 'frm_view_entries' ) && 'show' === $action ) {
			$url      = add_query_arg(
				array(
					'api'   => 1,
					'query' => $value,
				),
				'https://www.google.com/maps/search/'
			);
			$display .= ' — <a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Open Map', 'address-autocomplete-for-formidable-forms' ) . '</a>';
		}
		return $display;
	}
}
