---
ticket: SCON-310
url: https://stellarwp.atlassian.net/browse/SCON-310
status: todo
---

# Discovery: Uplink v3 responsibilities for Kadence AI credits

## Problem

Kadence AI credits are moving to licensing v4. Today, Kadence Blocks manages credit display independently by querying a remote credits API (`kadence-credits/v1/get-remaining`) and caching the balance in localStorage. Credit consumption happens server-side in the Prophecy SaaS, which the WordPress plugin proxies requests to with token-based auth.

With Kadence adopting Uplink v3 for unified licensing, it's unclear what responsibilities Uplink should take on for credits. The v4 licensing API will be the new source of credit balance data, but the boundaries between Uplink, the Prophecy SaaS, and Kadence's own code haven't been defined. We know Uplink does not need to handle credit consumption (that stays in the authenticated SaaS context), but we need to determine what Uplink does need to provide.

## Proposed solution

Discovery task to map out Uplink v3's credit responsibilities by answering:

- **What credit data will the v4 licensing API provide?** Will credit balance be part of the existing licensing response (alongside products and tiers), or a separate call? Is it per-key or per-product?
- **What does Uplink need to surface?** At minimum, a way for Kadence (and potentially other products) to query the current credit balance through Uplink's public API (global functions, REST endpoints) rather than hitting a separate credits API directly.
- **What does Uplink NOT do?** Credit consumption stays in the Prophecy SaaS. Uplink is read-only for credits.
- **What changes in Kadence?** Today Kadence queries the credits API directly and handles 423 (exhausted) errors from the Prophecy proxy. Identify which parts of that flow change when credits come through Uplink and which stay the same.
- **Is this Kadence-specific or general?** Determine whether credits should be a generic concept in Uplink (available to any product) or scoped specifically to Kadence AI.

The output of this task is a clear definition of Uplink's credit responsibilities and a follow-up implementation ticket.
