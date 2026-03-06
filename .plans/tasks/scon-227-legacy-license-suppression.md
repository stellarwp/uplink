---
ticket: SCON-227
url: https://stellarwp.atlassian.net/browse/SCON-227
status: todo
---

# Plugin-level legacy license suppression

## Problem

Products like GiveWP, Solid, and Kadence have their own licensing systems — admin notices for expired keys, nag banners, validation checks. When a site has a unified license key covering that product, these legacy notices are wrong and confusing. The product's own licensing system doesn't know about the unified key, so it still thinks the product is unlicensed or expired.

## Proposed solution

The signal to suppress is the presence of the unified key itself (from the task above). Products check whether a unified key covers them, and if so, suppress their own legacy licensing UI.

There's an existing precedent for products negotiating with Uplink: Kadence Blocks uses `stellarwp/uplink/kadence-blocks/prevent_update_check` to tell Uplink v2 "don't manage my updates, I have my own licensing." With unified licensing the relationship inverts — the leader handles everything, so products that currently opt out of Uplink's update checks would need to stop doing so (or the leader overrides the filter) for products under a unified key.

Note: Uplink has global kill switches (`TRIBE_DISABLE_PUE`, `STELLARWP_LICENSING_DISABLED`) that disable the entire Uplink system — those are too broad for this. We need per-product awareness of the unified key, not a global off switch.
