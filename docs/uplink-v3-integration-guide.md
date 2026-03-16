# Uplink v3 Integration Guide

This document explains how to integrate a WordPress plugin with StellarWP Uplink v3 for unified license management.

---

## 1. Initialization

Uplink must be initialized once per plugin, typically inside a service provider registered during the plugin bootstrap.

```php
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Uplink;

class UplinkServiceProvider
{
    public function register(): void
    {
        // Give Uplink access to your plugin's DI container
        Config::set_container($container);

        // Boot all Uplink subsystems
        Uplink::init();
    }

    public function boot(): void
    {
        // Register filters here (see sections below)
    }
}
```

> **Note:** If your plugin uses vendor-prefixed namespaces, use those instead (e.g. `MyPlugin\Vendors\StellarWP\Uplink`).

**Key points:**

- `Config::set_container()` must be called before `Uplink::init()`
- `Uplink::init()` sets up all internal providers (storage, API, licensing, admin UI, etc.)
- Register the Uplink service provider after all other providers so the container is fully configured

---

## 2. Registering Your Product

**Filter:** `stellarwp/uplink/product_registry`

Add your plugin to the Uplink product registry so it participates in unified licensing.

```php
add_filter('stellarwp/uplink/product_registry', function (array $products): array {
    $products[] = [
        'group'        => 'your-brand',          // Brand slug — all products in the same group share a unified license
        'slug'         => 'your-plugin',         // Unique slug for this specific product
        'name'         => 'Your Plugin',         // Human-readable product name
        'version'      => YOUR_PLUGIN_VERSION,   // Current plugin version
        'embedded_key' => getBundledLicenseKey(), // Optional: pre-embedded license key
    ];

    return $products;
});
```

**Product array fields:**

| Field          | Required | Description                                                                                             |
| -------------- | -------- | ------------------------------------------------------------------------------------------------------- |
| `group`        | Yes      | Brand slug. All products with the same group share a unified license.                                   |
| `slug`         | Yes      | Unique identifier for this product. Used in `stellarwp_uplink_is_product_license_active()`.             |
| `name`         | Yes      | Human-readable name shown in the license UI.                                                            |
| `version`      | Yes      | Current plugin version.                                                                                 |
| `embedded_key` | No       | A license key bundled with the plugin (see [Embedded License Keys](#5-embedded--bundled-license-keys)). |

---

## 3. Reporting Legacy Licenses

**Filter:** `stellarwp/uplink/legacy_licenses`

If your plugin has a pre-existing license system (licenses stored in the database before Uplink v3), report those licenses to Uplink so they appear in the unified license UI.

```php
add_filter('stellarwp/uplink/legacy_licenses', function (array $licenses): array {
    $storedLicenses = get_option('my_plugin_licenses', []);

    foreach ($storedLicenses as $license) {
        $licenses[] = [
            'key'        => $license['key'],         // The license key string
            'slug'       => $license['slug'],        // The product/add-on slug this key covers
            'name'       => $license['name'],        // Human-readable product name
            'brand'      => 'your-brand',            // Must match the group used in product_registry
            'is_active'  => $license['is_active'],   // bool
            'page_url'   => admin_url('...'),        // Where the user can manage this license
            'expires_at' => $license['expires'],     // Optional: ISO date string e.g. "2026-01-01"
        ];
    }

    return $licenses;
});
```

**Legacy license array fields:**

| Field        | Required | Description                                        |
| ------------ | -------- | -------------------------------------------------- |
| `key`        | Yes      | The license key string.                            |
| `slug`       | Yes      | The product/add-on slug this key applies to.       |
| `name`       | Yes      | Human-readable product name.                       |
| `brand`      | Yes      | Must match the `group` used in `product_registry`. |
| `is_active`  | Yes      | Whether the license is currently active (`bool`).  |
| `page_url`   | Yes      | Admin URL where the user can manage this license.  |
| `expires_at` | No       | Expiry date string (e.g. `"2026-01-01"`).          |

> **Tip:** If a single license key covers multiple add-ons, emit one entry per add-on slug so each slug can be checked independently via `stellarwp_uplink_is_product_license_active()`.

### Admin notices for inactive legacy licenses

Once you report licenses via this filter, Uplink automatically displays consolidated admin notices for any inactive licenses that are not already covered by a v3 unified license. Notices are grouped by brand, shown only to administrators, and are dismissible per user for 7 days.

Because Uplink handles this, you should remove or suppress any existing license-related admin notices in your own plugin to avoid showing duplicate warnings. The leader Uplink instance (the highest version on the site) is the one that renders the notices, so there is no risk of duplicates across plugins that all bundle Uplink.

---

## 4. Checking License Status

Use the global helper functions to check license state anywhere in your plugin. These functions always delegate to the highest-version Uplink instance present on the site, so they are safe to call even when multiple plugins bundle Uplink.

### Check if a product has an active license

```php
if (stellarwp_uplink_is_product_license_active('your-plugin')) {
    // Plugin has an active unified license
}
```

This is the primary check for gating features or waiving platform fees.

### Check if a unified license key exists (local only, no remote call)

```php
if (stellarwp_uplink_has_unified_license_key()) {
    // A unified key is stored locally
}
```

### Get the unified license key

```php
$key = stellarwp_uplink_get_unified_license_key(); // string|null
```

### Check feature flags

```php
// Feature must be in the catalog AND enabled
if (stellarwp_uplink_is_feature_enabled('feature-slug')) {
    // Feature is available and active
}

// Feature exists in the catalog regardless of enabled state
if (stellarwp_uplink_is_feature_available('feature-slug')) {
    // Feature exists in catalog
}
```

---

## 5. Embedded / Bundled License Keys

If your plugin ships with a pre-embedded license key (e.g. for white-labeling or bundled distribution), provide it via the `embedded_key` field in `product_registry`.

The recommended pattern is to store the key in a dedicated PHP file excluded from version control:

```php
// PLUGIN_LICENSE.php (gitignored, injected at build/deploy time)
<?php return 'your-embedded-license-key-here';
```

Load it at runtime:

```php
function getBundledLicenseKey(): ?string
{
    $filePath = PLUGIN_DIR . 'PLUGIN_LICENSE.php';

    if (!is_readable($filePath)) {
        return null;
    }

    return include $filePath;
}
```

Pass the return value as `embedded_key` when registering your product (see [Section 2](#2-registering-your-product)).

---

## 6. Quick Reference

### Filters

| Filter                              | Purpose                                                                         |
| ----------------------------------- | ------------------------------------------------------------------------------- |
| `stellarwp/uplink/product_registry` | Register your product with Uplink. Receives and returns `array $products`.      |
| `stellarwp/uplink/legacy_licenses`  | Report pre-existing licenses to Uplink. Receives and returns `array $licenses`. |

### Global Functions

| Function                                     | Signature              | Purpose                                                       |
| -------------------------------------------- | ---------------------- | ------------------------------------------------------------- |
| `stellarwp_uplink_is_product_license_active` | `(string $slug): bool` | Check if a specific product slug has an active license.       |
| `stellarwp_uplink_has_unified_license_key`   | `(): bool`             | Check if a unified key is stored locally (no remote call).    |
| `stellarwp_uplink_get_unified_license_key`   | `(): ?string`          | Retrieve the stored unified license key.                      |
| `stellarwp_uplink_is_feature_enabled`        | `(string $slug): bool` | Check if a feature is in the catalog and enabled.             |
| `stellarwp_uplink_is_feature_available`      | `(string $slug): bool` | Check if a feature exists in the catalog regardless of state. |
