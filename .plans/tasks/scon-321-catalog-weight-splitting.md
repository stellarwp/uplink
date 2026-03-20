---
status: draft
---

# Split catalog storage for lightweight licensing lookups

## Problem

The catalog is becoming richer. Fields like `changelog` (HTML strings per feature) add significant weight to the stored data. Today the full catalog is cached in a single option and loaded whenever it is needed, including lightweight operations like feature flag `is_valid` checks and license resolution. These operations only need a small subset of catalog data (product slugs, tiers, feature slugs, types, and minimum tier requirements). They do not need changelogs, descriptions, download URLs, or other fields that exist for the update system and the admin UI.

As the catalog grows, loading the full payload on every page becomes a performance concern. Licensing and feature flag checks happen frequently and should be fast. Update data resolution and REST responses happen less often and can tolerate heavier payloads.

## Proposed solution

Separate what the catalog stores from what it fetches. The catalog client should continue to return the full response from the Commerce Portal API. On the storage side, maintain two representations: a lightweight catalog containing only the fields needed for licensing and feature flag resolution, and the full catalog for update data and REST responses. The lightweight version gets loaded on every page. The full version is loaded on demand when building update payloads or serving REST requests.

The exact splitting strategy (two option rows, a single option with lazy-loaded sections, or something else) should be determined during implementation. The key constraint is that licensing and flag checks never pay the cost of loading changelog and other heavy content.
