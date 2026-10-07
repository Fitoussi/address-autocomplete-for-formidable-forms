<?php
/**
 * Exercise setup-notice lifecycle and security with WordPress test doubles.
 *
 * These fixtures do not replace installed WordPress acceptance.
 *
 * @package AddressAutocomplete\Tests
 */

$root = dirname( __DIR__ );
$slug = basename( $root );
$cases = array( 'unenrolled', 'first-activation', 'admin-pages', 'reactivation', 'configured', 'malformed-key', 'whitespace-key', 'no-capability', 'frontend', 'network-admin', 'network-activation', 'missing-host', 'old-host', 'premium', 'dismiss', 'dismiss-external-referer', 'dismiss-no-capability', 'dismiss-invalid-nonce' );
if ( ! isset( $argv[1] ) ) {
	foreach ( $cases as $case ) {
		$command = escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __FILE__ ) . ' ' . escapeshellarg( $case );
		exec( $command . ' 2>&1', $lines, $status );
		if ( 0 !== $status ) {
			fwrite( STDERR, implode( PHP_EOL, $lines ) . PHP_EOL );
			exit( 1 );
		}
		$lines = array();
		echo 'PASS: ' . $case . PHP_EOL;
	}
	echo count( $cases ) . ' setup-notice scenarios passed' . PHP_EOL;
	exit;
}

$case = $argv[1];
$hosts = array(
	'address-autocomplete-for-gravity-forms' => array( 'GravityGeolocationAutocomplete', 'GFGEOAC', 'gfgeoac' ),
	'address-autocomplete-for-ninja-forms' => array( 'NinjaGeolocationAutocomplete', 'NFGEOAC', 'nfgeoac' ),
	'address-autocomplete-for-formidable-forms' => array( 'FormidableGeolocationAutocomplete', 'FRMGEOAC', 'frmgeoac' ),
);
list( $namespace, $constants, $prefix ) = $hosts[ $slug ];
$notice_class = $namespace . '\\Admin\\SetupNotice';
$option = $prefix . '_setup_notice';
$action = $prefix . '_dismiss_setup_notice';
$GLOBALS['options'] = array();
$GLOBALS['hooks'] = array();
$GLOBALS['can_manage'] = true;
$GLOBALS['admin'] = true;
$GLOBALS['network'] = false;
$GLOBALS['nonce_valid'] = true;
$GLOBALS['referer'] = 'https://fixture.test/wp-admin/edit.php?post_type=page';
$GLOBALS['browser_key'] = 'configured' === $case ? 'fixture-browser-key' : ( 'malformed-key' === $case ? array( 'unexpected' ) : ( 'whitespace-key' === $case ? '  ' : '' ) );
define( 'ABSPATH', $root . '/' );

function add_action( $hook, $callback, $priority = 10, $args = 1 ) { $GLOBALS['hooks'][ $hook ][] = $callback; }
function add_filter( $hook, $callback, $priority = 10, $args = 1 ) { add_action( $hook, $callback, $priority, $args ); }
function register_activation_hook( $file, $callback ) { $GLOBALS['activation_callback'] = $callback; }
function plugin_basename( $file ) { return basename( dirname( $file ) ) . '/' . basename( $file ); }
function plugin_dir_path( $file ) { return dirname( $file ) . '/'; }
function plugin_dir_url( $file ) { return 'https://fixture.test/plugins/' . basename( dirname( $file ) ) . '/'; }
function current_user_can( $cap ) { return $GLOBALS['can_manage']; }
function is_admin() { return $GLOBALS['admin']; }
function is_network_admin() { return $GLOBALS['network']; }
function get_option( $key, $default = false ) { return $GLOBALS['options'][ $key ] ?? $default; }
function add_option( $key, $value, $deprecated = '', $autoload = null ) {
	if ( array_key_exists( $key, $GLOBALS['options'] ) ) { return false; }
	$GLOBALS['options'][ $key ] = $value;
	return true;
}
function update_option( $key, $value, $autoload = null ) { $GLOBALS['options'][ $key ] = $value; return true; }
function __( $value, $domain = '' ) { return $value; }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ) { return esc_html( $value ); }
function esc_html__( $value, $domain = '' ) { return esc_html( $value ); }
function esc_html_e( $value, $domain = '' ) { echo esc_html( $value ); }
function esc_url( $value ) { return $value; }
function admin_url( $path = '' ) { return 'https://fixture.test/wp-admin/' . $path; }
function add_query_arg( $key, $value, $url ) { return $url . '&' . $key . '=' . rawurlencode( $value ); }
function wp_nonce_field( $action ) { echo '<input type="hidden" name="_wpnonce" value="fixture-nonce">'; }
function check_admin_referer( $action ) {
	$GLOBALS['checked_nonce_action'] = $action;
	if ( ! $GLOBALS['nonce_valid'] ) { throw new RuntimeException( 'nonce denied' ); }
}
function wp_get_referer() { return $GLOBALS['referer']; }
function wp_safe_redirect( $target ) { throw new RuntimeException( 'redirect:' . $target ); }
function wp_redirect( ...$arguments ) { throw new RuntimeException( 'Unexpected activation redirect' ); }
function wp_die( ...$arguments ) { throw new RuntimeException( 'permission denied' ); }
function wp_remote_get( ...$arguments ) { throw new RuntimeException( 'Unexpected remote request' ); }
function wp_remote_post( ...$arguments ) { throw new RuntimeException( 'Unexpected remote request' ); }
function Ninja_Forms() {
	return new class() { public function get_setting( $name ) { return $GLOBALS['browser_key']; } };
}
function expect_notice( $truth, $message ) { if ( ! $truth ) { throw new RuntimeException( $message ); } }
function render_notice( $class ) { ob_start(); $class::render(); return ob_get_clean(); }

if ( 'missing-host' !== $case ) {
	class GFForms { public static $version = '3.0'; }
	if ( 'old-host' === $case ) {
		GFForms::$version = '1.0';
		class Ninja_Forms { const VERSION = '1.0'; }
	} else {
		class Ninja_Forms { const VERSION = '3.15.3'; }
	}
	class FrmAppHelper {
		public static function plugin_version() { return 'old-host' === $GLOBALS['case'] ? '1.0' : '6.35'; }
	}
}
$GLOBALS['options']['frmgeo_global_settings'] = array( 'google_maps_browser_api_key' => $GLOBALS['browser_key'], 'private_preserved' => 'untouched' );
define( 'GFGEOAC_GOOGLE_MAPS_BROWSER_API_KEY', $GLOBALS['browser_key'] );

require $root . '/' . $slug . '.php';
expect_notice( isset( $GLOBALS['activation_callback'] ) && $GLOBALS['activation_callback'] === array( $notice_class, 'activate' ), 'Real entry point must register the notice activation hook' );
if ( 'premium' === $case ) {
	$premium_constants = array( 'GFGEOAC' => 'GFGEO_VERSION', 'NFGEOAC' => 'NFGEO_VERSION', 'FRMGEOAC' => 'FRMGEO_VERSION' );
	define( $premium_constants[ $constants ], 'premium-fixture' );
}
$notice_class::register();
expect_notice( isset( $GLOBALS['hooks']['admin_notices'] ) && isset( $GLOBALS['hooks']['admin_post_' . $action] ), 'Admin notice and authenticated dismissal must be wired' );
expect_notice( ! isset( $GLOBALS['hooks']['admin_post_nopriv_' . $action] ), 'Dismissal must not be public' );

if ( 'unenrolled' === $case ) {
	expect_notice( '' === render_notice( $notice_class ) && ! isset( $GLOBALS['options'][ $option ] ), 'Updating an already active plugin must not enroll existing sites' );
	exit;
}
if ( 'network-activation' === $case || 'premium' === $case ) {
	$notice_class::activate( 'network-activation' === $case );
	expect_notice( ! isset( $GLOBALS['options'][ $option ] ), 'Network activation and inactive free must not enroll' );
	$GLOBALS['options'][ $option ] = 'pending';
	if ( 'premium' === $case ) { expect_notice( '' === render_notice( $notice_class ), 'Premium conflict must suppress setup guidance' ); }
	exit;
}
if ( 'reactivation' === $case ) {
	foreach ( array( 'dismissed', 'configured' ) as $state ) {
		$GLOBALS['options'][ $option ] = $state;
		$notice_class::activate();
		expect_notice( $state === $GLOBALS['options'][ $option ] && '' === render_notice( $notice_class ), 'Reactivation must preserve terminal state' );
	}
	exit;
}
$notice_class::activate();
expect_notice( 'pending' === $GLOBALS['options'][ $option ], 'First activation must record pending guidance' );
$notice_class::activate();
expect_notice( 'pending' === $GLOBALS['options'][ $option ], 'Repeated activation must not reset state' );

if ( 0 === strpos( $case, 'dismiss' ) ) {
	if ( 'dismiss-no-capability' === $case ) { $GLOBALS['can_manage'] = false; }
	if ( 'dismiss-invalid-nonce' === $case ) { $GLOBALS['nonce_valid'] = false; }
	if ( 'dismiss-external-referer' === $case ) { $GLOBALS['referer'] = 'https://outside.test/phishing'; }
	try { $notice_class::dismiss(); } catch ( RuntimeException $error ) { $result = $error->getMessage(); }
	if ( 'dismiss-no-capability' === $case || 'dismiss-invalid-nonce' === $case ) {
		expect_notice( 'pending' === $GLOBALS['options'][ $option ], 'Rejected dismissal must not change state' );
		expect_notice( 'dismiss-no-capability' === $case ? 'permission denied' === $result : 'nonce denied' === $result, 'Dismissal must validate capability and nonce' );
	} else {
		$target = 'dismiss-external-referer' === $case ? admin_url() : $GLOBALS['referer'];
		expect_notice( 'redirect:' . $target === $result && 'dismissed' === $GLOBALS['options'][ $option ], 'Dismissal must persist and return to same admin page or safe fallback' );
		expect_notice( $GLOBALS['checked_nonce_action'] === $action, 'Dismissal must use the plugin-specific nonce' );
		$notice_class::activate();
		expect_notice( '' === render_notice( $notice_class ), 'Dismissed notice must stay gone after reactivation' );
	}
	exit;
}
if ( 'no-capability' === $case ) { $GLOBALS['can_manage'] = false; }
if ( 'frontend' === $case ) { $GLOBALS['admin'] = false; }
if ( 'network-admin' === $case ) { $GLOBALS['network'] = true; }
$html = render_notice( $notice_class );
if ( in_array( $case, array( 'configured', 'no-capability', 'frontend', 'network-admin', 'missing-host', 'old-host' ), true ) ) {
	expect_notice( '' === $html, 'Ineligible contexts must not show setup notice' );
	if ( 'configured' === $case ) {
		expect_notice( 'configured' === $GLOBALS['options'][ $option ], 'Existing browser key must complete setup guidance' );
		$notice_class::activate();
		expect_notice( '' === render_notice( $notice_class ), 'Configured setup must not return on reactivation' );
	}
} else {
	expect_notice( false !== strpos( $html, 'Set up autocomplete' ) && false !== strpos( $html, 'View plugin overview' ), 'Notice must contain setup and overview links' );
	expect_notice( false !== strpos( $html, '<form method="post"' ) && false !== strpos( $html, 'fixture-nonce' ), 'Dismissal must be a nonce-protected POST' );
	expect_notice( false === strpos( $html, 'fixture-browser-key' ), 'Notice must not expose API keys' );
	foreach ( array( 'plugins.php', 'index.php', 'edit.php', 'admin.php' ) as $page ) {
		$GLOBALS['pagenow'] = $page;
		expect_notice( false !== strpos( render_notice( $notice_class ), 'Set up autocomplete' ), 'Guidance must be available on all site admin pages' );
	}
}
expect_notice( 'untouched' === $GLOBALS['options']['frmgeo_global_settings']['private_preserved'], 'Notice must not mutate shared premium settings' );
