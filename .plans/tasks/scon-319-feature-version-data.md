---
ticket: SCON-319
url: https://stellarwp.atlassian.net/browse/SCON-319
status: todo
---

# Add version data to catalog and features

## Problem

The catalog and feature system has no version awareness. `Catalog_Feature` supports a `version` field but it is never populated in fixtures, and there is no `released_at` or `changelog`. On the feature side, `Plugin` can read its installed version from disk, but `Theme` cannot. Feature resolution does not pass any version data through to the resolved Feature objects, and the REST schema does not expose it.

Without this data, the system cannot tell the frontend what version is installed, what the latest version is, or when it was released.

## Requirements

1. Add `released_at` and `changelog` to `Catalog_Feature` alongside the existing `version` field. `changelog` is an HTML string, consistent with how WordPress handles changelogs in `plugins_api()` sections.
2. Populate `version`, `released_at`, and `changelog` in the catalog test fixtures in `tests/_data/`.
3. Add `is_installed()` and `get_installed_version()` to `Theme`, using `wp_get_theme()` to read the version from the theme stylesheet headers.
4. Widen the `Installable` interface to include `is_installed()` and `get_installed_version()`.
5. Pass `installed_version` through feature resolution so resolved Feature objects carry the version currently on disk (null for Flag features and uninstalled extensions).
6. Update the Features and Catalog REST endpoint schemas to include the new fields.

## Proposed solution

The catalog should carry the latest available version, release date, and changelog. These come from the Commerce Portal API and are only stored in `Catalog_Feature`. The resolved Feature objects should carry the installed version, read from the actual extension on disk. This keeps the two concepts separate: catalog knows what's available, features know what's installed. The frontend (separate task) will combine both to show version info and update indicators.
