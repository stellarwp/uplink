---
ticket: SCON-344
url: https://stellarwp.atlassian.net/browse/SCON-344
status: in-progress
---

# Expose legacy licenses through the REST API

## Problem

The `Legacy\License_Repository` aggregates per-plugin license data via the `stellarwp/uplink/legacy_licenses` filter, and the React UI has a `LegacyLicenseBanner` component ready to display it. But nothing connects them. The UI has no way to access legacy license information.

It needs to come through the REST API rather than localized data so it can be refetched, in case we add legacy license management later.

## Proposed solution

Add a `GET /stellarwp/uplink/v1/license/legacy` endpoint that returns legacy license data from the `Legacy\License_Repository`. This keeps it under the license namespace where it belongs, while giving it its own endpoint that can be fetched independently.

## Requirements

- The endpoint returns an array of legacy license entries from the `License_Repository`.
- Each entry includes the fields from `Legacy_License` (slug, name, status, expires_at, page_url, etc.).
- Returns an empty array when no legacy licenses are reported.
