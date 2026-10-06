=== Address Autocomplete for Formidable Forms ===
Contributors: fitoussi
Tags: address autocomplete, formidable forms, google places, address, geolocation
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Google Places API (New) suggestions for Formidable Forms, with country, language, location bias and required suggestion selection controls.

== Description ==

Help visitors enter addresses with fewer keystrokes. Address Autocomplete for Formidable Forms adds Google-powered suggestions to a dedicated single-line Address field and can autofill native Address fields.

**Useful controls, included free**

* Modern Google Places API (New) autocomplete is the default.
* A dedicated single-line Address field, integrated with the native form builder.
* Autofill street, city, state, postal code and country in native Address fields.
* Restrict suggestions by countries and result types.
* Choose a language per field, or use the global language setting.
* Set global region and language preferences.
* Bias suggestions toward a location or geographic bounds, with optional strict bounds.
* Require visitors to select a suggestion: unselected typed text is cleared on blur with your configurable alert.
* Existing field types and settings are retained when switching to our premium geolocation plugin.
* Lean standalone runtime: no WPGeo Framework, account connection or licensing SDK.

The dedicated single-line Address field and native Address fields are supported. Native Address autocomplete is opt-in and requires a host version/edition providing that field.

**Need more than autocomplete?**

Explore [Formidable Geolocation](https://formidablegeolocation.com/) for connected maps, geocoding, dynamic location fields, current-location detection, directions, distance, address validation, nearby locations and drawing tools. Availability differs by package; free and premium are alternatives, not add-ons to activate together. The included Overview and Compare Packages pages explain the choices. Four locked editor links are discovery aids, not functioning premium fields.

**Google service configuration**

This plugin requires Formidable Forms 6.23 or newer and your own Google browser API key. Enable Maps JavaScript API and Places API (New), billing, and suitable website restrictions. Google usage charges are separate from this free plugin.

If the modern autocomplete API is unavailable in a compatible existing Google environment, a legacy Places fallback may run. The default modern path avoids the legacy AutocompleteService deprecation warning; this is not a promise of zero console warnings in every environment.

== Installation ==

1. Install and activate Formidable Forms.
2. Install and activate this plugin.
3. Open Formidable Forms' settings and select Address Autocomplete.
4. Enter your browser Google API key and optional region/language preferences.
5. Add our Address field, or enable Autocomplete on a native Address field, configure its options and save.
6. Preview and test selecting suggestions and submitting the form.

== Frequently Asked Questions ==

= Is Google Places usage free? =
The plugin is free. Google's billing, quotas and pricing apply separately.

= Does this provide maps, current location or address validation? =
No. Those are premium features; the free plugin only provides address autocomplete.

= Can I activate free and premium together? =
Free stops registering its integration while the matching premium geolocation plugin is active. Deactivate free when using premium. Saved fields and settings are not deleted.

= Does the plugin send data to Google? =
When an enabled Address field loads, the browser connects to Google Maps/Places using your configured API key. Address queries and selected-place requests are sent to Google to obtain suggestions. No account connection, telemetry or remote plugin feed is required.

= Can another integration load Google? =
Developers can use the frmgeo_disable_google_maps_api filter to prevent this plugin from loading the API. A compatible Google Maps JavaScript API with Places must then be available.

== External Services ==

Google Maps Platform supplies address suggestions and selected address details. The browser sends the API key and configured language/region when loading Google, and the typed address plus configured restrictions when requesting suggestions. Requests occur on forms with enabled autocomplete.
[Google Maps Platform Terms](https://cloud.google.com/maps-platform/terms/)
[Google Privacy Policy](https://policies.google.com/privacy)

Product, demo, documentation and pricing links open our websites only when clicked. Dashboard descriptions are bundled locally; no plugin installation or remote content download is performed by these pages.

== Changelog ==

= 1.0.0 =
* Initial standalone autocomplete integration for Formidable Forms.
* Local feature overview, package comparison, help and product discovery pages.
