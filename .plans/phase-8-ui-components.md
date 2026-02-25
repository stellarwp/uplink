# Phase 8: UI Components — License Manager Dashboard

**Status:** Complete
**Ticket:** SCON-26

## Summary

Implement the full License Manager Dashboard UI using strict atomic design. The interface has three major sections: a page header, a master license key form, and a list of brand sections each containing a plugin table. All components are built with shadcn/ui primitives, Tailwind v4 classes, and `@wordpress/icons`. No external icon library is needed — `@wordpress/icons` is already externalized by `@wordpress/scripts` at zero bundle cost.

---

## Feature License States

```typescript
type FeatureLicenseState = 'active' | 'inactive' | 'not_included'
```

| State | Visual | Toggle | Action cell |
|---|---|---|---|
| `active` | Green "Active" badge with dot | Blue, on | Toggle |
| `inactive` | Gray "Inactive" badge with dot | Gray, off | Toggle (turns on → installs if needed + activates) |
| `not_included` | Amber "Not Included" badge, dimmed row, lock icon | Disabled | "Buy Now" button |

> There is no "not installed" state. A licensed plugin that isn't installed on WordPress shows as `inactive`. When `downloadUrl` is present in the plugin data, toggling to active triggers programmatic installation via the REST API before activating.

---

## Icon Name Verification

Before implementing, verify the following icon names against `node_modules/@wordpress/icons/build/index.js` (or run `Object.keys(require('./node_modules/@wordpress/icons'))`). Replace any that don't exist with the closest available alternative:

| Used in | Icon name | Verify |
|---|---|---|
| `data/brands.ts` | `heart`, `calendar`, `plugins`, `brush` | Check exact exports |
| `molecules/FeatureInfo.tsx` | `lock` | Should exist |
| `molecules/LicenseStatusMessage.tsx` | `check`, `warning` | `warning` may be `alert` |
| `organisms/MasterLicenseForm.tsx` | `shield` | May not exist; check `protect` or `lock` |

---

## Full Directory Structure After This Phase

```
resources/js/
  components/
    atoms/
      BrandIcon.tsx
      FeatureToggle.tsx
      StatusBadge.tsx
      UpsellAction.tsx
    molecules/
      LicenseStatusMessage.tsx
      FeatureInfo.tsx
      FeatureRow.tsx
    organisms/
      BrandSection.tsx
      MasterLicenseForm.tsx
      FeatureTable.tsx
    templates/
      LicenseDashboard.tsx
    ui/
      button.tsx       ← Phase 5 (shadcn)
      card.tsx         ← Phase 5 (shadcn)
      input.tsx        ← Phase 5 (shadcn)
      label.tsx        ← Phase 5 (shadcn)
      switch.tsx       ← Phase 5 (shadcn)
  data/
    brands.ts          ← NEW: brand config (icon + color, non-serializable)
    mock-features.json
  lib/
    utils.ts
  types/
    api.ts
    global.d.ts
    uplink-data.ts
  App.tsx              ← UPDATED: renders LicenseDashboard
  index.tsx
```

---

## Step 1: Create `resources/js/data/brands.ts`

Brand icons are React JSX elements and cannot be serialized into JSON. This file maps brand slugs to their visual config (icon element + Tailwind color classes). It is imported at the component level, not in the mock data.

```typescript
/**
 * Brand visual configuration.
 *
 * Maps brand slugs to the @wordpress/icons icon element and the
 * Tailwind color classes used for the brand icon container.
 *
 * @package StellarWP\Uplink
 */
import {
    heart,
    calendar,
    plugins,
    brush,
} from '@wordpress/icons';
import type { JSX } from '@wordpress/element';

/**
 * @since TBD
 */
export interface BrandConfig {
    /** @wordpress/icons icon element */
    icon: JSX.Element;
    /**
     * Tailwind classes applied to the brand icon wrapper <div>.
     * Should include background + text color (e.g. "bg-green-100 text-green-600").
     */
    colorClass: string;
}

/**
 * Icon and color config per brand slug.
 *
 * Icons are the closest available match in @wordpress/icons.
 * Verify all names before implementing (see Icon Name Verification table above).
 * When designs are approved, swap icons in this single file — no component changes needed.
 *
 * @since TBD
 */
export const BRAND_CONFIGS: Record<string, BrandConfig> = {
    givewp: {
        icon: heart,
        colorClass: 'bg-green-100 text-green-600',
    },
    'the-events-calendar': {
        icon: calendar,
        colorClass: 'bg-blue-100 text-blue-600',
    },
    learndash: {
        icon: plugins,
        colorClass: 'bg-indigo-100 text-indigo-600',
    },
    kadence: {
        icon: brush,
        colorClass: 'bg-orange-100 text-orange-600',
    },
};
```

---

## Step 2: Atoms

### `resources/js/components/atoms/StatusBadge.tsx`

Renders the status pill badge for a plugin row. Handles all three license states.

```typescript
import { __ } from '@wordpress/i18n';
import { cn } from '@/lib/utils';
import type { FeatureLicenseState } from '@/types/api';

interface StatusBadgeProps {
    state: FeatureLicenseState;
}

const STATE_CONFIG: Record<
    FeatureLicenseState,
    { label: string; badgeClass: string; dotClass?: string }
> = {
    active: {
        label: __( 'Active', '%TEXTDOMAIN%' ),
        badgeClass: 'bg-green-100 text-green-700',
        dotClass: 'bg-green-500',
    },
    inactive: {
        label: __( 'Inactive', '%TEXTDOMAIN%' ),
        badgeClass: 'bg-slate-100 text-slate-600',
        dotClass: 'bg-slate-400',
    },
    not_included: {
        label: __( 'Not Included', '%TEXTDOMAIN%' ),
        badgeClass: 'bg-amber-50 text-amber-600',
    },
};

/**
 * @since TBD
 */
export function StatusBadge( { state }: StatusBadgeProps ) {
    const { label, badgeClass, dotClass } = STATE_CONFIG[ state ];

    return (
        <span
            className={ cn(
                'inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-xs font-medium',
                badgeClass
            ) }
        >
            { dotClass && (
                <span className={ cn( 'h-1.5 w-1.5 rounded-full', dotClass ) } />
            ) }
            { label }
        </span>
    );
}
```

---

### `resources/js/components/atoms/FeatureToggle.tsx`

Wraps the shadcn `Switch` component. Disabled when the feature is `not_included`.

```typescript
import { __ } from '@wordpress/i18n';
import { Switch } from '@/components/ui/switch';
import type { FeatureLicenseState } from '@/types/api';

interface FeatureToggleProps {
    state: FeatureLicenseState;
    onToggle: ( checked: boolean ) => void;
}

/**
 * @since TBD
 */
export function FeatureToggle( { state, onToggle }: FeatureToggleProps ) {
    const isLocked = state === 'not_included';
    const isChecked = state === 'active';

    return (
        <Switch
            checked={ isChecked }
            onCheckedChange={ onToggle }
            disabled={ isLocked }
            aria-label={
                isLocked
                    ? __( 'Feature not included in your plan', '%TEXTDOMAIN%' )
                    : isChecked
                      ? __( 'Deactivate feature', '%TEXTDOMAIN%' )
                      : __( 'Activate feature', '%TEXTDOMAIN%' )
            }
        />
    );
}
```

---

### `resources/js/components/atoms/UpsellAction.tsx`

The "Buy Now" button shown in the Action column for `not_included` features.

```typescript
import { __, sprintf } from '@wordpress/i18n';

interface UpsellActionProps {
    featureName: string;
    upgradeUrl: string;
}

/**
 * @since TBD
 */
export function UpsellAction( { featureName, upgradeUrl }: UpsellActionProps ) {
    return (
        <a
            href={ upgradeUrl }
            target="_blank"
            rel="noopener noreferrer"
            className="bg-primary/10 hover:bg-primary/20 text-primary px-3 py-1.5 rounded text-xs font-semibold transition-colors"
            aria-label={ sprintf( __( 'Buy license for %s', '%TEXTDOMAIN%' ), featureName ) }
        >
            { __( 'Buy Now', '%TEXTDOMAIN%' ) }
        </a>
    );
}
```

---

### `resources/js/components/atoms/BrandIcon.tsx`

The colored square icon container shown in each brand section header.

```typescript
import { Icon } from '@wordpress/icons';
import type { JSX } from '@wordpress/element';
import { cn } from '@/lib/utils';

interface BrandIconProps {
    /** @wordpress/icons icon element */
    icon: JSX.Element;
    /** Tailwind bg + text color classes (e.g. "bg-green-100 text-green-600") */
    colorClass: string;
}

/**
 * @since TBD
 */
export function BrandIcon( { icon, colorClass }: BrandIconProps ) {
    return (
        <div
            className={ cn(
                'w-10 h-10 rounded flex items-center justify-center shrink-0',
                colorClass
            ) }
        >
            <Icon icon={ icon } size={ 24 } />
        </div>
    );
}
```

---

## Step 3: Molecules

### `resources/js/components/molecules/FeatureInfo.tsx`

Feature name + description cell. Shows a lock icon and muted colors when the feature is `not_included`.

```typescript
import { Icon, lock } from '@wordpress/icons';
import { cn } from '@/lib/utils';
import type { FeatureLicenseState } from '@/types/api';

interface FeatureInfoProps {
    name: string;
    description: string;
    state: FeatureLicenseState;
}

/**
 * @since TBD
 */
export function FeatureInfo( { name, description, state }: FeatureInfoProps ) {
    const isLocked = state === 'not_included';

    return (
        <div className="flex items-center gap-2">
            { isLocked && (
                <Icon
                    icon={ lock }
                    size={ 16 }
                    className="text-slate-400 shrink-0"
                />
            ) }
            <div>
                <span
                    className={ cn(
                        'font-medium block text-sm',
                        isLocked ? 'text-slate-500' : 'text-slate-900'
                    ) }
                >
                    { name }
                </span>
                <span
                    className={ cn(
                        'text-xs',
                        isLocked ? 'text-slate-400' : 'text-slate-500'
                    ) }
                >
                    { description }
                </span>
            </div>
        </div>
    );
}
```

---

### `resources/js/components/molecules/FeatureRow.tsx`

A single table row. Composes `FeatureInfo`, `StatusBadge`, `FeatureToggle`, and `UpsellAction` based on the feature's `licenseState`.

```typescript
import { cn } from '@/lib/utils';
import { FeatureInfo } from '@/components/molecules/FeatureInfo';
import { StatusBadge } from '@/components/atoms/StatusBadge';
import { FeatureToggle } from '@/components/atoms/FeatureToggle';
import { UpsellAction } from '@/components/atoms/UpsellAction';
import type { Feature } from '@/types/api';

interface FeatureRowProps {
    feature: Feature;
    onToggle: ( slug: string, checked: boolean ) => void;
}

/**
 * @since TBD
 */
export function FeatureRow( { feature, onToggle }: FeatureRowProps ) {
    const isLocked = feature.licenseState === 'not_included';

    return (
        <tr
            className={ cn(
                'transition-colors',
                isLocked
                    ? 'bg-slate-50/50'
                    : 'hover:bg-slate-50'
            ) }
        >
            <td className="px-6 py-4">
                <FeatureInfo
                    name={ feature.name }
                    description={ feature.description }
                    state={ feature.licenseState }
                />
            </td>

            <td className="px-6 py-4 text-sm text-slate-600">
                { isLocked || ! feature.version ? '–' : `v${ feature.version }` }
            </td>

            <td className="px-6 py-4">
                <StatusBadge state={ feature.licenseState } />
            </td>

            <td className="px-6 py-4 text-right">
                { isLocked ? (
                    <UpsellAction
                        featureName={ feature.name }
                        upgradeUrl={ feature.upgradeUrl ?? '#' }
                    />
                ) : (
                    <FeatureToggle
                        state={ feature.licenseState }
                        onToggle={ ( checked ) => onToggle( feature.slug, checked ) }
                    />
                ) }
            </td>
        </tr>
    );
}
```

> The version cell renders `–` when `feature.version` is empty — covers both `not_included` features and `inactive` features not yet installed (`downloadUrl` present).

---

### `resources/js/components/molecules/LicenseStatusMessage.tsx`

The status line shown below the license form inputs (success/error/idle).

```typescript
import { Icon, check, warning } from '@wordpress/icons';
import { cn } from '@/lib/utils';

/** @since TBD */
export type LicenseMessageType = 'success' | 'error' | 'idle';

interface LicenseStatusMessageProps {
    type: LicenseMessageType;
    message: string;
}

/**
 * @since TBD
 */
export function LicenseStatusMessage( { type, message }: LicenseStatusMessageProps ) {
    if ( type === 'idle' ) {
        return null;
    }

    const isSuccess = type === 'success';

    return (
        <p
            className={ cn(
                'mt-3 flex items-center gap-2 text-xs',
                isSuccess ? 'text-green-600' : 'text-red-600'
            ) }
            role="status"
            aria-live="polite"
        >
            <Icon icon={ isSuccess ? check : warning } size={ 16 } />
            { message }
        </p>
    );
}
```

---

## Step 4: Organisms

### `resources/js/components/organisms/MasterLicenseForm.tsx`

The license key card with inputs, Activate/Deactivate buttons, and the status message below. Uses shadcn `Card` for the card-styled container.

```typescript
import { __, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Icon, shield } from '@wordpress/icons';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { LicenseStatusMessage } from '@/components/molecules/LicenseStatusMessage';
import type { LicenseData } from '@/types/api';

interface MasterLicenseFormProps {
    license: LicenseData;
    onActivate: ( key: string, email: string ) => void;
    onDeactivate: () => void;
}

/**
 * @since TBD
 */
export function MasterLicenseForm( {
    license,
    onActivate,
    onDeactivate,
}: MasterLicenseFormProps ) {
    const [ key, setKey ] = useState( license.key );
    const [ email, setEmail ] = useState( license.email );

    const statusType =
        license.status === 'active'
            ? 'success'
            : license.status === 'idle'
              ? 'idle'
              : 'error';

    const statusMessage =
        license.status === 'active'
            ? sprintf( __( 'Master license active. Expires on %s.', '%TEXTDOMAIN%' ), license.expires )
            : license.status === 'expired'
              ? __( 'Your license has expired. Please renew to continue receiving updates.', '%TEXTDOMAIN%' )
              : license.status === 'invalid'
                ? __( 'License key is invalid. Please check the key and try again.', '%TEXTDOMAIN%' )
                : '';

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2 text-lg">
                    <Icon icon={ shield } size={ 20 } className="text-primary" />
                    { __( 'Master License Key', '%TEXTDOMAIN%' ) }
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div className="flex flex-col md:flex-row items-end gap-4">
                    <div className="flex flex-col w-full md:flex-1 gap-1.5">
                        <Label htmlFor="license-key">
                            { __( 'License Key', '%TEXTDOMAIN%' ) }
                        </Label>
                        <Input
                            id="license-key"
                            placeholder="XXXX-XXXX-XXXX-XXXX"
                            value={ key }
                            onChange={ ( e ) => setKey( e.target.value ) }
                        />
                    </div>

                    <div className="flex flex-col w-full md:flex-1 gap-1.5">
                        <Label htmlFor="license-email">
                            { __( 'Registered Email', '%TEXTDOMAIN%' ) }
                        </Label>
                        <Input
                            id="license-email"
                            type="email"
                            placeholder="admin@example.com"
                            value={ email }
                            onChange={ ( e ) => setEmail( e.target.value ) }
                        />
                    </div>

                    <div className="flex gap-2 w-full md:w-auto shrink-0">
                        <Button onClick={ () => onActivate( key, email ) }>
                            { __( 'Activate', '%TEXTDOMAIN%' ) }
                        </Button>
                        <Button variant="outline" onClick={ onDeactivate }>
                            { __( 'Deactivate', '%TEXTDOMAIN%' ) }
                        </Button>
                    </div>
                </div>

                <LicenseStatusMessage type={ statusType } message={ statusMessage } />
            </CardContent>
        </Card>
    );
}
```

---

### `resources/js/components/organisms/FeatureTable.tsx`

The feature table. Uses shadcn `Card` with `overflow-hidden p-0` to get the card styling (border, rounded corners, shadow) while letting the table fill the full width without inner padding.

```typescript
import { __ } from '@wordpress/i18n';
import { Card } from '@/components/ui/card';
import { FeatureRow } from '@/components/molecules/FeatureRow';
import type { Feature } from '@/types/api';

interface FeatureTableProps {
    features: Feature[];
    onToggle: ( slug: string, checked: boolean ) => void;
}

/**
 * @since TBD
 */
export function FeatureTable( { features, onToggle }: FeatureTableProps ) {
    return (
        <Card className="overflow-hidden p-0">
            <table className="w-full text-left text-sm">
                <thead className="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th className="px-6 py-3 font-medium text-slate-600">
                            { __( 'Feature', '%TEXTDOMAIN%' ) }
                        </th>
                        <th className="px-6 py-3 font-medium text-slate-600 w-32">
                            { __( 'Version', '%TEXTDOMAIN%' ) }
                        </th>
                        <th className="px-6 py-3 font-medium text-slate-600 w-40">
                            { __( 'Status', '%TEXTDOMAIN%' ) }
                        </th>
                        <th className="px-6 py-3 font-medium text-slate-600 text-right w-32">
                            { __( 'Action', '%TEXTDOMAIN%' ) }
                        </th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-slate-200">
                    { features.map( ( feature ) => (
                        <FeatureRow
                            key={ feature.slug }
                            feature={ feature }
                            onToggle={ onToggle }
                        />
                    ) ) }
                </tbody>
            </table>
        </Card>
    );
}
```

> `p-0` overrides the Card's default `py-6` padding via `tailwind-merge` (the `Card` component uses `cn()` internally). `overflow-hidden` ensures the table's `bg-slate-50` header is clipped by the card's rounded corners.

---

### `resources/js/components/organisms/BrandSection.tsx`

Brand header (icon + name + tagline + active count) above a `FeatureTable`. Uses `BRAND_CONFIGS` to resolve the icon and color for a brand slug. The header is a `<button>` that toggles the `isExpanded` state, collapsing or revealing the feature table. The chevron icon flips between `chevronUp` and `chevronDown` to reflect the current state.

```typescript
import { __, sprintf } from '@wordpress/i18n';
import { useState } from 'react';
import { Icon, chevronDown, chevronUp } from '@wordpress/icons';
import { BrandIcon } from '@/components/atoms/BrandIcon';
import { FeatureTable } from '@/components/organisms/FeatureTable';
import { BRAND_CONFIGS } from '@/data/brands';
import type { Brand } from '@/types/api';

interface BrandSectionProps {
    brand: Brand;
    onToggle: ( slug: string, checked: boolean ) => void;
}

/**
 * @since TBD
 */
export function BrandSection( { brand, onToggle }: BrandSectionProps ) {
    const [ isExpanded, setIsExpanded ] = useState( true );
    const config = BRAND_CONFIGS[ brand.slug ];
    const activeCount = brand.features.filter(
        ( f ) => f.licenseState === 'active'
    ).length;

    return (
        <div className="flex flex-col gap-4">
            <button
                type="button"
                onClick={ () => setIsExpanded( ( prev ) => ! prev ) }
                className="flex items-center justify-between border-b border-slate-200 pb-2 w-full text-left cursor-pointer"
                aria-expanded={ isExpanded }
            >
                <div className="flex items-center gap-3">
                    { config && (
                        <BrandIcon
                            icon={ config.icon }
                            colorClass={ config.colorClass }
                        />
                    ) }
                    <div>
                        <h3 className="text-xl font-bold text-slate-800">
                            { brand.name }
                        </h3>
                        <p className="text-xs text-slate-500">{ brand.tagline }</p>
                    </div>
                </div>

                <div className="flex items-center gap-2 shrink-0">
                    <span className="bg-slate-100 text-slate-600 text-xs px-2 py-1 rounded">
                        { sprintf( __( '%d Active', '%TEXTDOMAIN%' ), activeCount ) }
                    </span>
                    <Icon
                        icon={ isExpanded ? chevronUp : chevronDown }
                        size={ 20 }
                        className="text-slate-400"
                    />
                </div>
            </button>

            { isExpanded && (
                <FeatureTable features={ brand.features } onToggle={ onToggle } />
            ) }
        </div>
    );
}
```

> The active count badge reflects the current reactive state — when a toggle is clicked and `licenseState` changes in the parent, the count updates automatically because it is computed from `brand.features` on each render.

---

## Step 5: Template

### `resources/js/components/templates/LicenseDashboard.tsx`

Composes the page header, `MasterLicenseForm`, and the list of `BrandSection` components. Owns all top-level state and event handlers.

**Toggle state management**: The `brands` array is held in `useState`. `handleToggle` performs an optimistic update — it maps over all brands and features, finds the matching slug, and flips its `licenseState` between `'active'` and `'inactive'`. Because `BrandSection` derives `activeCount` directly from `brand.features`, the badge count updates automatically without any extra wiring. License state is separated from the mock JSON at module scope (`initialData`) so the `useState` initializer only runs once.

```typescript
import { __ } from '@wordpress/i18n';
import { useState } from 'react';
import { MasterLicenseForm } from '@/components/organisms/MasterLicenseForm';
import { BrandSection } from '@/components/organisms/BrandSection';
import type { Brand, DashboardData } from '@/types/api';
import mockData from '@/data/mock-features.json';

const initialData = mockData as DashboardData;

/**
 * @since TBD
 */
export function LicenseDashboard() {
    // TODO: Replace with useQuery hook when REST API is ready.
    const [ brands, setBrands ] = useState<Brand[]>( initialData.brands );

    function handleToggle( slug: string, checked: boolean ) {
        // TODO: Replace optimistic update with REST API call.
        // POST /wp-json/uplink/v1/features/{slug}/toggle
        // If the feature has a downloadUrl, the REST API installs it first, then activates.
        setBrands( ( prev ) =>
            prev.map( ( brand ) => ( {
                ...brand,
                features: brand.features.map( ( feature ) =>
                    feature.slug === slug
                        ? { ...feature, licenseState: checked ? 'active' : 'inactive' }
                        : feature
                ),
            } ) )
        );
    }

    function handleActivate( key: string, email: string ) {
        // TODO: POST /wp-json/uplink/v1/license/activate
        console.log( 'Activate license:', key, email );
    }

    function handleDeactivate() {
        // TODO: POST /wp-json/uplink/v1/license/deactivate
        console.log( 'Deactivate license' );
    }

    return (
        <div className="max-w-[1200px] mx-auto space-y-8 p-4 md:p-8">
            {/* Page Header */}
            <div className="flex flex-col gap-2">
                <h1 className="text-3xl font-light text-slate-800">
                    { __( 'License Management', '%TEXTDOMAIN%' ) }
                </h1>
                <p className="text-slate-600 text-base max-w-3xl">
                    { __( 'Manage your premium feature licenses across all brands. Enter your master license key below to unlock features for GiveWP, The Events Calendar, LearnDash, and Kadence.', '%TEXTDOMAIN%' ) }
                </p>
            </div>

            {/* Master License Form */}
            <MasterLicenseForm
                license={ initialData.license }
                onActivate={ handleActivate }
                onDeactivate={ handleDeactivate }
            />

            {/* Brand Sections */}
            <div className="grid grid-cols-1 gap-8">
                { brands.map( ( brand ) => (
                    <BrandSection
                        key={ brand.slug }
                        brand={ brand }
                        onToggle={ handleToggle }
                    />
                ) ) }
            </div>
        </div>
    );
}
```

---

## Step 6: Update `resources/js/App.tsx`

Replace the placeholder content with the `LicenseDashboard` template:

```typescript
import type { FC } from '@wordpress/element';
import { LicenseDashboard } from '@/components/templates/LicenseDashboard';

export const App: FC = () => {
    return <LicenseDashboard />;
};
```

---

## Decisions

- **`@wordpress/icons` for icons** — zero bundle cost (externalized), no install needed. Brand icon mappings are isolated to `data/brands.ts` so a future icon library swap touches one file only.
- **`BrandConfig` in `data/brands.ts` (not in JSON)** — JSX elements cannot be serialized to JSON. Brand slugs in the mock data JSON map to configs in the TypeScript constants file at render time.
- **shadcn `Card` for card-styled containers** — `MasterLicenseForm` uses `Card`/`CardHeader`/`CardContent`. `PluginTable` uses `Card` with `overflow-hidden p-0` to get card styling (border, shadow, rounded corners) without inner padding. `tailwind-merge` inside `cn()` correctly resolves the `p-0` override of Card's default `py-6`.
- **`FeatureToggle` derives `checked` from `state`** — the toggle does not manage its own boolean state. `checked` is always derived from `licenseState` (`active` = true, anything else = false). This keeps the toggle purely controlled by the data layer.
- **`LicenseStatusMessage` returns `null` when `idle`** — avoids rendering an empty element. The parent `MasterLicenseForm` does not need to conditionally include it.
- **`onToggle` callback signature is `(slug, checked)`** — the slug is passed up so the parent (`LicenseDashboard`) can identify which feature to update when the REST API is wired. The REST API determines whether to install (using `downloadUrl`) or just activate based on the feature's server-side state.
- **Version cell renders `–` for empty `version`** — covers both `not_included` features and `inactive` features not yet installed (`downloadUrl` present). The expression `isLocked || ! feature.version` handles both cases.
- **`upgradeUrl` defaults to `'#'` in `UpsellAction`** — prevents broken `href` attributes while mock data is in use. Real URLs come from the REST API.
- **`BrandSection` is collapsible** — the header row is a `<button>` with `aria-expanded`. `isExpanded` defaults to `true` so all sections are open on first render. The `activeCount` badge is always visible in the header so users can see the summary even when collapsed.
- **Toggle state is owned by `LicenseDashboard`** — `brands` is held in `useState` so toggling a feature re-renders the affected `BrandSection` and its `activeCount` badge without extra props or context. `initialData` is extracted at module scope so the `useState` initializer runs only once. The REST API call replaces the optimistic `setBrands` update in a future phase.

---

## Verification

```bash
bun run build:dev   # must produce build-dev/index.js and build-dev/index.css with no errors
bun run typecheck   # must exit 0
```

In WordPress Admin → **LW Software**:

1. Page header renders: "License Management" heading + description
2. Master License Form renders as a Card: two inputs pre-filled from mock data, Activate + Deactivate buttons, green status message
3. Four brand sections render in order: GiveWP → The Events Calendar → LearnDash → Kadence WP
4. Each brand shows its colored icon square, name, tagline, active count badge, and a chevron icon
5. Active features: green badge, blue toggle (on), version shown
6. Inactive features (installed, no `downloadUrl`): gray badge, gray toggle (off), version shown
7. Inactive features (not installed, `downloadUrl` present): gray badge, gray toggle (off), version shows `–`
8. `not_included` features: amber "Not Included" badge, lock icon, dimmed text, "Buy Now" link, no toggle, version shows `–`
9. Each feature table renders inside a Card with rounded corners and border
10. Clicking an active toggle turns it off: badge changes to "Inactive" (gray), toggle turns gray, active count decrements
11. Clicking an inactive toggle turns it on: badge changes to "Active" (green), toggle turns blue, active count increments
12. Clicking "Buy Now" opens `#` (placeholder URL)
13. Clicking a brand section header collapses the feature table; clicking again expands it; chevron reflects the state
14. Active count badge remains visible in the header even when the section is collapsed
