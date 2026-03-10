# License Endpoints

All endpoints require the `manage_options` capability.

## GET /stellarwp/uplink/v1/license

Returns the stored unified license key and its associated products. Products come from the cached `Product_Collection` (fetched from the Licensing API). When no key is stored, `products` is an empty array.

### Response (200)

```json
{
    "key": "LWSW-...",
    "products": [
        {
            "product_slug": "give",
            "tier": "give-pro",
            "pending_tier": null,
            "status": "active",
            "expires": "2026-12-31 00:00:00",
            "activations": {
                "site_limit": 0,
                "active_count": 1,
                "over_limit": false
            },
            "installed_here": true,
            "validation_status": "valid",
            "is_valid": true
        }
    ]
}
```

When no key exists, returns `{ "key": null, "products": [] }`.

## POST /stellarwp/uplink/v1/license

Validates a license key against the Licensing API and stores it. Verifies the key is recognized (has products) and caches the product list, but does not activate any product or consume a seat.

### Parameters

| Parameter | Type    | Required | Description                             |
| --------- | ------- | -------- | --------------------------------------- |
| `key`     | string  | yes      | License key (must have `LWSW-` prefix)  |
| `network` | boolean | no       | Store at network level (multisite only) |

### Response (200)

```json
{
    "key": "LWSW-..."
}
```

### Errors

| HTTP | Code                              | Meaning                       |
| ---- | --------------------------------- | ----------------------------- |
| 400  | (validation)                      | Missing key or invalid format |
| 422  | `stellarwp-uplink-invalid-key`    | Key not recognized by API     |
| 500  | `stellarwp-uplink-store-failed`   | Key could not be persisted    |

## POST /stellarwp/uplink/v1/license/validate

Validates a product on this domain using the stored license key. Calls the Licensing API validate endpoint, which may consume an activation seat on first call for a new domain. On success, the cached product list is refreshed so the next GET reflects the new activation state.

Non-valid statuses from the licensing API (`expired`, `out_of_activations`, `suspended`, etc.) are returned as errors. They do not consume a seat or change any state.

### Parameters

| Parameter      | Type   | Required | Description             |
| -------------- | ------ | -------- | ----------------------- |
| `product_slug` | string | yes      | The product to validate |

### Response

Returns `201 Created` with no body.

### Errors

| HTTP | Code                                   | Meaning                          |
| ---- | -------------------------------------- | -------------------------------- |
| 400  | (validation)                           | Missing product_slug             |
| 422  | `stellarwp-uplink-invalid-key`         | No license key is stored         |
| 422  | `stellarwp-uplink-product-not-found`   | Product not found under this key |
| 422  | `stellarwp-uplink-expired`             | Subscription has expired         |
| 422  | `stellarwp-uplink-suspended`           | Subscription is suspended        |
| 422  | `stellarwp-uplink-cancelled`           | Subscription is cancelled        |
| 422  | `stellarwp-uplink-out-of-activations`  | All activation seats are in use  |
| 422  | `stellarwp-uplink-license-suspended`   | License is suspended             |
| 422  | `stellarwp-uplink-license-banned`      | License is banned                |
| 422  | `stellarwp-uplink-no-subscription`     | No subscription for this product |

## DELETE /stellarwp/uplink/v1/license

Removes the locally stored license key. Does not free any activation seats on the licensing service.

### Parameters

| Parameter | Type    | Required | Description                                |
| --------- | ------- | -------- | ------------------------------------------ |
| `network` | boolean | no       | Delete from network level (multisite only) |

### Response

Returns `204 No Content` with no body.
