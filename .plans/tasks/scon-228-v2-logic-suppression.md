---
ticket: SCON-228
url: https://stellarwp.atlassian.net/browse/SCON-228
status: todo
---

# Uplink v2 per-resource logic suppression

## Problem

When Uplink detects a `LWSW-` unified key, the v2 per-resource licensing machinery should not run — no per-product key validation, no per-product admin fields, no per-product API calls to licensing v2/v3. Today every Uplink instance is self-sufficient: it stores its own key, validates it, renders admin fields. In the unified model the leader handles all of this. The thin instances need to get out of the way.

## Proposed solution

When an instance detects a unified key (the `LWSW-` prefix), it should skip wiring into the v2/v3 hooks — no validation hooks, no key storage hooks, no admin field rendering. The resource and license objects still get created (the instance needs to know what product it is), but the v2/v3 lifecycle short-circuits. This is the "thin instance" behavior described in `docs/v4-fat-leader-thin-instance.md` — the instance declares itself to the leader and defers to it for everything else.

Uplink v2 has several hooks and systems involved in per-resource licensing: `should_prevent_update_without_license` (blocks plugin updates when licensing is invalid), the `Update_Prevention` class, and the update check flow in `Plugin::check_for_updates()`. These are all part of the v2 per-resource machinery that the thin instance needs to skip when a unified key is present.
