<?php
/**
 * Autocomplete-only schema; saved frmgeo names match premium.
 *
 * @package FormidableGeolocationAutocomplete\Features\Form\Fields\Address
 * @since 1.0.0
 */

namespace FormidableGeolocationAutocomplete\Features\Form\Fields\Address;
use FormidableGeolocationAutocomplete\Helpers\ReferenceData;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keep supported settings and defaults explicit.
 */
final class Settings {
	/**
	 * New-field defaults and supported option names.
	 *
	 * @return array<string,mixed> New-field defaults and supported option names.
	 */
	public static function defaults() {
		return array(
			'frmgeo_enable_address_autocomplete'        => 1,
			'frmgeo_force_suggested_address'            => 0,
			'frmgeo_force_suggested_address_message'    => __( 'Please select an address from the suggested results.', 'address-autocomplete-for-formidable-forms' ),
			'frmgeo_address_autocomplete_types'         => array(),
			'frmgeo_address_autocomplete_country'       => array(),
			'frmgeo_address_autocomplete_language'      => '',
			'frmgeo_autocomplete_restriction_usage'     => '',
			'frmgeo_autocomplete_proximity_lat'         => '',
			'frmgeo_autocomplete_proximity_lng'         => '',
			'frmgeo_autocomplete_proximity_radius'      => '',
			'frmgeo_autocomplete_bounds_sw_point'       => '',
			'frmgeo_autocomplete_bounds_ne_point'       => '',
			'frmgeo_address_autocomplete_strict_bounds' => 0,
		);
	}

	/**
	 * Native field-setting definitions.
	 *
	 * @return array<string,array> Native field-setting definitions.
	 */
	public static function get_settings() {
		$labels  = array(
			'frmgeo_enable_address_autocomplete'        => __( 'Enable autocomplete', 'address-autocomplete-for-formidable-forms' ),
			'frmgeo_force_suggested_address'            => __( 'Require address selection from suggestions', 'address-autocomplete-for-formidable-forms' ),
			'frmgeo_force_suggested_address_message'    => __( 'Selection alert message', 'address-autocomplete-for-formidable-forms' ),
			'frmgeo_address_autocomplete_types'         => __( 'Autocomplete Results Types', 'address-autocomplete-for-formidable-forms' ),
			'frmgeo_address_autocomplete_country'       => __( 'Restrict by Countries', 'address-autocomplete-for-formidable-forms' ),
			'frmgeo_address_autocomplete_language'      => __( 'Language', 'address-autocomplete-for-formidable-forms' ),
			'frmgeo_autocomplete_restriction_usage'     => __( 'Location Bias Type', 'address-autocomplete-for-formidable-forms' ),
			'frmgeo_autocomplete_proximity_lat'         => __( 'Latitude', 'address-autocomplete-for-formidable-forms' ),
			'frmgeo_autocomplete_proximity_lng'         => __( 'Longitude', 'address-autocomplete-for-formidable-forms' ),
			'frmgeo_autocomplete_proximity_radius'      => __( 'Radius in meters (maximum 50000)', 'address-autocomplete-for-formidable-forms' ),
			'frmgeo_autocomplete_bounds_sw_point'       => __( 'Southwest point (latitude,longitude)', 'address-autocomplete-for-formidable-forms' ),
			'frmgeo_autocomplete_bounds_ne_point'       => __( 'Northeast point (latitude,longitude)', 'address-autocomplete-for-formidable-forms' ),
			'frmgeo_address_autocomplete_strict_bounds' => __( 'Restrict results to the bounds', 'address-autocomplete-for-formidable-forms' ),
		);
		$selects = array(
			'frmgeo_address_autocomplete_language'  => ReferenceData::get_languages_as_options(),
			'frmgeo_address_autocomplete_types'     => ReferenceData::get_place_types_as_options(),
			'frmgeo_address_autocomplete_country'   => ReferenceData::get_countries_as_options(),
			'frmgeo_autocomplete_restriction_usage' => array(
				array(
					'label' => __( 'None', 'address-autocomplete-for-formidable-forms' ),
					'value' => '',
				),
				array(
					'label' => __( 'Proximity', 'address-autocomplete-for-formidable-forms' ),
					'value' => 'proximity',
				),
				array(
					'label' => __( 'Area Bounds', 'address-autocomplete-for-formidable-forms' ),
					'value' => 'area_bounds',
				),
			),
		);
		$selects['frmgeo_address_autocomplete_language'] = array_merge(
			array(
				array(
					'label' => __( 'Default (global language)', 'address-autocomplete-for-formidable-forms' ),
					'value' => '',
				),
			),
			$selects['frmgeo_address_autocomplete_language']
		);
		$fields = array();
		foreach ( self::defaults() as $key => $value ) {
			$type = is_int( $value ) ? 'toggle' : 'textbox';
			if ( isset( $selects[ $key ] ) ) {
				$type = is_array( $value ) ? 'frmgeoac-select-multiple' : 'select';
			}
			$fields[ $key ] = array(
				'name'  => $key,
				'type'  => $type,
				'group' => 'frmgeo_geolocation',
				'label' => $labels[ $key ],
				'width' => 'full',
				'value' => $value,
			);
			if ( isset( $selects[ $key ] ) ) {
				$fields[ $key ]['options'] = $selects[ $key ];
			}
		}
		return $fields;
	}
}
