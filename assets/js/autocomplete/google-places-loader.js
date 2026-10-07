/**
 * Load only Google Places; no WPGeo globals or optional engine libraries.
 *
 * @since 1.0.0
 */

let placesPromise;

/**
 * Wait for an existing Google API load instead of injecting a duplicate script.
 *
 * @since 1.0.0
 *
 * @param {number} [timeoutMs=15000] Maximum wait in milliseconds.
 * @returns {Promise<Object>} Google Maps namespace once Places can be loaded.
 */
function waitForPlaces(timeoutMs = 15000) {
	return new Promise((resolve, reject) => {
		const start = Date.now();
		const poll = () => {
			const maps = globalThis.google?.maps;
			if (maps?.importLibrary || maps?.places?.AutocompleteSessionToken) {
				return resolve(maps);
			}
			if (Date.now() - start >= timeoutMs) {
				return reject(new Error('Google Places did not become available.'));
			}
			setTimeout(poll, 50);
		};
		poll();
	});
}

/**
 * Load Places once per page and respect the existing disable-API setting.
 * Failure leaves ordinary Address inputs usable; callers can report the error.
 *
 * @param {Object} config Whitelisted browser-key/region/language configuration.
 * @returns {Promise<void>} Resolves when the Places classes are available.
 */
export function loadGooglePlaces(config = {}) {
	if (placesPromise) {
		return placesPromise;
	}
	placesPromise = (async () => {
		const alreadyLoading = Array.from(document.scripts).some((script) =>
			/^https?:\/\/maps\.(googleapis|google)\.com\/maps\/api\/js(?:\?|$)/.test(script.src)
		);
		if (!globalThis.google?.maps && !alreadyLoading && !config.disableGoogleApi) {
			if (!config.googleMapsBrowserApiKey) {
				throw new Error('A Google browser API key is required for autocomplete.');
			}
			await new Promise((resolve, reject) => {
				const script = document.createElement('script');
				const callback = 'frmgeoacGooglePlacesReady';
				const params = new URLSearchParams({
					key: config.googleMapsBrowserApiKey,
					libraries: 'places',
					v: 'weekly',
					loading: 'async',
					callback,
				});
				if (config.languageCode) {
					params.set('language', config.languageCode);
				}
				if (config.regionCode) {
					params.set('region', config.regionCode);
				}
				const finish = (error) => {
					clearTimeout(timeout);
					delete globalThis[callback];
					if (error) {
						script.remove();
						reject(error);
					} else {
						resolve();
					}
				};
				const timeout = setTimeout(
					() => finish(new Error('Google Places loading timed out.')),
					15000
				);
				globalThis[callback] = () => finish();
				script.async = true;
				script.src = `https://maps.googleapis.com/maps/api/js?${params}`;
				script.onerror = () => finish(new Error('Google Places could not be loaded.'));
				document.head.appendChild(script);
			});
		}
		const maps = await waitForPlaces();
		if (maps.importLibrary) {
			await maps.importLibrary('places');
		}
	})().catch((error) => {
		placesPromise = undefined;
		throw error;
	});
	return placesPromise;
}
