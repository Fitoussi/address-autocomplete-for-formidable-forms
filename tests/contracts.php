<?php
/** Fixture contracts; these do not replace installed WordPress acceptance. */
define( 'ABSPATH', __DIR__ . '/' );
$GLOBALS['hooks'] = [];
function add_action( $hook, $callback, $priority = 10, $args = 1 ) { $GLOBALS['hooks'][$hook][] = $callback; }
function add_filter( $hook, $callback, $priority = 10, $args = 1 ) { add_action( $hook, $callback, $priority, $args ); }
function __( $text, $domain = '' ) { return $text; }
function sanitize_text_field( $value ) { return strip_tags( $value ); }
function plugin_basename( $file ) { return basename( dirname( $file ) ) . '/' . basename( $file ); }
function plugin_dir_path( $file ) { return dirname( $file ) . '/'; }
function plugin_dir_url( $file ) { return 'https://fixture.test/plugins/' . basename( dirname( $file ) ) . '/'; }
function apply_filters( $hook, $value ) { return $value; }
function wp_json_encode( $value, $flags = 0 ) { return json_encode( $value, $flags ); }
function wp_enqueue_script() { $GLOBALS['scripts'][] = func_get_args(); }
function wp_enqueue_style() {}
function wp_add_inline_script( $handle, $value, $where ) { $GLOBALS['inline'] = $value; }
function get_option( $key, $default = false ) { return $GLOBALS['options'][$key] ?? $default; }
function Ninja_Forms() { return new class { public function get_setting( $key ) { return 'fixture-value'; } }; }
class Ninja_Forms { const VERSION = '3.15.3'; }
class NF_Fields_Textbox { protected $_settings = []; protected $_nicename; public function __construct() {} }
class FrmAppHelper { public static function plugin_version() { return '6.35'; } }
class FrmFieldText {}

function expect( $truth, $message ) {
	if ( ! $truth ) { throw new RuntimeException( $message ); }
}
require dirname( __DIR__ ) . '/address-autocomplete-for-formidable-forms.php';
expect( FRMGEOAC_PACKAGE_TYPE === 'free', 'Package identity' );
expect( ! defined( 'FRMGEO_VERSION' ), 'Free must not define premium bootstrap constants' );
FormidableGeolocationAutocomplete\Loader::load();
expect( isset( $GLOBALS['hooks']['frm_get_field_type_class'] ), 'Host field wiring' );
$defaults = FormidableGeolocationAutocomplete\Features\Form\Fields\Address\Settings::defaults();
expect( count( $defaults ) === 13, 'Explicit autocomplete whitelist' );
expect( ! isset( $defaults['frmgeo_geocoder_id'] ), 'No premium options' );
$schema = FormidableGeolocationAutocomplete\Features\Form\Fields\Address\Settings::get_settings();
expect( count( $schema ) === 13 && count( $schema['frmgeo_address_autocomplete_country']['options'] ) > 200, 'Native country controls' );
$features = FormidableGeolocationAutocomplete\Admin\DashboardFeatures::get_features();
expect( count( array_filter( $features, static function( $item ) { return $item['included']; } ) ) === 1, 'Only autocomplete included' );
expect( array_keys( FormidableGeolocationAutocomplete\Admin\DashboardFeatures::get_packages() ) === [ 'free', 'starter', 'pro', 'agency' ], 'Host package lineup' );
$processor = new FormidableGeolocationAutocomplete\Features\Form\Runtime\FormGeoProcessor();
$input = [
	[ 'id' => 11, 'type' => 'frmgeo_address', 'frmgeo_enable_address_autocomplete' => 1, 'frmgeo_geocoder_id' => 9, 'frmgeo_server_api_key' => 'must-not-export' ],
	[ 'id' => 12, 'type' => 'address' ],
	[ 'id' => 13, 'type' => 'frmgeo_map' ],
];
$args = [ 'form' => (object) [ 'id' => 7 ] ];
expect( $processor->collect( $input, $args ) === $input, 'Render must not modify forms' );
expect( strpos( $GLOBALS['inline'], 'must-not-export' ) === false, 'No hidden credentials in payload' );
expect( strpos( $GLOBALS['inline'], 'geocoder_id' ) === false, 'No geocoder runtime' );
$stored = [ 'server_api_key' => 'preserve', 'ip_address_locator' => 1 ];
$merged = FormidableGeolocationAutocomplete\Admin\Settings::merge_settings( [ 'google_maps_country' => 'IL', 'server_api_key' => 'overwrite' ], $stored );
expect( $merged['server_api_key'] === 'preserve' && $merged['ip_address_locator'] === 1, 'Hidden premium settings retained' );
expect( $merged['google_maps_country'] === 'IL', 'Supported preference saved' );
$GLOBALS['hooks'] = [];
define( 'FRMGEO_VERSION', 'premium-fixture' );
FormidableGeolocationAutocomplete\Loader::load();
expect( isset( $GLOBALS['hooks']['admin_notices'] ), 'Persistent conflict notice' );
expect( ! isset( $GLOBALS['hooks']['frm_get_field_type_class'] ), 'Free yields to premium' );
echo "PHP contracts passed\n";
