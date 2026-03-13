# Uplink v3: Fat Leader / Thin Instance Architecture

## Core Concept

Uplink v3 introduces **unified licensing** with `LWSW-`-prefixed keys. This changes the relationship between Uplink instances: one instance becomes the **fat leader** (owns all unified licensing concerns) and every other instance becomes a **thin instance** (declares itself to the leader, then gets out of the way).

In per-resource licensing (v2/v3), every instance was self-sufficient. It stored its own key, validated against the licensing APIs, rendered admin fields, and managed its own state. The leader was just a tiebreaker for shared UI. Unified licensing inverts this: the leader takes over key storage, API delegation, feature catalogs, and the admin page. Individual instances become thin shells.

For the unified key model itself: how the key relates to Licensing, Portal, and the WordPress site, how seats work, and what happens in various key change scenarios. See [Unified License Key: System Design](unified-license-key-system-design.md).

## Leader Election

Multiple vendor-prefixed copies negotiate leadership through a shared global function, `_stellarwp_uplink_instance_registry()`, defined in `src/Uplink/global-functions.php`. Because global functions are declared once (PHP's `function_exists` guard), the static variable inside that function is shared by all vendor-prefixed copies regardless of which one's file was included first. Each instance calls `_stellarwp_uplink_instance_registry( Uplink::VERSION )` during bootstrap to register itself. Registrations are only accepted before `wp_loaded`, so all real instances (which initialize on `plugins_loaded`) can register, but nothing can inject fake versions after the bootstrap window closes.

`Version::is_highest()` (in `src/Uplink/Utils/Version.php`) reads the registry and returns `true` if this instance's version string is greater than or equal to all registered versions. `Version::should_handle( $action )` layers a per-responsibility mutex on top: it fires `do_action( 'stellarwp/uplink/handled/{action}' )` the first time a qualifying instance claims a responsibility, and any subsequent call (even from the same instance) sees `did_action()` return `true` and backs off. This ensures exactly one instance handles each shared responsibility — admin page, REST routes, etc. — even when two copies run the same version.

## Fat Leader

### Key and License State

The leader stores the site's unified key and the full product catalog response from the v4 licensing API. The key is the site's identity to Licensing; the catalog response is the source of truth for what products are entitled, what tiers they're on, and whether seats are available. See [Key Management](unified-license-key-system-design.md#the-unified-key) in the system design doc for how keys enter a site and the one-key-per-site rule.

### Licensing Lifecycle

The leader delegates to the v4 licensing client, an external Composer package consumed through the `Licensing_Client` contract (`src/Uplink/Licensing/Clients/Licensing_Client.php`). That contract exposes two operations. `get_products()` is a read-only bulk fetch — it sends the key and gets back the status of all products in a single response without consuming seats. `validate()` validates a single product against its key and may consume a seat as a side effect if the product hasn't been activated on this domain before.

The production implementation (`src/Uplink/Licensing/Clients/Http_Client.php`) uses PSR-18 HTTP interfaces for transport (see [Licensing: HTTP Infrastructure](licensing.md#http-infrastructure)). The `License_Repository` wraps the client with option-based state storage so the rest of Uplink never touches the client directly.
A mock implementation (`src/Uplink/Licensing/Clients/Fixture_Client.php`) is wired during development. The `Licensing_Repository` (`src/Uplink/Licensing/Repositories/Licensing_Repository.php`) wraps the client with transient caching so the rest of Uplink never touches the client directly.

### Feature Catalog

The leader fetches a feature catalog from the Commerce Portal API using the unified key. Products query features through global functions defined in `src/Uplink/functions.php` — `is_feature_enabled()` and `is_feature_available()`. Today the catalog fetch has no key to authenticate with; the unified key is what unblocks this.

### Admin UI

The leader renders the unified licensing page (the "Software Manager"). It shows the unified key status, product registrations, and legacy key cards that link back to each product's own licensing page.

## Thin Instance

A thin instance is any Uplink copy operating under a unified `LWSW-` key. It still creates its resource and license objects — it needs to know what product it is — but when it detects a `LWSW-` key, it skips wiring into v2/v3: no per-resource validation hooks, no per-resource admin fields, no per-resource API calls. The v4 path short-circuits the v3 machinery.

What a thin instance does: it declares itself to the leader through the product registry so the leader knows it exists. If it has an embedded key, it contributes it via the registry. On plugin activation or key entry, it fires an action that the leader handles. And it queries the leader through global functions like `is_feature_enabled()`.

What a thin instance does not do: validate keys, talk to licensing APIs, or render licensing admin fields.

## Cross-Instance Communication

All communication between vendor-prefixed copies happens through non-prefixed WordPress hooks. This is the only mechanism available — each copy has its own namespace and cannot reference another copy's classes.

### Product Registry

Products declare themselves to the leader through a cross-instance filter. Each instance contributes its slug, display name, product family group (`kadence`, `give`, `the-events-calendar`, `learndash`), and a contributed key if it has one. The leader reads this filter lazily, by the time it's consumed, all plugins have loaded and registered.

A product does not provide its tier. Tiers come from the v4 licensing API response, they're a property of the license, not the product.

### Legacy Key Registry

Products using per-resource v2/v3 keys register metadata so the leader can display informational cards in the unified admin UI. The leader does not validate legacy keys. It displays them as-is with a link to the product's own licensing page. Per-resource keys continue through the existing per-resource path unchanged.

### Operation Delegation

The leader delegates license operations back to the owning instance through shared hooks. `Uplink::register_cross_instance_hooks()` in `src/Uplink/Uplink.php` wires this up: each instance listens for `stellarwp/uplink/validate_license`, `stellarwp/uplink/set_license_key`, and `stellarwp/uplink/delete_license_key`, but only acts on resources present in its own collection.

## What Unified Licensing Does NOT Do

- **Replace per-resource licensing** products that haven't adopted it continue to use v2/v3 as-is
- **Validate legacy keys** the leader only displays them; validation stays in the per-resource path
- **Build the v4 licensing client** that's an external package; Uplink delegates to it
- **Migrate existing keys** no automatic conversion from per-resource to unified
- **Assign tiers** tiers come from the v4 API response, not from product declarations
