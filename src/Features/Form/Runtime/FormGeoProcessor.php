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
 *
 * @since 1.0.0
 */
final class FormGeoProcessor {

	/**
	 * Rendered form configurations.
	 *
	 * @var array Rendered form configurations.
	 *
	 * @since 1.0.0
	 */
	private static $forms = [];
	/**
	 * Register render hook.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_filter( 'frm_fields_in_form', [ $this, 'collect' ], 20, 2 );
	}

	/**
	 * Collect enabled custom and native Address fields into a browser-safe payload.
	 *
	 * @param array $fields Native field data.
	 * @param array $args Native form arguments.
	 * @return array
	 * @since 1.0.0
	 */
	public function collect( $fields, $args ) {
		$form       = $args['form'] ?? null;
		$form_id    = is_object( $form ) ? $form->id : ( $args['form_id'] ?? 0 );
		$candidates = [];
		if ( $form_id && class_exists( '\\FrmField' ) ) {
			// Later AJAX pages and repeater children are not rendered initially.
			// Seed their supported options now; rendered field filters still win.
			$seen       = [];
			$candidates = $this->saved_fields( $form_id, $seen );
		}
		foreach ( (array) $fields as $field ) {
			$data = (array) $field;
			$candidates[ (string) ( $data['id'] ?? '' ) ] = $data;
		}
		$addresses = [];
		foreach ( $candidates as $field ) {
			$field   = (array) $field;
			$options = isset( $field['field_options'] ) && is_array( $field['field_options'] ) ? array_replace( $field['field_options'], $field ) : $field;
			$type    = $options['original_type'] ?? $options['type'] ?? '';
			if ( ! in_array( $type, [ 'frmgeo_address', 'address' ], true ) || ! in_array( $options['frmgeo_enable_address_autocomplete'] ?? ( 'frmgeo_address' === $type ? 1 : 0 ), [ true, 1, 1.0, '1', 'true' ], true ) ) {
				continue;
			}
			$supported                                       = array_intersect_key( $options, FieldSettings::defaults() );
			$supported['id']                                 = (string) ( $field['id'] ?? '' );
			$supported['type']                               = $type;
			$supported['frmgeo_enable_address_autocomplete'] = 1;
			$addresses[]                                     = $supported;
		}
		if ( ! $addresses ) {
			if ( $form_id && isset( self::$forms[ (string) $form_id ] ) ) {
				self::$forms[ (string) $form_id ]['fields'] = [];
				wp_add_inline_script( 'frmgeoac-autocomplete', 'window.frmgeoAutocompleteForms = Object.assign(window.frmgeoAutocompleteForms || {}, ' . wp_json_encode( self::$forms, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ');', 'before' );
			}
			return $fields;
		}
		$form_id = $form_id ? $form_id : ( $addresses[0]['form_id'] ?? 0 );
		if ( ! $form_id ) {
			return $fields;
		}
		self::$forms[ (string) $form_id ] = [
			'formId' => (string) $form_id,
			'fields' => $addresses,
			'config' => Settings::get_config(),
		];
		wp_enqueue_script( 'frmgeoac-autocomplete', FRMGEOAC_URL . 'build/js/frontend/address-autocomplete.min.js', [ 'jquery' ], FRMGEOAC_VERSION, true );
		wp_enqueue_style( 'frmgeoac-autocomplete', FRMGEOAC_URL . 'assets/css/address-autocomplete.css', [], FRMGEOAC_VERSION );
		wp_add_inline_script( 'frmgeoac-autocomplete', 'window.frmgeoAutocompleteForms = Object.assign(window.frmgeoAutocompleteForms || {}, ' . wp_json_encode( self::$forms, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ');', 'before' );
		return $fields;
	}

	/**
	 * Collect saved page and nested-form fields with cycle protection.
	 *
	 * @param int   $form_id Native form ID.
	 * @param array $seen Visited form IDs.
	 * @return array Fields indexed by their stable native IDs.
	 * @since 1.0.0
	 */
	private function saved_fields( $form_id, &$seen ) {
		$form_id = (int) $form_id;
		if ( ! $form_id || isset( $seen[ $form_id ] ) ) {
			return [];
		}
		$seen[ $form_id ] = true;
		$fields           = [];
		foreach ( \FrmField::getAll( [ 'fi.form_id' => $form_id ] ) as $field ) {
			$field                           = (array) $field;
			$fields[ (string) $field['id'] ] = $field;
			$options                         = $field['field_options'] ?? [];
			if ( in_array( $field['type'] ?? '', [ 'divider', 'form' ], true ) && ! empty( $options['form_select'] ) ) {
				$fields += $this->saved_fields( $options['form_select'], $seen );
			}
		}
		return $fields;
	}
}
