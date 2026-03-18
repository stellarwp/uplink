---
ticket: SCON-454
status: in-progress
url: https://stellarwp.atlassian.net/browse/SCON-454
---

# Add legacy license data to the frontend store

## Problem

SCON-344 added the `GET /stellarwp/uplink/v1/legacy-licenses` REST endpoint, but the frontend doesn't use it. The `LegacyLicenseBanner` component reads from `window.uplink.legacyLicenses` (localized script data), bypassing the `@wordpress/data` store that every other data source flows through.

This means legacy license data can't be refetched, doesn't participate in loading states, and the component carries its own `Window` type declaration instead of using shared API types.

## Proposed solution

Wire the legacy license endpoint into the existing `@wordpress/data` store following the same pattern as features, catalog, and license. This is read-only, no mutations needed.

- Add a `LegacyLicense` type in `types/api.ts` matching the endpoint response shape (key, slug, name, brand, is_active, page_url, expires_at).
- Add a `legacyLicenses` slice to the store state, with a `RECEIVE_LEGACY_LICENSES` action, reducer case, `getLegacyLicenses` selector, and `getLegacyLicenses` resolver that fetches from the REST endpoint.
- Update `LegacyLicenseBanner` to read from the store via `useSelect` instead of `window.uplink`.
