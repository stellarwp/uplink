# WP-CLI Commands

Uplink registers WP-CLI commands automatically when WP-CLI is present. No additional setup is needed.

## Command Reference

### `wp uplink license`

Manage the unified license key.

| Command    | Usage                               | Description                                               |
| ---------- | ----------------------------------- | --------------------------------------------------------- |
| `get`      | `wp uplink license get`             | Show the current license key and associated products      |
| `set`      | `wp uplink license set <key>`       | Validate and store a license key                          |
| `lookup`   | `wp uplink license lookup <key>`    | Look up products for a key without storing it             |
| `validate` | `wp uplink license validate <slug>` | Validate a product on this domain (may consume a seat)    |
| `delete`   | `wp uplink license delete`          | Delete the stored unified license key                     |
| `legacy`   | `wp uplink license legacy`          | List legacy per-plugin licenses from all Uplink instances |

### `wp uplink catalog`

Manage the product catalog.

| Command    | Usage                               | Description                                       |
| ---------- | ----------------------------------- | ------------------------------------------------- |
| `list`     | `wp uplink catalog list`            | List all products in the catalog                  |
| `tiers`    | `wp uplink catalog tiers <slug>`    | Show tiers for a specific product                 |
| `features` | `wp uplink catalog features <slug>` | Show features for a specific product              |
| `refresh`  | `wp uplink catalog refresh`         | Force refresh the catalog from the API            |
| `status`   | `wp uplink catalog status`          | Show when the catalog was last fetched and errors |
| `delete`   | `wp uplink catalog delete`          | Delete the cached catalog                         |

### `wp uplink feature`

Manage Uplink features.

| Command      | Usage                                 | Description                                       |
| ------------ | ------------------------------------- | ------------------------------------------------- |
| `list`       | `wp uplink feature list`              | List features with optional filters               |
| `get`        | `wp uplink feature get <slug>`        | Show detailed information for a single feature    |
| `is-enabled` | `wp uplink feature is-enabled <slug>` | Check if a feature is enabled (exit code 0 = yes) |
| `enable`     | `wp uplink feature enable <slug>`     | Enable a feature                                  |
| `disable`    | `wp uplink feature disable <slug>`    | Disable a feature                                 |
| `update`     | `wp uplink feature update <slug>`     | Update a feature to the latest version            |

## License Commands

### get

Shows the current license key and associated products.

```bash
wp uplink license get [--fields=<fields>] [--format=<format>]
```

**Default fields:** `product_slug, tier, status, expires, site_limit, active_count`

**Available fields:** `product_slug`, `tier`, `pending_tier`, `status`, `expires`, `site_limit`, `active_count`, `over_limit`, `installed_here`, `validation_status`, `is_valid`

**Examples:**

```bash
wp uplink license get
wp uplink license get --format=json
```

### set

Validates and stores a license key. Does not activate any product or consume a seat.

```bash
wp uplink license set <key> [--network] [--fields=<fields>] [--format=<format>]
```

| Option      | Description                               |
| ----------- | ----------------------------------------- |
| `<key>`     | The license key (must start with `LWSW-`) |
| `--network` | Store at the network level (multisite)    |

**Examples:**

```bash
wp uplink license set LWSW-abcdef-123456
wp uplink license set LWSW-abcdef-123456 --network
```

### lookup

Looks up products for a key without storing it.

```bash
wp uplink license lookup <key> [--fields=<fields>] [--format=<format>]
```

**Examples:**

```bash
wp uplink license lookup LWSW-abcdef-123456
```

### validate

Validates a product on this domain using the stored license key. This may consume an activation seat.

```bash
wp uplink license validate <product_slug>
```

**Examples:**

```bash
wp uplink license validate kadence
```

### delete

Deletes the stored unified license key. Does not free any activation seats on the licensing service.

```bash
wp uplink license delete [--network]
```

| Option      | Description                               |
| ----------- | ----------------------------------------- |
| `--network` | Delete from the network level (multisite) |

**Examples:**

```bash
wp uplink license delete
wp uplink license delete --network
```

### legacy

Lists legacy per-plugin licenses discovered across all Uplink instances. Read-only view of old-style keys stored individually by each plugin before unified licensing.

```bash
wp uplink license legacy [--fields=<fields>] [--format=<format>]
```

**Default fields:** `slug, name, brand, key, status, expires_at`

**Available fields:** `slug`, `name`, `brand`, `key`, `status`, `page_url`, `expires_at`

**Examples:**

```bash
wp uplink license legacy
wp uplink license legacy --format=json
```

## Catalog Commands

### list

Lists all products in the catalog.

```bash
wp uplink catalog list [--format=<format>]
```

**Default fields:** `product_slug, tiers, features`

**Examples:**

```bash
wp uplink catalog list
wp uplink catalog list --format=json
```

### tiers

Shows tiers for a specific product.

```bash
wp uplink catalog tiers <product_slug> [--fields=<fields>] [--format=<format>]
```

**Default fields:** `slug, name, rank, purchase_url`

**Examples:**

```bash
wp uplink catalog tiers kadence
wp uplink catalog tiers kadence --format=json
```

### features

Shows features for a specific product.

```bash
wp uplink catalog features <product_slug> [--fields=<fields>] [--format=<format>]
```

**Default fields:** `feature_slug, type, minimum_tier, name, category`

**Available fields:** `feature_slug`, `type`, `minimum_tier`, `name`, `description`, `category`, `plugin_file`, `is_dot_org`, `download_url`, `version`, `authors`, `documentation_url`

**Examples:**

```bash
wp uplink catalog features kadence
wp uplink catalog features kadence --format=json
```

### refresh

Force refreshes the catalog from the API, then displays the resulting product list.

```bash
wp uplink catalog refresh [--format=<format>]
```

**Examples:**

```bash
wp uplink catalog refresh
```

### status

Shows when the catalog was last fetched and any errors.

```bash
wp uplink catalog status
```

**Examples:**

```bash
wp uplink catalog status
```

### delete

Deletes the cached catalog. The next request for the catalog will fetch fresh data from the API.

```bash
wp uplink catalog delete
```

**Examples:**

```bash
wp uplink catalog delete
```

## Feature Commands

### list

Lists features with optional filters.

```bash
wp uplink feature list [--product=<product>] [--tier=<tier>] [--available=<bool>] [--type=<type>] [--fields=<fields>] [--format=<format>]
```

**Options:**

| Option                | Description                                                      |
| --------------------- | ---------------------------------------------------------------- |
| `--product=<product>` | Filter by product (e.g. `kadence`)                               |
| `--tier=<tier>`       | Filter by tier (e.g. `Tier 1`)                                   |
| `--available=<bool>`  | Filter by availability (`true` or `false`)                       |
| `--type=<type>`       | Filter by type (`flag`, `plugin`, `theme`)                       |
| `--fields=<fields>`   | Comma-separated field list                                       |
| `--format=<format>`   | Output format: `table` (default), `json`, `csv`, `yaml`, `count` |

**Default fields:** `slug, name, type, product, is_available, is_enabled`

**Available fields:**

- All types: `slug`, `name`, `description`, `type`, `product`, `tier`, `is_available`, `is_enabled`, `documentation_url`
- Plugin and Theme: `installed_version`, `released_at`, `authors`, `is_dot_org`
- Plugin only: `plugin_file`

**Examples:**

```bash
# Table output (default)
wp uplink feature list

# JSON for scripting
wp uplink feature list --format=json

# Available flag features only
wp uplink feature list --type=flag --available=true

# Count features in a product
wp uplink feature list --product=kadence --format=count

# Show plugin-specific fields
wp uplink feature list --type=plugin --fields=slug,plugin_file,authors,is_dot_org
```

### get

Shows detailed information for a single feature.

```bash
wp uplink feature get <slug> [--fields=<fields>] [--format=<format>]
```

**Examples:**

```bash
wp uplink feature get my-feature
wp uplink feature get my-feature --format=json
```

### is-enabled

Checks whether a feature is currently enabled. Exits with code 0 if enabled, 1 if not.

```bash
wp uplink feature is-enabled <slug>
```

**Examples:**

```bash
# Check in a script
if wp uplink feature is-enabled my-feature; then
  echo "Feature is enabled"
fi
```

### enable

Enables a feature.

```bash
wp uplink feature enable <slug>
```

**Examples:**

```bash
wp uplink feature enable my-feature
```

### disable

Disables a feature.

```bash
wp uplink feature disable <slug>
```

**Examples:**

```bash
wp uplink feature disable my-feature
```

### update

Updates a feature to the latest available version. Only applies to plugin and theme features — flag features do not support updates.

```bash
wp uplink feature update <slug>
```

**Examples:**

```bash
wp uplink feature update my-feature
```

## Scripting Patterns

### JSON piping

```bash
# Get all feature slugs
wp uplink feature list --format=json | jq -r '.[].slug'

# Get enabled features
wp uplink feature list --format=json | jq '[.[] | select(.is_enabled == "true")]'

# Get legacy license keys
wp uplink license legacy --format=json | jq -r '.[].key'
```

### Conditional logic

```bash
if wp uplink feature is-enabled my-feature; then
  echo "my-feature is enabled"
else
  wp uplink feature enable my-feature
fi
```

### Batch operations

```bash
# Enable all available flag features
for slug in $(wp uplink feature list --type=flag --available=true --format=json | jq -r '.[].slug'); do
  wp uplink feature enable "$slug"
done
```

## Cross-Instance Safety

When multiple vendor-prefixed copies of Uplink are active, only the highest version registers CLI commands. This uses the same `Version::should_handle()` mechanism as the REST API routes.
