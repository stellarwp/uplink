---
ticket: SCON-306
url: https://stellarwp.atlassian.net/browse/SCON-306
status: todo
---

# Kadence: Uplink v3 integration

## Problem

Kadence has its own licensing system independent of Uplink. It also actively opts out of Uplink v2's update checks via the `stellarwp/uplink/kadence-blocks/prevent_update_check` filter. When a site has a unified `LWSW-` key covering Kadence, there is no way for Kadence to know about it. Its legacy licensing UI continues running independently, and its opt-out of Uplink update checks prevents the unified system from managing updates on its behalf.

## Proposed solution

Add Uplink as a Strauss dependency and wire Kadence into the unified licensing system as a thin instance. This is a first pass that establishes the connection and begins legacy suppression. Follow-up tickets will be created for anything that requires deeper discovery.

### Product registration

Hook into `stellarwp/uplink/product_registry` to declare Kadence as a unified licensing participant. The registration should provide the product slug (`kadence`), display name, installed version, and group (`kadence`). If Kadence ships an embedded license key, include it so the leader can discover it.

### Legacy license reporting

Hook into `stellarwp/uplink/legacy_licenses` to report Kadence's existing per-plugin license key to the leader. The leader displays legacy keys as informational cards in the unified admin UI with a link back to Kadence's own licensing page. The data includes the key, slug, display name, brand, status, page URL, and expiration.

### Legacy suppression (first pass)

When `stellarwp_uplink_has_unified_license_key()` returns true, Kadence should suppress its own licensing UI and validation behavior. This includes admin notices, license nag banners, and validation checks that would conflict with the unified system.

Kadence's `prevent_update_check` filter is an important case. Under unified licensing, the leader should manage Kadence's updates. When a unified key is present, Kadence should stop opting out of Uplink's update path so the leader can take over.

Use `stellarwp_uplink_is_product_license_active( 'kadence' )` for more granular checks where Kadence needs to know whether the unified key actually covers it before suppressing specific behavior.

Discovery work during this task will likely produce follow-up tickets for suppression points embedded in Kadence's codebase.
