---
ticket: SCON-226
url: https://stellarwp.atlassian.net/browse/SCON-226
status: todo
---

# Unified license key storage and instance reporting

## Problem

There's no mechanism for Uplink instances to report that they have a unified license key, and no central place for the leader to store and expose it. The suppression tasks below and the licensing lifecycle all depend on knowing whether a unified key is present on the site, but that foundation doesn't exist yet.

The unified key model is documented in `docs/unified-license-key-system-design.md` — one `LWSW-` key per site, all products share it, keys enter via embedded license files or user entry, existing key takes precedence over newly contributed keys.

## Proposed solution

Instances report their key (or lack of one) to the leader through the product registry (the cross-instance filter described in `docs/v4-fat-leader-thin-instance.md`). The leader stores the canonical unified key. The presence of the unified key is the signal — to Uplink's own v2 machinery and to product legacy licensing systems — that the leader is in control.
