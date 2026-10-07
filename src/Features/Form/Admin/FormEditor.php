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
 *
 * @since 1.0.0
 */
final class FormEditor {

	/**
	 * Register field defaults, options, promotions and scoped assets.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_filter( 'frm_default_field_options', [ $this, 'defaults' ], 20, 2 );
		add_action( 'frm_before_field_options', [ $this, 'options' ], 20 );
		add_action( 'frm_extra_form_instructions', [ $this, 'field_group' ] );
		add_filter( 'frm_pro_available_fields', [ $this, 'available_fields' ], 20 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
	}

	/**
	 * Merge autocomplete defaults without replacing saved native field options.
	 *
	 * @param array $options Native options.
	 * @param array $args Field type.
	 * @return array
	 * @since 1.0.0
	 */
	public function defaults( $options, $args ) {
		if ( ! in_array( $args['type'] ?? '', [ 'frmgeo_address', 'address' ], true ) ) {
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
	 * @since 1.0.0
	 * @return void
	 */
	public function options( $field ) {
		if ( ! in_array( $field['type'] ?? '', [ 'frmgeo_address', 'address' ], true ) ) {
			return;
		}
		$id = (int) $field['id'];
		echo '<h3 class="frm-collapsed">' . esc_html__( 'Address Autocomplete', 'address-autocomplete-for-formidable-forms' ) . '<i class="frm_icon_font frm_arrowdown6_icon"></i></h3>';
		echo '<div class="frm_grid_container frm-collapse-me frmgeoac-options">';
		foreach ( Settings::get_settings() as $key => $definition ) {
			$value = $field[ $key ] ?? $definition['value'];
			if ( 'address' === $field['type'] && 'frmgeo_enable_address_autocomplete' === $key && ! isset( $field[ $key ] ) ) {
				$value = 0;
			}
			$name     = 'field_options[' . $key . '_' . $id . ']';
			$input_id = $key . '_' . $id;
			echo '<p class="frm6 frm_form_field frmgeoac-option" data-option="' . esc_attr( $key ) . '">';
			if ( 'toggle' !== $definition['type'] ) {
				echo '<label for="' . esc_attr( $input_id ) . '">' . esc_html( $definition['label'] );
				self::render_tooltip( $definition['tooltip'] );
				echo '</label>';
			}
			if ( 'toggle' === $definition['type'] ) {
				// Formidable's compact parser keeps the first value for duplicate names.
				// Put the fallback last so checked and unchecked values both persist.
				echo '<input type="checkbox" id="' . esc_attr( $input_id ) . '" name="' . esc_attr( $name ) . '" value="1" ' . checked( in_array( $value, [ 1, 1.0, '1', true, 'true' ], true ), true, false ) . '> <input type="hidden" name="' . esc_attr( $name ) . '" value="0"> <label class="frmgeoac-checkbox-label" for="' . esc_attr( $input_id ) . '">' . esc_html( $definition['label'] );
				self::render_tooltip( $definition['tooltip'] );
				echo '</label>';
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
		echo '</div>';
	}

	/**
	 * Render the host's help icon with keyboard access and an accessible explanation.
	 *
	 * @param string $text Plain-text tooltip content.
	 * @since 1.0.0
	 * @return void
	 */
	private static function render_tooltip( $text ) {
		\FrmAppHelper::tooltip_icon(
			$text,
			[
				'class'      => 'frmgeoac-tooltip',
				'tabindex'   => '0',
				'aria-label' => $text,
			]
		);
	}

	/**
	 * Use the same native unavailable-field definitions as premium, not runtime fields.
	 *
	 * @param array $fields Native field palette.
	 * @return array Updated palette.
	 * @since 1.0.0
	 */
	public function available_fields( $fields ) {
		$definitions = [
			'frmgeo_map'                => [ __( 'Map', 'address-autocomplete-for-formidable-forms' ), 'location' ],
			'frmgeo_directions'         => [ __( 'Directions', 'address-autocomplete-for-formidable-forms' ), 'flag' ],
			'frmgeo_distance'           => [ __( 'Distance & Duration', 'address-autocomplete-for-formidable-forms' ), 'clock' ],
			'frmgeo_address_validation' => [ __( 'Address Validation', 'address-autocomplete-for-formidable-forms' ), 'saved' ],
		];
		foreach ( $definitions as $type => $definition ) {
			$fields[ $type ] = [
				'name'       => $definition[0],
				'icon'       => 'dashicons dashicons-' . $definition[1] . ' frm_show_upgrade',
				'message'    => sprintf(
					/* translators: %1$s: Feature name, %2$s: Opening link, %3$s: Closing link. */
					esc_html__( '%1$s is available with Formidable Geolocation. %2$sExplore features%3$s to learn more.', 'address-autocomplete-for-formidable-forms' ),
					esc_html( $definition[0] ),
					'<a href="' . esc_url( Dashboard::get_url() ) . '">',
					'</a>'
				),
				'link'       => FRMGEOAC_SITE_URL . '/pricing/',
				'require'    => 'Formidable Geolocation',
				'learn-more' => 'frmgeo_hide',
			];
		}
		return $fields;
	}

	/**
	 * Preserve premium's Geolocation Fields group placement and native card markup.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function field_group() {
		echo '<div id="frmgeo-geolocation-fields-holder" style="display:none"><h3 class="frm-with-line"><span>' . esc_html__( 'Geolocation Fields', 'address-autocomplete-for-formidable-forms' ) . '</span></h3><ul class="field_type_list frm_grid_container frmgeo-fields-container"></ul></div>';
	}

	/**
	 * Load only on Formidable's admin screens.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) && is_string( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( ! in_array( $page, [ 'formidable', 'formidable-settings' ], true ) ) {
			return;
		}
		wp_enqueue_script( 'frmgeoac-form-editor', FRMGEOAC_URL . 'build/js/admin/frmgeo-form-editor.min.js', [ 'jquery' ], FRMGEOAC_VERSION, true );
		wp_enqueue_style( 'frmgeoac-choices', FRMGEOAC_URL . 'assets/css/choices.css', [], FRMGEOAC_VERSION );
		wp_enqueue_style( 'frmgeoac-form-editor', FRMGEOAC_URL . 'build/css/admin/frmgeo-admin.min.css', [ 'frmgeoac-choices' ], FRMGEOAC_VERSION );
		wp_localize_script(
			'frmgeoac-form-editor',
			'frmgeoacEditor',
			[
				'allCountries' => __( 'All countries', 'address-autocomplete-for-formidable-forms' ),
				'allTypes'     => __( 'All place types', 'address-autocomplete-for-formidable-forms' ),
				'search'       => __( 'Search', 'address-autocomplete-for-formidable-forms' ),
			]
		);
	}
}
