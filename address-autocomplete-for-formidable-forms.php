<?php
/**
 * Plugin Name: Address Autocomplete for Formidable Forms
 * Plugin URI: https://formidablegeolocation.com
 * Description: Modern Google Places autocomplete in dedicated and native Formidable Forms Address fields. Includes country and language controls, location bias and required suggestion selection. Requires Formidable Forms and your own Google API key.
 * Version: 1.0.0
 * Author: Eyal Fitoussi
 * Author URI: https://www.wpgeo.com
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Requires Plugins: formidable
 * Text Domain: address-autocomplete-for-formidable-forms
 * Domain Path: /languages/
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package FormidableGeolocationAutocomplete
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Isolated bootstrap; saved frmgeo field/settings identities remain compatible.
define( 'FRMGEOAC_VERSION', '1.0.0' );
define( 'FRMGEOAC_PLUGIN_FILE', __FILE__ );
define( 'FRMGEOAC_BASENAME', plugin_basename( __FILE__ ) );
define( 'FRMGEOAC_PREFIX', 'frmgeo' );
define( 'FRMGEOAC_PLUGIN_NAME', 'Address Autocomplete for Formidable Forms' );
define( 'FRMGEOAC_PATH', plugin_dir_path( __FILE__ ) );
define( 'FRMGEOAC_URL', plugin_dir_url( __FILE__ ) );
define( 'FRMGEOAC_PACKAGE_TYPE', 'free' );
define( 'FRMGEOAC_PACKAGE_LABEL', 'Address Autocomplete' );
define( 'FRMGEOAC_SITE_URL', 'https://formidablegeolocation.com' );
define( 'FRMGEOAC_MIN_HOST_VERSION', '6.23' );

spl_autoload_register(
	static function ( $class_name ) {
		$prefix = 'FormidableGeolocationAutocomplete\\';
		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}
		$file = __DIR__ . '/src/' . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';
		if ( is_file( $file ) ) {
			require_once $file;
		}
	}
);

// Inspect all active plugin bootstraps before registering overlapping field types.
add_action( 'plugins_loaded', array( FormidableGeolocationAutocomplete\Loader::class, 'load' ), 20 );
