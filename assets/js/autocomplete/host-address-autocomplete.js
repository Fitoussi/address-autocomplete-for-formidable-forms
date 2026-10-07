/**
 * Standalone Formidable Forms adapter. Owns only input bindings and host value sync.
 * No framework FormCore, geocoder, map, locator or paid service is initialized.
 * @since 1.0.0
 */
import { AddressAutocomplete } from './services/address-autocomplete.js';
import { buildAddressOptions, isEnabled } from './address-options.js';
import { loadGooglePlaces } from './google-places-loader.js';
import { populateNativeAddress } from './native-address.js';

const bindings = new Map();
const pending = new Map();

/**
 * Check whether an enabled physical input participates in the visible form.
 *
 * @since 1.0.0
 * @param {HTMLInputElement} input Address input.
 * @returns {boolean} Whether selection validation applies to this input.
 */
export function isActiveInput(input) {
	if (!input.isConnected || input.disabled) return false;
	for (let el = input; el && el !== input.form; el = el.parentElement) {
		const style = getComputedStyle(el);
		if (el.hidden || style.display === 'none' || style.visibility === 'hidden') return false;
	}
	return true;
}

/**
 * Notify native conditional logic after input population.
 *
 * @since 1.0.0
 * @param {HTMLInputElement} input Address input with the committed value.
 * @returns {void}
 */
function syncInput(input) {
	// A committed value is a change, not new typing: input would reopen predictions.
	input.dispatchEvent(new Event('change', { bubbles: true }));
}

/**
 * Reject new unselected edits while preserving defaults during API initialization.
 *
 * @since 1.0.0
 * @param {HTMLInputElement} input Address input.
 * @param {{field: Object, value: string}} state Initial field value and preferences.
 * @returns {boolean} Whether submission or blur may proceed.
 */
function validatePending(input, state) {
	if (
		!isActiveInput(input) ||
		!isEnabled(state.field.frmgeo_force_suggested_address) ||
		!input.value.trim() ||
		input.value === state.value
	)
		return true;
	input.value = '';
	state.value = '';
	syncInput(input);
	globalThis.alert(
		state.field.frmgeo_force_suggested_address_message ||
			'Please select an address from the suggested results.'
	);
	input.focus();
	return false;
}
/**
 * Validate only physical inputs belonging to the requested form or container.
 *
 * @since 1.0.0
 * @param {Element} root Native form instance being submitted.
 * @returns {boolean} Whether every active Address input permits submission.
 */
export function validateSelection(root) {
	for (const [input, state] of pending) {
		if (root?.contains(input) && !validatePending(input, state)) return false;
	}
	for (const [input, binding] of bindings) {
		if (root?.contains(input) && !binding.enforceSelection(true)) return false;
	}
	return true;
}

/**
 * Attach one service per physical input and preserve saved/default values.
 *
 * @since 1.0.0
 * @param {HTMLInputElement} input Address input being initialized.
 * @param {Element} wrapper Host field wrapper for native component population.
 * @param {Object} field Whitelisted, premium-compatible field preferences.
 * @param {Object} config Browser API and localization preferences.
 * @returns {Promise<void>} Resolves after binding or installing failure validation.
 */
async function bindInput(input, wrapper, field, config) {
	const signature = JSON.stringify({ field, config });
	if (bindings.get(input)?.signature === signature || pending.has(input)) return;
	bindings.get(input)?.destroy();
	const initial = { field, value: input.value };
	pending.set(input, initial);
	let loadingBlurTimer;
	const loadingBlur = () => {
		loadingBlurTimer = setTimeout(() => {
			if (pending.has(input)) validatePending(input, initial);
		}, 200);
	};
	input.addEventListener('blur', loadingBlur);
	try {
		await loadGooglePlaces(config);
		if (!input.isConnected) return;
		const service = new AddressAutocomplete(
			{
				inputElement: input,
				prefix: 'frmgeo',
				fetchFields:
					field.type === 'address'
						? ['formattedAddress', 'addressComponents']
						: ['formattedAddress'],
				debounceDelay: 200,
			},
			buildAddressOptions(field, config)
		);
		let selectedValue = initial.value;
		let blurTimer;
		const selected = (event) => {
			if (field.type === 'address') populateNativeAddress(wrapper, event.detail.place);
			clearTimeout(blurTimer);
			selectedValue = input.value;
			syncInput(input);
		};
		const enforceSelection = (submitting = false) => {
			clearTimeout(blurTimer);
			if (
				!isActiveInput(input) ||
				!isEnabled(field.frmgeo_force_suggested_address) ||
				!input.value.trim() ||
				input.value === selectedValue
			)
				return true;
			if (service.pendingSelections > 0) {
				if (!submitting) blurTimer = setTimeout(enforceSelection, 100);
				return false;
			}
			input.value = '';
			selectedValue = '';
			service.resetInteractionState();
			service.clearSuggestions();
			syncInput(input);
			globalThis.alert(
				field.frmgeo_force_suggested_address_message ||
					'Please select an address from the suggested results.'
			);
			input.focus();
			return false;
		};
		const blurred = () => {
			clearTimeout(blurTimer);
			blurTimer = setTimeout(enforceSelection, 200);
		};
		const focused = () => clearTimeout(blurTimer);
		const choosing = (event) => {
			if (event.target.closest('li')) {
				event.preventDefault();
				clearTimeout(blurTimer);
			}
		};
		input.addEventListener('place_changed', selected);
		input.addEventListener('blur', blurred);
		input.addEventListener('focus', focused);
		service.container.addEventListener('mousedown', choosing);
		bindings.set(input, {
			signature,
			enforceSelection,
			destroy() {
				clearTimeout(blurTimer);
				input.removeEventListener('place_changed', selected);
				input.removeEventListener('blur', blurred);
				input.removeEventListener('focus', focused);
				service.container.removeEventListener('mousedown', choosing);
				service.destroy();
				bindings.delete(input);
			},
		});
		if (input.value !== initial.value && document.activeElement === input) {
			input.dispatchEvent(new Event('input', { bubbles: true }));
		}
	} catch (error) {
		console.warn('[Address Autocomplete] Initialization failed:', error.message);
		// Keep require-selection honest when the API fails, while preserving saved values.
		let failureTimer;
		const enforceSelection = () => validatePending(input, initial);
		const failedBlur = () => {
			clearTimeout(failureTimer);
			failureTimer = setTimeout(enforceSelection, 200);
		};
		input.addEventListener('blur', failedBlur);
		bindings.set(input, {
			signature,
			enforceSelection,
			destroy() {
				clearTimeout(failureTimer);
				input.removeEventListener('blur', failedBlur);
				bindings.delete(input);
			},
		});
	} finally {
		clearTimeout(loadingBlurTimer);
		input.removeEventListener('blur', loadingBlur);
		pending.delete(input);
	}
}

/**
 * Resolve each mounted Formidable instance without global field ID lookups.
 *
 * @since 1.0.0
 * @returns {void}
 */
export function initializeAutocomplete() {
	for (const [input, binding] of bindings) if (!input.isConnected) binding.destroy();
	for (const data of Object.values(globalThis.frmgeoAutocompleteForms || {})) {
		const roots = Array.from(document.querySelectorAll('form.frm-show-form')).filter(
			(form) => form.querySelector('input[name="form_id"]')?.value === String(data.formId)
		);
		for (const root of roots) {
			for (const field of data.fields) {
				if (!isEnabled(field.frmgeo_enable_address_autocomplete)) continue;
				// Flat wrappers use an underscore; repeater rows use a hyphen.
				const actual = root.querySelectorAll(
					'[id^="frm_field_' + field.id + '_"], [id^="frm_field_' + field.id + '-"]'
				);
				for (const wrapper of actual) {
					const input =
						field.type === 'address'
							? wrapper.querySelector('[name$="[line1]"]')
							: wrapper.querySelector('input[type="text"]');
					if (input && !input.disabled) bindInput(input, wrapper, field, data.config);
				}
			}
		}
	}
}

/**
 * Initialize after native rendering and observe physical input replacements.
 *
 * @since 1.0.0
 * @returns {void}
 */
function boot() {
	initializeAutocomplete();
	globalThis
		.jQuery?.(document)
		.on(
			'frmFormComplete.frmgeoac frmPageChanged.frmgeoac frmAfterAddRow.frmgeoac frmAfterAddRepeaterRow.frmgeoac',
			initializeAutocomplete
		);
	document.addEventListener(
		'submit',
		(event) => {
			if (
				event.submitter?.matches(
					'.frm_prev_page, .frm_save_draft, [name="frm_prev_page"], [name="frm_save_draft"]'
				)
			)
				return;
			if (!validateSelection(event.target)) {
				event.preventDefault();
				event.stopImmediatePropagation();
			}
		},
		true
	);
	document.addEventListener(
		'click',
		(event) => {
			const button = event.target.closest('.frm_submit button, .frm_submit input[type="submit"]');
			if (
				button?.matches(
					'.frm_prev_page, .frm_save_draft, [name="frm_prev_page"], [name="frm_save_draft"]'
				)
			)
				return;
			const root = button?.closest('form.frm-show-form');
			if (root && !validateSelection(root)) {
				event.preventDefault();
				event.stopImmediatePropagation();
			}
		},
		true
	);
	let refresh;
	new MutationObserver((records) => {
		const changed =
			records.some((record) =>
				Array.from(record.addedNodes).some(
					(node) => node.nodeType === 1 && (node.matches('input') || node.querySelector('input'))
				)
			) || Array.from(bindings.keys()).some((input) => !input.isConnected);
		if (changed) {
			clearTimeout(refresh);
			refresh = setTimeout(initializeAutocomplete, 50);
		}
	}).observe(document.body, { childList: true, subtree: true });
}

if (document.readyState === 'loading')
	document.addEventListener('DOMContentLoaded', boot, { once: true });
else boot();
