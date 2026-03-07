---
ticket: SCON-304
url: https://stellarwp.atlassian.net/browse/SCON-304
status: todo
---

# Include products in the license REST response

## Problem

The `GET /stellarwp/uplink/v1/license` endpoint only returns `{ key: string | null }`. The full licensing products response (tiers, subscription status, expiration dates, validation state) is fetched and cached server-side in `Product_Collection` but never exposed to the frontend.

The frontend needs this data to support upcoming work around license validity, grace periods, and product-level status. Without it, the UI can't show which tier the user is on, whether a subscription is expiring, or make decisions about feature validity on the client side.

## Proposed solution

Expand the `GET /license` response to include the full `Product_Collection` alongside the key. The response shape should look something like:

```json
{
  "key": "LWSW-...",
  "products": [
    {
      "product_slug": "give",
      "tier": "pro",
      "pending_tier": null,
      "status": "active",
      "expires": "2026-12-31 00:00:00",
      "activations": {
        "site_limit": 0,
        "active_count": 1,
        "over_limit": false
      },
      "installed_here": true,
      "validation_status": "VALID",
      "is_valid": true
    }
  ]
}
```

When no license key is stored, `products` should be an empty array.

The `POST /license` and `DELETE /license` responses should also include the products array so the frontend state stays in sync after activation or removal without needing a separate fetch.

The frontend store, types, and resolvers will need to be updated to consume and store the products data. That can happen in the same PR or as a follow-up.
