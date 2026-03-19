---
ticket: SCON-429
url: https://stellarwp.atlassian.net/browse/SCON-429
status: todo
---


# Wire feature update action into the frontend datastore

## Problem

The backend already exposes a `POST /stellarwp/uplink/v1/features/{slug}/update` endpoint that triggers a plugin or theme update and returns the full Feature object. The frontend datastore has no corresponding action. There is no `updateFeature` thunk, no `UPDATE_FEATURE_*` action types, and no error code for update failures. Until the store supports this, no UI work can build on top of it.

## Proposed solution

Add an `updateFeature` thunk to the `@wordpress/data` store that mirrors the existing `enableFeature` / `disableFeature` pattern. It should POST to the update endpoint, dispatch start/finished/failed actions, and replace the feature in state with the response. Add a corresponding `FeatureUpdateFailed` error code.

This task is strictly the datastore wiring. It does not include UI elements like an update button, badge, or progress indicator. Those will come in a follow-up task once the action is available to consume.
