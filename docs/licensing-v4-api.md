# Licensing Service V4 API

Reference for the two v4 endpoints that the `Licensing_Client` contract abstracts.

## GET /stellarwp/v4/products

Fetches all products (subscriptions) under a license key. When a domain is provided, includes per-product validation status and installation state.

### Request

| Parameter | Type   | Required | Description                                     |
| --------- | ------ | -------- | ----------------------------------------------- |
| key       | string | yes      | License key (e.g. `LWSW-...`)                   |
| domain    | string | no       | Site domain. Adds validation status per product |

### Response (200)

```json
{
    "products": [
        {
            "product_slug": "kadence",
            "tier": "professional",
            "pending_tier": null,
            "status": "active",
            "expires": "2027-02-18 00:00:00",
            "activations": {
                "site_limit": 5,
                "active_count": 2,
                "over_limit": false
            },
            "installed_here": true,
            "validation_status": "valid",
            "is_valid": true
        }
    ]
}
```

The `installed_here`, `validation_status`, and `is_valid` fields are only present when a domain is provided in the request.

`pending_tier` is non-null when the subscription has a scheduled downgrade.

`site_limit: 0` means unlimited activations (`over_limit` is always false).

### Error responses

| HTTP | Code               | Meaning                     |
| ---- | ------------------ | --------------------------- |
| 404  | `not_found`        | License key not recognized  |
| 403  | `license_inactive` | License is suspended/banned |
| 500  | `unknown_error`    | Unexpected server error     |

---

## POST /stellarwp/v4/licenses/validate

Validates a license for a specific product on a domain. Automatically creates an activation record on first call for a new domain (if the license is valid and has available seats).

### Request

| Parameter    | Type   | Required | Description        |
| ------------ | ------ | -------- | ------------------ |
| key          | string | yes      | License key        |
| product_slug | string | yes      | Product identifier |
| domain       | string | yes      | Domain to validate |

### Response (200)

```json
{
    "status": "valid",
    "is_valid": true,
    "license": {
        "key": "LWSW-TEST-TEST-TEST-TEST-TEST",
        "status": "active"
    },
    "subscription": {
        "product_slug": "kadence",
        "tier": "kadence-agency",
        "site_limit": 5,
        "expiration_date": "2027-02-18 00:00:00",
        "status": "active"
    },
    "activation": {
        "domain": "example.com",
        "activated_at": "2026-02-18 00:00:00"
    }
}
```

The `activation` object is null when the domain has no activation record (status will be `not_activated`).

### Error responses

| HTTP | Code          | Meaning                    |
| ---- | ------------- | -------------------------- |
| 404  | `invalid_key` | License key not recognized |

---

## Validation statuses

The validation pipeline runs these checks in order, stopping at the first failure.

| Status               | `is_valid` | Meaning                                                |
| -------------------- | ---------- | ------------------------------------------------------ |
| `valid`              | true       | License active, subscription current, domain activated |
| `not_activated`      | false      | Subscription valid but domain has no activation        |
| `expired`            | false      | Subscription past its expiration date                  |
| `suspended`          | false      | Subscription suspended                                 |
| `cancelled`          | false      | Subscription cancelled                                 |
| `out_of_activations` | false      | All seats consumed, new domain cannot activate         |
| `no_subscription`    | false      | License exists but no subscription for this product    |
| `license_suspended`  | false      | License-level suspension (all products affected)       |
| `license_banned`     | false      | License permanently banned (all products affected)     |
| `invalid_key`        | false      | License key not recognized                             |

### Pipeline order

1. **Check subscription** - does a subscription exist for this product?
2. **Check expiration** - is the subscription past its expiration date?
3. **Check status** - is the subscription suspended or cancelled?
4. **Check seat limit** - are all activation seats consumed? (skipped for unlimited or existing activations)
5. **Default** - `valid` if an active activation exists, `not_activated` otherwise

---

## License and subscription statuses

These are distinct from validation statuses. They describe the state of the license or subscription record itself.

### License status

| Status      | Meaning                                                        |
| ----------- | -------------------------------------------------------------- |
| `active`    | Normal operation                                               |
| `suspended` | Temporarily suspended (maps to `license_suspended` validation) |
| `banned`    | Permanently banned (maps to `license_banned` validation)       |

### Subscription status

| Status      | Meaning               |
| ----------- | --------------------- |
| `active`    | Normal operation      |
| `suspended` | Temporarily suspended |
| `cancelled` | Permanently cancelled |
