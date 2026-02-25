# Phase 7: PHP Integration

**Status:** Pending
**Ticket:** SCON-26

## Summary

Wire the React build output into WordPress by modifying `Feature_Manager_Page.php` only (renamed from `Plugin_Manager_Page.php`). Asset registration and enqueuing are handled entirely within that class using the hook suffix returned by `add_menu_page()` — no other PHP files are changed. The existing jQuery-based assets (`key-admin.js`, `main.css`) are left completely untouched. After this phase, navigating to the **LW Software** admin menu item renders the React application.

## Files Modified

| File | What changes |
|---|---|
| `src/Uplink/Admin/Plugin_Manager_Page.php` → `Feature_Manager_Page.php` | **Rename file and class** (`Plugin_Manager_Page` → `Feature_Manager_Page`); update docblocks; add `$page_hook` property; update `add_menu_page()` titles/slug + capture return value; add `maybe_enqueue_assets()` + private `enqueue_assets()` methods; replace `render()` body |
| `src/Uplink/Admin/Provider.php` | Update all references from `Plugin_Manager_Page` to `Feature_Manager_Page`; update docblock |

## Files That Must NOT Be Modified

- `src/Uplink/Admin/Asset_Manager.php`
- `src/assets/js/key-admin.js`
- `src/assets/css/main.css`
- All other PHP files

---

## `src/Uplink/Admin/Feature_Manager_Page.php`

Five changes in total (including the file/class rename), all within the same file.

### Change 0: Rename file and class

1. Rename `src/Uplink/Admin/Plugin_Manager_Page.php` → `src/Uplink/Admin/Feature_Manager_Page.php`
2. Inside the file, rename the class declaration and add a class-level docblock:

```php
// Before:
class Plugin_Manager_Page {

// After:
/**
 * Manages the unified feature manager admin page.
 *
 * @since TBD
 *
 * @package StellarWP\Uplink
 */
class Feature_Manager_Page {
```

3. Update all `@since 3.0.0` tags inside the class to `@since TBD` — the class is being introduced as `Feature_Manager_Page` for the first time and its version is not yet decided.
4. Update docblocks that say "unified plugin manager page" → "unified feature manager page".
5. In `src/Uplink/Admin/Provider.php`, update every reference:
   - `@since 3.0.0 added Plugin_Manager_Page` → `@since TBD added Feature_Manager_Page`
   - `Plugin_Manager_Page::class` → `Feature_Manager_Page::class` (two occurrences)
   - Method `register_unified_plugin_manager_page()` → `register_unified_feature_manager_page()`
   - `add_action( 'admin_menu', [ $this, 'register_unified_plugin_manager_page' ] )` → `register_unified_feature_manager_page`
   - Docblock for the method: "unified plugin manager page" → "unified feature manager page"

---

### Change A: Add `$page_hook` property

Add a private property to store the hook suffix returned by `add_menu_page()`. Place it immediately before `should_render()`.

```php
/**
 * Hook suffix returned by add_menu_page().
 * Empty string until the page is registered.
 *
 * @since TBD
 *
 * @var string
 */
private string $page_hook = '';
```

---

### Change B: Update `maybe_register_page()`

Two updates inside this method:

1. Update the `add_menu_page()` arguments (page title, menu title, menu slug) and capture the return value into `$this->page_hook`.
2. Register the `admin_enqueue_scripts` hook immediately after, so assets load only on this specific page.

Current code (lines 48–56):

```php
add_menu_page(
    __( 'StellarWP Licenses', '%TEXTDOMAIN%' ),
    __( 'StellarWP', '%TEXTDOMAIN%' ),
    'manage_options',
    'stellarwp-licenses',
    [ $this, 'render' ],
    'dashicons-admin-network',
    81
);
```

Replace with:

```php
$this->page_hook = add_menu_page(
    __( 'Liquid Web Software', '%TEXTDOMAIN%' ),
    __( 'LW Software', '%TEXTDOMAIN%' ),
    'manage_options',
    'lws-plugin-manager',
    [ $this, 'render' ],
    'dashicons-admin-network',
    81
);

add_action( 'admin_enqueue_scripts', [ $this, 'maybe_enqueue_assets' ] );
```

> `add_action( 'admin_enqueue_scripts', ... )` is registered here — inside the `if ( ! $this->should_render() )` guard — so the hook only fires when this instance actually owns the page. On sites where a higher-version Uplink instance wins the election, `maybe_register_page()` returns early and this hook is never registered.

---

### Change C: Add `maybe_enqueue_assets()` method

Add this public method after `maybe_register_page()`. It is the `admin_enqueue_scripts` callback and gates enqueuing behind the stored page hook suffix.

```php
/**
 * Enqueues the React Feature Manager UI assets only on the lws-plugin-manager page.
 *
 * Called on admin_enqueue_scripts. The hook suffix is compared against
 * $this->page_hook — the value returned by add_menu_page() — to ensure
 * the React bundle is loaded only on this specific admin page.
 *
 * @since TBD
 *
 * @param string $hook_suffix Current admin page hook suffix.
 *
 * @return void
 */
public function maybe_enqueue_assets( string $hook_suffix ): void {
    if ( $hook_suffix !== $this->page_hook ) {
        return;
    }

    $this->enqueue_assets();
}
```

---

### Change D: Add private `enqueue_assets()` method

Add this private method after `maybe_enqueue_assets()`. It registers and enqueues the compiled React JS and CSS assets.

```php
/**
 * Registers and enqueues the React Feature Manager UI JS and CSS.
 *
 * Loads from build-dev/ when WP_DEBUG is true (source maps included),
 * from build/ otherwise (minified, no source maps).
 *
 * Path resolution from this file:
 *   __DIR__                               → src/Uplink/Admin
 *   dirname(__DIR__)                      → src/Uplink
 *   dirname(dirname(__DIR__))             → src
 *   dirname(dirname(dirname(__DIR__)))    → plugin root (uplink/)
 *
 * @since TBD
 *
 * @return void
 */
private function enqueue_assets(): void {
    $build_dir       = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? 'build-dev' : 'build';
    $plugin_root_url = trailingslashit(
        plugin_dir_url( dirname( dirname( dirname( __DIR__ ) ) ) . '/index.php' )
    );
    $handle = 'stellarwp-uplink-ui';

    wp_register_script(
        $handle,
        $plugin_root_url . $build_dir . '/index.js',
        [ 'wp-element' ],  // wp-element provides React + ReactDOM from WP Core
        null,              // null = no ?ver= query string; cache busting via contenthash
        [ 'in_footer' => true ]
    );

    wp_localize_script(
        $handle,
        'uplinkData',
        [
            'restUrl' => rest_url( 'uplink/v1/' ),
            'nonce'   => wp_create_nonce( 'wp_rest' ),
        ]
    );

    wp_register_style(
        $handle,
        $plugin_root_url . $build_dir . '/index.css',
        [],
        null
    );

    wp_enqueue_script( $handle );
    wp_enqueue_style( $handle );
}
```

---

### Change E: Update `render()`

Replace the body of the existing `render()` method with the React mount point.

Current code (lines 66–72):

```php
public function render(): void {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'StellarWP Licenses', '%TEXTDOMAIN%' ); ?></h1>
    </div>
    <?php
}
```

Replace with:

```php
/**
 * Renders the unified feature manager page.
 *
 * Outputs the React application mount point. The React bundle
 * (index.js + index.css) is registered and enqueued by enqueue_assets(),
 * called via maybe_enqueue_assets() on admin_enqueue_scripts.
 *
 * The .uplink-ui class activates CSS scoping for Tailwind styles,
 * preventing conflicts with WordPress Admin global styles.
 *
 * @since TBD
 *
 * @return void
 */
public function render(): void {
    ?>
    <div class="wrap">
        <div id="uplink-root" class="uplink-ui"></div>
    </div>
    <?php
}
```

> `<div class="wrap">` is intentionally kept — WordPress uses it to apply standard admin page margins and max-width.

---

## How WordPress Derives the Hook Suffix

When `add_menu_page()` registers a top-level menu item with `$menu_slug = 'lws-plugin-manager'`, WordPress generates the hook suffix:

```
toplevel_page_lws-plugin-manager
```

This string is what `add_menu_page()` returns. Storing it in `$this->page_hook` and comparing in `maybe_enqueue_assets()` ensures the React bundle is only enqueued on this exact page, regardless of slug changes.

---

## WP_DEBUG Build Toggle

| `WP_DEBUG` | Loads from | Includes source maps |
|---|---|---|
| `true` | `build-dev/` | Yes |
| `false` | `build/` | No (minified) |

---

## Decisions

- **Self-contained enqueue logic:** Asset registration and enqueuing live entirely in `Feature_Manager_Page`. No changes are needed to `Asset_Manager.php` or `Provider.php`.
- **Hook registered inside `maybe_register_page()`:** The `admin_enqueue_scripts` action is only registered if this Uplink instance wins the version election and registers the page. On sites where `should_render()` returns `false`, the hook never fires.
- **`$this->page_hook` comparison:** Using the actual return value of `add_menu_page()` is more reliable than hardcoding `'toplevel_page_lws-plugin-manager'`. If the slug ever changes, the comparison stays correct automatically.
- **Class renamed:** `Plugin_Manager_Page` → `Feature_Manager_Page` (file: `Feature_Manager_Page.php`). The menu slug `lws-plugin-manager` is intentionally kept unchanged to preserve any existing bookmarks or external references to the admin URL.
- **`wp-element` dependency:** The script is registered with `['wp-element']` — WordPress Core provides React and ReactDOM as `window.wp.element`. `@wordpress/scripts` configures webpack to externalize React to this global, so no duplicate copy of React is bundled.
- **`null` version:** Passing `null` to `wp_register_script/style` omits the `?ver=` query string. Cache busting for `index.js`/`index.css` is handled at the deployment level.
- **`uplinkData` localized object:** `restUrl` and `nonce` are passed via `wp_localize_script()`. The TypeScript `Window` augmentation in `resources/js/types/global.d.ts` types `window.uplinkData` for safe consumption in the app.

---

## Verification

1. Run `bun run build:dev`
2. In WordPress Admin, navigate to **LW Software** (the new menu item)
3. **DevTools → Network:** `build-dev/index.js` and `build-dev/index.css` return HTTP 200
4. **DevTools → Elements:** confirm DOM structure:
   ```html
   <div class="wrap">
     <div id="uplink-root" class="uplink-ui">
       <div class="min-h-screen p-8">
         <h1 class="text-2xl font-bold text-foreground">Liquid Web Software</h1>
         <p class="mt-2 text-muted-foreground">Feature Manager UI — coming soon.</p>
       </div>
     </div>
   </div>
   ```
5. **DevTools → Console:** no JavaScript errors
6. On any OTHER admin page (e.g. Dashboard): `build-dev/index.js` does NOT appear in the Network tab
7. Run `bun run build:prod` and confirm `build/index.js` and `build/index.css` are minified
