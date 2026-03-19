# Uplink v3

> **Development status.** This system is under active development. The architectural patterns described here (how the layers connect, how resolution works, how strategies operate) are stable. Specific data shapes are not. Tier slugs, tier names, catalog structure, and API response formats are all subject to change as we negotiate the final contracts with the Licensing and Portal teams.
>
> The v4 Licensing API that the current implementation targets is also still in development. If v4 is not ready or does not meet our needs, we may fall back to the existing v3 Licensing API, which is already plugin/theme-aware and provides most of the entitlement data we need.
>
> Fixture data in `tests/_data/` reflects our current best understanding, not a finalized spec.

## What This Is

Uplink is a PHP library that StellarWP plugins bundle to handle licensing, updates, and feature management. Each StellarWP plugin ships its own vendor-prefixed copy via Strauss, and there is no shared installation. Multiple copies coexist on a single WordPress site, and the library negotiates internally to avoid conflicts.

Uplink v3 introduces **unified licensing**. Instead of each plugin managing its own license key independently, all StellarWP products on a site share a single `LWSW-`-prefixed key. That key determines what products are entitled, what tier each is on, and what features are available. The site asks two external services (the Licensing API and the Commerce Portal) and combines their answers to produce a resolved picture of what the customer can use.

## Products and Entry Plugins

A product is a brand family, like Kadence, GiveWP, The Events Calendar, or LearnDash. Each product encompasses many features: plugins, themes, and capability flags that the customer can enable based on their tier.

Each product has an **entry plugin**, a WordPress plugin that bootstraps Uplink on the site. The entry plugin bundles a vendor-prefixed copy of the Uplink library, registers the product with the leader via the product registry, and may contribute an embedded license key. The entry plugin is how a product gets on the site, but it is not the product itself.

Most entry plugins are free and available on WordPress.org. This is deliberate. A customer can install the entry plugin for free, and the unified key unlocks the premium features within that product family.

| Product             | Entry plugin          | On WordPress.org |
| ------------------- | --------------------- | ---------------- |
| GiveWP              | `give`                | Yes              |
| Kadence             | `kadence-blocks`      | Yes              |
| The Events Calendar | `the-events-calendar` | Yes              |
| LearnDash           | `learndash`           | No               |

All entry plugins share the same unified `LWSW-` key. When an entry plugin activates and detects a unified key on the site, it registers itself with the leader and defers to it. If the entry plugin shipped with an embedded key and the site doesn't have one yet, the embedded key becomes the site's key.

## The Three Data Layers

Uplink v3 is organized around three data layers. Each answers a different question, and none is sufficient alone.

### Licensing: "What does this key cover?"

The site presents its unified key to the Licensing API. The response is a list of products associated with the key, each with a tier, subscription status, seat counts, and activation state for this domain.

Licensing is the authority on entitlements. It decides whether a key is valid, what tier the customer is on, and whether seats are available. It does not know what features exist within a product. That's the catalog's job.

See [Licensing](licensing.md) for the full data shapes, caching, key discovery, and validation workflows.

### Catalog: "What does each product offer?"

The Commerce Portal API provides the product catalog, the complete definition of every product family, its tiers, and its features. The catalog is not personalized. Every site sees the same catalog regardless of what key it has.

Each product defines a ranked set of tiers (Basic, Pro, Agency) and a set of features. Each feature has a minimum tier requirement and a delivery type: `plugin` (installable WordPress plugin), `theme` (installable WordPress theme), or `flag` (capability toggle within an existing plugin).

The catalog defines the menu. It does not know what the customer ordered.

See [Catalog](catalog.md) for the product/tier/feature structure, caching, and data shapes.

### Features: "What can this customer actually use?"

Features are not a third data source. They are the computed join of catalog and licensing data. The resolution process walks every feature in the catalog, looks up the customer's tier from licensing, compares ranks, and produces a resolved collection where each feature knows whether it's available to this customer.

Beyond availability, features track local state, specifically whether a feature is currently enabled on this site. A Plugin feature is enabled by installing and activating the plugin. A Flag feature is enabled by setting a WordPress option that unlocks functionality within an already-installed plugin. Strategies handle the mechanics of each type.

The two types have different behavior on license expiration. Plugin features require an active license to enable. If the license expires, the plugin stays installed but new Plugin features can't be added. Flag features follow a grandfathering rule: once enabled, a flag stays active even if the license expires or downgrades. The customer keeps what they turned on, but can't enable new flags or re-enable ones they disabled.

See [Features](features.md) for the resolution algorithm, strategies, caching, and the REST API.

### How They Relate

```
                    ┌─────────────┐
                    │  Licensing  │
                    │  API (v4)   │
                    └──────┬──────┘
                           │ product_slug, tier, status,
                           │ seats, validation_status
                           ▼
┌─────────────┐    ┌──────────────┐    ┌───────────────┐
│  Commerce   │    │   Feature    │    │   WordPress   │
│  Portal     │───▶│  Resolution  │───▶│   Site        │
│  (Catalog)  │    │              │    │               │
└─────────────┘    └──────────────┘    └───────────────┘
  product families,   joins by           is_available,
  tiers + ranks,      product_slug,      is_enabled,
  features + types,   compares tier      enable/disable
  minimum_tier        ranks              via strategies
```

The catalog provides structure (what exists, what tier it requires). Licensing provides entitlements (what the key covers, what tier it grants). Feature resolution compares the two by tier rank and produces a collection where each feature knows its availability. Strategies then handle the local mechanics of enabling and disabling.

## Tier Slugs

Both Licensing and the Catalog currently use the same product-prefixed tier slug convention: `kadence-basic`, `give-pro`, `the-events-calendar-agency`. This means tier values from a licensing response can be looked up directly in the catalog's tier collection without transformation. The actual slug format, tier names, and number of tiers per product are not finalized and will change as the catalog and licensing contracts are settled.

Tiers have integer ranks. Availability is determined by comparing ranks, not slugs. A feature is available when the customer's tier rank meets or exceeds the feature's minimum tier rank. This design is intentional. As long as both systems agree on tier slugs and the catalog defines ranks, the resolution logic works regardless of what the slugs or tier names end up being.

## One Key Per Site

A site stores exactly one unified key. All StellarWP products share it. The key enters the site either embedded in a product's license file or typed into the admin UI by the user. If a key already exists, it takes precedence over newly contributed embedded keys.

The key is the site's identity to the licensing system. Without a key, the site is unlicensed and no API calls are made.

See [Unified License Key: System Design](unified-license-key-system-design.md) for key change scenarios, seat mechanics, and system boundaries.

## Multi-Instance Architecture

Because each entry plugin bundles its own vendor-prefixed copy of Uplink, a site with multiple StellarWP products has many Uplink instances loaded simultaneously. The instances negotiate leadership (the highest version wins), and the leader takes ownership of all unified licensing concerns: key storage, API communication, feature resolution, REST routes, and the admin page.

Non-leader instances (thin instances) declare themselves to the leader through the product registry and defer to it for everything else. They do not validate keys, talk to APIs, or render licensing UI.

See [Multi-Instance Architecture](uplink-v3-fat-leader-thin-instance.md) for leader election, cross-instance communication, and the product registry.

## The Admin Page

The leader renders the Software Manager, a React-based admin page for managing all StellarWP products on the site. It shows the unified key status, licensed products with their tiers, and features that can be toggled on and off. The frontend communicates with the backend through REST endpoints served by the leader instance.

## Caching

The data layers use different caching strategies:

| Cache             | Type      | TTL             | Key / Location                        | Invalidation                    |
| ----------------- | --------- | --------------- | ------------------------------------- | ------------------------------- |
| Licensed products | Option    | None (persist)  | `stellarwp_uplink_licensing_products` | `License_Repository::refresh()` |
| Product catalog   | Option    | None (persist)  | `stellarwp_uplink_catalog_state`      | `Catalog_Repository::refresh()` |
| Resolved features | In-memory | Current request | —                                     | `Feature_Repository::refresh()` |

The unified key itself is stored in a WordPress option (`stellarwp_uplink_unified_license_key`), not a transient.

## Legacy Compatibility

Uplink v3 does not replace per-resource licensing for products that haven't adopted unified keys. Products using v2/v3 per-resource keys continue through their existing path unchanged. The leader displays legacy key information in the admin UI but does not validate legacy keys. Validation stays in the per-resource path.

There is no automatic migration from per-resource keys to unified keys.

## Documentation Map

| Document                                                             | Covers                                                             |
| -------------------------------------------------------------------- | ------------------------------------------------------------------ |
| [This document](uplink-v3.md)                                        | Architecture overview and how the layers relate                    |
| [Licensing](licensing.md)                                            | Key discovery, API responses, validation workflows, caching        |
| [Catalog](catalog.md)                                                | Product families, tiers, features, the Commerce Portal API         |
| [Features](features.md)                                              | Feature types, resolution, strategies, Manager API, REST endpoints |
| [Unified License Key](unified-license-key-system-design.md)          | Key model, seat mechanics, system boundaries                       |
| [Multi-Instance Architecture](uplink-v3-fat-leader-thin-instance.md) | Leader election, cross-instance hooks, thin instances              |
