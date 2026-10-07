<?php
/**
 * Autocomplete settings on Formidable's native settings screen.
 *
 * @package FormidableGeolocationAutocomplete\Admin
 * @since 1.0.0
 */

namespace FormidableGeolocationAutocomplete\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Preserve the premium option while updating only free browser preferences.
 *
 * @since 1.0.0
 */
final class Settings {

	/**
	 * Register native settings hooks.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_filter( 'frm_add_settings_section', [ $this, 'add_section' ], 50 );
		add_action( 'admin_init', [ $this, 'save' ] );
	}

	/**
	 * Add Address Autocomplete to the native global-settings navigation.
	 *
	 * @param array $sections Native sections.
	 * @return array
	 * @since 1.0.0
	 */
	public function add_section( $sections ) {
		$sections['frmgeo_geolocation'] = [
			'class'    => self::class,
			'function' => 'render',
			'name'     => __( 'Address Autocomplete', 'address-autocomplete-for-formidable-forms' ),
			'icon'     => 'frm_icon_font frm_location_icon',
		];
		return $sections;
	}

	/**
	 * Editable browser preferences only.
	 *
	 * @return array<string,string> Editable browser preferences only.
	 * @since 1.0.0
	 */
	public static function defaults() {
		return [
			'google_maps_browser_api_key' => '',
			'google_maps_country'         => 'US',
			'google_maps_language'        => 'en',
		];
	}

	/**
	 * Sanitize the three editable browser preferences and retain hidden values.
	 *
	 * @param array $submitted Unslashed preferences.
	 * @param array $existing All stored preferences.
	 * @return array
	 * @since 1.0.0
	 */
	public static function merge_settings( $submitted, $existing ) {
		$result = is_array( $existing ) ? $existing : [];
		foreach ( self::defaults() as $key => $default ) {
			$value          = $submitted[ $key ] ?? $default;
			$result[ $key ] = is_scalar( $value ) ? sanitize_text_field( (string) $value ) : $default;
		}
		return $result;
	}

	/**
	 * Verify native context, capability and nonce before a narrow option update.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function save() {
		// phpcs:disable WordPress.Security.NonceVerification
		$action = isset( $_POST['frm_action'] ) && is_string( $_POST['frm_action'] ) ? sanitize_key( wp_unslash( $_POST['frm_action'] ) ) : '';
		$page   = isset( $_GET['page'] ) && is_string( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		$tab    = isset( $_GET['t'] ) && is_string( $_GET['t'] ) ? sanitize_key( wp_unslash( $_GET['t'] ) ) : '';
		if ( 'process-form' !== $action || 'formidable-settings' !== $page || 'frmgeo_geolocation_settings' !== $tab ) {
			return;
		}
		// phpcs:enable WordPress.Security.NonceVerification
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		check_admin_referer( 'frmgeoac_settings', 'frmgeoac_settings_nonce' );
		// Values are unslashed here and sanitized individually by merge_settings before storage.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$submitted = isset( $_POST['frmgeo_settings'] ) && is_array( $_POST['frmgeo_settings'] ) ? wp_unslash( $_POST['frmgeo_settings'] ) : [];
		update_option( 'frmgeo_global_settings', self::merge_settings( $submitted, get_option( 'frmgeo_global_settings', [] ) ) );
	}

	/**
	 * Output three preferences within the host's existing form.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function render() {
		$settings = get_option( 'frmgeo_global_settings', [] );
		$settings = is_array( $settings ) ? array_merge( self::defaults(), $settings ) : self::defaults();
		wp_nonce_field( 'frmgeoac_settings', 'frmgeoac_settings_nonce' );
		Dashboard::render_settings_link();
		$labels = [
			'google_maps_browser_api_key' => __( 'Browser API Key', 'address-autocomplete-for-formidable-forms' ),
			'google_maps_country'         => __( 'Region Code', 'address-autocomplete-for-formidable-forms' ),
			'google_maps_language'        => __( 'Language', 'address-autocomplete-for-formidable-forms' ),
		];
		echo '<p>' . esc_html__( 'Use your own Google browser API key with Maps JavaScript API and Places API (New) enabled. Region influences results; field country restrictions limit suggestions.', 'address-autocomplete-for-formidable-forms' ) . '</p>';
		foreach ( $labels as $key => $label ) {
			$value = is_scalar( $settings[ $key ] ) ? (string) $settings[ $key ] : '';
			echo '<p><label class="frm_left_label" for="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>';
			echo '<input class="frm_with_left_label" type="text" id="' . esc_attr( $key ) . '" name="frmgeo_settings[' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '"></p>';
			if ( 'google_maps_browser_api_key' === $key ) {
				echo '<p class="frm_with_left_label">' . esc_html__( 'For this free plugin, enable Maps JavaScript API and Places API (New) for your browser key. No server key is required.', 'address-autocomplete-for-formidable-forms' ) . ' ' . esc_html__( 'Need help?', 'address-autocomplete-for-formidable-forms' ) . ' <a href="' . esc_url( FRMGEOAC_SITE_URL . '/docs/google-maps-api-keys-formidable-geolocation/' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'View the Google API setup guide', 'address-autocomplete-for-formidable-forms' ) . '</a></p>';
			}
		}
	}

	/**
	 * Browser loader options, with no hidden paid settings exported.
	 *
	 * @return array Browser loader options, with no hidden paid settings exported.
	 * @since 1.0.0
	 */
	public static function get_config() {
		$all      = get_option( 'frmgeo_global_settings', [] );
		$all      = is_array( $all ) ? $all : [];
		$settings = self::merge_settings( $all, [] );
		return [
			'googleMapsBrowserApiKey' => $settings['google_maps_browser_api_key'],
			'regionCode'              => $settings['google_maps_country'],
			'languageCode'            => $settings['google_maps_language'],
			'disableGoogleApi'        => (bool) apply_filters( 'frmgeo_disable_google_maps_api', ! empty( $all['disable_google_maps_api'] ) ),
		];
	}
}
