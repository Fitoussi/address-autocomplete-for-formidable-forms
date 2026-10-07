# Address Autocomplete for Formidable Forms

## Standalone bootstrap convention

Use this convention for these three plugins and future standalone autocomplete plugins:

- Give free its own constant prefix and PHP namespace: GFGEOAC / GravityGeolocationAutocomplete,
  NFGEOAC / NinjaGeolocationAutocomplete, or FRMGEOAC / FormidableGeolocationAutocomplete.
  Never define aliases using premium's constants or classes.
- Register the guard on plugins_loaded, after all active plugin files have loaded.
  Check the corresponding premium version constant before registering any dashboard,
  field, settings or runtime integration. Premium wins regardless of file load order.
- Gravity runs the guard at priority 5, then registers its Add-On on gform_loaded at
  priority 8; Gravity Forms initializes Add-Ons during plugins_loaded at priority 10.
  Ninja/Formidable check at priority 20. Ninja constructs translated fields on init
  at priority 1, before its host's priority-5 field list. Match host lifecycle, not
  arbitrary identical hook numbers.
- On conflict, free registers only capability-gated site/network admin notices.
  Do not auto-deactivate plugins, redirect, delete data or change saved preferences.
  Premium being active but unable to run does not permit free to claim its field types.
- Preserve saved field types, field option names, global setting keys, native settings
  slugs, capabilities and existing integration hooks. These are data/API contracts;
  PHP bootstrap identities are not shared storage. Third-party PHP integrations must
  use the intended plugin's namespace/constants explicitly.
- Use short arrays in first-party production PHP. Tool/test formatting is independent.
- Test free alone and both premium/free load orders in separate processes; test
  notices with and without administrator capability. Run installed acceptance too.


A lean standalone plugin forked from our Gravity autocomplete baseline.
The dedicated single-line Address field and native Address fields are supported. Native Address autocomplete is opt-in and requires a host version/edition providing that field.

## Structure

- Main plugin: isolated FRMGEOAC constants and FormidableGeolocationAutocomplete autoloader.
- src/Loader.php: dependency check and premium-wins guard after all bootstraps load.
- src/Core/Bootstrap.php: native settings, one real Address field and editor/runtime hooks.
- src/Features/Form/Fields/Address: native text field lifecycle and supported options.
- src/Features/Form/Admin: autocomplete controls and four discovery-only editor links.
- src/Features/Form/Runtime: immutable, whitelisted frontend payloads.
- src/Admin: native global settings plus locally rendered Overview, Compare Packages,
  Help and More Plugins pages.
- assets/js/autocomplete: Google loader, extracted service and host-specific adapter.
- assets/js/editor and assets/scss: narrowly scoped native-builder presentation.
- tests: source contracts and actual built-bundle DOM fixtures.

No framework, account SDK, licensing SDK, geocoder, locator, premium field services,
migrations or uninstall data deletion is bundled. Premium fields already saved in
a form are the host/premium plugin's responsibility; free does not reinterpret them.

## Activation setup notice

First site activation records a setup notice in the plugin's own option. It appears
across site admin pages for administrators until permanently dismissed or a browser
API key is configured. It never redirects on activation. Reactivation does not reset
dismissed or completed guidance, and existing active installs are not enrolled by
an update alone. Network activation, missing/outdated hosts and premium conflicts
do not display setup guidance. The explicit Dismiss action is a capability-checked,
nonce-protected POST that returns to the same admin page. Shared host settings and
premium options are not changed. Plugin-row Settings and Overview links remain.

## Development

Use npm ci, npm run build and npm test. Runtime needs no Composer dependencies.
First-party JavaScript/SCSS formatting follows .prettierrc.json; vendor sources
are excluded by .prettierignore. PHP follows tools/phpcs.xml.dist, matching the
Gravity standalone conventions. Test-only host stubs are not shipped in ZIPs.
Development quality tooling lives in tools/. Readable JS/SCSS and sourcemaps ship
with the generated assets. npm run package creates a versioned, non-overwriting
ZIP under dist/. Public metadata lives in readme.txt and change_log.txt.

Global preferences preserve existing premium keys. Formidable merges only three
browser settings into its existing option; Ninja uses native per-key persistence.
Field country/language/type/bias and selection settings keep premium-compatible names.

## Acceptance and release

Inspect fresh-ZIP activation, dashboard links, editor persistence, real Google
suggestions, blur selection enforcement and valid retry, AJAX/conditional rendering,
entry editing, existing forms and premium switching in both activation orders.
Record tested host versions and any untested flows honestly. Use Plugin Check.
Minimum-host compatibility and screenshots remain release gates unless verified.

One real frontend screenshot is bundled in assets/screenshots/screenshot-1.png,
with its matching readme caption. Additional native editor, global preferences
and entry screenshots remain planned; do not relabel Gravity captures.
GitHub publication and WordPress.org submission require separate approval.
