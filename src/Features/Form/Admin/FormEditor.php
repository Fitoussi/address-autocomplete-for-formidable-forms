<?php
/**
 * Supported Address options and four presentation-only premium buttons.
 *
 * @package FormidableGeolocationAutocomplete\Features\Form\Admin
 * @since 1.0.0
 */

namespace FormidableGeolocationAutocomplete\Features\Form\Admin;
use FormidableGeolocationAutocomplete\Features\Form\Fields\Address\Settings;
use FormidableGeolocationAutocomplete\Admin\Dashboard;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Native builder integration, with no unsupported field registration.
 */
final class FormEditor {
	/**
	 * Register field defaults, options, promotions and scoped assets.
	 */
	public function __construct() {
		add_filter( 'frm_default_field_options', array( $this, 'defaults' ), 20, 2 );
		add_action( 'frm_before_field_options', array( $this, 'options' ), 20 );
		add_action( 'frm_extra_form_instructions', array( $this, 'promotions' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}
	/**
	 * Configure defaults.
	 *
	 * @param array $options Native options.
	 * @param array $args Field type.
	 * @return array
	 */
	public function defaults( $options, $args ) {
		if ( ! in_array( $args['type'] ?? '', array( 'frmgeo_address', 'address' ), true ) ) {
			return $options;
		}
		$defaults = Settings::defaults();
		if ( 'address' === $args['type'] ) {
			$defaults['frmgeo_enable_address_autocomplete'] = 0;
		}
		return array_merge( $defaults, $options );
	}
	/**
	 * Render inputs using Formidable's persisted field_options[slug_ID] contract.
	 *
	 * @param array $field Native editor data.
	 */
	public function options( $field ) {
		if ( ! in_array( $field['type'] ?? '', array( 'frmgeo_address', 'address' ), true ) ) {
			return;
		}
		$id = (int) $field['id'];
		echo '<details class="frmgeoac-options"><summary>' . esc_html__( 'Autocomplete', 'address-autocomplete-for-formidable-forms' ) . '</summary>';
		foreach ( Settings::get_settings() as $key => $definition ) {
			$value = $field[ $key ] ?? $definition['value'];
			if ( 'address' === $field['type'] && 'frmgeo_enable_address_autocomplete' === $key && ! isset( $field[ $key ] ) ) {
				$value = 0;
			}
			$name     = 'field_options[' . $key . '_' . $id . ']';
			$input_id = $key . '_' . $id;
			echo '<p class="frmgeoac-option" data-option="' . esc_attr( $key ) . '"><label for="' . esc_attr( $input_id ) . '">' . esc_html( $definition['label'] ) . '</label>';
			if ( 'toggle' === $definition['type'] ) {
				echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="0"><input type="checkbox" id="' . esc_attr( $input_id ) . '" name="' . esc_attr( $name ) . '" value="1" ' . checked( in_array( $value, array( 1, 1.0, '1', true, 'true' ), true ), true, false ) . '>';
			} elseif ( isset( $definition['options'] ) ) {
				$multiple = is_array( $definition['value'] );
				if ( $multiple ) {
					echo '<input type="hidden" name="' . esc_attr( $name . '[]' ) . '" value="">';
				}
				echo '<select id="' . esc_attr( $input_id ) . '" name="' . esc_attr( $name . ( $multiple ? '[]' : '' ) ) . '" ' . ( $multiple ? 'multiple class="frmgeoac-multiple"' : '' ) . '>';
				foreach ( $definition['options'] as $option ) {
					$is_selected = $multiple ? in_array( $option['value'], (array) $value, true ) : (string) $value === (string) $option['value'];
					echo '<option value="' . esc_attr( $option['value'] ) . '" ' . selected( $is_selected, true, false ) . '>' . esc_html( $option['label'] ) . '</option>';
				}
				echo '</select>';
			} else {
				echo '<input type="text" id="' . esc_attr( $input_id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( is_scalar( $value ) ? (string) $value : '' ) . '">';
			}
			echo '</p>';
		}
		echo '</details>';
	}
	/**
	 * Output links, not draggable field types or saved placeholders.
	 */
	public function promotions() {
		echo '<div class="frmgeoac-promotions"><h3>' . esc_html__( 'More location tools', 'address-autocomplete-for-formidable-forms' ) . '</h3>';
		foreach ( array( __( 'Map', 'address-autocomplete-for-formidable-forms' ), __( 'Directions', 'address-autocomplete-for-formidable-forms' ), __( 'Distance & Duration', 'address-autocomplete-for-formidable-forms' ), __( 'Address Validation', 'address-autocomplete-for-formidable-forms' ) ) as $label ) {
			echo '<a draggable="false" href="' . esc_url( Dashboard::get_url() ) . '" title="' . esc_attr__( 'Available with Formidable Geolocation', 'address-autocomplete-for-formidable-forms' ) . '"><span class="dashicons dashicons-lock" aria-hidden="true"></span>' . esc_html( $label ) . '</a>';
		}
		echo '</div>';
	}
	/**
	 * Load only on Formidable's admin screens.
	 */
	public function enqueue() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) && is_string( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( 'formidable' !== $page ) {
			return;
		}
		wp_enqueue_script( 'frmgeoac-form-editor', FRMGEOAC_URL . 'build/js/admin/frmgeo-form-editor.min.js', array( 'jquery' ), FRMGEOAC_VERSION, true );
		wp_enqueue_style( 'frmgeoac-form-editor', FRMGEOAC_URL . 'build/css/admin/frmgeo-admin.min.css', array(), FRMGEOAC_VERSION );
	}
}
