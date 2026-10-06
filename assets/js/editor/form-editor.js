/**
 * Keep supported native editor options focused; never create premium fields.
 * @since 1.0.0
 */
export function refreshOptions() {
	for (const panel of document.querySelectorAll('.frmgeoac-options')) {
		const input = key => panel.querySelector('[data-option="' + key + '"] input[type="checkbox"], [data-option="' + key + '"] select');
		const enabled = input('frmgeo_enable_address_autocomplete')?.checked;
		const force = input('frmgeo_force_suggested_address')?.checked;
		const bias = input('frmgeo_autocomplete_restriction_usage')?.value;
		for (const option of panel.querySelectorAll('[data-option]')) {
			const key = option.dataset.option;
			let visible = key === 'frmgeo_enable_address_autocomplete' || enabled;
			if (key === 'frmgeo_force_suggested_address_message') visible = enabled && force;
			if (key.includes('_proximity_')) visible = enabled && bias === 'proximity';
			if (key.includes('_bounds_') || key.endsWith('_strict_bounds')) visible = enabled && bias === 'area_bounds';
			option.hidden = !visible;
		}
	}
}
document.addEventListener('change', event => {
	if (event.target.matches('.frmgeoac-multiple')) {
		Array.from(event.target.selectedOptions).slice(5).forEach(option => { option.selected = false; });
	}
	refreshOptions();
});
let timer;
new MutationObserver(() => { clearTimeout(timer); timer = setTimeout(refreshOptions, 30); }).observe(document.documentElement, {childList: true, subtree: true});
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', refreshOptions);
else refreshOptions();
