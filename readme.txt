=== Address Autocomplete for Formidable Forms ===
Contributors: eyalfitoussi
Tags: address autocomplete, formidable forms, google places, address, geolocation
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Google Places address suggestions for Formidable Forms, with country and language controls, location bias and required selection.

== Description ==

Less typing. More useful address suggestions.

Address Autocomplete for Formidable Forms adds Google-powered address suggestions to your forms, with practical controls included free. Visitors start typing, choose a suggestion, and continue filling out the form.

**An Address field built for Formidable Forms**

* Add a dedicated single-line Address field from the Geolocation field group.
* Configure autocomplete in that field's own options, alongside the native builder's settings.
* Autofill street address, city, state, postal code and country in a native Address field, if your Formidable edition supplies it.
* Open a dedicated Address field's saved address in Google Maps from the admin entry-detail page.

The dedicated field works with the free Formidable Forms plugin. Native Address autocomplete is opt-in and requires a host version/edition providing that native field.

**Useful controls, included free**

* **Countries:** limit suggestions to the countries relevant to your form.
* **Result types:** request regions, cities, street addresses or premises.
* **Language:** choose a language per field or use the global preference.
* **Region:** set a site-wide region preference. Region influences results; country restrictions limit them.
* **Location bias:** favor suggestions near coordinates or within geographic bounds, with optional strict bounds.
* **Required suggestion selection:** clear unselected typed text when the visitor leaves the field and display your configurable alert.

Required selection is a browser-side form behavior, not postal validation, proof of a deliverable address or a server-side security check. Google controls the available suggestions.

**Modern Google Places by default**

The default autocomplete path uses Google Places API (New), rather than the deprecated legacy AutocompleteService. A legacy compatibility fallback remains for compatible environments where the modern API is unavailable. The default modern path avoids that legacy service's deprecation warning; it does not guarantee every Google configuration is warning-free.

**Focused and standalone**

No WPGeo account, licensing SDK or WPGeo Framework is required. The dashboard, feature overview and package comparison are bundled locally. Your Google browser API key is still required, and Google's billing and usage limits apply separately.

Existing Address field types and supported settings retain the same saved identities as Formidable Geolocation. The free integration yields when the matching premium plugin is active. Premium is a separate plugin installation, not functionality unlocked inside this ZIP.

**Need more than address autocomplete?**

Explore [Formidable Geolocation](https://formidablegeolocation.com/) for connected maps, geocoding, dynamic location fields, current-location detection, directions, distance, address validation, nearby locations and drawing tools.

Features depend on the premium package. Use the included Compare Packages page to compare the options. Editor links for Map, Directions, Distance & Duration and Address Validation are informational only; premium functionality is not bundled in this free plugin.

== Installation ==

1. Install and activate Formidable Forms 6.23 or newer.
2. Install and activate Address Autocomplete for Formidable Forms.
3. Open Formidable Forms' settings and select Address Autocomplete.
4. Enter your own Google browser API key. Enable Maps JavaScript API and Places API (New), configure billing, and restrict the key to your website.
5. Set optional global region and language preferences.
6. Add the Address field from Geolocation, or enable autocomplete on a native Address field if your Formidable edition supplies it. Configure its options and save.
7. Preview and test selecting suggestions and submitting the form.

== Frequently Asked Questions ==

= Are all the autocomplete controls included free? =
Yes. The controls described above are included in this free plugin. Formidable Forms is required separately. Google usage and any paid host edition have separate terms and pricing.

= Does this validate postal addresses or provide maps and directions? =
No. Autocomplete helps visitors choose a Google suggestion, but does not verify postal deliverability or provide connected maps, directions or current-location detection. Those workflows belong to separate premium packages.

= Can I use Formidable's native Address field? =
Yes, if your host version/edition supplies it. Enable autocomplete in its options to fill its address components. The dedicated single-line Address field does not require that native field.

= What happens if someone types an address without selecting a suggestion? =
With required suggestion selection enabled, the plugin clears that unselected typed text on leaving the field and displays your configured message. The visitor can retry and select a suggestion. Without that option, manually typed addresses are permitted. Host-required-field validation remains separate.

= What is the difference between region and country restriction? =
The global region preference influences Google's results. A field's country restriction limits suggestions to the selected countries. Field language can override the global language.

= Can I use it on AJAX or multi-page forms? =
The adapter handles supported host form rendering and dynamic updates. Multi-page and conditional workflows depend on the corresponding Formidable Forms features/add-ons. Test your own form, theme and integrations before going live.

= Can free and premium be active together? =
Free stops registering its overlapping fields and hooks while the matching premium geolocation plugin is active. Deactivate free when using premium. Saved fields and settings are not deleted. This is not a conversion tool for premium-only fields left in a form after premium is removed.

= Is a WPGeo account required? =
No. The plugin does not include account connection, license activation, telemetry, a remote product catalog or plugin installation services.

= Can another integration load Google? =
Developers can use the frmgeo_disable_google_maps_api filter to prevent this plugin from loading the API. Another integration must then supply a compatible Google Maps JavaScript API with Places. No Google API Loading switch is shown in the free settings interface.

== External Services ==

Google Maps Platform supplies address suggestions and selected-place address details. A form with enabled autocomplete connects the visitor's browser to Google using your configured browser API key. The API loader sends the key and configured language/region. Suggestion and place-detail requests send the typed query or selected place identifier and configured countries, result types, language and geographic bias/restriction where applicable.

Configure Google's APIs, billing and website key restrictions before use. Site owners are responsible for privacy notices or consent needed for their audience and deployment.

[Google Maps Platform Terms](https://cloud.google.com/maps-platform/terms/)
[Google Privacy Policy](https://policies.google.com/privacy)

Product, demo, documentation and pricing links open our websites only when clicked. Dashboard descriptions are bundled locally; these pages do not download remote catalogs or install plugins. Clicking an entry's Open Map link opens Google Maps with the stored address as the search query.

== Screenshots ==

1. Live Google Places suggestions in the dedicated Formidable Forms Address field. Google attribution remains visible.

== Changelog ==

= 1.0.0 =
* Added a one-time administrator setup notice with permanent dismissal and no activation redirect.
* Initial standalone address autocomplete integration for Formidable Forms.
* Google Places API (New) by default, country/type/language controls, location bias and optional required suggestion selection.
* Dedicated single-line Address field, opt-in native Address autofill and admin entry-detail map links.
* Locally bundled overview, package comparison, help and product discovery pages.
