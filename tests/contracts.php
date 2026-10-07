<?php
/** Fixture contracts; these do not replace installed WordPress acceptance. */
define( 'ABSPATH', __DIR__ . '/' );
$GLOBALS['hooks'] = array();
function add_action( $hook, $callback, $priority = 10, $args = 1 ) {
	$GLOBALS['hooks'][ $hook ][] = $callback; }
function add_filter( $hook, $callback, $priority = 10, $args = 1 ) {
	add_action( $hook, $callback, $priority, $args ); }
function register_activation_hook( $file, $callback ) {
	$GLOBALS['hooks'][ 'activate_' . plugin_basename( $file ) ][] = $callback; }
function __( $text, $domain = '' ) {
	return $text; }
function esc_html( $text ) {
	return htmlspecialchars( $text, ENT_QUOTES ); }
function esc_attr( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES ); }
function checked( $value, $expected = true, $echo = true ) {
	return $value === $expected ? 'checked="checked"' : ''; }
function selected( $value, $expected = true, $echo = true ) {
	return $value === $expected ? 'selected="selected"' : ''; }
function esc_html__( $text, $domain = '' ) {
	return esc_html( $text ); }
function esc_html_e( $text, $domain = '' ) {
	echo esc_html( $text ); }
function wp_nonce_field( $action, $name ) {}
function esc_url( $url ) {
	return $url; }
function admin_url( $path ) {
	return 'https://fixture.test/wp-admin/' . $path; }
function add_query_arg( $key, $value, $url ) {
	return $url . '&' . $key . '=' . $value; }
function sanitize_text_field( $value ) {
	return strip_tags( $value ); }
function plugin_basename( $file ) {
	return basename( dirname( $file ) ) . '/' . basename( $file ); }
function plugin_dir_path( $file ) {
	return dirname( $file ) . '/'; }
function plugin_dir_url( $file ) {
	return 'https://fixture.test/plugins/' . basename( dirname( $file ) ) . '/'; }
function apply_filters( $hook, $value ) {
	return $value; }
function wp_json_encode( $value, $flags = 0 ) {
	return json_encode( $value, $flags ); }
function wp_enqueue_script() {
	$GLOBALS['scripts'][] = func_get_args(); }
function wp_enqueue_style() {}
function wp_add_inline_script( $handle, $value, $where ) {
	$GLOBALS['inline'] = $value; }
function get_option( $key, $default = false ) {
	return $GLOBALS['options'][ $key ] ?? $default; }
function Ninja_Forms() {
	return new class() { public function get_setting( $key ) {
			return 'fixture-value';
	} }; }
class Ninja_Forms {
	const VERSION = '3.15.3';
}
class NF_Fields_Textbox {
	protected $_settings = array();
	protected $_nicename; public function __construct() {}
}
class FrmAppHelper {
	public static function plugin_version() {
		return '6.35'; }
	public static function tooltip_icon( $text, $atts = array() ) {
		echo '<span class="' . esc_attr( ( $atts['class'] ?? '' ) . ' frm_help' ) . '" title="' . esc_attr( $text ) . '" tabindex="' . esc_attr( $atts['tabindex'] ?? '' ) . '" aria-label="' . esc_attr( $atts['aria-label'] ?? '' ) . '"><svg class="frm_tooltip_icon"></svg></span>'; }
}
class FrmFieldText {}
class FrmField {
	public static $fixtures = array();
	public static function getAll( $where ) {
		return self::$fixtures[ $where['fi.form_id'] ] ?? array(); }
}

function expect( $truth, $message ) {
	if ( ! $truth ) {
		throw new RuntimeException( $message ); }
}
require dirname( __DIR__ ) . '/address-autocomplete-for-formidable-forms.php';
expect( FRMGEOAC_PACKAGE_TYPE === 'free', 'Package identity' );
expect( ! defined( 'FRMGEO_VERSION' ), 'Free must not define premium bootstrap constants' );
FormidableGeolocationAutocomplete\Loader::load();
expect( isset( $GLOBALS['hooks']['frm_get_field_type_class'] ), 'Host field wiring' );
expect( isset( $GLOBALS['hooks']['frm_pro_available_fields'] ), 'Promotions must use native unavailable-field hook' );
$editor     = new FormidableGeolocationAutocomplete\Features\Form\Admin\FormEditor();
$promotions = $editor->available_fields( array() );
expect( count( $promotions ) === 4, 'Exactly four native discovery cards' );
foreach ( $promotions as $promotion ) {
	expect( strpos( $promotion['icon'], ' frm_show_upgrade' ) !== false, 'Native upgrade marker blocks insertion' );
}
ob_start();
$editor->field_group();
$group = ob_get_clean();
expect( strpos( $group, 'frmgeo-geolocation-fields-holder' ) !== false, 'Premium group holder retained' );
$defaults = FormidableGeolocationAutocomplete\Features\Form\Fields\Address\Settings::defaults();
expect( count( $defaults ) === 13, 'Explicit autocomplete whitelist' );
expect( ! isset( $defaults['frmgeo_geocoder_id'] ), 'No premium options' );
$schema = FormidableGeolocationAutocomplete\Features\Form\Fields\Address\Settings::get_settings();
expect( $schema['frmgeo_address_autocomplete_types']['label'] === 'Autocomplete result types', 'Clear result-type label' );
expect( $schema['frmgeo_address_autocomplete_country']['label'] === 'Restrict to countries', 'Clear country restriction label' );
foreach ( $schema as $definition ) {
	expect( ! empty( $definition['tooltip'] ) && strip_tags( $definition['tooltip'] ) === $definition['tooltip'], 'Every supported option has plain-text help' );
}
expect( strpos( $schema['frmgeo_force_suggested_address']['tooltip'], 'clear the input' ) !== false, 'Selection help describes blur clearing, not only submission' );
expect( strpos( $schema['frmgeo_address_autocomplete_language']['tooltip'], 'global language setting' ) !== false, 'Language help describes the actual default' );
expect( strpos( $schema['frmgeo_autocomplete_restriction_usage']['tooltip'], 'does not detect' ) !== false, 'Bias help does not promise visitor detection' );
$products = FormidableGeolocationAutocomplete\Admin\Dashboard::get_products();
$product_urls = array_column( $products, 'url' );
expect( count( $product_urls ) === count( array_unique( $product_urls ) ), 'Discovery cards do not duplicate products' );
expect( in_array( 'https://gravitygeolocation.com/', $product_urls, true ), 'Gravity product is included in discovery cards' );
expect( count( $schema ) === 13 && count( $schema['frmgeo_address_autocomplete_country']['options'] ) > 200, 'Native country controls' );
foreach ( array( 0, 1 ) as $enabled ) {
	ob_start();
	$editor->options(
		array(
			'id'                                 => 103,
			'type'                               => 'frmgeo_address',
			'frmgeo_enable_address_autocomplete' => $enabled,
		)
	);
	$html = ob_get_clean();
	expect( substr_count( $html, 'frmgeoac-tooltip frm_help' ) === 13, 'Exactly one native help icon per option' );
	expect( substr_count( $html, 'tabindex="0" aria-label=' ) === 13, 'Help icons remain keyboard-accessible with named explanations' );
	foreach ( $schema as $key => $definition ) {
		expect( strpos( $html, 'title="' . esc_attr( $definition['tooltip'] ) . '"' ) !== false, 'Tooltip explanations are attribute-escaped' );
		expect( strpos( $html, 'for="' . $key . '_103"' ) !== false, 'Native input label association retained' );
	}
	expect( strpos( $html, '<h3 class="frm-collapsed">Address Autocomplete<i' ) !== false, 'Specific field-options label retains the native collapsible group' );
	preg_match_all( '/<input[^>]+name="field_options\[frmgeo_enable_address_autocomplete_103\]"[^>]*>/', $html, $inputs );
	$serialized = array();
	foreach ( $inputs[0] as $input ) {
		if ( strpos( $input, 'type="checkbox"' ) !== false && strpos( $input, 'checked="checked"' ) === false ) {
			continue; }
		preg_match( '/value="([01])"/', $input, $value );
		$serialized[] = $value[1];
	}
	expect( (int) $serialized[0] === $enabled, 'Compact first-value-wins parser must persist checked and unchecked toggles' );
}
$features = FormidableGeolocationAutocomplete\Admin\DashboardFeatures::get_features();
ob_start();
$editor->options( array( 'id' => 104, 'type' => 'address' ) );
$native_html = ob_get_clean();
expect( substr_count( $native_html, 'frmgeoac-tooltip frm_help' ) === 13, 'Native multi-part Address receives the same help icons' );
expect( strpos( $native_html, 'for="frmgeo_enable_address_autocomplete_104"' ) !== false, 'Native Address keeps its own input IDs' );
ob_start();
$editor->options( array( 'id' => 105, 'type' => 'text' ) );
expect( ob_get_clean() === '', 'Unrelated native fields receive no autocomplete help or options' );
expect(
	count(
		array_filter(
			$features,
			static function ( $item ) {
				return $item['included'];
			}
		)
	) === 1,
	'Only autocomplete included'
);
expect( array_keys( FormidableGeolocationAutocomplete\Admin\DashboardFeatures::get_packages() ) === array( 'free', 'starter', 'pro', 'agency' ), 'Host package lineup' );
$processor = new FormidableGeolocationAutocomplete\Features\Form\Runtime\FormGeoProcessor();
$input     = array(
	array(
		'id'                                 => 11,
		'type'                               => 'frmgeo_address',
		'frmgeo_enable_address_autocomplete' => 1,
		'frmgeo_geocoder_id'                 => 9,
		'frmgeo_server_api_key'              => 'must-not-export',
	),
	array(
		'id'   => 12,
		'type' => 'address',
	),
	array(
		'id'   => 13,
		'type' => 'frmgeo_map',
	),
);
$args      = array( 'form' => (object) array( 'id' => 7 ) );
expect( $processor->collect( $input, $args ) === $input, 'Render must not modify forms' );
expect( strpos( $GLOBALS['inline'], 'must-not-export' ) === false, 'No hidden credentials in payload' );
expect( strpos( $GLOBALS['inline'], 'geocoder_id' ) === false, 'No geocoder runtime' );
FrmField::$fixtures = array(
	8 => array(
		(object) array(
			'id'            => 80,
			'type'          => 'divider',
			'field_options' => array( 'form_select' => 9 ),
		),
	),
	9 => array(
		(object) array(
			'id'            => 81,
			'type'          => 'frmgeo_address',
			'field_options' => array(
				'frmgeo_enable_address_autocomplete' => 1,
				'frmgeo_server_api_key'              => 'never-export',
			),
		),
		(object) array(
			'id'            => 82,
			'type'          => 'form',
			'field_options' => array( 'form_select' => 8 ),
		),
	),
);
expect( $processor->collect( array(), array( 'form' => (object) array( 'id' => 8 ) ) ) === array(), 'Later-page collection preserves host fields' );
expect( strpos( $GLOBALS['inline'], '"id":"81"' ) !== false, 'Repeater child payload available before AJAX rendering' );
expect( strpos( $GLOBALS['inline'], 'never-export' ) === false, 'Child payload excludes premium secrets' );
expect( substr_count( $GLOBALS['inline'], '"id":"81"' ) === 1, 'Nested form cycles do not duplicate fields' );
$processor->collect(
	array(
		array(
			'id'                                 => 81,
			'type'                               => 'frmgeo_address',
			'frmgeo_enable_address_autocomplete' => 0,
		),
	),
	array( 'form' => (object) array( 'id' => 8 ) )
);
expect( strpos( $GLOBALS['inline'], '"id":"81"' ) === false, 'Rendered disable filter overrides saved child configuration' );
$stored = array(
	'server_api_key'     => 'preserve',
	'ip_address_locator' => 1,
);
$merged = FormidableGeolocationAutocomplete\Admin\Settings::merge_settings(
	array(
		'google_maps_country' => 'IL',
		'server_api_key'      => 'overwrite',
	),
	$stored
);
expect( $merged['server_api_key'] === 'preserve' && $merged['ip_address_locator'] === 1, 'Hidden premium settings retained' );
expect( $merged['google_maps_country'] === 'IL', 'Supported preference saved' );
ob_start();
FormidableGeolocationAutocomplete\Admin\Settings::render();
$global_html = ob_get_clean();
$key_position = strpos( $global_html, 'id="google_maps_browser_api_key"' );
$help_position = strpos( $global_html, 'https://formidablegeolocation.com/docs/google-maps-api-keys-formidable-geolocation/' );
$region_position = strpos( $global_html, 'id="google_maps_country"' );
expect( $key_position !== false && $help_position > $key_position && $region_position > $help_position, 'Setup guide follows the browser-key input before regional preferences' );
expect( strpos( $global_html, 'target="_blank" rel="noopener noreferrer"' ) !== false && strpos( $global_html, 'No server key is required.' ) !== false, 'Setup help has safe new-tab attributes and free browser-only requirements' );
$GLOBALS['hooks'] = array();
define( 'FRMGEO_VERSION', 'premium-fixture' );
FormidableGeolocationAutocomplete\Loader::load();
expect( isset( $GLOBALS['hooks']['admin_notices'] ), 'Persistent conflict notice' );
expect( ! isset( $GLOBALS['hooks']['frm_get_field_type_class'] ), 'Free yields to premium' );
echo "PHP contracts passed\n";
