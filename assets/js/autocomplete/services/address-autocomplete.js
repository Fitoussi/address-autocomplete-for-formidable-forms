/**
 * @file address-autocomplete.js
 * @description Provides address autocomplete functionality using the Google Places API.
 * Builds and manages a suggestion dropdown, handles keyboard navigation, and dispatches
 * structured CustomEvents to allow external interaction and customization.
 *
 * The service owns its own DOM/event lifecycle and supports explicit teardown via
 * `destroy()` so field instances can safely recreate autocomplete during rebinds.
 *
 * @author Eyal Fitoussi
 * @version 1.0.0
 */

import {
	LegacyPlacesAutocomplete,
	shouldUseLegacyPlacesFallback,
} from './compat/places-autocomplete.js';

const PLACES_NEW_PROVIDER = 'places-new';
const PLACES_LEGACY_PROVIDER = 'places-legacy';

// Share only the provider decision across autocomplete fields on the current
// page. Each field still owns its legacy service instance and teardown.
let sharedAutocompleteProvider = PLACES_NEW_PROVIDER;
let sharedProviderProbe = null;

/**
 * Address autocomplete service.
 *
 * Creates and manages a suggestions dropdown for a single input element, fetches
 * Google Places suggestions, handles keyboard and mouse selection, and emits
 * structured lifecycle events for integrations.
 *
 * Instances may be safely destroyed and recreated when form fields are reinitialized.
 *
 * @class AddressAutocomplete
 * @property {Object} args Input, output and debounce preferences.
 * @property {HTMLInputElement} inputElement Bound Address input.
 * @property {Object} options Current Places autocomplete request options.
 * @property {HTMLElement|null} container Owned suggestions container.
 * @property {string} prefix Event and DOM identity prefix supplied by the caller.
 * @property {number} requestId Generation used to reject stale prediction responses.
 * @property {number} selectionContextId Generation used to reject stale Place details.
 * @property {number} pendingSelections Number of Place detail requests in progress.
 * @property {boolean} userSelected Whether a suggestion was explicitly selected.
 * @property {LegacyPlacesAutocomplete|null} legacyAutocomplete Optional fallback provider.
 */
export class AddressAutocomplete {

	/**
	 * Creates a new address autocomplete instance and optionally initializes it immediately.
	 *
	 * @param {Object} args - Configuration arguments.
	 * @param {Object} options - Configuration options.
	 * @param {Object} [objectInstance] - Optional object instance (form, map, etc.).
	 */
	/**
	 * @event wpgeofw_address_autocomplete_start
	 * @description Fired when the AddressAutocomplete instance is being initialized.
	 * @type {CustomEvent}
	 * @property {Object} detail.args - Constructor arguments.
	 * @property {Object} detail.options - Autocomplete options.
	 * @property {Object|undefined} detail.objectInstance - Owning form or component instance.
	 * @property {AddressAutocomplete} detail.instance - The autocomplete instance.
	 */
	constructor(args, options = {}, objectInstance = undefined) {

		const startEvent = new CustomEvent(`${args.prefix}_address_autocomplete_start`, {
			detail: { args, options, objectInstance, instance: this }
		});
		document.dispatchEvent(startEvent);

		// Allow listeners to replace or modify args and options
		args = startEvent.detail.args || args;
		options = startEvent.detail.options || options;

		this.args = {
			inputElement: null,
			prefix: 'wpgeofw',
			fetchFields: ['formattedAddress', 'addressComponents'],
			outputField: 'formattedAddress',
			autoInit: true,
			debounceDelay: 50,
			...args
		};

		this.prefix = this.args.prefix;
		this.inputElement = this.args.inputElement;
		this.options = options;
		this.requestId = 0;
		this.request = {};
		this.container = {};
		this.closeHandler = {};
		this.objectInstance = objectInstance;
		this.selectionContextId = 0;
		this.pendingSelections = 0;
		this.autocompleteProvider = sharedAutocompleteProvider;
		this.legacyAutocomplete = null;

		// Tracks whether a suggestion was explicitly selected
		this.userSelected = false;

		// Used to suppress unwanted change events on blur
		this.suppressChangeOnBlur = false;

		if (!this.inputElement) {
			console.error('Input element not found');
			return;
		}

		/**
		 * @event wpgeofw_address_autocomplete_init_before
		 * @description Fired just before initializing the AddressAutocomplete instance.
		 * @type {CustomEvent}
		 * @property {AddressAutocomplete} detail.instance - The autocomplete instance.
		 */
		document.dispatchEvent(new CustomEvent(`${this.prefix}_address_autocomplete_init_before`, {
			detail: { instance: this }
		}));

		if (this.args.autoInit) {
			this.init();
		}
	}

	/**
	 * Initializes the autocomplete instance.
	 *
	 * This method is safe to call again on the same instance. Existing suggestion
	 * container DOM is removed before rebuilding the UI and rebinding listeners.
	 *
	 * @returns {void}
	 */
	init() {
		if (this._destroyed) {
			this._destroyed = false;
		}
		if (this.container instanceof Element && this.container.isConnected) {
			this.container.remove();
		}

		this.initializeOptions();
		this.setupEventListeners();
		this.createSuggestionsContainer();
		this.setupCloseHandler();
		this.bindAutocompleteEvents();
		this.refreshAutocompleteToken(this.options);
	}

	/**
	 * Initializes autocomplete request options with defaults and custom overrides.
	 *
	 * @returns {void}
	 */
	initializeOptions() {
		this.options = Object.assign({
			input: '',
			includedPrimaryTypes: [],
			includedRegionCodes: [],
			inputOffset: null,
			language: 'en-US',
			locationBias: null, // LatLng|LatLngLiteral|LatLngBounds|LatLngBoundsLiteral|Circle|CircleLiteral|string
			locationRestriction: null, // LatLngBounds|LatLngBoundsLiteral
			origin: null,
			region: 'us',
			sessionToken: null,
		}, this.options);

		this.options.includedPrimaryTypes = this.normalizeIncludedPrimaryTypes(this.options.includedPrimaryTypes);
	}

	/**
	 * Normalize legacy and user-provided place type filters for Places API New.
	 *
	 * @param {string|string[]} types - Requested primary type filters.
	 * @returns {string[]} Places API New compatible primary type filters.
	 */
	normalizeIncludedPrimaryTypes(types = []) {
		const typeMap = {
			geocode: 'geocode',
			address: 'street_address',
			city: '(cities)',
			cities: '(cities)',
			'(cities)': '(cities)',
			region: '(regions)',
			regions: '(regions)',
			'(regions)': '(regions)',
			establishment: 'establishment',
			street_address: 'street_address',
		};

		const requestedTypes = Array.isArray(types) ? types : [types];
		const normalizedTypes = requestedTypes
			.map((type) => {
				const value = String(type || '').trim().toLowerCase();
				// Preserve ordinary primary types such as restaurant/gas_station;
				// the old alias-only lookup silently discarded the editor's choices.
				return typeMap[value] || value;
			})
			.filter(Boolean);

		const uniqueTypes = [...new Set(normalizedTypes)];

		if (uniqueTypes.includes('(cities)')) {
			return ['(cities)'];
		}

		if (uniqueTypes.includes('(regions)')) {
			return ['(regions)'];
		}

		return uniqueTypes;
	}

	/**
	 * Sets up base input listeners for browser autofill suppression and Enter-key handling.
	 *
	 * Stable handler references are cached so listeners can be removed during reinit
	 * or `destroy()`.
	 *
	 * @returns {void}
	 */
	setupEventListeners() {
		if (this._focusInHandler) {
			this.inputElement.removeEventListener('focusin', this._focusInHandler);
		}
		if (this._preventSubmitHandler) {
			this.inputElement.removeEventListener('keydown', this._preventSubmitHandler);
		}

		this._focusInHandler = this.disableAutofill.bind(this);
		this._preventSubmitHandler = this.preventSubmitOnEnter.bind(this);

		this.inputElement.addEventListener('focusin', this._focusInHandler);
		this.inputElement.addEventListener('keydown', this._preventSubmitHandler);
	}

	/**
	 * Disables browser autofill-related behaviors on the input.
	 *
	 * @returns {void}
	 */
	disableAutofill() {
		// Do not close/cancel a fresh interaction after the input regained focus.
		clearTimeout(this._blurTimer);
		this.inputElement.setAttribute('autocomplete', 'off');
		this.inputElement.setAttribute('autocorrect', 'off');
		this.inputElement.setAttribute('autocapitalize', 'off');
	}

	/**
	 * Prevents native form submission when Enter is pressed inside the autocomplete input.
	 *
	 * If no suggestion is currently active, the dropdown is cleared and the pending
	 * debounce timer is cancelled. Selection itself is handled by the keyboard
	 * navigation listener bound in `addKeyboardNavigation()`.
	 *
	 * @param {KeyboardEvent} event - The keyboard event.
	 * @returns {void}
	 */
	preventSubmitOnEnter(event) {
		if (event.key !== 'Enter') {
			return;
		}
		const inputValue = String(this.inputElement?.value || '').trim();

		// Check if a suggestion is currently selected.
		const hasActiveSuggestion = this.container.querySelector('li.active') !== null;

		event.preventDefault();

		if (!inputValue) {
			this.resetInteractionState();
			this.clearSuggestions();
			return;
		}

		if (!hasActiveSuggestion) {
			this.clearSuggestions();
			clearTimeout(this.debounceTimer);
		}
	}

	/*preventSubmitOnEnter(event) {
		if (event.key !== 'Enter') {
			return;
		}

		const hasActiveSuggestion = this.container.querySelector('li.active') !== null;

		// Always prevent default Enter behavior
		event.preventDefault();
		event.stopPropagation();

		// Mark that this Enter press was NOT a place selection
		this.userSelected = false;
		this.suppressChangeOnBlur = true;

		// Clear after a short delay so blur handler doesn't run a search
		setTimeout(() => {
			this.suppressChangeOnBlur = false;
		}, 50);

		// If user didn't pick from suggestions → just close dropdown
		if (!hasActiveSuggestion) {
			this.clearSuggestions();
			clearTimeout(this.debounceTimer);
			return;
		}

		return;
	}*/

	/**
	 * Creates the DOM container used to render autocomplete suggestions.
	 *
	 * Also annotates the input wrapper with helper CSS classes used for styling.
	 *
	 * @returns {void}
	 */
	createSuggestionsContainer() {
		const wrapper = this.inputElement.parentElement;

		// Ensure the wrapper has relative positioning to align the suggestions dropdown
		const computedStyle = window.getComputedStyle(wrapper);
		// Ensure the parent has relative positioning for dropdown alignment
		if (computedStyle.position === 'static') {
			wrapper.style.position = 'relative';
		}

		// --------------------------------------------
		// 1) ALWAYS add the base autocomplete wrapper class
		// --------------------------------------------
		wrapper.classList.add(`${this.prefix}-has-autocomplete`);

		// --------------------------------------------
		// 2) Detect extra children in the wrapper
		//    (anything other than input + autocomplete container)
		// --------------------------------------------
		const children = [...wrapper.children];
		const nonEssentialChildren = children.filter(child => {
			// Skip the input itself
			if (child === this.inputElement) {
				return false;
			}

			// Skip the autocomplete containers
			if (child.classList.contains(`${this.prefix}-autocomplete-suggestions`)) {
				return false;
			}

			// Skip elements that are absolutely positioned (they do not push layout)
			const style = window.getComputedStyle(child);
			if (style.position === 'absolute') {
				return false;
			}

			// Skip elements that are display:none
			if (style.display === 'none' || child.hidden) {
				return false;
			}

			// Skip elements that have no height (don’t affect layout)
			if (child.offsetHeight === 0) {
				return false;
			}

			// Everything else counts as an “extra child”
			return true;
		});

		if (nonEssentialChildren.length > 0) {
			wrapper.classList.add(`${this.prefix}-has-extra-children`);
		}

		// --------------------------------------------
		// Create the suggestions container
		// --------------------------------------------
		const inputId = this.inputElement?.id || Math.random().toString(36).slice(2);
		const container = document.createElement('div');
		container.id = `${this.prefix}-autocomplete-suggestions-${inputId}`;
		container.className = `${this.prefix}-autocomplete-suggestions`;
		container.style.display = 'none';

		this.inputElement.insertAdjacentElement('afterend', container);
		this.container = container;
	}

	/**
	 * Fetches autocomplete suggestions based on the latest input value.
	 *
	 * Ignores stale responses using a monotonic request ID and exits early when the
	 * instance was destroyed before the response resolves.
	 *
	 * @param {Event} event - The input event triggered by the user.
	 * @returns {Promise<void>}
	 */
	async requestAutocompleteSuggestions(event) {
		if (this._destroyed) {
			return;
		}

		const inputValue = event.target.value.trim();  // Get the input value and trim whitespace
		// If input is empty, clear the suggestions and exit the function
		if (!inputValue) {
			this.resetInteractionState();
			return this.clearSuggestions();
		}

		// Update the autocomplete options with the current input value
		this.options.input = inputValue;

		// Increment and capture the request ID to manage out-of-date requests
		const requestId = ++this.requestId;

		/**
		 * @event wpgeofw_address_autocomplete_fetch_request_before
		 * @description Fired before requesting suggestions from the API.
		 * @type {CustomEvent}
		 * @property {Object} detail.options - The current request options.
		 * @property {AddressAutocomplete} detail.instance - The autocomplete instance.
		 */
		document.dispatchEvent(new CustomEvent(`${this.prefix}_address_autocomplete_fetch_request_before`, {
			detail: { options: this.options, instance: this }
		}));

		try {

			// Fetch suggestions from Places API (New), falling back once to the
			// isolated legacy adapter when the new API is unavailable for this key.
			const { suggestions } = await this.fetchSuggestions();

			// Check if the response is valid
			if (!suggestions || !Array.isArray(suggestions)) {
				console.error("Invalid suggestions response:", suggestions);
				return;
			}

			// If the request ID doesn't match the latest, abort processing (this prevents out-of-date results)
			if (requestId !== this.requestId || this._destroyed || !(this.container instanceof Element)) {
				return;
			}

			// Attach the event handler for closing the autocomplete suggestions,
			// if it has not already been attached (this is for handling clicks outside the dropdown)
			if (!this._closeHandlerBound) {
				document.addEventListener('click', this.closeHandler);
				this._closeHandlerBound = true;
			}

			// Build and display the autocomplete suggestions dropdown
			this.buildSuggestionsDropdown(suggestions);
		} catch (error) {
			// Log any errors during the fetch operation
			console.error("Autocomplete Error:", error);
		}

		/**
		 * @event wpgeofw_address_autocomplete_fetch_request_after
		 * @description Fired after fetching suggestions from the API.
		 * @type {CustomEvent}
		 * @property {AddressAutocomplete} detail.instance - The autocomplete instance.
		 */
		document.dispatchEvent(new CustomEvent(`${this.prefix}_address_autocomplete_fetch_request_after`, {
			detail: { instance: this }
		}));
	}

	/**
	 * Fetch suggestions from the active Google Places provider.
	 *
	 * The modern provider remains primary. A legacy provider is selected only
	 * for errors that indicate Places API (New) is unavailable or unauthorized.
	 * That provider decision is shared by every autocomplete field on the page,
	 * while each field retains its own provider instance and lifecycle.
	 *
	 * @returns {Promise<Object>} Provider response containing suggestions.
	 */
	async fetchSuggestions() {
		this.autocompleteProvider = sharedAutocompleteProvider;

		if (sharedAutocompleteProvider === PLACES_LEGACY_PROVIDER) {
			return this.fetchLegacySuggestions();
		}

		// If another field is currently determining provider availability, wait
		// for that decision instead of issuing another new-API probe.
		if (sharedProviderProbe) {
			await sharedProviderProbe;
			this.autocompleteProvider = sharedAutocompleteProvider;

			if (sharedAutocompleteProvider === PLACES_LEGACY_PROVIDER) {
				return this.fetchLegacySuggestions();
			}

			return this.fetchPlacesNewSuggestions();
		}

		const placesNewRequest = this.fetchPlacesNewSuggestions();
		const providerProbe = placesNewRequest.then(
			() => PLACES_NEW_PROVIDER,
			(error) => {
				if (!shouldUseLegacyPlacesFallback(error)) {
					return PLACES_NEW_PROVIDER;
				}

				this.activateLegacyFallback(error);
				return PLACES_LEGACY_PROVIDER;
			}
		);

		sharedProviderProbe = providerProbe;

		try {
			return await placesNewRequest;
		} catch (error) {
			if (!shouldUseLegacyPlacesFallback(error)) {
				throw error;
			}

			// Wait for the shared decision so this request and any concurrent
			// field requests observe the same provider state.
			await providerProbe;
			return this.fetchLegacySuggestions(error);
		} finally {
			if (sharedProviderProbe === providerProbe) {
				sharedProviderProbe = null;
			}
		}
	}

	/**
	 * Request suggestions from Places API (New).
	 *
	 * @returns {Promise<Object>} Provider response containing suggestions.
	 */
	async fetchPlacesNewSuggestions() {
		const fetchSuggestions = globalThis.google?.maps?.places?.AutocompleteSuggestion?.fetchAutocompleteSuggestions;

		if (typeof fetchSuggestions !== 'function') {
			throw new TypeError('Google Places AutocompleteSuggestion is unavailable.');
		}

		return fetchSuggestions.call(
			globalThis.google.maps.places.AutocompleteSuggestion,
			this.options
		);
	}

	/**
	 * Select the legacy provider for every autocomplete field on this page.
	 *
	 * @param {*} error Places API (New) failure that triggered the fallback.
	 * @returns {void}
	 */
	activateLegacyFallback(error) {
		const wasLegacyProvider = sharedAutocompleteProvider === PLACES_LEGACY_PROVIDER;

		this.getLegacyAutocomplete(error);
		sharedAutocompleteProvider = PLACES_LEGACY_PROVIDER;
		this.autocompleteProvider = PLACES_LEGACY_PROVIDER;

		if (!wasLegacyProvider) {
			document.dispatchEvent(new CustomEvent(`${this.prefix}_address_autocomplete_legacy_fallback`, {
				detail: { error, instance: this }
			}));

			console.warn('Places API (New) is unavailable; using the temporary legacy autocomplete fallback.');
		}
	}

	/**
	 * Return this field's legacy provider instance.
	 *
	 * @param {*} [primaryError] Optional new-API failure for diagnostic context.
	 * @returns {LegacyPlacesAutocomplete} Legacy provider instance.
	 */
	getLegacyAutocomplete(primaryError = null) {
		if (this.legacyAutocomplete) {
			return this.legacyAutocomplete;
		}

		try {
			this.legacyAutocomplete = new LegacyPlacesAutocomplete();
		} catch (legacyError) {
			if (primaryError) {
				legacyError.primaryError = primaryError;
			}
			throw legacyError;
		}

		return this.legacyAutocomplete;
	}

	/**
	 * Request suggestions from this field's legacy provider instance.
	 *
	 * @param {*} [primaryError] Optional new-API failure for diagnostic context.
	 * @returns {Promise<Object>} Provider response containing suggestions.
	 */
	fetchLegacySuggestions(primaryError = null) {
		this.autocompleteProvider = PLACES_LEGACY_PROVIDER;
		return this.getLegacyAutocomplete(primaryError)
			.fetchAutocompleteSuggestions(this.options);
	}

	/**
	 * Renders the suggestions dropdown for autocomplete results.
	 *
	 * Exits safely when the instance was destroyed before render time.
	 *
	 * @param {Array} suggestions - Array of suggestions from Google Places API.
	 * @returns {void}
	 */
	buildSuggestionsDropdown(suggestions) {
		if (this._destroyed || !(this.container instanceof Element)) {
			return;
		}

		// Create a new dropdown container.
		const dropdown = document.createElement('ul');
		dropdown.className = `${this.prefix}-autocomplete-dropdown`;
		dropdown.style.maxWidth = this.inputElement.offsetWidth + 'px';

		// Iterate through suggestions to build list items.
		suggestions.forEach((suggestion, index) => {
			const placePrediction = suggestion.placePrediction;
			const li = document.createElement('li');
			li.textContent = placePrediction.text.toString();
			li.setAttribute('data-index', index);
			li.addEventListener('click', () => {
				const rawPrediction = placePrediction;
				const place = placePrediction.toPlace();
				this.handlePlaceSelection(place, rawPrediction, this.selectionContextId);
			});
			li.addEventListener('mouseenter', () => {
				[...dropdown.querySelectorAll('li')].forEach(item => item.classList.remove('active'));
				li.classList.add('active');
			});
			li.addEventListener('mouseleave', () => {
				li.classList.remove('active');
			});
			dropdown.appendChild(li);
		});

		// Empty existing container, append the new dropdown, and show it.
		this.container.innerHTML = '';
		this.container.appendChild(dropdown);
		// The compact custom dropdown must retain visible Google attribution.
		const attribution = document.createElement('div');
		attribution.className = `${this.prefix}-autocomplete-attribution`;
		attribution.setAttribute('translate', 'no');
		attribution.textContent = 'Google Maps';
		this.container.appendChild(attribution);
		this.container.style.display = '';

		// Enable keyboard navigation for the dropdown.
		this.addKeyboardNavigation(dropdown);
	}

	/**
	 * Enables keyboard navigation for the autocomplete dropdown.
	 *
	 * Rebinds the keydown navigation listener on the input so arrow keys, Enter,
	 * Escape, and Tab interact with the current dropdown instance only.
	 *
	 * @param {HTMLElement} dropdown - The dropdown container element for suggestions.
	 * @returns {void}
	 */
	addKeyboardNavigation($dropdown) {
		let selectedIndex = -1; // Tracks the selected item index in the dropdown
		const items = $dropdown.querySelectorAll("li"); // List items in the dropdown (native NodeList)

		// Remove any previous keydown event listener
		if (this._keydownHandler) {
			this.inputElement.removeEventListener('keydown', this._keydownHandler);
		}
		this._keydownHandler = (event) => {
			if (!items.length) {
				return;
			}

			switch (event.key) {
				case "ArrowDown":
					event.preventDefault();
					selectedIndex = (selectedIndex + 1) % items.length;
					items.forEach(el => el.classList.remove('active'));
					items[selectedIndex].classList.add('active');
					break;
				case "ArrowUp":
					event.preventDefault();
					selectedIndex = (selectedIndex - 1 + items.length) % items.length;
					items.forEach(el => el.classList.remove('active'));
					items[selectedIndex].classList.add('active');
					break;
				case "Enter":
					if (selectedIndex > -1) {
						event.preventDefault();
						items[selectedIndex].click();
					} else {
						this.clearSuggestions();
					}
					break;
				case "Escape":
				case "Tab":
					this.clearSuggestions();
					break;
			}
		};
		this.inputElement.addEventListener('keydown', this._keydownHandler);
	}

	/**
	 * Clears the visible suggestions dropdown and removes document-level close handlers.
	 *
	 * @returns {void}
	 */
	clearSuggestions() {
		if (this.container && this.container.style.display !== 'none') {
			this.container.innerHTML = '';
			this.container.style.display = 'none';
		}
		if (this._keydownHandler) {
			this.inputElement.removeEventListener('keydown', this._keydownHandler);
			this._keydownHandler = null;
		}
		this.cleanupAutocomplete();
	}

	/**
	 * Removes document-level close handling for the current autocomplete session.
	 *
	 * @returns {void}
	 */
	cleanupAutocomplete() {
		document.removeEventListener('click', this.closeHandler);
		this._closeHandlerBound = false;
	}

	/**
	 * Sets up the document click handler used to close suggestions when clicking
	 * outside the input or dropdown.
	 *
	 * @returns {void}
	 */
	setupCloseHandler() {
		this.closeHandler = (event) => {
			const target = event.target;
			if (target !== this.inputElement && !this.container.contains(target)) {
				this.clearSuggestions();
				document.removeEventListener('click', this.closeHandler);
			}
		};
	}

	/**
	 * Binds input, blur, and dropdown mouse interactions for suggestion fetching and closing.
	 *
	 * @returns {void}
	 */
	bindAutocompleteEvents() {
		const input = this.inputElement;

		// Input event with debounce
		input.removeEventListener('input', this._inputHandler);
		this._inputHandler = (event) => {
			clearTimeout(this.debounceTimer);
			this.selectionContextId += 1;
			this.userSelected = false;
			const delay = this.args.debounceDelay || 200;
			this.debounceTimer = setTimeout(() => {
				this.requestAutocompleteSuggestions(event);
			}, delay);
		};
		input.addEventListener('input', this._inputHandler);

		// Blur / focusout behavior
		input.removeEventListener('blur', this._blurHandler);

		this._blurHandler = () => {
			this._blurTimer = setTimeout(() => {

				// Always clear dropdown
				clearTimeout(this.debounceTimer);
				this.clearSuggestions();

				// If user DID NOT select a suggestion → suppress blur-triggered change()
				if (!this.userSelected) {
					this.suppressChangeOnBlur = true;
					setTimeout(() => (this.suppressChangeOnBlur = false), 50);
				}

				// Reset for next interaction
				this.userSelected = false;

			}, 200);
		};

		input.addEventListener('blur', this._blurHandler);

		// Mousedown on container to prevent blur clear
		this.container.removeEventListener('mousedown', this._mousedownHandler);
		this._mousedownHandler = () => {
			clearTimeout(this._blurTimer);
		};
		this.container.addEventListener('mousedown', this._mousedownHandler);
	}

	/**
	 * Handles selection of a place from the autocomplete suggestions.
	 *
	 * Fetches the configured place fields, updates the input value, dispatches
	 * selection events, and emits a `place_changed` event on the input element.
	 *
	 * @param {Object} place - The selected place object from Google Places API.
	 * @param {Object|null} rawPrediction - Raw prediction metadata used to preserve the user-visible label.
	 * @returns {Promise<void>}
	 */
	async handlePlaceSelection(place, rawPrediction = null, contextId = this.selectionContextId) {

		/**
		 * @event wpgeofw_address_autocomplete_place_selection_before
		 * @description Fired before place data is processed and set in the input.
		 * @type {CustomEvent}
		 * @property {Object} detail.place - The place object selected.
		 * @property {AddressAutocomplete} detail.instance - The autocomplete instance.
		 */
		document.dispatchEvent(new CustomEvent(`${this.prefix}_address_autocomplete_place_selection_before`, {
			detail: { place, instance: this }
		}));

		clearTimeout(this.debounceTimer);

		if (!place) {
			console.error("Invalid place object:", place);
			return;
	}

		this.pendingSelections += 1;
		try {
			const userSelectedLabel = rawPrediction?.text?.toString() || '';

			// Fetch fields and ensure the place object is ready
			await place.fetchFields({
				fields: this.args.fetchFields
			});

			if (this._destroyed || !this.inputElement.isConnected || contextId !== this.selectionContextId) {
				return;
			}

			// Ensure the expected field exists in the place object
			if (!place[this.args.outputField]) {
				console.warn(`No ${this.args.outputField} field found for selected place.`);
				return;
			}

			this.userSelected = true;

			// Use the user-selected label if available, otherwise fallback
			this.inputElement.value = userSelectedLabel || place[this.args.outputField];

			// Clear suggestions
			this.clearSuggestions();

			// Trigger event after selection
			/**
			 * @event wpgeofw_address_autocomplete_place_selection_after
			 * @description Fired after a place is selected and input value is updated.
			 * @type {CustomEvent}
			 * @property {string} detail.value - The final input value.
			 * @property {Object} detail.place - The selected place object.
			 * @property {AddressAutocomplete} detail.instance - The autocomplete instance.
			 */
			document.dispatchEvent(new CustomEvent(`${this.prefix}_address_autocomplete_place_selection_after`, {
				detail: { value: this.inputElement.value, place, instance: this }
			}));

			// Trigger custom 'place_changed' event on the input
			this.inputElement.dispatchEvent(new CustomEvent('place_changed', {
				detail: {
					selected: this.inputElement.value,
					place: place
				}
			}));

			if (!this.suppressChangeOnBlur) {
				this.inputElement.dispatchEvent(new Event('change', { bubbles: true }));
			}

			// Refresh the autocomplete token
			this.refreshAutocompleteToken(this.options);

		} catch (error) {
			console.error("Error fetching place fields:", error);
		} finally {
			this.pendingSelections -= 1;
		}
    }

	/**
	 * Generates a new session token for the current autocomplete session and assigns it
	 * to the provided request object. This ensures billing and prediction continuity
	 * for grouped autocomplete and place fetch requests.
	 *
	 * @param {Object} request - The autocomplete options object used in the fetch.
	 * @returns {void}
	 */
	refreshAutocompleteToken(request) {
		request.sessionToken = new google.maps.places.AutocompleteSessionToken();
	}

	/**
	 * Tears down autocomplete listeners and DOM artifacts.
	 *
	 * Safe to call multiple times when fields are reinitialized or destroyed.
	 *
	 * @returns {void}
	 */
	destroy() {
		this._destroyed = true;
		this.requestId++;
		this.legacyAutocomplete?.destroy();

		clearTimeout(this.debounceTimer);
		clearTimeout(this._blurTimer);

		if (this._focusInHandler) {
			this.inputElement.removeEventListener('focusin', this._focusInHandler);
		}
		if (this._preventSubmitHandler) {
			this.inputElement.removeEventListener('keydown', this._preventSubmitHandler);
		}
		if (this._inputHandler) {
			this.inputElement.removeEventListener('input', this._inputHandler);
		}
		if (this._blurHandler) {
			this.inputElement.removeEventListener('blur', this._blurHandler);
		}
		if (this._keydownHandler) {
			this.inputElement.removeEventListener('keydown', this._keydownHandler);
		}
		if (this._mousedownHandler) {
			this.container?.removeEventListener('mousedown', this._mousedownHandler);
		}

		this.cleanupAutocomplete();

		if (this.container?.parentNode) {
			this.container.parentNode.removeChild(this.container);
		}

		this.container = null;
		this.closeHandler = null;
		this._closeHandlerBound = false;
		this._focusInHandler = null;
		this._preventSubmitHandler = null;
		this._inputHandler = null;
		this._blurHandler = null;
		this._keydownHandler = null;
		this._mousedownHandler = null;
		this.legacyAutocomplete = null;
	}

	/**
	 * Invalidate in-flight interaction state after an address is cleared or reset.
	 *
	 * @since 1.0.0
	 *
	 * @returns {void}
	 */
	resetInteractionState() {
		this.selectionContextId += 1;
		this.requestId += 1;
		this.userSelected = false;
		this.options.input = '';
		clearTimeout(this.debounceTimer);
	}
}
