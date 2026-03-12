# WP-CLI Commands

Uplink registers WP-CLI commands automatically when WP-CLI is present. No additional setup is needed.

## Feature Commands

All feature commands live under the `wp uplink feature` namespace.

### list

Lists features with optional filters.

```bash
wp uplink feature list [--group=<group>] [--tier=<tier>] [--available=<bool>] [--type=<type>] [--fields=<fields>] [--format=<format>]
```

**Options:**

| Option               | Description                                                      |
| -------------------- | ---------------------------------------------------------------- |
| `--group=<group>`    | Filter by product group (e.g. `kadence`)                         |
| `--tier=<tier>`      | Filter by tier (e.g. `Tier 1`)                                   |
| `--available=<bool>` | Filter by availability (`true` or `false`)                       |
| `--type=<type>`      | Filter by type (`flag`, `plugin`, `theme`)                       |
| `--fields=<fields>`  | Comma-separated field list                                       |
| `--format=<format>`  | Output format: `table` (default), `json`, `csv`, `yaml`, `count` |

**Default fields:** `slug, name, type, group, is_available, is_enabled`

**Available fields:**

- All types: `slug`, `name`, `description`, `type`, `group`, `tier`, `is_available`, `is_enabled`, `documentation_url`
- Plugin and Theme: `installed_version`, `released_at`, `authors`, `is_dot_org`
- Plugin only: `plugin_file`, `plugin_slug`

**Examples:**

```bash
# Table output (default)
wp uplink feature list

# JSON for scripting
wp uplink feature list --format=json

# Available flag features only
wp uplink feature list --type=flag --available=true

# Count features in a product group
wp uplink feature list --group=kadence --format=count

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

## Extending

To add new command groups (e.g. licensing, catalog), create a new command class in `src/Uplink/CLI/Commands/` and register it in `CLI\Provider::register_commands()`:

```php
WP_CLI::add_command( 'uplink license', $this->container->get( License::class ) );
```
