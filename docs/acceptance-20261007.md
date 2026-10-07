# Standalone acceptance — Formidable Forms

Date: 2026-10-07. Build: 1.0.0. Status: working first draft for owner review, not published.

## Boundaries

Separate local repository and isolated bootstrap/namespace. Shared premium-compatible saved field type and supported option keys.
No framework, account/licensing SDK, remote catalog/feed, geocoder/locator or paid field runtime.
Four discovery-only editor links: Map, Directions, Distance & Duration, Address Validation.
Dedicated single-line Address plus opt-in native Address autofill where the host edition provides that field.

## Environment

WordPress 7.1.1 on Test Site 2. Formidable Forms 6.35 plus Formidable Pro 6.34; Formidable Geolocation 3.0.0-rc4 used for switching.
PHP source contracts also executed using PHP 7.4.30. This is not a full installed-site PHP 7.4 test.
Minimum declared host 6.23 and WordPress 6.5 were not installed and are not certified by these results.

## Passed

- Fresh ZIP activation and isolated free bootstrap.
- Local Overview, Compare Packages, Help and More Plugins pages, settings navigation and scoped assets.
- Exactly four nonfunctional premium discovery links, without registering fake field types.
- Native field editor saves country, language, force-selection and proximity controls; saved values persist.
- Global browser key, region and language preferences integrate with the host settings page.
- Live region changes reflected in the frontend browser configuration.
- Real Google Places API (New) suggestions; selected address saved by the native submission flow.
- Typed unselected value clears on blur with an alert; a subsequent selected address successfully submits.
- Native Address autofill populates street, city, state, postal code and country, preserving a typed Suite 12.
- Saved entry displays the custom address with an Open Map link. Native component values remain intact.
- Existing entry edit screen loads the saved values and accepts an unchanged update; database read-back preserves both address types.
- Premium takeover when free is already active and when premium is active first.
- Deactivating premium lets free resume on the next request.
- Stored field/settings SHA-256 fingerprints identical before, during and after the activation-order tests.
- Data-version markers unchanged by switching.
- Final installed ZIP: Plugin Check reports “Checks complete. No errors found.”
- Repository PHP coding standards: zero errors and zero warnings.
- PHP source contracts pass, including scalar input handling, persistence keys and runtime payload whitelisting.
- 18 actual built-bundle DOM tests pass (including native Choices and hyphenated repeater rows).
- Retested saved US/IL restrictions, Cities results, Hebrew, proximity values and custom selection text. Real Google returned Hebrew city suggestions.
- Native checkbox editor saves retain both checked and unchecked values after the compact-save correction below.
- Actual AJAX Next with a conditionally hidden Address succeeds; later-page repeater suggestions and a dynamically added row initialize correctly.

Automated frontend coverage: saved defaults, selection synchronization, blur rejection, valid retry,
hidden conditional inputs, duplicate embeds, AJAX replacement, API-loading window, API failure,
pending place-detail fetch, country/language/type/proximity/bounds translation and narrowly allowed legacy fallback.
Native autofill preserves the apartment/unit field when absent from Google results; previous-page and draft submission are exempt from final-selection enforcement.

## Fix found during installed acceptance

A synthetic input event after selection reopened predictions over the host Submit button.
Committed values now notify the native change lifecycle without pretending to be new typing.
Both adapters have an explicit regression proving that committing a suggestion does not request or reopen predictions.

Night audit found and fixed two further problems:

- Formidable's compact parser keeps the first duplicate input value. The hidden unchecked fallback had preceded the checkbox, so enabled toggles saved as zero. Checkbox now precedes its fallback; checked/unchecked saves passed in the native editor and a renderer contract guards the ordering.
- Later AJAX pages did not preload nested Address options, and actual repeater wrappers use hyphens rather than only underscores. The whitelisted payload now includes saved later-page/nested fields with cycle protection, rendered options retain precedence, and the adapter recognizes both native wrapper formats. No form data or premium runtime is modified. PHP and built-bundle regressions cover these cases.

## Remaining release gates

- Owner review of labels, discovery links, package marketing and the two dashboards.
- Actual screenshots for these products; no Gravity screenshots relabeled, no placeholder images shipped.
- Full installed-site minimum host/WordPress/PHP matrix.
- Complete live repeater rejection/retry, Previous/Next with a visible conditional Address, and final repeater submission. During second-row forced-selection rejection, the expected alert stalled the in-app browser connection before dismissal. Initial hidden conditional Next, AJAX later-page suggestions and added-row initialization passed; the remaining steps are not marked passed.
- Native export/import round trip and broader entry-edit variations remain unverified.
- Language choices retain the existing reference list; not every listed Google language/result type was exercised.
- GitHub publication and WordPress.org submission need separate approval.

## Package

Filename: address-autocomplete-for-formidable-forms.1.0.0.zip
SHA-256: fed36ab43294bf6231a09e82a378765d0224427eeb229c25d2391a23f8d0e1b8

Night cleanup: first-party PHP/JS/SCSS formatting and lifecycle documentation completed;
the unused dashboard variable and empty tests/helpers directory were removed.
Final ZIP contains 39 files, with no framework/account SDK, tools/tests/docs or installed dependencies.
No commit, push or publication was performed during this cleanup.

Final installed-ZIP browser smoke was unavailable after the alert stall; Plugin Check,
source contracts, bundle tests and native runtime checks were used for the final ZIP instead.

Readable JS/SCSS and frontend sourcemaps ship with the compiled assets.
Development tools, tests, docs, editor files, node_modules and vendor are excluded.
The local package builder refuses to overwrite an existing ZIP.

## Test data and restoration

Private evidence and the reversible database backup live outside the repository in
artifacts/standalone-host-qa-20261006.6G91os and artifacts/autocomplete-night-audit-20261007.9mM2Gn.
They contain local configuration and must not be published.
Only disposable QA forms/entries were changed. Original activation and global-settings options are restored after testing.
QA forms are Ninja 7 and Formidable 12; pages 347/348 are retained as drafts, with entries kept as review fixtures.
The night audit added Ninja 8, Formidable 13 plus flow parent/child 14/15.
Its pages 353/354/357 are also retained as drafts, and QA entries remain available for review.
The temporary save diagnostic is removed from the test site after testing.
The two new installed plugins were inactive after the night restoration. Premium and Gravity source repositories were not modified.

## Daytime live-flow follow-up — completed

This follow-up supersedes the older repeater/Previous/conditional and final
browser-smoke gaps above. gmwdev: WordPress 7.2-alpha-63323, Formidable 6.34
and Pro 6.34. Only isolated no-email QA forms were used.

- AJAX QA form 243: hidden conditional Address permits Next; visible Address
  returns suggestions and its selected value survives Next/Previous.
- Native conditional hiding clears its value (host behavior, not a plugin rewrite).
- Later-page repeater and added rows initialize independently. Removing the empty
  third row leaves both selected rows intact. Invalid second-row blur raises the
  alert and clears only that row; valid retry and final submission save two
  independent values (parent 1765, children 1763/1764), verified natively.
- Non-AJAX multipage QA form 245: real suggestions, Next/Previous retention,
  later-page repeater initialization and final submission pass (1770/1769).
  Native form has no AJAX-submit class; saved options disable AJAX submit/load.
- Non-AJAX single-page QA form 247: invalid dedicated Address clears/alerts;
  valid retry submits both address types (1768). Native autofill preserves Suite 12
  and saves city/state/postcode/country components.
- Rechecked both premium activation orders on Test Site 2: premium owns the field,
  standalone runtime callbacks absent; standalone resumes after premium deactivation.
  Four field/settings/data-version fingerprint snapshots remain identical.
- Final installed ZIP browser smoke on Test Site 2: real suggestions, selection
  and native submission pass, entry 17 saves the address exactly once. The first
  Google request failed transiently with an XHR/RPC error; the next request worked
  without a code change. This is not an error-free-network claim.
- Automated suites rerun: 18 JavaScript tests and PHP contracts pass; installed
  native checks reverify the earlier admin entry edit and apartment preservation.

No new runtime correction was needed. Row-removal confirmation temporarily stalled
the browser's dialog controls; owner dismissal recovered it. The subsequent
selection alerts were dismissed through the supported browser controls.
The minimum-version matrix and export/import gates above remain unverified.

Per owner instruction, no daytime backup/restoration: both standalone plugins
remain active on both sites, both premiums inactive. QA forms/pages/entries retained
locally; owner forms/settings untouched. No commit, push, publication or submission.
Detailed evidence: artifacts/standalone-flow-acceptance-20261007.Ty0zcg in the
development workspace. It is private QA material, not release content.
