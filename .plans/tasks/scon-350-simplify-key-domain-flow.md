---
ticket: SCON-350
url: https://stellarwp.atlassian.net/browse/SCON-350
status: todo
---

# Simplify license key and domain dependency flow

## Problem

The license key and site domain flow through too many classes in the feature resolution chain. `Feature_Manager` stores both as constructor properties (captured once at DI registration) and passes them on every call to `Feature_Repository`, which passes them to `Resolve_Feature_Collection`.

In practice, the key is only needed by `Licensing_Client`, and `License_Manager` already reads it on-demand from the repository. `Feature_Repository` receives the key as a parameter but never uses it. The domain is only needed by `Resolve_Feature_Collection` when it calls `License_Manager::get_products()`.

This creates unnecessary coupling and a staleness bug. If the license key changes after the `Feature_Manager` singleton is constructed, the manager holds the old value. The `stellarwp/uplink/unified_license_key_changed` hook clears transients but does not reset the manager's cached key.

## Proposed solution

Remove key and domain as constructor dependencies from `Feature_Manager`. The key should only be injected into the `Licensing_Client` (or read on-demand by `License_Manager` as it does today). The domain should be resolved at call time by the class that actually needs it (`Resolve_Feature_Collection`), either injected there or read from `Data::get_domain()`.

Drop the unused `$key` parameter from `Feature_Repository::get()`, `refresh()`, and `resolve()`. This eliminates the unnecessary pass-through and the staleness risk.
