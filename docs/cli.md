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

| Option | Description |
|--------|-------------|
| `--group=<group>` | Filter by product group (e.g. `Kadence`) |
| `--tier=<tier>` | Filter by tier (e.g. `Tier 1`) |
| `--available=<bool>` | Filter by availability (`true` or `false`) |
| `--type=<type>` | Filter by type (`flag`, `plugin`, `theme`) |
| `--fields=<fields>` | Comma-separated field list |
| `--format=<format>` | Output format: `table` (default), `json`, `csv`, `yaml`, `count` |

**Default fields:** `slug, name, type, group, is_available, is_enabled`

**Available fields:** `slug, name, description, type, group, tier, is_available, is_enabled, documentation_url`

**Examples:**

```bash
# Table output (default)
wp uplink feature list

# JSON for scripting
wp uplink feature list --format=json

# Available flag features only
wp uplink feature list --type=flag --available=true

# Count features in a group
wp uplink feature list --group=Kadence --format=count
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

### is-active

Checks whether a feature is currently active. Exits with code 0 if active, 1 if not.

```bash
wp uplink feature is-active <slug>
```

**Examples:**

```bash
# Check in a script
if wp uplink feature is-active my-feature; then
  echo "Feature is active"
fi
```

### enable

Enables a feature. Without a slug, enters interactive mode.

```bash
wp uplink feature enable [<slug>]
```

**Direct mode** (slug provided) executes immediately with no prompts:

```bash
wp uplink feature enable my-feature
```

**Interactive mode** (no slug) shows disabled features and prompts for selection:

```bash
wp uplink feature enable
```

Interactive mode requires a TTY. If STDIN is piped, the command exits with an error telling you to pass a slug directly.

### disable

Disables a feature. Without a slug, enters interactive mode.

```bash
wp uplink feature disable [<slug>]
```

Works identically to `enable` but shows enabled features in interactive mode.

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
if wp uplink feature is-active my-feature; then
  echo "my-feature is active"
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
