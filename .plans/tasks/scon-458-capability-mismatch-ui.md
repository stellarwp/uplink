---
ticket: SCON-458
url: https://stellarwp.atlassian.net/browse/SCON-458
status: todo
---

# Show capability/catalog mismatches in the UI

## Problem

Once feature availability is driven by capabilities instead of catalog tiers, two new states exist that the UI does not handle.

A feature can be available through capabilities but not part of the user's catalog tier (grandfathered access, promotional grants, special exceptions). These features currently render mixed in with their tier group or as locked, neither of which is correct.

A feature can also be in the user's catalog tier but missing from their capabilities (revoked or excluded). Today these would show as available since the tier check would pass, which is wrong once capabilities are the source of truth.

## Proposed solution

Surface both mismatch states with distinct visual treatments so the user understands why a feature's availability differs from what their tier would suggest.

## Requirements

- Features that are capable but outside the user's catalog tier render at the top of the product section alongside other available features, not inside a tier group accordion.
- These features display a visual indicator (badge or tag) communicating that this is bonus or grandfathered access.
- Features that are in the user's catalog tier but not in their capabilities render in their normal tier position but as disabled. These features display an indicator that distinguishes them from features that are locked because the user is on a lower tier.
