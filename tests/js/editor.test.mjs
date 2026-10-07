/** Exercise the compiled native editor adapter and its bundled Choices. */
import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { createRequire } from 'node:module';
const require = createRequire(import.meta.url);
const { JSDOM } = require('jsdom');
const bundle = readFileSync(
	new URL('../../build/js/admin/frmgeo-form-editor.min.js', import.meta.url),
	'utf8'
);
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

test('keyboard focus uses native tooltip initialization once and stays scoped', async () => {
	const dom = new JSDOM(
		'<span class="frmgeoac-tooltip frm_help" title="Address selection help" tabindex="0"></span><span class="frm_help" title="Native help" tabindex="0"></span>',
		{ runScripts: 'outside-only', pretendToBeVisual: true }
	);
	try {
		const calls = [];
		const { window } = dom;
		window.jQuery = (element) => ({
			trigger(event) {
				calls.push(event);
				element.removeAttribute('title');
			},
		});
		window.eval(bundle);
		const help = window.document.querySelector('.frmgeoac-tooltip');
		help.dispatchEvent(new window.FocusEvent('focusin', { bubbles: true }));
		help.dispatchEvent(new window.FocusEvent('focusin', { bubbles: true }));
		window.document.querySelector('.frm_help:not(.frmgeoac-tooltip)').dispatchEvent(
			new window.FocusEvent('focusin', { bubbles: true })
		);
		assert.deepEqual(calls, ['mouseenter.frm']);
		assert.equal(window.document.querySelector('.frm_help:not(.frmgeoac-tooltip)').title, 'Native help');
	} finally {
		dom.window.close();
	}
});

test('native group and AJAX-loaded Choices retain field option values', async () => {
	const dom = new JSDOM(
		'<div id="frm-insert-fields"><ul><li class="frmbutton" id="frmgeo_address">Address</li><li class="frmbutton frm_show_upgrade" id="frmgeo_map">Map</li></ul><h3 class="frm-with-line">Pricing</h3></div><div id="frmgeo-geolocation-fields-holder"><h3 class="frm-with-line">Geolocation Fields</h3><ul class="frmgeo-fields-container"></ul></div><div class="frmgeoac-options"><p data-option="frmgeo_enable_address_autocomplete"><input type="checkbox" checked></p><p data-option="frmgeo_address_autocomplete_country"><select name="field_options[frmgeo_address_autocomplete_country_1][]" class="frmgeoac-multiple" multiple><option value="US" selected>United States</option><option value="IL">Israel</option></select></p><p data-option="frmgeo_address_autocomplete_types"><select name="field_options[frmgeo_address_autocomplete_types_1][]" class="frmgeoac-multiple" multiple><option value="street_address" selected>Street addresses</option></select></p></div>',
		{ runScripts: 'outside-only', pretendToBeVisual: true }
	);
	try {
		const { window } = dom;
		window.matchMedia = () => ({ matches: false, addListener() {}, removeListener() {} });
		window.eval(bundle);
		await sleep(100);
		assert.equal(window.document.querySelectorAll('.frmgeo-fields-container .frmbutton').length, 2);
		assert.equal(
			window.document.querySelector('#frm-insert-fields > h3').textContent,
			'Geolocation Fields'
		);
		assert.equal(window.document.querySelectorAll('.choices').length, 2);
		const country = window.document.querySelector('select');
		assert.deepEqual(
			Array.from(country.selectedOptions, (o) => o.value),
			['US']
		);
		country.choices.setChoiceByValue('IL');
		assert.deepEqual(
			Array.from(country.selectedOptions, (o) => o.value),
			['US', 'IL']
		);
		window.FRMGEOAC_FormEditor.refreshOptions();
		await sleep(60);
		assert.equal(window.document.querySelectorAll('.choices').length, 2);
		assert.equal(window.document.querySelectorAll('.frmgeo-fields-container .frmbutton').length, 2);
		assert.equal(window.document.querySelector('details'), null);
		const replacement = country.closest('p').cloneNode(false);
		replacement.innerHTML =
			'<select name="field_options[frmgeo_address_autocomplete_country_2][]" class="frmgeoac-multiple" multiple><option selected value="GB">United Kingdom</option></select>';
		country.closest('p').replaceWith(replacement);
		await sleep(100);
		assert.equal(window.document.querySelectorAll('.choices').length, 2);
		assert.equal(
			replacement
				.querySelector('.choices__item[data-value="GB"]')
				?.textContent.includes('United Kingdom'),
			true
		);
	} finally {
		dom.window.close();
	}
});
