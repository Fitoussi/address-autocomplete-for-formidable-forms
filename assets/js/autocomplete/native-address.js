/**
 * Native Formidable Forms Address population, without the geocoding pipeline.
 *
 * @since 1.0.0
 */

/**
 * Read Google's address components into the six native Address sub-inputs.
 * Values exist only to populate Address, not a Geocoder or Dynamic fields.
 *
 * @param {Object} place Modern Place payload, including legacy-adapter output.
 * @returns {Object} Address parts, with explicit short/long alternatives.
 */
export function getNativeAddressParts(place) {
	const components = place.addressComponents || [];
	const find = (...types) =>
		types
			.map((type) => components.find((component) => component.types?.includes(type)))
			.find(Boolean);
	const long = (...types) => find(...types)?.longText || '';
	const short = (...types) => find(...types)?.shortText || '';
	const postcode = long('postal_code');
	const suffix = long('postal_code_suffix');
	return {
		// City/region suggestions may have no street components. Keep their
		// formatted address instead of clearing the user's first Address input.
		line1:
			[long('street_number'), long('route')].filter(Boolean).join(' ') ||
			long('premise') ||
			place.formattedAddress ||
			'',
		line2: long('subpremise'),
		city: long('locality', 'postal_town', 'sublocality_level_1', 'administrative_area_level_3'),
		state: long('administrative_area_level_1'),
		stateCode: short('administrative_area_level_1'),
		zip: postcode && suffix ? `${postcode}-${suffix}` : postcode,
		country: long('country'),
		countryCode: short('country'),
	};
}

/**
 * Select an exact enabled option value; never rewrite options or guess aliases.
 * A text input uses its long name; dropdowns may store long names or ISO codes.
 *
 * @param {HTMLInputElement|HTMLSelectElement} input Native Address sub-input.
 * @param {string} value Preferred value.
 * @param {string[]} alternatives Explicit short/long alternatives.
 * @returns {string} Matching value, or empty when a dropdown has no match.
 */
export function resolveInputValue(input, value, alternatives = []) {
	if (input.tagName !== 'SELECT' || value === '') {
		return value;
	}
	const codeOption = alternatives
		.map((code) =>
			Array.from(input.options).find((option) => !option.disabled && option.dataset.code === code)
		)
		.find(Boolean);
	if (codeOption) return codeOption.value;
	return (
		[value, ...alternatives].find(
			(candidate) =>
				candidate &&
				Array.from(input.options).some((option) => !option.disabled && option.value === candidate)
		) || ''
	);
}

/**
 * Populate native Address sub-inputs using scoped host class names.
 *
 * @since 1.0.0
 *
 * @param {HTMLElement} wrapper Native Address field container.
 * @param {Object} place Selected Place with address components.
 * @returns {void}
 */
export function populateNativeAddress(wrapper, place) {
	const parts = getNativeAddressParts(place);
	const fields = [
		['[name$="[line1]"]', parts.line1],
		['[name$="[line2]"]', parts.line2],
		['[name$="[city]"]', parts.city],
		['[name$="[state]"]', parts.state, [parts.stateCode]],
		['[name$="[zip]"]', parts.zip],
		['[name$="[country]"]', parts.country, [parts.countryCode]],
	];
	for (const [selector, value, alternatives] of fields) {
		const input = wrapper.querySelector(selector);
		if (!input || input.disabled) {
			continue;
		}
		// Preserve an apartment/unit the visitor typed when Google has none.
		if (selector === '[name$="[line2]"]' && !value) {
			continue;
		}
		input.value = resolveInputValue(input, value, alternatives);
		input.dispatchEvent(new Event('change', { bubbles: true }));
	}
}
