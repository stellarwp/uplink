---
ticket: SCON-235
url: https://stellarwp.atlassian.net/browse/SCON-235
status: done
pr: "#127"
---

# Mock v4 catalog client

## Problem

The licensing client tells us what products and tiers a key covers. The features system knows how to toggle and check local state. But there's nothing in between that says "Kadence Pro includes these 12 features, and patchstack requires at least Pro tier." That's the catalog — the product definition data that maps product/tier combinations to features.

Without it, the `Manager` can't resolve features. It has the license (product + tier) and the strategies (local toggle state), but no way to know which features belong to which product or what tier they require. The catalog is the missing third input.

The Commerce Portal will eventually serve this data, but isn't ready. The schema we need from the portal is documented in `catalog-api-request.md`.

## Proposed solution

Same approach as the v4 licensing client — interface, value objects, fixture implementation with JSON data. The fixture covers all four products with their tiers and features. When the Commerce Portal API ships, swap the fixture for a real implementation.
