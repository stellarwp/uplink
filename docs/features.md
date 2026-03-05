# Features

## Summary

The Features subsystem is the resolved output of combining [Catalog](catalog.md) data with [Licensing](licensing.md) data. The catalog says "Kadence includes Blocks Pro at the Basic tier." Licensing says "this key has Kadence at the Pro tier." Features joins the two and concludes: "Blocks Pro is available, and here's how to install it."

Features are not a third data source. They are the computed intersection of what exists (catalog) and what's entitled (licensing), plus local state tracking for what's actually enabled on the site.

> **Development status.** The resolution algorithm, strategy pattern, and caching approach are stable. The specific data shapes that feed into resolution (catalog features, tier slugs, licensing responses) are still being finalized. As those upstream contracts change, the resolution layer will adapt.

## What a Feature Is

A feature is a single capability, plugin, theme, or flag within a product family. Each feature has two independent states:

- **Available**: the customer's license tier meets or exceeds the feature's minimum tier requirement. Computed from catalog + licensing data.
- **Enabled**: the feature is actively turned on for this site. Determined by local state (plugin activation status or an option flag).

A feature can be available but not enabled (the customer qualifies but hasn't turned it on). A feature cannot be enabled if it's not available, with one exception: grandfathered flags.

## Feature Types

Features come in two types, mapped from the catalog's delivery types during resolution:

### Plugin

A standalone installable, either a WordPress plugin or theme. The catalog provides the `plugin_file` path, `download_url`, and `is_dot_org` flag. The feature system handles downloading, installing, activating, and deactivating through WordPress's plugin/theme infrastructure.


### Flag

A capability toggle within an existing plugin, not a separate installable. Enabling a flag sets a WordPress option, and the owning plugin checks that option to unlock functionality within its own codebase. These are in-plugin feature flags gated by tier.

#### Flag Grandfathering

Flags follow a "once enabled, always honored" rule. If a customer enables a flag while their license qualifies, the flag stays enabled even if the license later expires or the customer downgrades below the flag's minimum tier.

What changes on expiration or downgrade:

- **Already-enabled flags continue to work.** The stored option is not cleared.
- **New flags cannot be enabled.** The tier check blocks it.
- **Disabled flags cannot be re-enabled.** If the customer disables a grandfathered flag, they cannot turn it back on without a qualifying license.

Flags control functionality baked into an already-installed plugin, so there's nothing to "uninstall." Revoking active functionality on license expiration would degrade the customer's site.

## Resolution

The `Resolve_Feature_Collection` class joins catalog and licensing data to produce a `Feature_Collection`. For each product in the catalog, it finds the matching licensing entry and compares tier ranks to determine which features are available.

Availability is determined by comparing integer ranks, not tier slug strings. The catalog defines the rank for each tier (e.g., `kadence-basic` = 1, `kadence-pro` = 2, `kadence-agency` = 3). A feature is available when the customer's tier rank meets or exceeds the feature's minimum tier rank.

If a product has no licensing entry, the tier rank is `0`, making all of its features unavailable. If a feature's `minimum_tier` slug isn't found in the tier collection, the minimum rank defaults to `PHP_INT_MAX`, also making it unavailable.

The mapping from catalog type strings to Feature subclasses is extensible:

| Catalog type | Feature class | Notes                                   |
| ------------ | ------------- | --------------------------------------- |
| `plugin`     | `Plugin`      | Installable WordPress plugin            |
| `theme`      | `Theme`       | Installable WordPress theme             |
| `flag`       | `Flag`        | Option-based toggle                     |

## The Manager

The `Manager` is the public interface for all feature operations. It wraps the resolution layer and the strategy layer into a single API.

| Method                       | Returns                        | Purpose                                          |
| ---------------------------- | ------------------------------ | ------------------------------------------------ |
| `get_features()`             | `Feature_Collection\|WP_Error` | Get all resolved features                        |
| `get_feature(string $slug)`  | `Feature\|null`                | Look up a single feature by slug                 |
| `is_available(string $slug)` | `bool\|WP_Error`               | Check if the customer's tier qualifies           |
| `is_enabled(string $slug)`   | `bool\|WP_Error`               | Check if the feature is currently active locally |
| `enable(string $slug)`       | `true\|WP_Error`               | Enable a feature                                 |
| `disable(string $slug)`      | `true\|WP_Error`               | Disable a feature                                |

Two convenience functions in `src/Uplink/functions.php` provide the simplest API:

- **`is_feature_enabled(string $slug): bool|WP_Error`**: is the feature in the catalog AND active locally?
- **`is_feature_available(string $slug): bool|WP_Error`**: is the feature in the catalog and does the tier qualify?

### WordPress Hooks

The Manager fires actions before and after enable/disable operations, both globally and per-slug:

- `stellarwp/uplink/feature_enabling` / `stellarwp/uplink/{slug}/feature_enabling`
- `stellarwp/uplink/feature_enabled` / `stellarwp/uplink/{slug}/feature_enabled`
- `stellarwp/uplink/feature_disabling` / `stellarwp/uplink/{slug}/feature_disabling`
- `stellarwp/uplink/feature_disabled` / `stellarwp/uplink/{slug}/feature_disabled`

## Strategies

Each feature type has a strategy that defines how enable, disable, and active-state checking work.

### Flag Strategy

The simplest strategy. A WordPress option (`stellarwp_uplink_feature_{slug}_active`) is the source of truth. Enabling sets it to `'1'`, disabling sets it to `'0'`. The tier check only gates enabling. Disabling is always allowed, but re-enabling requires a qualifying license.

The option persists across license changes. This is what makes flag grandfathering work.

### Plugin Strategy

Manages the full WordPress plugin lifecycle. Live WordPress plugin state is the source of truth.

Enabling a Plugin feature installs the plugin (if needed) and activates it. Disabling deactivates it but never deletes plugin files. The strategy includes ownership verification, checking the `Author` header against the feature's expected authors to prevent accidentally managing a third-party plugin that shares a directory name.

Plugin installs use per-slug transient locks with a 120-second TTL to prevent concurrent install races.

The stored option self-heals: if the live plugin state drifts from the stored option (e.g., a user deactivates a plugin through the WordPress plugins page), the stored state updates to match on the next check.

## Caching and Data Access

### Feature Repository

The `Feature_Repository` caches the resolved `Feature_Collection` in a WordPress transient (`stellarwp_uplink_feature_catalog`) with a 12-hour TTL.

The cache entry includes a hash of the license key. On each read, the repository compares the current key's hash against the cached hash. If they differ, the cache is discarded and features are re-resolved. This handles key changes without manual invalidation.

`refresh()` explicitly clears the cache and re-resolves.

### Stored Feature State

Each feature's enabled/disabled state is stored in a WordPress option (`stellarwp_uplink_feature_{slug}_active`) with autoload enabled, since feature state is checked on every page load.

## Feature Collection

The `Feature_Collection` is a typed, keyed collection of `Feature` objects with built-in filtering:

```php
$features->filter(
    group: 'kadence',      // product family
    tier: 'kadence-pro',   // minimum tier
    available: true,       // only available features
    type: 'plugin',        // only installable features
);
```

All filter parameters are optional. The filter returns a new collection. The original is not mutated.

## REST API

The Feature Controller exposes four endpoints under `stellarwp/uplink/v1`. All require `manage_options` capability.

| Route                      | Method | Purpose                                                                    |
| -------------------------- | ------ | -------------------------------------------------------------------------- |
| `/features`                | GET    | List features with optional filters (`group`, `tier`, `available`, `type`) |
| `/features/{slug}`         | GET    | Get a single feature                                                       |
| `/features/{slug}/enable`  | POST   | Enable a feature                                                           |
| `/features/{slug}/disable` | POST   | Disable a feature                                                          |

Each feature in the response includes an `is_enabled` field computed live from the strategy, not from cached state.

## Error Codes

| Constant                         | HTTP | Meaning                                                |
| -------------------------------- | ---- | ------------------------------------------------------ |
| `FEATURE_NOT_FOUND`              | 404  | Feature slug doesn't exist in the resolved catalog     |
| `FEATURE_TYPE_MISMATCH`          | 400  | Feature type doesn't match the strategy being used     |
| `INSTALL_LOCKED`                 | 409  | Another install for this plugin is already in progress |
| `PLUGIN_OWNERSHIP_MISMATCH`      | 409  | A different developer's plugin occupies the directory  |
| `DEACTIVATION_FAILED`            | 409  | Plugin stayed active after deactivation attempt        |
| `REQUIREMENTS_NOT_MET`           | 422  | PHP or WordPress version requirements not met          |
| `INSTALL_FAILED`                 | 422  | `Plugin_Upgrader::install()` failed                    |
| `ACTIVATION_FATAL`               | 422  | PHP fatal error or `die()` during plugin activation    |
| `ACTIVATION_FAILED`              | 422  | `activate_plugin()` returned an error                  |
| `PLUGIN_NOT_FOUND_AFTER_INSTALL` | 422  | Expected plugin file missing after ZIP extraction      |
| `DOWNLOAD_LINK_MISSING`          | 422  | `plugins_api()` returned no download link              |
| `UNKNOWN_FEATURE_TYPE`           | 422  | No Feature subclass registered for the catalog type    |
| `FEATURE_REQUEST_FAILED`         | 502  | Resolution failed (catalog or licensing API error)     |
| `FEATURE_CHECK_FAILED`           | 502  | Unexpected error during availability check             |
| `INVALID_RESPONSE`               | 502  | Catalog response couldn't be parsed                    |
| `PLUGINS_API_FAILED`             | 502  | WordPress `plugins_api()` call failed                  |

## Relationship to Licensing and Catalog

Features are the product of combining catalog and licensing data. Neither is sufficient alone.

| Data                                 | Source                                                     |
| ------------------------------------ | ---------------------------------------------------------- |
| Feature exists                       | Catalog                                                    |
| Feature's minimum tier               | Catalog                                                    |
| Feature's delivery type and metadata | Catalog                                                    |
| Tier rank hierarchy                  | Catalog                                                    |
| Customer's tier for a product        | Licensing                                                  |
| Whether the key is valid             | Licensing                                                  |
| **Whether feature is available**     | **Computed: catalog minimum rank vs. licensing tier rank** |
| Whether feature is enabled           | Local state (WordPress options / plugin activation)        |

When the license key changes, the feature cache auto-invalidates. A license upgrade or downgrade changes which features are available on the next resolution.

## What Features Does Not Do

- **Fetch its own data**: features are resolved from catalog and licensing data. There is no separate "features API."
- **Delete plugins**: disabling a Plugin feature deactivates it but never removes files.
- **Manage seats**: seat consumption happens in the licensing layer during validation, not during feature enable/disable.
- **Override tier gating for new enables**: if a customer's tier doesn't qualify, new features can't be enabled. Grandfathered flags are the exception.
- **Handle updates**: plugin/theme updates flow through a separate system that hooks into WordPress's native update infrastructure.
