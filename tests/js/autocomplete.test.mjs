/** Actual compiled-bundle tests with a DOM and mock Google service. */
import test from 'node:test';
import assert from 'node:assert/strict';
import { createRequire } from 'node:module';
import { readFileSync } from 'node:fs';
import { buildAddressOptions, isEnabled } from '../../assets/js/autocomplete/address-options.js';
import {
	buildLegacyPlaceFields,
	shouldUseLegacyPlacesFallback,
} from '../../assets/js/autocomplete/services/compat/places-autocomplete.js';
const require = createRequire(import.meta.url);
const { JSDOM } = require('jsdom');
const bundle = readFileSync(
	new URL('../../build/js/frontend/address-autocomplete.min.js', import.meta.url),
	'utf8'
);
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const parts = [
	['street_number', '350'],
	['route', '5th Avenue'],
	['locality', 'Brooklyn'],
	['administrative_area_level_1', 'New York', 'NY'],
	['postal_code', '11215'],
	['country', 'United States', 'US'],
].map(([type, longText, shortText = longText]) => ({ types: [type], longText, shortText }));

async function fixture({ duplicate = false, loading = false, failed = false } = {}) {
	const custom =
		'<form class="frm-show-form"><input name="form_id" value="7" type="hidden"><div id="frm_field_11_container"><input name="item_meta[11]" type="text" value="Saved address"></div><div id="frm_field_12_container"><input name="item_meta[12][line1]" type="text"><input name="item_meta[12][line2]" value="Unit 7"><input name="item_meta[12][city]"><input name="item_meta[12][state]"><input name="item_meta[12][zip]"><select name="item_meta[12][country]"><option value=""></option><option data-code="US" value="United States">USA</option></select></div><div class="frm_submit"><button type="submit">Submit</button></div></form>';
	const extra = duplicate ? custom : '';
	const dom = new JSDOM(custom + extra, {
		runScripts: 'outside-only',
		url: 'https://fixture.test/',
	});
	const { window } = dom;
	const state = { requests: [], alerts: [], values: [], delay: null };
	window.alert = (message) => state.alerts.push(message);

	let release;
	const ready = new Promise((resolve) => {
		release = resolve;
	});
	state.release = release;
	window.console.warn = () => {};
	window.google = {
		maps: {
			importLibrary: async () => {
				if (failed) throw new Error('API unavailable');
				if (loading) await ready;
			},
			places: {
				AutocompleteSessionToken: class {},
				AutocompleteSuggestion: {
					fetchAutocompleteSuggestions: async (options) => {
						state.requests.push(options);
						return {
							suggestions: [
								{
									placePrediction: {
										text: { toString: () => '350 5th Avenue, Brooklyn, NY' },
										toPlace: () => ({
											formattedAddress: '350 5th Avenue, Brooklyn, NY',
											addressComponents: parts,
											fetchFields: async () => {
												if (state.delay) await state.delay;
											},
										}),
									},
								},
							],
						};
					},
				},
			},
		},
	};
	window.frmgeoAutocompleteForms = {
		7: {
			formId: 7,
			config: { disableGoogleApi: true },
			fields: [
				{
					id: 11,
					type: 'frmgeo_address',
					frmgeo_enable_address_autocomplete: 1,
					frmgeo_force_suggested_address: 1,
				},
				{
					id: 12,
					type: 'address',
					frmgeo_enable_address_autocomplete: 1,
					frmgeo_force_suggested_address: 1,
				},
			],
		},
	};
	window.eval(bundle);
	await sleep(35);
	return { dom, window, state };
}
const customInput = (window) => window.document.querySelector('#frm_field_11_container input');

test('native hyphenated repeater rows bind independently after AJAX insertion', async () => {
	const { dom, window, state } = await fixture();
	try {
		const root = window.document.querySelector('form');
		root.insertAdjacentHTML(
			'beforeend',
			'<div id="frm_field_11-109-0_container"><input type="text" name="item_meta[109][0][11]"></div><div id="frm_field_11-109-1_container"><input type="text" name="item_meta[109][1][11]"></div>'
		);
		await sleep(100);
		const rows = root.querySelectorAll('[id^="frm_field_11-"]');
		await suggest(window, rows[0].querySelector('input'));
		rows[0].querySelector('li').click();
		await sleep(30);
		await suggest(window, rows[1].querySelector('input'));
		rows[1].querySelector('input').dispatchEvent(new window.Event('blur'));
		await sleep(200);
		assert.equal(rows[0].querySelector('input').value, '350 5th Avenue, Brooklyn, NY');
		assert.equal(rows[1].querySelector('input').value, '');
		assert.equal(state.alerts.length, 1);
	} finally {
		dom.window.close();
	}
});
async function suggest(window, input) {
	input.focus();
	input.value = '350 Fifth';
	input.dispatchEvent(new window.Event('input', { bubbles: true }));
	await sleep(250);
	assert.ok(input.parentElement.querySelector('li'), 'Suggestions rendered');
}

test('committing a suggestion does not reopen predictions over the submit button', async () => {
	const { dom, window, state } = await fixture();
	try {
		const input = customInput(window);
		await suggest(window, input);
		const requests = state.requests.length;
		input.parentElement.querySelector('li').click();
		await sleep(300);
		assert.equal(state.requests.length, requests);
		assert.equal(input.parentElement.querySelector('li'), null);
	} finally {
		dom.window.close();
	}
});

test('bundle contains only local autocomplete modules and no engine', () => {
	const map = JSON.parse(
		readFileSync(
			new URL('../../build/js/frontend/address-autocomplete.min.js.map', import.meta.url)
		)
	);
	assert.ok(map.sources.every((source) => source.includes('/assets/js/autocomplete/')));
	assert.ok(!bundle.includes('FormCore') && !bundle.includes('WPGeoFW'));
});
test('explicit flags and country/language/bounds controls', () => {
	assert.equal(isEnabled('false'), false);
	const options = buildAddressOptions(
		{
			frmgeo_address_autocomplete_country: ['US', 'IL', 'US'],
			frmgeo_address_autocomplete_language: 'he',
			frmgeo_autocomplete_restriction_usage: 'area_bounds',
			frmgeo_autocomplete_bounds_sw_point: '20,-130',
			frmgeo_autocomplete_bounds_ne_point: '50,-70',
			frmgeo_address_autocomplete_strict_bounds: 1,
		},
		{ regionCode: 'IL', languageCode: 'en' }
	);
	assert.deepEqual(options.includedRegionCodes, ['US', 'IL']);
	assert.equal(options.language, 'he');
	assert.deepEqual(options.locationRestriction, { south: 20, west: -130, north: 50, east: -70 });
});
test('invalid proximity ignored; valid bias stays local to autocomplete', () => {
	const field = {
		frmgeo_autocomplete_restriction_usage: 'proximity',
		frmgeo_autocomplete_proximity_lat: '32',
		frmgeo_autocomplete_proximity_lng: '34',
		frmgeo_autocomplete_proximity_radius: '500',
	};
	assert.deepEqual(buildAddressOptions(field).locationBias, {
		center: { lat: 32, lng: 34 },
		radius: 500,
	});
	field.frmgeo_autocomplete_proximity_radius = '999999';
	assert.equal(buildAddressOptions(field).locationBias, undefined);
});
test('legacy fallback does not mask billing or referrer failures', () => {
	assert.deepEqual(buildLegacyPlaceFields(['formattedAddress']), ['formatted_address']);
	assert.equal(shouldUseLegacyPlacesFallback({ code: 'REQUEST_DENIED' }), false);
});
test('saved value preserved; selected suggestion synchronizes native values', async () => {
	const { dom, window, state } = await fixture();
	try {
		const input = customInput(window);
		assert.equal(input.value, 'Saved address');
		await suggest(window, input);
		input.parentElement.querySelector('li').click();
		await sleep(20);
		assert.equal(input.value, '350 5th Avenue, Brooklyn, NY');

		assert.equal(state.alerts.length, 0);
	} finally {
		dom.window.close();
	}
});
test('unselected typed value clears and alerts on blur', async () => {
	const { dom, window, state } = await fixture();
	try {
		const input = customInput(window);
		input.value = 'Unselected address';
		input.dispatchEvent(new window.Event('blur'));
		await sleep(230);
		assert.equal(input.value, '');
		assert.equal(state.alerts.length, 1);
	} finally {
		dom.window.close();
	}
});
test('submission blocked before serialization; valid retry succeeds', async () => {
	const { dom, window, state } = await fixture();
	try {
		const input = customInput(window),
			form = input.closest('form');
		input.value = 'Unselected';
		assert.equal(
			form.dispatchEvent(new window.Event('submit', { bubbles: true, cancelable: true })),
			false
		);
		assert.equal(state.alerts.length, 1);
		await suggest(window, input);
		input.parentElement.querySelector('li').click();
		await sleep(20);
		assert.equal(
			form.dispatchEvent(new window.Event('submit', { bubbles: true, cancelable: true })),
			true
		);
	} finally {
		dom.window.close();
	}
});
test('hidden conditional input is not cleared', async () => {
	const { dom, window, state } = await fixture();
	try {
		const input = customInput(window);
		input.value = 'Conditional';
		input.parentElement.style.display = 'none';
		assert.equal(
			input
				.closest('form')
				.dispatchEvent(new window.Event('submit', { bubbles: true, cancelable: true })),
			true
		);
		assert.equal(input.value, 'Conditional');
		assert.equal(state.alerts.length, 0);
	} finally {
		dom.window.close();
	}
});
test('duplicate embeds bind independently', async () => {
	const { dom, window } = await fixture({ duplicate: true });
	try {
		assert.equal(window.document.querySelectorAll('.frmgeo-autocomplete-suggestions').length, 4);
		const inputs = Array.from(
			window.document.querySelectorAll('[id="frm_field_11_container"] input')
		);
		await suggest(window, inputs[1]);
		inputs[1].parentElement.querySelector('li').click();
		await sleep(20);
		assert.equal(inputs[0].value, 'Saved address');
		assert.equal(inputs[1].value, '350 5th Avenue, Brooklyn, NY');
	} finally {
		dom.window.close();
	}
});
test('AJAX replacement tears down old binding and initializes the new input', async () => {
	const { dom, window } = await fixture();
	try {
		const input = customInput(window),
			clone = input.cloneNode();
		input.replaceWith(clone);
		await sleep(100);
		await suggest(window, clone);
		clone.parentElement.querySelector('li').click();
		await sleep(20);
		assert.equal(clone.value, '350 5th Avenue, Brooklyn, NY');
	} finally {
		dom.window.close();
	}
});
test('native Address autofill preserves apartment and resolves country ISO data', async () => {
	const { dom, window } = await fixture();
	try {
		const wrapper = window.document.querySelector('#frm_field_12_container');
		const input = wrapper.querySelector('[name$="[line1]"]');
		await suggest(window, input);
		input.parentElement.querySelector('li').click();
		await sleep(20);
		assert.equal(input.value, '350 5th Avenue');
		assert.equal(wrapper.querySelector('[name$="[line2]"]').value, 'Unit 7');
		assert.equal(wrapper.querySelector('[name$="[city]"]').value, 'Brooklyn');
		assert.equal(wrapper.querySelector('[name$="[zip]"]').value, '11215');
		assert.equal(wrapper.querySelector('[name$="[country]"]').value, 'United States');
	} finally {
		dom.window.close();
	}
});

test('typed edits cannot bypass required selection while Google loads', async () => {
	const { dom, window, state } = await fixture({ loading: true });
	try {
		const input = customInput(window);
		input.value = 'Typed before API ready';
		assert.equal(
			input
				.closest('form')
				.dispatchEvent(new window.Event('submit', { bubbles: true, cancelable: true })),
			false
		);
		assert.equal(input.value, '');
		assert.equal(state.alerts.length, 1);
		state.release();
		await sleep(30);
		await suggest(window, input);
		input.parentElement.querySelector('li').click();
		await sleep(20);
		assert.equal(
			input
				.closest('form')
				.dispatchEvent(new window.Event('submit', { bubbles: true, cancelable: true })),
			true
		);
	} finally {
		dom.window.close();
	}
});
test('API failure preserves defaults but enforces selection for new edits', async () => {
	const { dom, window, state } = await fixture({ failed: true });
	try {
		const input = customInput(window);
		assert.equal(input.value, 'Saved address');
		assert.equal(
			input
				.closest('form')
				.dispatchEvent(new window.Event('submit', { bubbles: true, cancelable: true })),
			true
		);
		input.value = 'Unverified edit';
		assert.equal(
			input
				.closest('form')
				.dispatchEvent(new window.Event('submit', { bubbles: true, cancelable: true })),
			false
		);
		assert.equal(input.value, '');
		assert.equal(state.alerts.length, 1);
	} finally {
		dom.window.close();
	}
});
test('pending place details block submission without clearing a chosen suggestion', async () => {
	const { dom, window, state } = await fixture();
	try {
		let release;
		state.delay = new Promise((resolve) => {
			release = resolve;
		});
		const input = customInput(window);
		await suggest(window, input);
		input.parentElement.querySelector('li').click();
		assert.equal(
			input
				.closest('form')
				.dispatchEvent(new window.Event('submit', { bubbles: true, cancelable: true })),
			false
		);
		assert.equal(state.alerts.length, 0);
		release();
		await sleep(30);
		assert.equal(input.value, '350 5th Avenue, Brooklyn, NY');
		assert.equal(
			input
				.closest('form')
				.dispatchEvent(new window.Event('submit', { bubbles: true, cancelable: true })),
			true
		);
	} finally {
		dom.window.close();
	}
});

test('previous-page and save-draft submits do not enforce final selection', async () => {
	const { dom, window, state } = await fixture();
	try {
		const input = customInput(window);
		input.value = 'Still being edited';
		const button = window.document.createElement('button');
		button.className = 'frm_prev_page';
		input.closest('form').appendChild(button);
		const event = new window.Event('submit', { bubbles: true, cancelable: true });
		Object.defineProperty(event, 'submitter', { value: button });
		assert.equal(input.closest('form').dispatchEvent(event), true);
		assert.equal(input.value, 'Still being edited');
		assert.equal(state.alerts.length, 0);
	} finally {
		dom.window.close();
	}
});
