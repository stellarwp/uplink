---
ticket: SCON-305
url: https://stellarwp.atlassian.net/browse/SCON-305
status: todo
---

# GiveWP: Uplink v3 integration

## Problem

GiveWP has its own licensing system that manages license keys, admin notices, and validation checks independently. It does not participate in Uplink's unified licensing model. When a site has a unified `LWSW-` key covering GiveWP, there is no way for GiveWP to know about it, and its legacy licensing UI continues showing its own key status, which may conflict with or duplicate what the unified system provides.

## Proposed solution

Add Uplink as a Strauss dependency and wire GiveWP into the unified licensing system as a thin instance. This is a first pass that establishes the connection and begins legacy suppression. Follow-up tickets will be created for anything that requires deeper discovery.

### Product registration

Hook into `stellarwp/uplink/product_registry` to declare GiveWP as a unified licensing participant. The registration should provide the product slug (`give`), display name, installed version, and group (`givewp`). If GiveWP ships an embedded license key, include it so the leader can discover it.

### Legacy license reporting

Hook into `stellarwp/uplink/legacy_licenses` to report GiveWP's existing per-plugin license key to the leader. The leader displays legacy keys as informational cards in the unified admin UI with a link back to GiveWP's own licensing page. The data includes the key, slug, display name, brand, status, page URL, and expiration.

### Legacy suppression (first pass)

When `stellarwp_uplink_has_unified_license_key()` returns true, GiveWP should suppress its own licensing UI and validation behavior. This includes admin notices about key status, license nag banners, and any validation checks that would conflict with the unified system.

Use `stellarwp_uplink_is_product_license_active( 'give' )` for more granular checks where GiveWP needs to know whether the unified key actually covers it before suppressing specific behavior.

Discovery work during this task will likely produce follow-up tickets for suppression points that are deeply embedded in GiveWP's codebase or that require coordination with other GiveWP systems.
