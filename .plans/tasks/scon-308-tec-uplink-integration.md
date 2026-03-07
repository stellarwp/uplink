---
ticket: SCON-308
url: https://stellarwp.atlassian.net/browse/SCON-308
status: todo
---

# The Events Calendar: Uplink v3 integration

## Problem

The Events Calendar has its own licensing and update system (PUE) that manages license keys, admin notices, and validation independently. It does not participate in Uplink's unified licensing model. When a site has a unified `LWSW-` key covering The Events Calendar, there is no way for TEC to know about it. Its legacy licensing UI continues showing its own key status, and PUE continues running validation checks that may conflict with the unified system.

TEC also has broad kill switches like `TRIBE_DISABLE_PUE` and `STELLARWP_LICENSING_DISABLED` that disable the entire licensing system. These are too coarse for unified licensing, which needs per-product awareness rather than a global off switch.

## Proposed solution

Add Uplink as a Strauss dependency and wire The Events Calendar into the unified licensing system as a thin instance. This is a first pass that establishes the connection and begins legacy suppression. Follow-up tickets will be created for anything that requires deeper discovery.

### Product registration

Hook into `stellarwp/uplink/product_registry` to declare The Events Calendar as a unified licensing participant. The registration should provide the product slug (`the-events-calendar`), display name, installed version, and group (`the-events-calendar`). If TEC ships an embedded license key, include it so the leader can discover it.

### Legacy license reporting

Hook into `stellarwp/uplink/legacy_licenses` to report TEC's existing per-plugin license key to the leader. The leader displays legacy keys as informational cards in the unified admin UI with a link back to TEC's own licensing page. The data includes the key, slug, display name, brand, status, page URL, and expiration.

### Legacy suppression (first pass)

When `stellarwp_uplink_has_unified_license_key()` returns true, TEC should suppress its own licensing UI and validation behavior. This includes PUE validation, admin notices about key status, license nag banners, and any checks that conflict with the unified system.

Use `stellarwp_uplink_is_product_license_active( 'the-events-calendar' )` for more granular checks where TEC needs to know whether the unified key actually covers it before suppressing specific behavior.

Discovery work during this task will likely produce follow-up tickets, especially around PUE suppression which is deeply integrated into TEC and its add-on ecosystem.
