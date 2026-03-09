---
ticket: SCON-203
url: https://stellarwp.atlassian.net/browse/SCON-203
status: done
---

# Catalog REST endpoint with mock client

## Problem

The product catalog — the 4 product families, their tier definitions, taglines, and upgrade URLs — is hardcoded in `resources/js/data/products.ts`. This data comes from the Commerce Portal, which isn't ready. There's no PHP-side representation of the catalog and no endpoint to serve it.

## Proposed solution

Create a catalog client interface with a mock implementation that returns fixture data matching the current `products.ts` — 4 families, 3 tiers each. The catalog is separate from features — it describes what product families and tiers exist, not the individual toggleable capabilities.

- **Route:** `GET /stellarwp/uplink/v1/catalog`
- **Response:** Array of product families, each with slug, name, tagline, and tiers (slug, name, description, upgradeUrl)

When the Commerce Portal API ships, swap the mock client for a real implementation.
