# Standalone plugin guidance

Read docs/README.md before changing this repository. Inspect its branch, HEAD,
remote and entire working tree; preserve user changes.

- Keep this autocomplete-only. No WPGeo Framework, licensing, accounts, migrations,
  premium runtime or remote product feeds.
- Preserve frmgeo_address, existing frmgeo option names and native global setting keys.
  Bootstrap classes/constants are isolated from premium.
- Free yields when premium's version constant is defined. Do not edit premium
  without explicit authorization.
- The dedicated single-line Address field and native Address fields are supported. Native Address autocomplete is opt-in and requires a host version/edition providing that field.
- Only four promotional editor links: Map, Directions, Distance & Duration,
  Address Validation. They must never create fields or save unsupported values.
- All dashboard content is local. No image placeholders or fabricated screenshots.
- Run npm run build, npm test and tools/package/build.php. Include readable sources;
  exclude tools, tests, dependencies and private docs from release ZIPs.
- Fixtures do not replace installed/browser acceptance. Publishing, production
  changes and WordPress.org submission require separate approval.
