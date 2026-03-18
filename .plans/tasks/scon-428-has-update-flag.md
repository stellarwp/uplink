---
ticket: SCON-428
url: https://stellarwp.atlassian.net/browse/SCON-428
status: todo
---


# Add `has_update` flag to installable features

## Problem

The frontend currently receives `version` (latest from catalog) and `installed_version` (what's on disk) as separate fields, but has no way to know whether an update is available without doing its own semver comparison. Meanwhile, the plugin and theme update handlers (`Plugin_Handler`, `Theme_Handler`) each perform their own inline `version_compare()` calls independently. There's no single shared method that answers "does this feature have an update available?"

## Proposed solution

Add a `has_update()` method to the `Installable` interface and implement it on `Plugin` and `Theme`. The method should use `version_compare()` against the feature's own `version` and `installed_version` properties, returning `true` when a newer version is available and the feature is currently installed.

The update handlers should call this method instead of doing their own comparison, so the logic lives in one place.

The REST endpoint should serialize `has_update` alongside the existing version fields so the frontend can use it directly without any version parsing.
