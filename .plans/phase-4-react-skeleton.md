# Phase 4: React App Skeleton

**Status:** Pending
**Ticket:** SCON-26

## Summary

Create the minimal React application structure needed for the build to succeed and produce a working mount point in WordPress Admin. This includes the entry point, root `App` component (placeholder to be replaced in Phase 8), the `cn()` utility, and the working TypeScript types for the dashboard data model. The types are defined here so they are available as-is in Phase 6 (mock data) and Phase 8 (UI components).

## Directory Structure to Create

```
resources/
  js/
    components/
      ui/              ← empty for now; shadcn components added in Phase 5
    data/              ← empty for now; mock JSON added in Phase 6
    lib/
      utils.ts
    types/
      api.ts
      uplink-data.ts
      global.d.ts
    App.tsx
    index.tsx
  css/
    globals.css        ← already created in Phase 3
```

## Files Created

### `resources/js/index.tsx`

Entry point. Uses `@wordpress/element` (never `react-dom` directly) and mounts the React app into `#uplink-root`.

```typescript
import { createRoot } from '@wordpress/element';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { App } from '@/App';
import '@css/globals.css';

const queryClient = new QueryClient( {
    defaultOptions: {
        queries: {
            staleTime: 5 * 60 * 1000, // 5 minutes
            retry: 1,
        },
    },
} );

function Root() {
    return (
        <QueryClientProvider client={ queryClient }>
            <App />
        </QueryClientProvider>
    );
}

const rootElement = document.getElementById( 'uplink-root' );

if ( rootElement ) {
    createRoot( rootElement ).render( <Root /> );
}
```

> `@css/globals.css` uses the `@css` alias defined in `webpack.config.js` → `resources/css/`. webpack's `MiniCssExtractPlugin` (bundled with `@wordpress/scripts`) extracts this import into a separate `index.css` file in the build output.

### `resources/js/App.tsx`

Root component. Intentional placeholder — Phase 8 replaces this body with `<LicenseDashboard />`.

```typescript
import type { FC } from '@wordpress/element';

export const App: FC = () => {
    return (
        <div className="min-h-screen p-8">
            <h1 className="text-2xl font-bold text-foreground">
                Liquid Web Software
            </h1>
            <p className="mt-2 text-muted-foreground">
                Feature Manager UI — coming soon.
            </p>
        </div>
    );
};
```

### `resources/js/lib/utils.ts`

The `cn()` helper required by every shadcn component. Merges Tailwind class names and resolves conflicts.

```typescript
import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn( ...inputs: ClassValue[] ): string {
    return twMerge( clsx( inputs ) );
}
```

### `resources/js/types/api.ts`

TypeScript interfaces mirroring the shape of the `uplink/v1` REST API response. Used for typed JSON imports from the mock data file in Phase 6 and consumed directly by the UI components in Phase 8.

```typescript
/**
 * API type definitions for the License Manager Dashboard.
 *
 * These mirror what the uplink/v1 REST API will return.
 * Currently backed by resources/js/data/mock-features.json.
 *
 * @package StellarWP\Uplink
 */

/**
 * The license and activation state of a feature entry.
 *
 * - active:       Licensed and the WP feature is active.
 * - inactive:     Licensed but the WP feature is either not activated or not yet
 *                 installed. When downloadUrl is present, toggling to active
 *                 triggers programmatic installation then activation via the REST API.
 * - not_included: Not part of the user's plan. Row is locked with an upsell.
 *
 * @since TBD
 */
export type FeatureLicenseState = 'active' | 'inactive' | 'not_included';

/**
 * Activation status of the master license key itself.
 *
 * @since TBD
 */
export type LicenseStatus = 'active' | 'expired' | 'invalid' | 'idle';

/**
 * @since TBD
 */
export interface Feature {
    /** Unique feature slug (e.g. "give-recurring") */
    slug: string;
    /** Human-readable feature name */
    name: string;
    /** Short description shown below the feature name */
    description: string;
    /** Installed version string (e.g. "2.4.1"). Empty string when not installed or not_included. */
    version: string;
    /** License and activation state */
    licenseState: FeatureLicenseState;
    /**
     * URL to download and install the feature zip.
     * Only present for inactive features that are not yet installed on this WordPress site.
     * When toggling to active, the REST API uses this URL to install the feature first.
     */
    downloadUrl?: string;
    /**
     * URL to purchase or upgrade the license.
     * Only present when licenseState === 'not_included'.
     */
    upgradeUrl?: string;
}

/**
 * @since TBD
 */
export interface Brand {
    /** Unique brand slug (e.g. "givewp"). Used to look up BrandConfig. */
    slug: string;
    /** Display name (e.g. "GiveWP") */
    name: string;
    /** Short tagline shown below the brand name */
    tagline: string;
    /** Features belonging to this brand */
    features: Feature[];
}

/**
 * @since TBD
 */
export interface LicenseData {
    /** The license key value */
    key: string;
    /** Registered email address */
    email: string;
    /** Current license status */
    status: LicenseStatus;
    /** Human-readable expiry date (e.g. "December 31, 2025") */
    expires: string;
}

/**
 * Root data shape returned by the mock JSON and eventually by the REST API.
 *
 * @since TBD
 */
export interface DashboardData {
    license: LicenseData;
    brands: Brand[];
}
```

### `resources/js/types/uplink-data.ts`

Shape of the `uplinkData` object localized by PHP via `wp_localize_script()` in Phase 7.

```typescript
export interface UplinkData {
    restUrl: string;
    nonce:   string;
}
```

### `resources/js/types/global.d.ts`

Augments the global `Window` interface so `window.uplinkData` is fully typed throughout the app.

```typescript
import type { UplinkData } from './uplink-data';

declare global {
    interface Window {
        uplinkData?: UplinkData;
    }
}
```

## Commands

```bash
bun run build:dev
bun run typecheck
```

## Decisions

- All React hooks are imported from `@wordpress/element`, never directly from `react`. This applies to all application code — `App.tsx`, `index.tsx`, and every component outside of `components/ui/`. **Note:** `FC` (FunctionComponent) is not re-exported by `@wordpress/element`'s TypeScript declarations — omit the `FC` annotation and let TypeScript infer the return type instead. The `components/ui/` folder is an exception: files there are generated by the shadcn CLI and may reference the `React` namespace (e.g. `React.ButtonHTMLAttributes`). This works because `@types/react` is available globally in `node_modules` (installed transitively by `@wordpress/scripts`) — do not alter CLI-generated `ui/` files to change their import style.
- `QueryClient` is instantiated at the module level (outside `Root`) so it persists for the full page lifetime and is not re-created on re-renders.
- `staleTime: 5 * 60 * 1000` (5 minutes) — avoids redundant API refetches during a typical admin session while keeping data reasonably fresh.
- `downloadUrl` is optional on `Feature` — present only for inactive features not yet installed. `upgradeUrl` is optional — present only for `not_included` features. This keeps the interface uniform without null fields on every entry.

## Verification

- `build-dev/index.js` and `build-dev/index.css` both exist after `bun run build:dev`
- `bun run typecheck` exits with zero errors
- In WordPress Admin, navigating to **LW Software** renders the placeholder heading "Liquid Web Software" from the React component (not the PHP heading)
