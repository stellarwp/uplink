---
ticket: SCON-342
url: https://stellarwp.atlassian.net/browse/SCON-342
status: todo
---

# Add feature update endpoint

## Problem

There is no REST endpoint to trigger a plugin or theme update for a specific feature. WordPress core does not provide one either. The only way to update plugins/themes is through the admin UI or WP-CLI. The feature management UI needs a way to trigger updates programmatically.

## Proposed solution

Add a `POST /stellarwp/uplink/v1/features/{slug}/update` endpoint that triggers an update for a plugin or theme feature. It checks the WordPress update transient (via `plugins_api` / the update data already injected by `Plugin_Handler`) for an available update. If an update is present, it runs the upgrade. If no update is available, it returns an error.

This follows the same pattern as the existing `enable` and `disable` actions on the feature controller.

## Requirements

- The endpoint accepts a feature slug and triggers an update using the appropriate strategy (plugin or theme).
- Available updates are determined from the WordPress plugin/theme API. The endpoint does not compare versions itself.
- Returns an error if no update is available for the feature.
- Returns an error if the feature type does not support updates (e.g. flag features).
