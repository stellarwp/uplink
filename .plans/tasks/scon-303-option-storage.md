---
ticket: SCON-303
url: https://stellarwp.atlassian.net/browse/SCON-303
status: todo
---

# Persist catalog response in a wp_option

## Problem

The catalog response is stored in a transient (`stellarwp_uplink_catalog`) with a 12-hour TTL. When it expires and the API is unreachable, the catalog data vanishes. Feature resolution depends on the catalog to know what features exist, what tier they belong to, and what product they map to. Without it, the feature manager UI can't render and no features can be enabled or disabled.

The current implementation also caches errors with the same 12-hour TTL. A single failed API call poisons the cache for the full window, even if the API recovers minutes later. During that time the system behaves as if no catalog exists.

This is the same class of problem described in SCON-302 for the licensing products response. Both data sources need to survive API outages for the feature resolver to function.

## Proposed solution

Replace the transient with a `wp_option` that stores the full catalog state. The option should contain:

- **The last successful catalog response.** The `Catalog_Collection` data from the most recent successful API call.
- **The timestamp of that successful fetch.** So consumers can reason about staleness if needed.
- **The last error.** The `WP_Error` from the most recent failed attempt, if any. This surfaces problems in the UI without discarding the last known good catalog data.

On a failed fetch, the last successful response should be preserved. The error should be stored alongside it, not replace it.

The transient should be removed. Re-validation frequency becomes a separate concern from data persistence, same as SCON-302.

The `Catalog_Repository` should follow the same storage pattern as whatever SCON-302 establishes for `License_Repository`, so the two repositories stay consistent.
