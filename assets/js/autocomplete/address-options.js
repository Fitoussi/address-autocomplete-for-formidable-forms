/**
 * Translate existing frmgeo Address preferences into standalone Places options.
 * Saved names stay compatible with premium; no FormCore or field class is used.
 */

/**
 * Interpret native checkbox values without treating the string "false" as true.
 *
 * @since 1.0.0
 *
 * @param {*} value Saved checkbox preference.
 * @returns {boolean} Whether the preference explicitly enables the option.
 */
export function isEnabled(value) {
	return value === true || value === 1 || value === '1' || value === 'true';
}

/**
 * Normalize multi-select preferences without mutating the stored value.
 *
 * @since 1.0.0
 *
 * @param {string|string[]|null|undefined} value Saved list preference.
 * @returns {string[]} Unique, non-empty string values.
 */
export function normalizeList(value) {
	const items = Array.isArray(value) ? value : String(value || '').split(',');
	return [
		...new Set(
			items
				.filter((item) => typeof item === 'string')
				.map((item) => item.trim())
				.filter(Boolean)
		),
	];
}

/**
 * Parse valid coordinates without allowing invalid bounds to block typing.
 *
 * @since 1.0.0
 *
 * @param {string} value Comma-separated latitude and longitude.
 * @returns {{lat: number, lng: number}|null} Valid point, or null.
 */
function point(value) {
	const parts = String(value || '').split(',');
	if (parts.length !== 2 || parts.some((part) => part.trim() === '')) {
		return null;
	}
	const [lat, lng] = parts.map(Number);
	return Number.isFinite(lat) && Number.isFinite(lng) && Math.abs(lat) <= 90 && Math.abs(lng) <= 180
		? { lat, lng }
		: null;
}

/**
 * Build only autocomplete configuration from existing field/global settings.
 * IP bias and locator flags are deliberately ignored, even in imported forms.
 *
 * @param {Object} field Whitelisted saved Address options.
 * @param {Object} config Browser API and localization preferences.
 * @returns {Object} Google Places autocomplete request options.
 */
export function buildAddressOptions(field, config = {}) {
	const options = {
		includedPrimaryTypes: normalizeList(field.frmgeo_address_autocomplete_types).slice(0, 5),
		includedRegionCodes: normalizeList(field.frmgeo_address_autocomplete_country).slice(0, 5),
		language: field.frmgeo_address_autocomplete_language || config.languageCode || 'en',
		region: config.regionCode || undefined,
	};

	if (field.frmgeo_autocomplete_restriction_usage === 'proximity') {
		const center = point(
			`${field.frmgeo_autocomplete_proximity_lat},${field.frmgeo_autocomplete_proximity_lng}`
		);
		const radius = Number(field.frmgeo_autocomplete_proximity_radius);
		if (center && Number.isFinite(radius) && radius > 0 && radius <= 50000) {
			options.locationBias = { center, radius };
		}
	} else if (field.frmgeo_autocomplete_restriction_usage === 'area_bounds') {
		const sw = point(field.frmgeo_autocomplete_bounds_sw_point);
		const ne = point(field.frmgeo_autocomplete_bounds_ne_point);
		if (sw && ne && sw.lat <= ne.lat) {
			const bounds = { south: sw.lat, west: sw.lng, north: ne.lat, east: ne.lng };
			options[
				isEnabled(field.frmgeo_address_autocomplete_strict_bounds)
					? 'locationRestriction'
					: 'locationBias'
			] = bounds;
		}
	}

	return options;
}
