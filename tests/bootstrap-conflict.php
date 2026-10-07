<?php
/**
 * Isolated bootstrap and load-order contracts; not live WordPress acceptance.
 *
 * @package AddressAutocomplete\Tests
 */
$root = dirname( __DIR__ );
$slug = basename( $root );
$hosts = array(
 'address-autocomplete-for-gravity-forms' => array( 'GravityGeolocationAutocomplete', 'GFGEOAC', 'GFGEO', 'gform_pre_render' ),
 'address-autocomplete-for-ninja-forms' => array( 'NinjaGeolocationAutocomplete', 'NFGEOAC', 'NFGEO', 'ninja_forms_register_fields' ),
 'address-autocomplete-for-formidable-forms' => array( 'FormidableGeolocationAutocomplete', 'FRMGEOAC', 'FRMGEO', 'frm_get_field_type_class' ),
);
list( $namespace, $own, $premium, $runtime_hook ) = $hosts[ $slug ];
if ( ! isset( $argv[1] ) ) {
	$production = array( $root . '/' . $slug . '.php' );
	foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root . '/src', FilesystemIterator::SKIP_DOTS ) ) as $file ) {
		if ( 'php' === $file->getExtension() ) { $production[] = $file->getPathname(); }
	}
	foreach ( $production as $file ) {
		$tokens = token_get_all( file_get_contents( $file ) );
		foreach ( $tokens as $index => $token ) {
			if ( is_array( $token ) && T_ARRAY === $token[0] ) {
				$next = $index + 1;
				while ( isset( $tokens[ $next ] ) && is_array( $tokens[ $next ] ) && in_array( $tokens[ $next ][0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) { $next++; }
				if ( '(' === ( $tokens[ $next ] ?? null ) ) { throw new RuntimeException( 'Production arrays must use []: ' . $file ); }
			}
		}
	}
 foreach ( array( 'free-only', 'premium-first', 'free-first', 'premium-first-denied', 'free-first-denied' ) as $case ) {
  exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __FILE__ ) . ' ' . escapeshellarg( $case ) . ' 2>&1', $lines, $status );
  if ( $status !== 0 ) { throw new RuntimeException( implode( PHP_EOL, $lines ) ); }
  $lines = array();
  echo 'PASS: ' . $case . PHP_EOL;
 }
 echo "Five isolated bootstrap/load-order scenarios passed.\n";
 exit;
}
$case = $argv[1];
define( 'ABSPATH', $root . '/' );
$GLOBALS['hooks'] = array();
$GLOBALS['settings'] = array( 'saved_field' => $premium . '_address', 'premium_secret' => 'untouched' );
$before = $GLOBALS['settings'];
function add_action( $name, $callback, $priority = 10, $args = 1 ) { $GLOBALS['hooks'][ $name ][ $priority ][] = $callback; }
function add_filter( $name, $callback, $priority = 10, $args = 1 ) { add_action( $name, $callback, $priority, $args ); }
function apply_filters( $name, $value ) { return $value; }
function register_activation_hook( $file, $callback ) { $GLOBALS['activation'] = $callback; }
function plugin_basename( $file ) { return basename( dirname( $file ) ) . '/' . basename( $file ); }
function plugin_dir_path( $file ) { return dirname( $file ) . '/'; }
function plugin_dir_url( $file ) { return 'https://fixture.test/plugins/'; }
function __( $text, $domain = '' ) { return $text; }
function esc_html__( $text, $domain = '' ) { return htmlspecialchars( $text, ENT_QUOTES ); }
function current_user_can( $cap ) { return strpos( $GLOBALS['case'], 'denied' ) === false; }
function bootstrap_expect( $truth, $message ) { if ( ! $truth ) { throw new RuntimeException( $message ); } }
function fixture_action( $hook ) {
 $callbacks = $GLOBALS['hooks'][ $hook ] ?? array();
 ksort( $callbacks );
 foreach ( $callbacks as $group ) { foreach ( $group as $callback ) { call_user_func( $callback ); } }
}
class GFForms { public static $version = '3.0'; public static function include_addon_framework() {} }
class GFAddOn { public static $registered = array(); public static function register( $class ) { self::$registered[] = $class; } }
class Ninja_Forms { const VERSION = '3.15.3'; }
class NF_Fields_Textbox { protected $_settings = array(); protected $_nicename; public function __construct() {} }
class FrmAppHelper { public static function plugin_version() { return '6.35'; } }
if ( strpos( $case, 'premium-first' ) === 0 ) { define( $premium . '_VERSION', 'premium-fixture' ); }
require $root . '/' . $slug . '.php';
bootstrap_expect( defined( $own . '_VERSION' ) && constant( $own . '_PACKAGE_TYPE' ) === 'free', 'Free bootstrap uses its own constants' );
bootstrap_expect( ! isset( $GLOBALS['hooks']['admin_menu'] ) && ! isset( $GLOBALS['hooks'][ $runtime_hook ] ), 'Entry point must defer runtime and dashboard registration' );
bootstrap_expect( ! class_exists( substr( $namespace, 0, -12 ) . '\\Loader', false ), 'Free must not define premium classes or aliases' );
if ( strpos( $case, 'free-first' ) === 0 ) { define( $premium . '_VERSION', 'premium-fixture' ); }
fixture_action( 'plugins_loaded' );
if ( $case === 'free-only' ) {
 bootstrap_expect( ! defined( $premium . '_VERSION' ), 'Free must never claim premium identity' );
 bootstrap_expect( isset( $GLOBALS['hooks']['admin_menu'] ), 'Local dashboard registered after guard succeeds' );
 if ( $own === 'GFGEOAC' ) {
  bootstrap_expect( isset( $GLOBALS['hooks']['gform_loaded'][8] ), 'Gravity add-on registered during native host event' );
  fixture_action( 'gform_loaded' );
  bootstrap_expect( GFAddOn::$registered === array( $namespace . '\\Core\\AddOn' ), 'Only isolated free Add-On is registered' );
 } else { bootstrap_expect( isset( $GLOBALS['hooks'][ $runtime_hook ] ), 'Standalone native field wiring is registered' ); }
} else {
 bootstrap_expect( constant( $premium . '_VERSION' ) === 'premium-fixture', 'Premium identity must remain unchanged in both orders' );
 bootstrap_expect( ! isset( $GLOBALS['hooks']['admin_menu'] ) && ! isset( $GLOBALS['hooks'][ $runtime_hook ] ) && ! isset( $GLOBALS['hooks']['gform_loaded'] ), 'Free dashboard and field runtime must yield in both orders' );
 foreach ( array( 'admin_notices', 'network_admin_notices' ) as $hook ) {
  ob_start(); fixture_action( $hook ); $output = ob_get_clean();
  bootstrap_expect( current_user_can( 'activate_plugins' ) ? strpos( $output, 'is not running because' ) !== false : $output === '', 'Conflict notice respects capability on both admin surfaces' );
 }
}
bootstrap_expect( $before === $GLOBALS['settings'], 'Load-order checks must not mutate saved data' );
foreach ( array_merge( array( $root . '/' . $slug . '.php' ), glob( $root . '/src/*.php' ) ) as $file ) {
 bootstrap_expect( strpos( file_get_contents( $file ), "define( '" . $premium . "_" ) === false, 'No premium constant aliases' );
}
