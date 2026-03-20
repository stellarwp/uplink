---
ticket: SCON-302
url: https://stellarwp.atlassian.net/browse/SCON-302
status: todo
---

# Persist license response in a wp_option

## Problem

The products response from the licensing v4 API is currently stored in a transient with a 12-hour TTL. When the transient expires and the API is unreachable, the data disappears entirely. Feature flag resolution depends on knowing which products and tiers the site is entitled to. Without that data, the system can't make correct decisions about flag features, grandfathering, or tier-gated availability.

## Proposed solution

Replace the transient with a `wp_option` that stores the full license state. The option should contain:

- **The last successful products response.** The `Product_Collection` data from the most recent successful v4 API call.
- **The timestamp of that successful fetch.** So consumers can reason about staleness if needed.
- **The last error.** The `WP_Error` from the most recent failed attempt, if any. This helps surface problems in the UI without losing the last known good products data.

The transient should be removed. The option has no TTL, so the products data survives indefinitely. Re-validation frequency (how often we call the API to refresh) becomes a separate concern from data persistence.

`WP_Error` objects serialize safely into `wp_options` since all their properties are plain arrays. The codebase already stores them in transients, so this is a known-safe pattern.
