---
ticket: SCON-455
url: https://stellarwp.atlassian.net/browse/SCON-455
status: todo
---

# Show legacy license status on feature rows

## Problem

When a feature is not covered by the unified license (`is_available` is false), it shows as a generic locked row. But some of these features are actually licensed through a legacy per-plugin key. The user has no way to tell the difference between "not licensed at all" and "licensed via a legacy key."

## Proposed solution

For each unavailable feature, check whether a matching legacy license exists. A match means the legacy license slug equals the feature slug AND the legacy license brand (planned to be renamed to `product`) equals the feature's product. The implementer should verify that the brand/product field alignment is in place before relying on it.

When a match is found, the unavailable feature row should show a "Legacy" badge and the expanded row should include a note explaining that this feature is licensed by a legacy key. If the legacy license has a `page_url`, link to it so the user can manage that license from its original settings page.
