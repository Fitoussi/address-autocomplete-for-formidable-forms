<?php
/**
 * Register only native settings, one field and autocomplete adapters.
 *
 * @package FormidableGeolocationAutocomplete\Core
 * @since 1.0.0
 */

namespace FormidableGeolocationAutocomplete\Core;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wire supported host APIs without the premium framework.
 */
final class Bootstrap {
	/**
	 * Register hooks after dependency and premium checks succeed.
	 */
	public function __construct() {
		new \FormidableGeolocationAutocomplete\Admin\Settings();
		new \FormidableGeolocationAutocomplete\Features\Form\Admin\FormEditor();
		new \FormidableGeolocationAutocomplete\Features\Form\Runtime\FormGeoProcessor();
		add_filter( 'frm_get_field_type_class', array( $this, 'field_class' ), 20, 2 );
		add_filter( 'frm_available_fields', array( $this, 'field_button' ) );
	}
	/**
	 * Configure field class.
	 *
	 * @param string $class_name Native class.
	 * @param string $type Saved type.
	 * @return string
	 */
	public function field_class( $class_name, $type ) {
		return 'frmgeo_address' === $type ? \FormidableGeolocationAutocomplete\Features\Form\Fields\Address\Field::class : $class_name;
	}
	/**
	 * Configure field button.
	 *
	 * @param array $fields Builder fields.
	 * @return array
	 */
	public function field_button( $fields ) {
		$fields['frmgeo_address'] = array(
			'name' => __( 'Address', 'address-autocomplete-for-formidable-forms' ),
			'icon' => 'frmfont frm_location_icon',
		);
		return $fields;
	}
}
