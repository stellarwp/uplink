---
ticket: SCON-320
url: https://stellarwp.atlassian.net/browse/SCON-320
status: todo
---

# Add version fields to frontend TypeScript types

## Problem

Once the backend adds version data to the Features and Catalog REST responses (see `draft-feature-version-data.md`), the frontend TypeScript types will be out of date. The `Feature` interface has no `installed_version` field, and the catalog types have no `version`, `released_at`, or `changelog`. The frontend cannot display version information or "update available" indicators until the types and store reflect what the API provides.

## Proposed solution

Update the frontend TypeScript types to match the new REST response shapes. The `Feature` interface should include `installed_version` (nullable, null when uninstalled). The catalog feature type should include `version`, `released_at`, and `changelog`. The Zustand store and resolvers should pass this data through so components can access it.

This task covers types and data flow only. Building the actual UI components for version display and update indicators is separate work.
