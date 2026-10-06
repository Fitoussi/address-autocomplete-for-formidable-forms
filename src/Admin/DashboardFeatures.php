<?php
/**
 * Local feature catalog aligned with the host's Starter, Pro and Agency builds.
 *
 * @package FormidableGeolocationAutocomplete\Admin
 * @since 1.0.0
 */

namespace FormidableGeolocationAutocomplete\Admin;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Presentation only, not licenses, remote feeds or runtime entitlement gates.
 */
final class DashboardFeatures {
	/**
	 * Public package headings.
	 *
	 * @return array<string,string> Public package headings.
	 */
	public static function get_packages() {
		return array(
			'free'    => __( 'Free', 'address-autocomplete-for-formidable-forms' ),
			'starter' => __( 'Starter', 'address-autocomplete-for-formidable-forms' ),
			'pro'     => __( 'Pro', 'address-autocomplete-for-formidable-forms' ),
			'agency'  => __( 'Agency', 'address-autocomplete-for-formidable-forms' ),
		);
	}
	/**
	 * Local descriptions of free and premium capabilities.
	 *
	 * @return array Local descriptions of free and premium capabilities.
	 */
	public static function get_features() {
		return array(
			'autocomplete'      => array(
				'title'       => __( 'Address autocomplete', 'address-autocomplete-for-formidable-forms' ),
				'description' => __( 'Google Places API (New) in a dedicated single-line field or native Address autofill.', 'address-autocomplete-for-formidable-forms' ),
				'details'     => __( 'Countries, place types, per-field language, location bias, global region and language, and required suggestion selection.', 'address-autocomplete-for-formidable-forms' ),
				'icon'        => 'location-alt',
				'doc'         => '',
				'packages'    => array( 'free', 'starter', 'pro', 'agency' ),
				'included'    => true,
			),
			'location-map'      => array(
				'title'       => __( 'Interactive form maps', 'address-autocomplete-for-formidable-forms' ),
				'description' => __( 'Show connected locations and let visitors refine them with a draggable marker.', 'address-autocomplete-for-formidable-forms' ),
				'details'     => __( 'Customize map appearance, zoom, default location and marker behavior.', 'address-autocomplete-for-formidable-forms' ),
				'icon'        => 'location',
				'doc'         => '',
				'packages'    => array( 'starter', 'pro', 'agency' ),
				'included'    => false,
			),
			'geocoder'          => array(
				'title'       => __( 'Geocoder & complete location data', 'address-autocomplete-for-formidable-forms' ),
				'description' => __( 'Store formatted addresses, coordinates and individual location components.', 'address-autocomplete-for-formidable-forms' ),
				'details'     => __( 'Connect addresses, maps and ordinary fields to shared geocoding results.', 'address-autocomplete-for-formidable-forms' ),
				'icon'        => 'database',
				'doc'         => '',
				'packages'    => array( 'starter', 'pro', 'agency' ),
				'included'    => false,
			),
			'dynamic-fields'    => array(
				'title'       => __( 'Dynamic location fields', 'address-autocomplete-for-formidable-forms' ),
				'description' => __( 'Populate city, region, postal code, coordinates and other location values.', 'address-autocomplete-for-formidable-forms' ),
				'details'     => __( 'Use connected values in native fields and your form workflows.', 'address-autocomplete-for-formidable-forms' ),
				'icon'        => 'randomize',
				'doc'         => '',
				'packages'    => array( 'starter', 'pro', 'agency' ),
				'included'    => false,
			),
			'current-location'  => array(
				'title'       => __( 'Current-location detection', 'address-autocomplete-for-formidable-forms' ),
				'description' => __( 'Offer a locator button or detect a location when the form loads.', 'address-autocomplete-for-formidable-forms' ),
				'details'     => __( 'Browser location requires visitor permission; optional IP location needs a provider.', 'address-autocomplete-for-formidable-forms' ),
				'icon'        => 'admin-site-alt3',
				'doc'         => '',
				'packages'    => array( 'starter', 'pro', 'agency' ),
				'included'    => false,
			),
			'coordinates'       => array(
				'title'       => __( 'Coordinate inputs', 'address-autocomplete-for-formidable-forms' ),
				'description' => __( 'Collect latitude and longitude directly.', 'address-autocomplete-for-formidable-forms' ),
				'details'     => __( 'A dedicated input for workflows where an address is not the starting point.', 'address-autocomplete-for-formidable-forms' ),
				'icon'        => 'admin-generic',
				'doc'         => '',
				'packages'    => array( 'starter', 'pro', 'agency' ),
				'included'    => false,
			),
			'location-controls' => array(
				'title'       => __( 'Standalone Locator & Reset fields', 'address-autocomplete-for-formidable-forms' ),
				'description' => __( 'Place current-location and reset buttons where they fit your form.', 'address-autocomplete-for-formidable-forms' ),
				'details'     => __( 'Separate controls complement the locator inside premium Address fields.', 'address-autocomplete-for-formidable-forms' ),
				'icon'        => 'controls-repeat',
				'doc'         => '',
				'packages'    => array( 'starter', 'pro', 'agency' ),
				'included'    => false,
			),
			'directions'        => array(
				'title'       => __( 'Directions & route displays', 'address-autocomplete-for-formidable-forms' ),
				'description' => __( 'Connect an origin, destination and waypoints, and display route details.', 'address-autocomplete-for-formidable-forms' ),
				'details'     => __( 'Route maps, summaries and turn-by-turn directions with connected fields.', 'address-autocomplete-for-formidable-forms' ),
				'icon'        => 'leftright',
				'doc'         => '',
				'packages'    => array( 'pro', 'agency' ),
				'included'    => false,
			),
			'distance'          => array(
				'title'       => __( 'Distance & travel time', 'address-autocomplete-for-formidable-forms' ),
				'description' => __( 'Calculate travel distance and duration or straight-line distance.', 'address-autocomplete-for-formidable-forms' ),
				'details'     => __( 'Use readable results or numeric distance and duration in form calculations.', 'address-autocomplete-for-formidable-forms' ),
				'icon'        => 'performance',
				'doc'         => '',
				'packages'    => array( 'pro', 'agency' ),
				'included'    => false,
			),
			'validation'        => array(
				'title'       => __( 'Address validation', 'address-autocomplete-for-formidable-forms' ),
				'description' => __( 'Compare addresses with Google Address Validation recommendations.', 'address-autocomplete-for-formidable-forms' ),
				'details'     => __( 'Review corrections and validation results. Requires the Address Validation API.', 'address-autocomplete-for-formidable-forms' ),
				'icon'        => 'yes-alt',
				'doc'         => '',
				'packages'    => array( 'pro', 'agency' ),
				'included'    => false,
			),
			'entry-maps'        => array(
				'title'       => __( 'Saved-entry maps', 'address-autocomplete-for-formidable-forms' ),
				'description' => __( 'Display a saved location on its own map or multiple locations together.', 'address-autocomplete-for-formidable-forms' ),
				'details'     => __( 'Single-entry and mashup map shortcodes outside your form.', 'address-autocomplete-for-formidable-forms' ),
				'icon'        => 'admin-multisite',
				'doc'         => '',
				'packages'    => array( 'pro', 'agency' ),
				'included'    => false,
			),
			'nearby'            => array(
				'title'       => __( 'Nearby locations & result panels', 'address-autocomplete-for-formidable-forms' ),
				'description' => __( 'Find destinations near a visitor and display selectable results.', 'address-autocomplete-for-formidable-forms' ),
				'details'     => __( 'Configure destination sources and results with an optional map.', 'address-autocomplete-for-formidable-forms' ),
				'icon'        => 'search',
				'doc'         => '',
				'packages'    => array( 'agency' ),
				'included'    => false,
			),
			'drawing'           => array(
				'title'       => __( 'Drawing Tools & Drawing Shape', 'address-autocomplete-for-formidable-forms' ),
				'description' => __( 'Draw markers, lines, polygons, rectangles, circles and freehand shapes.', 'address-autocomplete-for-formidable-forms' ),
				'details'     => __( 'Collect shapes and geometry outputs on a connected map.', 'address-autocomplete-for-formidable-forms' ),
				'icon'        => 'edit',
				'doc'         => '',
				'packages'    => array( 'agency' ),
				'included'    => false,
			),
		);
	}
}
