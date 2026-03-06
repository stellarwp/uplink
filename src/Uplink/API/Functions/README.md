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

### 1. The Registry (`uplink_fn_registry`)

`src/Uplink/global-functions.php` defines a single function that owns a `static $registry` variable:

```php
function uplink_fn_registry( string $key, string $version = '', ?callable $callback = null ): ?callable {
    static $registry = []; // one instance, shared across all callers

    if ( $callback !== null ) {
        $registry[ $key ][ $version ] = $callback; // write mode
        return null;
    }

    $highest = apply_filters( 'stellarwp/uplink/highest_version', '0.0.0' ); // read mode
    return $registry[ $key ][ $highest ] ?? null;
}
```

The `static` variable lives inside the function — there is only ever one `$registry` array in the process, shared by every caller.

```
uplink_fn_registry()
└── static $registry = [
        'has_unified_license_key' => [
            '3.0.0' => Closure(GiveWP instance),    ← registered by Give's copy
            '3.0.1' => Closure(TEC instance),        ← registered by TEC's copy
            '3.0.3' => Closure(Kadence instance),    ← registered by Kadence's copy
        ],
        'is_product_license_active' => [
            '3.0.0' => Closure(GiveWP instance),
            '3.0.1' => Closure(TEC instance),
            '3.0.3' => Closure(Kadence instance),
        ],
        ...
    ]

Read path: apply_filters('stellarwp/uplink/highest_version') → '3.0.3'
           $registry['has_unified_license_key']['3.0.3'] → Kadence's Closure
```

**Write mode** — called with a version and callback:

```php
uplink_fn_registry( 'has_unified_license_key', '3.0.3', fn() => ... );
```

**Read mode** — called with a key only, resolves the leader's callable:

```php
$callback = uplink_fn_registry( 'has_unified_license_key' );
```

### 2. Version Leadership

The highest-version Uplink copy is the "leader." Each copy registers a filter handler on `stellarwp/uplink/highest_version` during `Uplink::init()`:

```php
add_filter( 'stellarwp/uplink/highest_version', function( string $current ) {
    return version_compare( self::VERSION, $current, '>' ) ? self::VERSION : $current;
});
```

Starting from `'0.0.0'`, each copy has a chance to raise the value. The final result is the highest version loaded. When `uplink_fn_registry` reads, it calls this filter at that moment so the leader is always resolved correctly, regardless of plugin load order.

### 3. Registering Closures (`Global_Function_Registry`)

`Global_Function_Registry::register()` is called by `Provider::register()` during each Uplink instance's `init()`. It registers version-keyed closures for every public function key:

```php
\uplink_fn_registry(
    'has_unified_license_key',
    $version,
    static function () use ( $container ): bool {
        return $container->get( License_Manager::class )->key_exists();
    }
);
```

**Why closures are defined here (in a namespaced file) and not in `global-functions.php`:**
Strauss rewrites class references at parse time. `License_Manager::class` inside this file resolves to the correct Strauss-prefixed name for *this specific Uplink copy* — e.g. `GiveWP\Vendor\StellarWP\Uplink\Licensing\License_Manager`. Defining the closures in the global-namespace file would break this resolution.

### 4. The Public Functions

`src/Uplink/global-functions.php` exposes four public functions. Plugin consumers call these:

| Function                                       | What it checks                                                                |
| ---------------------------------------------- | ----------------------------------------------------------------------------- |
| `uplink_has_unified_license_key()`             | Whether any unified license key is stored locally (no API call)               |
| `uplink_is_product_license_active( $product )` | Whether a product slug has `validation_status: valid` in the cached catalog   |
| `uplink_is_feature_enabled( $slug )`           | Whether a feature is in the catalog AND currently enabled/active              |
| `uplink_is_feature_available( $slug )`         | Whether a feature exists in the catalog, regardless of enabled state          |

Each function looks up the registered callback and delegates, returning `false` if no callback is registered yet:

```php
function uplink_has_unified_license_key(): bool {
    // @phpstan-ignore function.internal
    $callback = uplink_fn_registry( 'has_unified_license_key' );
    return $callback ? (bool) $callback() : false;
}
```

## Registration Flow

```
Uplink::init()
  └─ API\Functions\Provider::register()        ← runs unconditionally (outside is_enabled())
       ├─ require_once global-functions.php     ← defines uplink_fn_registry() and public wrappers
       └─ Global_Function_Registry::register() ← stores version-keyed closures in the registry

  └─ is_enabled() block
       ├─ Licensing\Provider::register()       ← binds License_Manager, License_Repository
       ├─ Features\Provider::register()        ← binds Features\Manager
       └─ register_cross_instance_hooks()      ← registers stellarwp/uplink/highest_version filter
```

`API\Functions\Provider` runs unconditionally so the global functions and version-keyed closures are always registered, even when the legacy PUE/update machinery is disabled via `TRIBE_DISABLE_PUE` or `STELLARWP_LICENSING_DISABLED`. The closures themselves resolve services lazily from the container and are wrapped in `try/catch (Throwable)`, so if the container cannot resolve a dependency (because `is_enabled()` was false) the functions fall back gracefully to `false`.

## Security

The callbacks are stored in a PHP `static` variable inside `uplink_fn_registry()`. There is no WordPress filter on the return value of the public functions themselves. An attacker cannot simply hook a filter to force `uplink_is_product_license_active()` to return `true` — they would need to both manipulate the `stellarwp/uplink/highest_version` filter **and** know the internal registry key, making trivial overrides impractical.

`uplink_fn_registry` is marked `@internal` to signal that plugin consumers should not call it directly and should only use the public wrapper functions.

## Adding a New Global Function

1. Register the closure in `Global_Function_Registry::register()` under a new key.
2. Add a public wrapper function in `global-functions.php` inside a `function_exists` guard.
3. Add tests in `tests/wpunit/API/Functions/GlobalFunctionsTest.php`.
