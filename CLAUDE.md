# StellarWP Uplink

A PHP library bundled by StellarWP WordPress plugins to handle licensing, updates, and feature management. Each StellarWP plugin ships its own vendor-prefixed copy via Strauss. Multiple copies coexist on a single WordPress site and negotiate leadership internally.

We are developing version 3. It is not released. Do not worry about backward compatibility or breaking changes. When something needs to change, refactor to fit. Do not add shims, aliases, or deprecation layers.

See `docs/uplink-v3.md` for the full architecture overview and links to the subsystem docs (licensing, catalog, feature resolution).

## V3 code

The v3 subsystems live in these directories. This is where active development happens:

- `src/Uplink/Catalog/` - Product catalog from the Commerce Portal (products, tiers, features)
- `src/Uplink/Features/` - Feature resolution (joins catalog + licensing), strategies, Manager
- `src/Uplink/Licensing/` - Unified license key management, validation, product registry
- `src/Uplink/API/REST/V1/` - WordPress REST endpoints for the above
- `src/Uplink/Legacy/` - Adapter for reading old per-plugin license data
- `src/Uplink/Admin/Feature_Manager_Page.php` - Admin page that hosts the React app
- `src/Uplink/Utils/` - Shared utilities (Collection, Version, Cast, License_Key)
- `resources/js/` - React frontend (TypeScript, Tailwind, Zustand)

## V2 code we are not using

The v3 subsystems do not depend on these. They still exist for products that haven't adopted unified licensing, but v3's Catalog, Features, Licensing, and REST layers bypass them entirely:

- `src/Uplink/API/V3/` - Stellar Licensing V3 API client
- `src/Uplink/Resources/` - Old per-plugin registration and license storage
- `src/Uplink/Messages/` - Legacy message formatting
- `src/Uplink/Notice/` - Legacy admin notices
- `src/Uplink/Pipeline/` - Legacy validation pipeline
- `src/Uplink/Exceptions/` - Legacy registration errors
- `src/Uplink/Admin/` (except Feature_Manager_Page) - Old license field UI, plugins page hooks
-

## V2 code we still use

Some legacy infrastructure is still needed:

- `src/Uplink/Config.php` - Container setup, hook prefix, storage driver config
- `src/Uplink/Contracts/` - Abstract_Provider base class
- `src/Uplink/Site/` - Domain and environment data
- `src/Uplink/Storage/` - Option_Storage driver
- `src/Uplink/View/` - Template rendering
- `src/Uplink/Uplink.php` - Bootstrap and provider registration
- `src/Uplink/Register.php` - Plugin/service registration entry point
- `src/Uplink/Auth/` - Token management for OAuth (we actually need to answer this question)

## Testing

Tests use Codeception with `slic` for containerized WordPress test execution. See `docs/testing.md`.

Fixture data lives in `tests/_data/`. The catalog and licensing fixture files are working prototypes, not finalized API contracts.

## PHP version

The minimum PHP version is 7.4 (see `composer.json`). Do not use language features from PHP 8.0+ (named arguments, union types, match expressions, constructor promotion, etc.).

## Debugging

All debug logging goes through the `With_Debugging` trait (`src/Uplink/Traits/With_Debugging.php`). Never call `error_log()` directly — use `debug_log()`, `debug_log_throwable()`, or `debug_log_wp_error()` instead. Since it's a trait, standalone global functions that aren't inside a class can't use it — route those through a class that uses the trait (see `Global_Function_Registry` for the pattern).

## Key principles

- One unified `LWSW-` license key per site, shared by all products
- A product is a brand family (Kadence, GiveWP, etc.), not a plugin
- Features are the resolved join of catalog + licensing data, not a third data source
- The `Licensing_Client` and `Catalog_Client` contracts exist so the backend can be swapped without affecting the rest of the system
- Flag features are grandfathered on expiration. Once enabled, they stay enabled.
