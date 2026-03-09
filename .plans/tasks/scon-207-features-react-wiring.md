---
ticket: SCON-207
url: https://stellarwp.atlassian.net/browse/SCON-207
status: done
---

# Wire features into the React app

## Problem

Feature state is entirely client-side — Zustand + localStorage. The UI needs to fetch features from the REST API and persist toggle state through it.

## Proposed solution

Replace the feature-related parts of the Zustand store with React Query hooks that call the features REST endpoints. The migration plan in `.plans/rest-api-react-query-migration.md` covers the pattern.

The frontend `ProductFeature` type (id, requiredTier, category) doesn't exactly match the backend `Feature` schema (slug, group, tier, type, is_available) — these need to be reconciled as part of this work.
