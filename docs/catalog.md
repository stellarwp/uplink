# Catalog

## Summary

The Catalog subsystem is how a WordPress site learns the full shape of a product family: what tiers exist, what features are available at each tier, and how to acquire or install those features. Where Licensing tells the site "what does this key cover?", the Catalog tells the site "what does this product offer?"

The catalog data comes from the Commerce Portal API. It is not license-specific. It describes the complete product catalog regardless of what a particular key is entitled to. The intersection of catalog data and licensing data is what determines what a site can actually use.

> **Development status.** The catalog structure described here represents the data we have identified that we need, not a finalized contract. The actual field names, tier slugs, tier names, and response shape are still being negotiated with the Portal team. Fixture data in `tests/_data/catalog.json` is a working prototype, not a spec.

## What the Catalog Contains

### Products

The catalog is organized by product. A product is a brand family, not a plugin. Kadence, GiveWP, LearnDash, and The Events Calendar are each a product. A product encompasses many features (plugins, themes, flags) that customers can enable based on their tier.

Each product has an entry plugin that bootstraps Uplink on the site (see [Products and Entry Plugins](uplink-v3.md#products-and-entry-plugins)), but the product itself is the umbrella under which all of its features, tiers, and licensing live. A product catalog contains two things: tiers and features.

The product's own entry plugin is also returned as a feature within its catalog. For example, the `kadence` product includes a `kadence` feature of type `theme` representing Kadence itself. This means the update and feature management pipelines treat the product the same as any other feature — there is no special case for "the product itself" versus "add-on features."

### Tiers

Each product defines an ordered set of tiers that represent subscription levels. Tiers are ranked, and a higher rank means a higher tier with more entitlements.

| Field          | Type   | Description                                                                 |
| -------------- | ------ | --------------------------------------------------------------------------- |
| `slug`         | string | Unique identifier within the product (e.g., `kadence-basic`, `kadence-pro`) |
| `name`         | string | Display name (e.g., "Basic", "Pro", "Agency")                               |
| `rank`         | int    | Numeric ordering value. Higher rank = higher tier                           |
| `purchase_url` | string | URL where users can purchase or upgrade to this tier                        |

Tiers are always sorted by rank. This ordering drives feature availability. A feature that requires `kadence-pro` (rank 2) is available to anyone on `kadence-pro` or `kadence-agency` (rank 3), but not to someone on `kadence-basic` (rank 1).

A product's tiers are its own. Tier slugs are namespaced to the product (`kadence-basic`, `give-basic`) so there's no collision across product families.

### Features

Features are the individual capabilities, plugins, themes, and flags that make up a product family. Each feature belongs to one product and has a minimum tier requirement.

| Field               | Type           | Description                                                                                                                        |
| ------------------- | -------------- | ---------------------------------------------------------------------------------------------------------------------------------- |
| `feature_slug`      | string         | Unique identifier (e.g., `kad-blocks-pro`, `ld-propanel`)                                                                          |
| `type`              | string         | One of `plugin`, `theme`, or `flag`                                                                                                |
| `minimum_tier`      | string         | Tier slug required to access this feature                                                                                          |
| `plugin_file`       | string\|null   | Plugin file path relative to the plugins directory (e.g., `kadence-blocks-pro/kadence-blocks-pro.php`). Null for themes and flags. |
| `is_dot_org`        | bool           | Whether the feature is available on WordPress.org                                                                                  |
| `download_url`      | string\|null   | Download URL for features not on WordPress.org                                                                                     |
| `name`              | string         | Display name                                                                                                                       |
| `description`       | string         | Short description of what the feature does                                                                                         |
| `category`          | string         | Grouping category (e.g., `blocks`, `theme`, `security`, `woocommerce`)                                                             |
| `authors`           | string[]\|null | Brand/author names for ownership verification. Null if not applicable.                                                             |
| `documentation_url` | string         | Link to the feature's documentation                                                                                                |

#### Feature Types

Features come in three types, each representing a different kind of deliverable:

**`plugin`**: an installable WordPress plugin. Has a `plugin_file` (plugin file path) and either a `download_url` (for exclusive features) or is available on WordPress.org (`is_dot_org: true`). These are features that need to be downloaded, installed, and activated.

**`theme`**: an installable WordPress theme. The `feature_slug` doubles as the theme stylesheet (directory name). Has either a `download_url` (for exclusive features) or is available on WordPress.org (`is_dot_org: true`).

**`flag`**: a capability toggle. Not a separate installable; it unlocks functionality within an existing plugin. Has no `plugin_file` or `download_url`. Think of these as feature flags that are gated by tier.

#### Tier Gating

Every feature declares a `minimum_tier`. This is the lowest tier slug at which the feature becomes available. Because tiers are ranked, a feature available at `kadence-pro` (rank 2) is also available at `kadence-agency` (rank 3).

The catalog defines what tier a feature requires. Licensing defines what tier the customer is on. The intersection determines availability.

## Caching and Data Access

### Catalog Repository

The `Catalog_Repository` wraps the catalog client with transient caching. The cache uses a 12-hour TTL (`stellarwp_uplink_catalog`), the same duration as the licensing cache.

```
Catalog_Repository::get()
├─ check transient cache
├─ if hit → return cached Catalog_Collection
├─ if miss → Catalog_Client::get_catalog()
├─ cache result (success or error, 12hr TTL)
└─ return Catalog_Collection|WP_Error
```

`refresh()` explicitly clears the cache and re-fetches. This is used when stale data needs to be invalidated immediately.

Both successful responses and errors are cached. An API error is stored for the full TTL to avoid hammering the API on repeated failures.

### Collections

The catalog uses two typed collection classes:

**`Catalog_Collection`** holds `Product_Catalog` objects, keyed by product slug. This is what the repository returns. Lookups are by slug: `$collection->get('kadence')` returns the Kadence product catalog or `null`.

**`Tier_Collection`** holds `Catalog_Tier` objects within a product, keyed by tier slug. Tiers are automatically sorted by rank on construction.

Both collections prevent duplicate keys. Adding an item with an existing key returns the existing item without overwriting.

## API Client

The `Catalog_Client` contract defines a single operation:

- **`get_catalog(): Catalog_Collection|WP_Error`**: fetch the full product catalog.

Unlike the licensing client, this is not parameterized by key or domain. The catalog describes the full product universe. It is the same regardless of who is asking.

During development, the `Fixture_Client` is wired in. It reads a single JSON fixture file (`tests/_data/catalog.json`) containing all products.

## Error Codes

| Code                                         | Constant            | Meaning                                   |
| -------------------------------------------- | ------------------- | ----------------------------------------- |
| `stellarwp-uplink-catalog-product-not-found` | `PRODUCT_NOT_FOUND` | Requested product slug not in the catalog |
| `stellarwp-uplink-catalog-invalid-response`  | `INVALID_RESPONSE`  | API response couldn't be parsed           |

## Catalog Shape

The fixture data illustrates the structure. Each product in the current catalog follows a common pattern:

```
Product: kadence
├─ Tiers
│  ├─ kadence-basic  (rank 1, "Basic")
│  ├─ kadence-pro    (rank 2, "Pro")
│  └─ kadence-agency (rank 3, "Agency")
└─ Features (31)
   ├─ kadence           (theme, minimum: kadence-basic, the product itself, dot-org)
   ├─ kad-blocks-pro    (plugin, minimum: kadence-basic, exclusive)
   ├─ kad-shop-kit      (plugin, minimum: kadence-pro,   exclusive)
   ├─ kad-pattern-hub   (flag,   minimum: kadence-basic)
   └─ ...
```

Note that `kadence` appears as both the product and as a feature within it. This is intentional — the product's entry point flows through the same update and feature management pipelines as any other feature.

The current fixture covers four product families:

| Product               | Tiers                  | Features | Categories                                                                                    |
| --------------------- | ---------------------- | -------- | --------------------------------------------------------------------------------------------- |
| `kadence`             | 3 (Basic, Pro, Agency) | 31       | theme, blocks, design, woocommerce, forms, social, content, security, management, performance |
| `learndash`           | 3 (Basic, Pro, Agency) | 8        | core, membership, reporting, import, community                                                |
| `give`                | 3 (Basic, Pro, Agency) | 28       | core, forms, gateway, email, reporting, marketing, integration                                |
| `the-events-calendar` | 3 (Basic, Pro, Agency) | 9        | core, ticketing, community, integration                                                       |

## Relationship to Licensing and Features

### What the Catalog Provides to Feature Resolution

The catalog is one of two inputs to the [Features](features.md) layer. It contributes:

1. **The feature definitions**, meaning every feature that exists within a product, with its type, minimum tier, installation metadata, and display information.
2. **The tier hierarchy**, the ranked set of tiers that determines which features a given tier unlocks. The `Resolve_Feature_Collection` class looks up each feature's `minimum_tier` in the product's tier collection to get its rank, then compares against the customer's tier rank from [Licensing](licensing.md).

### Tier Slugs

Tier slugs are product-prefixed (`kadence-pro`, `give-basic`) and are consistent between the catalog and licensing responses. This means a tier value from a licensing `Product_Entry` can be looked up directly in the catalog's `Tier_Collection` without transformation.

### Feature Type Mapping

The catalog uses delivery-oriented type names (`plugin`, `theme`, `flag`). The Features subsystem maps these to its own type hierarchy during resolution:

| Catalog type | Feature class | Meaning                                     |
| ------------ | ------------- | ------------------------------------------- |
| `plugin`     | `Plugin`      | Installable WordPress plugin                |
| `theme`      | `Theme`       | Installable WordPress theme                 |
| `flag`       | `Flag`        | Capability toggle within an existing plugin |

### What the Catalog Does Not Know

The catalog describes what exists. It does not know:

| Question                                     | Answer comes from                                   |
| -------------------------------------------- | --------------------------------------------------- |
| What tier is the customer on?                | [Licensing](licensing.md)                           |
| Is this key valid?                           | [Licensing](licensing.md)                           |
| Is a feature available to this customer?     | [Features](features.md) (joins catalog + licensing) |
| Is a feature currently enabled on this site? | [Features](features.md) (checks local state)        |

The catalog is the menu. Licensing is the receipt. Feature resolution is the waiter who checks both before serving.

## What the Catalog Does Not Do

- **Know about license keys**: the catalog is not parameterized by key. It describes what exists, not what a customer owns.
- **Track activation state**: whether a feature is installed or active on a site is not a catalog concern.
- **Change based on customer**: every site sees the same catalog. Personalization happens by combining catalog data with licensing data.
