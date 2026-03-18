---
ticket: SCON-430
url: https://stellarwp.atlassian.net/browse/SCON-430
status: todo
---


# Add feature update UI to the feature manager

## Problem

Even once the backend serves `has_update` on installable features and the frontend store has an `updateFeature` action, there is no way for a user to trigger an update from the feature manager UI. The update endpoint and store action have no surface to call them.

## Proposed solution

Add a minimal update interaction to FeatureRow for installable features where `has_update` is true. This should dispatch the `updateFeature` store action and disable all feature actions (toggles, other updates) while the update is in flight, matching how install already locks other installable feature actions. Show an "updating" status badge during the operation.

Keep the implementation simple and functional. This is a working prototype to present to design for visual refinement, not a final UI. Avoid over-building the interaction or adding polish that will likely change.

## Dependencies

- `draft-has-update-flag.md` (backend `has_update` field on installable features)
- `draft-frontend-update-action.md` (store `updateFeature` thunk)
