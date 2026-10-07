/**
 * Native Formidable builder presentation, with no premium field engine.
 * @since 1.0.0
 */
import Choices from './vendor/choices.js';

// The host initializes help tooltips on first hover. Let keyboard users reach
// that same native initialization on focus; subsequent focus uses the host's
// own handlers once it has consumed the title attribute.
document.addEventListener('focusin', (event) => {
	const help = event.target;
	if (help.matches?.('.frmgeoac-tooltip[title]') && window.jQuery) {
		window.jQuery(help).trigger('mouseenter.frm');
	}
});

/**
 * Enhance supported multi-selects once, retaining their native submitted values.
 *
 * @since 1.0.0
 * @returns {void}
 */
export function enhanceSelects() {
	const strings = window.frmgeoacEditor || {};
	for (const select of document.querySelectorAll('select.frmgeoac-multiple')) {
		if (select.choices) continue;
		select.choices = new Choices(select, {
			removeItemButton: true,
			maxItemCount: 5,
			shouldSort: false,
			allowHTML: false,
			itemSelectText: '',
			searchEnabled: true,
			placeholder: true,
			placeholderValue: select.name.includes('_country_')
				? strings.allCountries || 'All countries'
				: strings.allTypes || 'All place types',
			searchPlaceholderValue: strings.search || 'Search',
		});
		select
			.closest('.choices')
			?.querySelector('.choices__list--dropdown')
			?.addEventListener('wheel', (event) => event.stopPropagation(), { passive: true });
	}
}

/**
 * Move native geolocation cards into premium's group, before the other groups.
 *
 * @since 1.0.0
 * @returns {void}
 */
export function placeFieldButtons() {
	const holder = document.querySelector('#frmgeo-geolocation-fields-holder');
	const group = document.querySelector('.frmgeo-fields-container');
	const palette = document.querySelector('#frm-insert-fields');
	if (!holder || !group || !palette) return;
	for (const button of palette.querySelectorAll('.frmbutton[id^="frmgeo_"]')) {
		if (button.parentElement !== group) group.append(button);
	}
	const separator = palette.querySelector('.frm-with-line:not([data-frmgeoac-group])');
	for (const child of Array.from(holder.children)) {
		child.dataset.frmgeoacGroup = 'true';
		child.style.display = '';
		if (separator) separator.before(child);
		else palette.append(child);
	}
}

/**
 * Redraw supported controls without changing the host's saved options.
 *
 * @since 1.0.0
 * @returns {void}
 */
export function refreshOptions() {
	placeFieldButtons();
	enhanceSelects();
	for (const panel of document.querySelectorAll('.frmgeoac-options')) {
		const input = (key) =>
			panel.querySelector(
				'[data-option="' + key + '"] input[type="checkbox"], [data-option="' + key + '"] select'
			);
		const enabled = input('frmgeo_enable_address_autocomplete')?.checked;
		const force = input('frmgeo_force_suggested_address')?.checked;
		const bias = input('frmgeo_autocomplete_restriction_usage')?.value;
		for (const option of panel.querySelectorAll('[data-option]')) {
			const key = option.dataset.option;
			let visible = key === 'frmgeo_enable_address_autocomplete' || enabled;
			if (key === 'frmgeo_force_suggested_address_message') visible = enabled && force;
			if (key.includes('_proximity_')) visible = enabled && bias === 'proximity';
			if (key.includes('_bounds_') || key.endsWith('_strict_bounds'))
				visible = enabled && bias === 'area_bounds';
			option.hidden = !visible;
		}
	}
}
document.addEventListener('change', refreshOptions);
document.addEventListener('frm_ajax_loaded_field', refreshOptions);
let timer;
new MutationObserver(() => {
	clearTimeout(timer);
	timer = setTimeout(refreshOptions, 30);
}).observe(document.documentElement, { childList: true, subtree: true });
if (document.readyState === 'loading')
	document.addEventListener('DOMContentLoaded', refreshOptions);
else refreshOptions();
