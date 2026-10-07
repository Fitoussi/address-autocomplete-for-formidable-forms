/**
 * Transitional compatibility adapter for Google Places API (Legacy).
 *
 * Keep legacy request and response translation isolated here. Remove this
 * adapter and its fallback coverage in the standalone tests alongside
 * `address-autocomplete.js` when support for the legacy service ends.
 *
 * @since 1.0.0
 */

const FIELD_MAP = {
	addressComponents: 'address_components',
	formattedAddress: 'formatted_address',
};

// This fork populates Address only. Do not request geocoder/map metadata.
const REQUIRED_FIELDS = ['formatted_address'];

/**
 * Determine whether a Places API (New) failure should use the legacy adapter.
 *
 * Transient, quota, invalid-key, referrer, billing, and unrelated permission
 * errors deliberately remain on the new service. The fallback is reserved for
 * failures that specifically identify an unavailable Places API (New) method.
 *
 * @param {*} error Error thrown by Places API (New).
 * @returns {boolean} Whether the legacy adapter should be attempted.
 */
export function shouldUseLegacyPlacesFallback(error) {
	const code = String(error?.code || error?.status || '').toUpperCase();
	const message = String(error?.message || error || '').toUpperCase();
	const errorText = String(error || '').toUpperCase();
	let details = '';

	try {
		details = JSON.stringify(error?.details || '').toUpperCase();
	} catch (detailsError) {
		details = String(error?.details || '').toUpperCase();
	}

	const text = `${code} ${message} ${errorText} ${details}`;
	const unavailableTypeError =
		error instanceof TypeError &&
		(text.includes('AUTOCOMPLETESUGGESTION') || text.includes('FETCHAUTOCOMPLETESUGGESTIONS'));
	const permissionFailure =
		code === '403' ||
		code === 'PERMISSION_DENIED' ||
		code === 'REQUEST_DENIED' ||
		text.includes('PERMISSION_DENIED') ||
		text.includes('REQUEST_DENIED');
	const placesNewService =
		text.includes('PLACES API (NEW)') ||
		text.includes('PLACES.GOOGLEAPIS.COM') ||
		text.includes('AUTOCOMPLETEPLACES') ||
		text.includes('AUTOCOMPLETESUGGESTION') ||
		text.includes('FETCHAUTOCOMPLETESUGGESTIONS');
	const unrelatedConfigurationFailure =
		text.includes('BILLING') ||
		text.includes('REFERER') ||
		text.includes('REFERRER') ||
		(text.includes('API KEY') && (text.includes('INVALID') || text.includes('EXPIRED')));
	const unavailableService =
		text.includes('SERVICE_DISABLED') ||
		text.includes('API_KEY_SERVICE_BLOCKED') ||
		text.includes('ACCESS_NOT_CONFIGURED') ||
		text.includes('API_NOT_ACTIVATED') ||
		text.includes('NOT BEEN USED') ||
		text.includes('NOT ENABLED') ||
		(text.includes('PLACES API (NEW)') && text.includes('IS DISABLED')) ||
		(text.includes('REQUESTS TO THIS API') && text.includes('METHOD') && text.includes('BLOCKED'));

	return (
		unavailableTypeError ||
		(!unrelatedConfigurationFailure && permissionFailure && placesNewService && unavailableService)
	);
}

/**
 * Convert Places API (New) autocomplete options to the legacy request shape.
 *
 * @param {Object} options Places API (New) request options.
 * @returns {Object} Legacy AutocompletionRequest.
 */
export function buildLegacyAutocompleteRequest(options = {}) {
	const request = {
		input: String(options.input || ''),
	};
	const countries = Array.isArray(options.includedRegionCodes)
		? options.includedRegionCodes.filter(Boolean)
		: [];
	const types = Array.isArray(options.includedPrimaryTypes)
		? options.includedPrimaryTypes
				.filter(Boolean)
				.map((type) => (type === 'street_address' ? 'address' : type))
		: [];
	let bounds = options.locationRestriction || options.locationBias || null;

	if (bounds && typeof bounds.getBounds === 'function') {
		bounds = bounds.getBounds();
	}

	if (countries.length) {
		request.componentRestrictions = {
			// Places API (Legacy) accepts at most five country codes.
			country: countries.length === 1 ? countries[0] : countries.slice(0, 5),
		};
	}
	if (types.length) {
		request.types = types;
	}
	if (options.locationRestriction) {
		request.locationRestriction = bounds;
	} else if (bounds) {
		request.locationBias = bounds;
	}
	if (options.language) {
		request.language = options.language;
	}
	if (options.region) {
		request.region = options.region;
	}
	if (options.origin) {
		request.origin = options.origin;
	}
	if (Number.isInteger(options.inputOffset)) {
		request.offset = options.inputOffset;
	}
	if (options.sessionToken) {
		request.sessionToken = options.sessionToken;
	}

	return request;
}

/**
 * Convert requested Places API (New) fields to supported legacy field names.
 *
 * @param {string[]} fields Requested new-API field names.
 * @returns {string[]} Legacy Places Details fields.
 */
export function buildLegacyPlaceFields(fields = []) {
	const mapped = fields.map((field) => FIELD_MAP[field] || null).filter(Boolean);

	return [...new Set([...REQUIRED_FIELDS, ...mapped])];
}

/**
 * Create an Error carrying the legacy Google Places status.
 *
 * @param {string} operation Human-readable operation name.
 * @param {string} status Google Places status.
 * @returns {Error} Structured service error.
 */
function createPlacesError(operation, status) {
	const error = new Error(`${operation} failed with status ${status || 'UNKNOWN_ERROR'}.`);
	error.code = status || 'UNKNOWN_ERROR';
	return error;
}

/**
 * New-API-compatible wrapper around a legacy AutocompletePrediction.
 */
class LegacyPlacePrediction {
	/**
	 * Retain the legacy prediction and its details provider.
	 *
	 * @since 1.0.0
	 *
	 * @param {Object} prediction Legacy Google Places prediction.
	 * @param {LegacyPlacesAutocomplete} adapter Legacy details provider.
	 */
	constructor(prediction, adapter) {
		this.prediction = prediction;
		this.adapter = adapter;
		this.text = {
			toString: () => prediction.description || '',
		};
	}

	/**
	 * Create a new-API-shaped Place wrapper for this prediction.
	 *
	 * @since 1.0.0
	 *
	 * @returns {LegacyPlace} Compatible Place wrapper.
	 */
	toPlace() {
		return new LegacyPlace(this.prediction, this.adapter);
	}
}

/**
 * New-API-compatible wrapper around a legacy PlaceResult.
 */
class LegacyPlace {
	/**
	 * Initialize the compatible Place identity and formatted address.
	 *
	 * @since 1.0.0
	 *
	 * @param {Object} prediction Legacy Google Places prediction.
	 * @param {LegacyPlacesAutocomplete} adapter Legacy details provider.
	 */
	constructor(prediction, adapter) {
		this.prediction = prediction;
		this.adapter = adapter;
		this.id = prediction.place_id || '';
		this.formattedAddress = prediction.description || '';
	}

	/**
	 * Fetch requested legacy details and expose only supported Address components.
	 *
	 * @since 1.0.0
	 *
	 * @param {Object} [options={}] Requested Place details.
	 * @param {string[]} [options.fields=[]] New-API field names to translate.
	 * @returns {Promise<LegacyPlace>} This populated Place wrapper.
	 */
	async fetchFields({ fields = [] } = {}) {
		const result = await this.adapter.fetchPlaceDetails(
			this.prediction.place_id,
			buildLegacyPlaceFields(fields)
		);

		// Expose only the address values consumed by the standalone adapter.
		this.formattedAddress = result.formatted_address || this.formattedAddress;
		this.addressComponents = Array.isArray(result.address_components)
			? result.address_components.map((component) => ({
					longText: component.long_name || '',
					shortText: component.short_name || '',
					types: component.types || [],
				}))
			: [];

		return this;
	}
}

/**
 * Legacy Google Places prediction/details provider.
 */
export class LegacyPlacesAutocomplete {
	/**
	 * Create the legacy prediction and details services.
	 *
	 * @since 1.0.0
	 *
	 * @throws {Error} When the legacy Google Places classes are unavailable.
	 */
	constructor() {
		const places = globalThis.google?.maps?.places;

		if (!places?.AutocompleteService || !places?.PlacesService) {
			throw new Error('Google Places API (Legacy) is unavailable.');
		}

		this.places = places;
		this.autocompleteService = new places.AutocompleteService();
		this.serviceContainer = document.createElement('div');
		this.placesService = new places.PlacesService(this.serviceContainer);
		this.destroyed = false;
	}

	/**
	 * Fetch legacy predictions and return the shape used by the new provider.
	 *
	 * @param {Object} options Places API (New) request options.
	 * @returns {Promise<Object>} Object containing compatible suggestions.
	 */
	fetchAutocompleteSuggestions(options = {}) {
		const request = buildLegacyAutocompleteRequest(options);

		return new Promise((resolve, reject) => {
			this.autocompleteService.getPlacePredictions(request, (predictions, status) => {
				if (this.destroyed) {
					resolve({ suggestions: [] });
					return;
				}

				if (status === this.places.PlacesServiceStatus.ZERO_RESULTS) {
					resolve({ suggestions: [] });
					return;
				}

				if (status !== this.places.PlacesServiceStatus.OK || !Array.isArray(predictions)) {
					reject(createPlacesError('Legacy autocomplete request', status));
					return;
				}

				resolve({
					suggestions: predictions.map((prediction) => ({
						placePrediction: new LegacyPlacePrediction(prediction, this),
					})),
				});
			});
		});
	}

	/**
	 * Fetch details for a selected legacy prediction.
	 *
	 * @param {string} placeId Google place ID.
	 * @param {string[]} fields Legacy fields to request.
	 * @returns {Promise<Object>} Legacy PlaceResult.
	 */
	fetchPlaceDetails(placeId, fields) {
		return new Promise((resolve, reject) => {
			this.placesService.getDetails(
				{
					placeId,
					fields,
				},
				(place, status) => {
					if (this.destroyed) {
						reject(createPlacesError('Legacy place-details request', 'CANCELLED'));
						return;
					}

					if (status !== this.places.PlacesServiceStatus.OK || !place) {
						reject(createPlacesError('Legacy place-details request', status));
						return;
					}

					resolve(place);
				}
			);
		});
	}

	/**
	 * Release the provider references and invalidate future requests.
	 *
	 * @since 1.0.0
	 *
	 * @returns {void}
	 */
	destroy() {
		this.destroyed = true;
		this.autocompleteService = null;
		this.placesService = null;
		this.serviceContainer = null;
	}
}
