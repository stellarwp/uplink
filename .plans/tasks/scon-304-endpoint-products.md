---
ticket: SCON-304
url: https://stellarwp.atlassian.net/browse/SCON-304
status: in-progress
---

# Include products in the license REST response

## Problem

The `GET /stellarwp/uplink/v1/license` endpoint only returns `{ key: string | null }`. The full licensing products response (tiers, subscription status, expiration dates, validation state) is fetched and cached server-side in `Product_Collection` but never exposed to the frontend.

The frontend needs this data to support upcoming work around license validity, grace periods, and product-level status. Without it, the UI can't show which tier the user is on, whether a subscription is expiring, or make decisions about feature validity on the client side.

## Solution

### GET /license

Returns `{ key, products[] }`. Products come from the cached `Product_Collection` (originally fetched from the v4 `/products` endpoint). When no license key is stored, `products` is an empty array.

### POST /license

Accepts `key` (required) and `network` (optional). Fetches the product catalog from the v4 API to verify the key is recognized, then stores it. Does not activate any product or consume a seat. Returns `{ key }` on success.

After a successful POST, the frontend invalidates the GET `/license` resolver to refetch the product list.

### POST /license/validate

Accepts `product_slug` (required). Operates on the stored license key. Calls the v4 validate endpoint, which may consume an activation seat. Returns the `Validation_Result` (status, license, subscription, activation). On success, clears the cached product list so the next GET reflects the new activation state.

This is a separate endpoint because storing a key and consuming a seat are different concerns. The user may want to enter/change their key without immediately activating a product.

### DELETE /license

Returns `204 No Content` with no body. This only removes the locally stored key. It does not free activation seats on the licensing service.

### Frontend

The store has three async operations, each with their own loading/error state:

- `storeLicense(key)` - POST /license
- `validateProduct(productSlug)` - POST /license/validate
- `deleteLicense()` - DELETE /license

The `getLicense` resolver fetches `{ key, products[] }` from GET and populates the store.
