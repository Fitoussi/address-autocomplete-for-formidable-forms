# Address Autocomplete for Formidable Forms

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

## Development

Use npm ci, npm run build and npm test. Runtime needs no Composer dependencies.
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

No screenshots are currently bundled. Capture these plugins' own real interfaces
before adding a Screenshots section; do not relabel Gravity captures.
GitHub publication and WordPress.org submission require separate approval.
