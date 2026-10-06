<?php
/**
 * Immutable autocomplete payloads at Formidable's native render boundary.
 *
 * @package FormidableGeolocationAutocomplete\Features\Form\Runtime
 * @since 1.0.0
 */

namespace FormidableGeolocationAutocomplete\Features\Form\Runtime;
use FormidableGeolocationAutocomplete\Admin\Settings;
use FormidableGeolocationAutocomplete\Features\Form\Fields\Address\Settings as FieldSettings;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Export supported fields only; never export premium credentials or options.
 */
final class FormGeoProcessor {
	/**
	 * Rendered form configurations.
	 *
	 * @var array Rendered form configurations.
	 */
	private static $forms = array();
	/**
	 * Register render hook.
	 */
	public function __construct() {
		add_filter( 'frm_fields_in_form', array( $this, 'collect' ), 20, 2 );
	}
	/**
	 * Configure collect.
	 *
	 * @param array $fields Native field data.
	 * @param array $args Native form arguments.
	 * @return array
	 */
	public function collect( $fields, $args ) {
		$addresses = array();
		foreach ( (array) $fields as $field ) {
			$field   = (array) $field;
			$options = isset( $field['field_options'] ) && is_array( $field['field_options'] ) ? array_replace( $field['field_options'], $field ) : $field;
			$type    = $options['original_type'] ?? $options['type'] ?? '';
			if ( ! in_array( $type, array( 'frmgeo_address', 'address' ), true ) || ! in_array( $options['frmgeo_enable_address_autocomplete'] ?? ( 'frmgeo_address' === $type ? 1 : 0 ), array( true, 1, 1.0, '1', 'true' ), true ) ) {
				continue;
			}
			$supported                                       = array_intersect_key( $options, FieldSettings::defaults() );
			$supported['id']                                 = (string) ( $field['id'] ?? '' );
			$supported['type']                               = $type;
			$supported['frmgeo_enable_address_autocomplete'] = 1;
			$addresses[]                                     = $supported;
		}
		if ( ! $addresses ) {
			return $fields;
		}
		$form    = $args['form'] ?? null;
		$form_id = is_object( $form ) ? $form->id : ( $args['form_id'] ?? $addresses[0]['form_id'] ?? 0 );
		if ( ! $form_id ) {
			return $fields;
		}
		self::$forms[ (string) $form_id ] = array(
			'formId' => (string) $form_id,
			'fields' => $addresses,
			'config' => Settings::get_config(),
		);
		wp_enqueue_script( 'frmgeoac-autocomplete', FRMGEOAC_URL . 'build/js/frontend/address-autocomplete.min.js', array( 'jquery' ), FRMGEOAC_VERSION, true );
		wp_enqueue_style( 'frmgeoac-autocomplete', FRMGEOAC_URL . 'assets/css/address-autocomplete.css', array(), FRMGEOAC_VERSION );
		wp_add_inline_script( 'frmgeoac-autocomplete', 'window.frmgeoAutocompleteForms = Object.assign(window.frmgeoAutocompleteForms || {}, ' . wp_json_encode( self::$forms, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ');', 'before' );
		return $fields;
	}
}
