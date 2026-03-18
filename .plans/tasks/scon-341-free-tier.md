---
ticket: SCON-341
url: https://stellarwp.atlassian.net/browse/SCON-341
status: done
---

# Add free tier to the catalog

## Problem

The tier hierarchy starts at rank 1 (Basic/paid). Free plugins have no place in the tier system, so the feature UI cannot show what free software provides or present the natural upgrade path to paid tiers. Free plugins should be the entry point to the tier system, not something outside of it.

## Proposed solution

The Commerce Portal includes a free tier (rank 0) in a product's tier list when the product has free offerings. It sits at the bottom of the same hierarchy as Basic, Pro, and Agency, with purchase URLs on the tiers above it providing the on-ramp to paid. Features gated at the free tier are available without a license key, since an unlicensed user resolves to tier rank 0 and `0 >= 0` satisfies the availability check.

## Requirements

- The free tier has rank 0 and sits below all paid tiers in the hierarchy.
- The catalog fixture data must include a free tier with features gated at that tier. The fixtures define the schema the Portal implements.
- Features with `minimum_tier` set to the free tier are available without a license key.
- The architecture docs describe the free tier as the entry point to the tier system.
- Tests confirm that free-tier features resolve as available for unlicensed users.

## Dependencies

Depends on the unlicensed feature resolution task landing first, so that feature resolution works without a license key at all.
