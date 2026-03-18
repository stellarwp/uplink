---
ticket: SCON-340
url: https://stellarwp.atlassian.net/browse/SCON-340
status: todo
---

# Allow feature resolution without a license

## Problem

When no license key is stored, `License_Manager::get_products()` returns a `WP_Error(INVALID_KEY)`. This error propagates through `Resolve_Feature_Collection`, which bails early and never resolves features. The features REST endpoint returns an HTTP error instead of the feature catalog.

This means the features API is unusable until a license key is entered. Free plugins and the feature management UI have no way to report what features exist on the system or which flag features are enabled.

## Proposed solution

Treat "no license key" as a valid state rather than an error. When no key is stored, feature resolution should still run against the catalog and return the full feature collection. Every product gets tier rank 0, so tier-gated features show as unavailable, but the catalog structure and any locally-enabled flag features are still reported.

The fix belongs in `Resolve_Feature_Collection`. When the licensing layer reports no key, the resolver should substitute an empty `Product_Collection` and continue resolution instead of returning the error. The licensing layer itself can keep returning `WP_Error` for the no-key case since that's accurate from its perspective. The resolver is the right place to decide that "no license" is not fatal for feature resolution.

A separate task will add a "free" tier to the catalog so that free-tier features show as available even without a license.
