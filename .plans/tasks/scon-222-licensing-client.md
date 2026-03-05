---
ticket: SCON-222
url: https://stellarwp.atlassian.net/browse/SCON-222
status: done
pr: "#125"
---

# Mock v4 licensing client

## Problem

The v4 licensing API is being built by the licensing team but isn't available yet. Uplink needs a client to call it — specifically two endpoints that serve different purposes:

- **`GET /products`** (key + domain) — read-only state check. Returns all products on the license with their tier, activation status, seat counts, and whether the product is installed on this domain. This is what Uplink calls periodically and what the UI uses to show licensing state. Does **not** consume seats.

- **`POST /licenses/validate`** (key + product_slug + domain) — activation event. Called when a plugin activates with an embedded key, when a user enters a key, or when a user toggles a product on. Consumes a seat as a side effect if the product isn't already activated on this domain. Auto-activates on first call if within seat limits.

The distinction matters: `/products` is a safe read you can call on a schedule. `/licenses/validate` is a write that consumes a seat — it should only fire in response to a deliberate user or system action.

## Proposed solution

Create an interface for the v4 licensing client with methods for both operations. Implement it as a mock that returns fixture data. The licensing team will build the real client as a Composer package; when it's ready, we swap the binding.

The `/products` response shape per the v4 API:

```
{
  "product_slug": "kadence",
  "tier": "professional",
  "pending_tier": "starter",        // scheduled downgrade, or null
  "status": "active",               // subscription status
  "expires": "2027-02-26 00:00:00",
  "activations": {
    "site_limit": 5,                // 0 = unlimited
    "active_count": 5,
    "over_limit": false
  },
  "installed_here": true,           // null when no domain provided
  "validation_status": "valid",     // from Validation_Status enum
  "is_valid": true
}
```

The `validation_status` field maps to one of: `valid`, `expired`, `suspended`, `cancelled`, `license_suspended`, `license_banned`, `no_subscription`, `not_activated`, `out_of_activations`, `invalid_key`.

The `/licenses/validate` response shape:

```
{
  "status": "valid",
  "is_valid": true,
  "license": { "key": "LWSW-...", "status": "active" },
  "subscription": { "product_slug": "kadence", "tier": "professional", "site_limit": 5, ... },
  "activation": { "domain": "example.com", "activated_at": "..." }
}
```

The mock should cover the common UI scenarios: product active and valid, product not activated (available to activate), product out of activations (show upsell), subscription expired, etc.
