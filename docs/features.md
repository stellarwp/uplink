# Features

## Summary

The Features subsystem is the resolved output of combining [Catalog](catalog.md) data with [Licensing](licensing.md) data. The catalog says "Kadence includes Blocks Pro at the Basic tier." Licensing says "this key has Kadence at the Pro tier." Features joins the two and concludes: "Blocks Pro is available, and here's how to install it."

Features are not a third data source. They are the computed intersection of what exists (catalog) and what's entitled (licensing), plus local state tracking for what's actually enabled on the site.

> **Development status.** The resolution algorithm, strategy pattern, and caching approach are stable. The specific data shapes that feed into resolution (catalog features, tier slugs, licensing responses) are still being finalized.

## Feature States

Every feature has two independent states:

- **Available**: the feature's slug appears in the capabilities array returned by the licensing API for this product. Computed from the licensing response — the catalog defines what features exist and their metadata, but capabilities are the source of truth for access.
- **Enabled**: the feature is actively turned on for this site. A feature cannot be enabled without being available, with one exception: grandfathered flags.

## Feature Types

Each feature type has a strategy that defines how enable, disable, and active-state checking work. The mapping from catalog delivery types to feature classes:

### Plugin

An installable WordPress plugin. The catalog provides `plugin_file`, `download_url`/`is_dot_org`.

| Aspect              | Behavior                                                                               |
| ------------------- | -------------------------------------------------------------------------------------- |
| **Source of truth** | Live WordPress plugin activation state — no DB option stored                           |
| **Enable**          | Installs (if needed) and activates the plugin                                          |
| **Disable**         | Deactivates the plugin but never deletes files                                         |
| **Ownership**       | Author header checked against expected authors to prevent managing third-party plugins |

### Theme

An installable WordPress theme. The theme's `feature_slug` is its WordPress slug (used for installation, `get_stylesheet()`, etc.). The catalog provides `download_url`/`is_dot_org`.

| Aspect              | Behavior                                                                                                                                                                                               |
| ------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Source of truth** | Theme disk presence — no DB option stored                                                                                                                                                              |
| **Enable**          | Installs the theme but does not switch to it. Users activate themes via Appearance > Themes                                                                                                            |
| **Disable**         | Does **not** delete files. Returns success if already absent; returns `THEME_DELETE_REQUIRED` if still on disk. Programmatic deletion is intentionally unsupported — it's destructive and irreversible |
| **Ownership**       | Author header checked via `wp_get_theme()`                                                                                                                                                             |

### Flag

A capability toggle within an existing plugin, not a separate installable. The owning plugin checks a WordPress option to unlock functionality.

| Aspect              | Behavior                                                                         |
| ------------------- | -------------------------------------------------------------------------------- |
| **Source of truth** | WordPress option `stellarwp_uplink_feature_{slug}_active` (autoloaded)           |
| **Enable**          | Sets option to `'1'`. Requires a qualifying tier                                 |
| **Disable**         | Sets option to `'0'`. Always allowed, but re-enabling requires a qualifying tier |

**Grandfathering:** Once a flag is enabled, it stays enabled even if the license expires or the customer downgrades. The stored option is never cleared by the system. New flags cannot be enabled without a qualifying license, and a disabled grandfathered flag cannot be re-enabled without one.

### Install Lock

Plugin and Theme features share a global transient lock (`stellarwp_uplink_install_lock`, 120s TTL). Only one installable feature can install at a time. Flags are unaffected.

## Resolution

`Resolve_Feature_Collection` joins catalog and licensing data to produce a `Feature_Collection`. Availability is determined by checking whether the feature's slug appears in the product entry's `capabilities` array from the licensing response.

The catalog still defines which features exist, their metadata (name, description, type, minimum tier for display), and which tier they belong to for UI purposes. But the `capabilities` array is what decides access. This allows the licensing service to handle cases the catalog alone cannot: grandfathered access after a tier restructure, one-time promotional grants, or individual exceptions made for a specific license.

For `Installable` features (Plugin, Theme), the resolver also reads `installed_version` from disk and stores it on the resolved Feature. This is the version currently on the site, distinct from the catalog's `version` which is the latest available. Flag features always have `installed_version: null`.

`has_update()` is a computed method on the `Installable` interface that centralizes the update-available check. It returns `true` when the feature is installed on disk and `version_compare( catalog_version, installed_version, '>' )`. Both Plugin and Theme implement this method. The update handlers (`Plugin_Handler`, `Theme_Handler`) delegate to this result (via the `has_update` field in the update data array) rather than performing their own inline comparison.

Edge cases:

- No licensing entry for a product (unlicensed): the resolver falls back to tier rank comparison using rank 0, making only free-tier features (`minimum_tier` at rank 0) available. Paid-tier features are unavailable.
- A feature capable but outside the catalog tier: it is available — capabilities override the catalog tier.
- A feature in the customer's catalog tier but absent from capabilities: it is unavailable — capabilities are the authority.

## The Manager

The `Manager` is the public interface for all feature operations.

| Method                       | Returns                        | Purpose                                            |
| ---------------------------- | ------------------------------ | -------------------------------------------------- |
| `get_all()`                  | `Feature_Collection\|WP_Error` | Get all resolved features with live is_enabled     |
| `get(string $slug)`          | `Feature\|null`                | Look up a single feature with live is_enabled      |
| `exists(string $slug)`       | `bool\|WP_Error`               | Check if the feature is in the catalog             |
| `is_available(string $slug)` | `bool\|WP_Error`               | Check if the customer's tier includes this feature |
| `is_enabled(string $slug)`   | `bool\|WP_Error`               | Check if the feature is active locally             |
| `enable(string $slug)`       | `Feature\|WP_Error`            | Enable a feature, return updated Feature           |
| `disable(string $slug)`      | `Feature\|WP_Error`            | Disable a feature, return updated Feature          |
| `update(string $slug)`       | `Feature\|WP_Error`            | Update a feature to latest version                 |

Global convenience functions in `src/Uplink/global-functions.php` (non-namespaced, always delegate to the version leader):

- **`stellarwp_uplink_is_feature_enabled(string $slug): bool|WP_Error`** — in the catalog AND active locally?
- **`stellarwp_uplink_is_feature_available(string $slug): bool|WP_Error`** — does the customer's tier include this feature?

### WordPress Hooks

Actions fired before and after enable/disable, both globally and per-slug:

- `stellarwp/uplink/feature_enabling` / `stellarwp/uplink/{slug}/feature_enabling`
- `stellarwp/uplink/feature_enabled` / `stellarwp/uplink/{slug}/feature_enabled`
- `stellarwp/uplink/feature_disabling` / `stellarwp/uplink/{slug}/feature_disabling`
- `stellarwp/uplink/feature_disabled` / `stellarwp/uplink/{slug}/feature_disabled`
- `stellarwp/uplink/feature_updating` / `stellarwp/uplink/{slug}/feature_updating`
- `stellarwp/uplink/feature_updated` / `stellarwp/uplink/{slug}/feature_updated`

## Caching

The `Feature_Repository` caches the resolved `Feature_Collection` in memory for the current request. Resolution is cheap (iterates the cached catalog and licensing arrays), so no cross-request cache is needed. Fresh requests always resolve from the upstream caches (catalog and licensing), which are the single source of truth for staleness. `refresh()` clears the in-memory cache and re-resolves.

## Feature Collection

A typed, keyed collection of `Feature` objects with filtering:

```php
$features->filter(
    product: 'kadence',    // product family
    tier: 'kadence-pro',   // minimum tier
    available: true,       // only available features
    type: 'plugin',        // only installable features
);
```

All parameters optional. Returns a new collection without mutating the original.

## REST API

Five endpoints under `stellarwp/uplink/v1`. All require `manage_options`.

| Route                      | Method | Purpose                                                         |
| -------------------------- | ------ | --------------------------------------------------------------- |
| `/features`                | GET    | List features (filters: `product`, `tier`, `available`, `type`) |
| `/features/{slug}`         | GET    | Get a single feature                                            |
| `/features/{slug}/enable`  | POST   | Enable a feature                                                |
| `/features/{slug}/disable` | POST   | Disable a feature                                               |
| `/features/{slug}/update`  | POST   | Update a feature to the latest available version                |

Each Feature object includes `is_enabled`, stamped with live state from its strategy by the Manager before any consumer receives it. Installable features (Plugin, Theme) additionally include `has_update` — a pre-computed boolean the frontend can read directly without doing any version parsing.

## Error Codes

| Constant                         | HTTP | Meaning                                            |
| -------------------------------- | ---- | -------------------------------------------------- |
| `FEATURE_NOT_FOUND`              | 404  | Slug doesn't exist in the resolved catalog         |
| `FEATURE_TYPE_MISMATCH`          | 400  | Type doesn't match the strategy                    |
| `FEATURE_REQUEST_FAILED`         | 502  | Resolution failed (catalog or licensing API error) |
| `FEATURE_CHECK_FAILED`           | 502  | Unexpected error during availability check         |
| `FEATURE_ENABLE_FAILED`          | 422  | Strategy threw an exception during enable          |
| `FEATURE_DISABLE_FAILED`         | 422  | Strategy threw an exception during disable         |
| `FEATURE_NOT_ACTIVE`             | 422  | Feature is not installed or active                 |
| `UPDATE_NOT_SUPPORTED`           | 422  | Feature type does not support updates (e.g. flags) |
| `NO_UPDATE_AVAILABLE`            | 422  | No update available for the feature                |
| `UPDATE_FAILED`                  | 422  | The update operation failed                        |
| `INVALID_RESPONSE`               | 502  | Catalog response couldn't be parsed                |
| `UNKNOWN_FEATURE_TYPE`           | 422  | No Feature subclass for the catalog type           |
| `INSTALL_LOCKED`                 | 409  | Another install already in progress                |
| `REQUIREMENTS_NOT_MET`           | 422  | PHP or WordPress version requirements not met      |
| **Plugin-specific**              |      |                                                    |
| `PLUGIN_OWNERSHIP_MISMATCH`      | 409  | Different developer's plugin in the directory      |
| `DEACTIVATION_FAILED`            | 409  | Plugin stayed active after deactivation            |
| `INSTALL_FAILED`                 | 422  | `Plugin_Upgrader::install()` failed                |
| `ACTIVATION_FATAL`               | 422  | Fatal error during plugin activation               |
| `ACTIVATION_FAILED`              | 422  | `activate_plugin()` returned an error              |
| `PLUGIN_NOT_FOUND_AFTER_INSTALL` | 422  | Plugin file missing after ZIP extraction           |
| `DOWNLOAD_LINK_MISSING`          | 422  | `plugins_api()` returned no download link          |
| `PLUGINS_API_FAILED`             | 502  | `plugins_api()` call failed                        |
| **Theme-specific**               |      |                                                    |
| `THEME_OWNERSHIP_MISMATCH`       | 409  | Different developer's theme in the directory       |
| `THEME_IS_ACTIVE`                | 409  | Active theme cannot be disabled                    |
| `THEME_DELETE_REQUIRED`          | 409  | Theme on disk; user must delete manually           |
| `THEME_NOT_FOUND_AFTER_INSTALL`  | 422  | Theme directory missing after ZIP extraction       |
| `THEMES_API_FAILED`              | 502  | `themes_api()` call failed                         |

## Data Sources

| Data                                                    | Source                                                                                                                                                                              |
| ------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Feature exists, minimum tier, delivery type, tier ranks | Catalog                                                                                                                                                                             |
| Latest version, release date, changelog                 | Catalog (`version`, `released_at`, `changelog`)                                                                                                                                     |
| Customer's tier, key validity                           | Licensing                                                                                                                                                                           |
| **Whether available** (`is_available`)                  | **Licensing capabilities array** — feature slug present in `Product_Entry::get_capabilities()`. Falls back to catalog tier rank 0 when unlicensed.                                  |
| **Whether enabled** (`is_enabled`)                      | Live WordPress state (plugin activation / theme disk / flag option), stamped by Manager                                                                                             |
| **Installed version** (`installed_version`)             | Read from disk during resolution via `Installable`. Null for flags and uninstalled extensions                                                                                       |
| **Update available** (`has_update`)                     | Computed by `Installable::has_update()`: `version_compare( catalog_version, installed_version, '>' )`. False when not installed or catalog version is absent. Plugin and Theme only |

## What Features Does Not Do

- **Fetch its own data** — resolved from catalog and licensing. No separate "features API."
- **Delete extensions** — plugins are deactivated, never removed. Themes require manual deletion.
- **Manage seats** — seat consumption is in the licensing layer, not feature enable/disable.
