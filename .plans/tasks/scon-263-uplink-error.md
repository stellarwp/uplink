---
ticket: SCON-263
url: https://stellarwp.atlassian.net/browse/SCON-263
status: done
---

# Add frontend UplinkError

## Problem

`additionalErrors` is typed as `WpRestError[]` (plain objects). Code that receives the error can't walk the chain with typed access to `.code`, `.status`, `.data`.

## Proposed solution

Change `additionalErrors` from `WpRestError[]` to `UplinkError[]`. The constructor hydrates each entry through `new UplinkError()` so the entire chain is typed.
