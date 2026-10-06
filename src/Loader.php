<?php
/**
 * Standalone dependency and premium-conflict checks.
 *
 * @package FormidableGeolocationAutocomplete
 * @since 1.0.0
 */

namespace FormidableGeolocationAutocomplete;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bootstrap only the native integration and local product pages.
 */
final class Loader {
	/**
	 * Defer field registration until all plugin bootstraps have loaded.
	 */
	public static function load() {
		if ( defined( 'FRMGEO_VERSION' ) ) {
			add_action( 'admin_notices', array( self::class, 'conflict_notice' ) );
			add_action( 'network_admin_notices', array( self::class, 'conflict_notice' ) );
			return;
		}
		Admin\Dashboard::register();
		if ( ! class_exists( '\\FrmAppHelper' ) || version_compare( \FrmAppHelper::plugin_version(), FRMGEOAC_MIN_HOST_VERSION, '<' ) ) {
			add_action( 'admin_notices', array( self::class, 'dependency_notice' ) );
			return;
		}
		new Core\Bootstrap();
	}
	/**
	 * Explain free inactivity without deleting data or changing activation.
	 */
	public static function conflict_notice() {
		if ( current_user_can( 'activate_plugins' ) ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'Address Autocomplete for Formidable Forms is not running because Formidable Geolocation is active. Deactivate the free plugin; your existing Address fields and settings remain available in premium.', 'address-autocomplete-for-formidable-forms' ) . '</p></div>';
		}
	}
	/**
	 * Explain an unsupported host without producing frontend errors.
	 */
	public static function dependency_notice() {
		if ( current_user_can( 'activate_plugins' ) ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'Address Autocomplete requires Formidable Forms 6.23 or newer.', 'address-autocomplete-for-formidable-forms' ) . '</p></div>';
		}
	}
}
