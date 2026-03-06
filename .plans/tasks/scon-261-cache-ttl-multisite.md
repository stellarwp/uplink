---
ticket: SCON-261
url: https://stellarwp.atlassian.net/browse/SCON-261
status: todo
---

# Shorter error TTL and multisite-correct transients

## Problem

Product_Repository, Catalog_Repository, and Feature_Repository all cache `WP_Error` responses for the full 12-hour TTL, meaning a temporary network blip locks the management UI into an error state for half a day. Separately, all three use `set_transient()` (per-site), but the unified license key is stored at the network level via `get_network_option()`. On multisite, each subsite independently fetches and caches the same data, and cache invalidation on key change only clears the current site's transient.

## Proposed solution

- Reduce error cache TTL to 15 minutes so sites recover quickly after transient network failures. Successful responses keep the 12-hour TTL.
- Switch to `set_site_transient()` / `get_site_transient()` / `delete_site_transient()` so there's one cache per network matching the one unified key. Cache invalidation on key change then works across the whole multisite install.

Note: `is_active` (Flag_Strategy, Zip_Strategy) reads from local DB state only and is not affected by any of this. These changes only impact the management/REST layer.
