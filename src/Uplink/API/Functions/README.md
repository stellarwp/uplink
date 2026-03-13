# Global Function Registry

## The Problem

StellarWP plugins (Give, The Events Calendar, Kadence, LearnDash, etc.) each ship their own vendor-prefixed copy of Uplink via Strauss. On a single WordPress site you may have:

```
GiveWP\Vendor\StellarWP\Uplink\...        (version 3.0.0)
TEC\Vendor\StellarWP\Uplink\...           (version 3.0.1)
Kadence\Vendor\StellarWP\Uplink\...       (version 3.0.3)
```

Each copy is a completely separate PHP class tree. If a plugin calls its own namespaced `License_Manager` to check licensing, it runs that copy's logic — even if a newer copy with a bug fix is already loaded. Worse, the namespaced classes are not directly usable across copies.

Plugin consumers also need a simple, stable public API to answer questions like "does this site have an active license for Give?" without coupling to any particular Uplink copy or requiring knowledge of the internal class structure.

## The Solution

**Global (non-namespaced) PHP functions** are not Strauss-prefixed and are truly shared across all copies. The first copy to `require_once` the file defines the functions; subsequent copies skip the definition via `function_exists` guards.

**Version-keyed closures** allow each copy to register its own implementation under its version number. When a function is called, the registry resolves to the closure registered by the **highest-version** copy — so the most up-to-date logic always runs regardless of which copy's file defined the global function shell.

## How It Works

### 1. The Instance Registry (`_stellarwp_uplink_instance_registry`)

`src/Uplink/global-functions.php` defines a function with a `static $versions` variable that tracks every active Uplink copy:

```php
function _stellarwp_uplink_instance_registry( string $version = '' ): array {
    static $versions = []; // one instance, shared across all callers

    // Only accept registrations before wp_loaded (the bootstrap window).
    if ( $version !== '' && ! did_action( 'wp_loaded' ) ) {
        $versions[ $version ] = true;
    }

    return $versions;
}
```

Each Uplink instance calls `_stellarwp_uplink_instance_registry( self::VERSION )` during `register_cross_instance_hooks()` (in `Uplink::init()`). Because the static variable lives inside the function, and the function is only defined once (via `function_exists` guard), all vendor-prefixed copies share the same registry. Registrations are only accepted before `wp_loaded`, so all real instances (which initialize on `plugins_loaded`) can register, but nothing can inject fake versions after the bootstrap window closes.

### 2. The Function Registry (`_stellarwp_uplink_global_function_registry`)

A second global function stores version-keyed closures and resolves to the highest version's callable on read:

```php
function _stellarwp_uplink_global_function_registry( string $key, string $version = '', ?callable $callback = null ): ?callable {
    static $registry = []; // one instance, shared across all callers

    if ( $callback !== null ) {
        $registry[ $key ][ $version ] = $callback; // write mode
        return null;
    }

    // Read mode: resolve to the highest registered version's callable.
    $versions = array_keys( _stellarwp_uplink_instance_registry() );
    $highest  = array_reduce( $versions, fn( $carry, $v ) => version_compare( $v, $carry, '>' ) ? $v : $carry, '0.0.0' );
    return $registry[ $key ][ $highest ] ?? null;
}
```

The `static` variable lives inside the function — there is only ever one `$registry` array in the process, shared by every caller.

```
_stellarwp_uplink_global_function_registry()
└── static $registry = [
        'stellarwp_uplink_has_unified_license_key' => [
            '3.0.0' => Closure(GiveWP instance),    ← registered by Give's copy
            '3.0.1' => Closure(TEC instance),        ← registered by TEC's copy
            '3.0.3' => Closure(Kadence instance),    ← registered by Kadence's copy
        ],
        'stellarwp_uplink_is_product_license_active' => [
            '3.0.0' => Closure(GiveWP instance),
            '3.0.1' => Closure(TEC instance),
            '3.0.3' => Closure(Kadence instance),
        ],
        ...
    ]

Read path: _stellarwp_uplink_instance_registry() → ['3.0.0', '3.0.1', '3.0.3']
           array_reduce → '3.0.3'
           $registry['stellarwp_uplink_has_unified_license_key']['3.0.3'] → Kadence's Closure
```

**Write mode** — called with a version and callback:

```php
_stellarwp_uplink_global_function_registry( 'stellarwp_uplink_has_unified_license_key', '3.0.3', fn() => ... );
```

**Read mode** — called with a key only, resolves the leader's callable:

```php
$callback = _stellarwp_uplink_global_function_registry( 'stellarwp_uplink_has_unified_license_key' );
```

### 3. Registering Closures (`Global_Function_Registry`)

`Global_Function_Registry::register()` is called by `Provider::register()` during each Uplink instance's `init()`. It registers version-keyed closures for every public function key:

```php
\_stellarwp_uplink_global_function_registry(
    'stellarwp_uplink_has_unified_license_key',
    $version,
    static function (): bool {
        return Config::get_container()->get( License_Repository::class )->key_exists();
    }
);
```

**Why closures are defined here (in a namespaced file) and not in `global-functions.php`:**
Strauss rewrites class references at parse time. `License_Repository::class` inside this file resolves to the correct Strauss-prefixed name for *this specific Uplink copy* — e.g. `GiveWP\Vendor\StellarWP\Uplink\Licensing\Repositories\License_Repository`. Defining the closures in the global-namespace file would break this resolution.

### 4. The Public Functions

`src/Uplink/global-functions.php` exposes four public functions. Plugin consumers call these:

| Function                                                  | What it checks                                                              |
| --------------------------------------------------------- | --------------------------------------------------------------------------- |
| `stellarwp_uplink_has_unified_license_key()`              | Whether any unified license key is stored locally (no API call)             |
| `stellarwp_uplink_get_unified_license_key()`              | Returns the unified license key string, or null if not found                |
| `stellarwp_uplink_is_product_license_active( $product )`  | Whether a product slug has `validation_status: valid` in the cached catalog |
| `stellarwp_uplink_is_feature_enabled( $slug )`            | Whether a feature is in the catalog AND currently enabled/active            |
| `stellarwp_uplink_is_feature_available( $slug )`          | Whether a feature exists in the catalog, regardless of enabled state        |

Each function looks up the registered callback and delegates, returning `false` if no callback is registered yet:

```php
function stellarwp_uplink_has_unified_license_key(): bool {
    $callback = _stellarwp_uplink_global_function_registry( 'stellarwp_uplink_has_unified_license_key' );
    return $callback ? (bool) $callback() : false;
}
```

## Registration Flow

```
Uplink::init()
  └─ API\Functions\Provider::register()        ← runs unconditionally (outside is_enabled())
       ├─ require_once global-functions.php     ← defines _stellarwp_uplink_instance_registry(),
       │                                           _stellarwp_uplink_global_function_registry(),
       │                                           and public wrapper functions
       └─ Global_Function_Registry::register() ← stores version-keyed closures in the registry

  └─ register_cross_instance_hooks()           ← calls _stellarwp_uplink_instance_registry( VERSION )
                                                  to register this instance in the version registry

  └─ is_enabled() block
       ├─ Licensing\Provider::register()       ← binds License_Repository
       └─ Features\Provider::register()        ← binds Features\Manager
```

`API\Functions\Provider` runs unconditionally so the global functions and version-keyed closures are always registered, even when the legacy PUE/update machinery is disabled via `TRIBE_DISABLE_PUE` or `STELLARWP_LICENSING_DISABLED`. The closures themselves resolve services lazily from the container and are wrapped in `try/catch (Throwable)`, so if the container cannot resolve a dependency (because `is_enabled()` was false) the functions fall back gracefully to `false`.

## Security

The callbacks are stored in a PHP `static` variable inside `_stellarwp_uplink_global_function_registry()`. The version registry is locked after `wp_loaded`, so no code running after the bootstrap window can inject a fake high version to hijack the leader callable. An attacker cannot simply hook a filter to force `stellarwp_uplink_is_product_license_active()` to return `true` — they would need to both register a fake version before `wp_loaded` **and** know the internal registry key, making trivial overrides impractical.

`_stellarwp_uplink_instance_registry` and `_stellarwp_uplink_global_function_registry` are marked `@internal` to signal that plugin consumers should not call them directly and should only use the public wrapper functions.

## Adding a New Global Function

1. Register the closure in `Global_Function_Registry::register()` under a new key.
2. Add a public wrapper function in `global-functions.php` inside a `function_exists` guard.
3. Add tests in `tests/wpunit/API/Functions/GlobalFunctionsTest.php`.
