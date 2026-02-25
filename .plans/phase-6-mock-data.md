# Phase 6: Mock Data

**Status:** Pending
**Ticket:** SCON-26

## Summary

Create the hardcoded JSON file that mirrors the full shape the `uplink/v1` REST API will return. The dataset covers all four brands (GiveWP, The Events Calendar, LearnDash, Kadence WP) with a mix of `active`, `inactive`, and `not_included` plugins in each brand. This gives the REST API team a clear contract to implement against and lets the UI be fully developed without a live backend.

## Files Created

- `resources/js/data/mock-plugins.json` — Complete 4-brand dataset matching the `DashboardData` interface

## `resources/js/data/mock-plugins.json`

```json
{
  "license": {
    "key": "GWP-8921-TEC-LD-KAD-PRO",
    "email": "webmaster@agency.com",
    "status": "active",
    "expires": "December 31, 2025"
  },
  "brands": [
    {
      "slug": "givewp",
      "name": "GiveWP",
      "tagline": "Donation platform & fundraising",
      "plugins": [
        {
          "slug": "give-recurring",
          "name": "GiveWP Recurring Donations",
          "description": "Accept monthly, weekly, or daily donations.",
          "version": "2.4.1",
          "licenseState": "active"
        },
        {
          "slug": "give-pdf-receipts",
          "name": "GiveWP PDF Receipts",
          "description": "Generate PDF receipts for donors.",
          "version": "1.8.0",
          "licenseState": "active"
        },
        {
          "slug": "give-stripe",
          "name": "GiveWP Stripe Pro",
          "description": "Advanced Stripe payment processing.",
          "version": "3.1.0",
          "licenseState": "inactive"
        },
        {
          "slug": "give-peer-to-peer",
          "name": "GiveWP Peer-to-Peer",
          "description": "Enable peer-to-peer fundraising campaigns.",
          "version": "",
          "licenseState": "not_included",
          "upgradeUrl": "#"
        },
        {
          "slug": "give-form-field-manager",
          "name": "GiveWP Form Field Manager",
          "description": "Add custom fields to donation forms.",
          "version": "",
          "licenseState": "not_included",
          "upgradeUrl": "#"
        }
      ]
    },
    {
      "slug": "the-events-calendar",
      "name": "The Events Calendar",
      "tagline": "Event management & ticketing",
      "plugins": [
        {
          "slug": "events-calendar-pro",
          "name": "Events Calendar Pro",
          "description": "Premium calendar features and recurring events.",
          "version": "6.0.2",
          "licenseState": "active"
        },
        {
          "slug": "event-tickets-plus",
          "name": "Event Tickets Plus",
          "description": "Sell tickets with WooCommerce or EDD.",
          "version": "",
          "licenseState": "inactive",
          "downloadUrl": "https://downloads.stellarwp.com/releases/event-tickets-plus/event-tickets-plus.zip"
        },
        {
          "slug": "virtual-events",
          "name": "Virtual Events",
          "description": "Zoom and virtual meeting integration.",
          "version": "",
          "licenseState": "not_included",
          "upgradeUrl": "#"
        },
        {
          "slug": "events-filterbar",
          "name": "Filter Bar",
          "description": "Advanced event filtering and search UI.",
          "version": "",
          "licenseState": "not_included",
          "upgradeUrl": "#"
        }
      ]
    },
    {
      "slug": "learndash",
      "name": "LearnDash",
      "tagline": "LMS & Course Creator",
      "plugins": [
        {
          "slug": "learndash-lms",
          "name": "LearnDash LMS",
          "description": "Core Learning Management System.",
          "version": "4.5.1",
          "licenseState": "active"
        },
        {
          "slug": "learndash-woocommerce",
          "name": "LearnDash WooCommerce Integration",
          "description": "Sell courses via WooCommerce.",
          "version": "",
          "licenseState": "inactive",
          "downloadUrl": "https://downloads.stellarwp.com/releases/learndash-woocommerce/learndash-woocommerce.zip"
        },
        {
          "slug": "propanel",
          "name": "ProPanel",
          "description": "Advanced reporting and analytics.",
          "version": "",
          "licenseState": "not_included",
          "upgradeUrl": "#"
        }
      ]
    },
    {
      "slug": "kadence",
      "name": "Kadence WP",
      "tagline": "Theme builder & blocks",
      "plugins": [
        {
          "slug": "kadence-blocks-pro",
          "name": "Kadence Blocks Pro",
          "description": "Premium Gutenberg blocks.",
          "version": "3.1.8",
          "licenseState": "active"
        },
        {
          "slug": "kadence-theme-pro",
          "name": "Kadence Theme Pro",
          "description": "Header/Footer builder and hooked elements.",
          "version": "1.1.2",
          "licenseState": "active"
        },
        {
          "slug": "kadence-shop-kit",
          "name": "Kadence Shop Kit",
          "description": "WooCommerce product layouts.",
          "version": "",
          "licenseState": "not_included",
          "upgradeUrl": "#"
        },
        {
          "slug": "kadence-conversions",
          "name": "Kadence Conversions",
          "description": "Popups, slide-ins, and banners.",
          "version": "",
          "licenseState": "not_included",
          "upgradeUrl": "#"
        }
      ]
    }
  ]
}
```

All fields match the interfaces in `resources/js/types/api.ts` (`DashboardData`, `LicenseData`, `Brand`, `Plugin`).

## How It Will Be Used

`LicenseDashboard.tsx` (Phase 8) imports this file directly and casts it to `DashboardData`. When the REST API is ready, the import is replaced with a `useQuery` call — no component changes needed because the shape is already correct.

```typescript
// Current usage in LicenseDashboard.tsx (Phase 8):
import mockData from '@/data/mock-plugins.json';
const data = mockData as DashboardData;

// Future usage when REST API is ready:
// const { data } = useQuery<DashboardData>({
//     queryKey: ['dashboard'],
//     queryFn: () => apiFetch<DashboardData>('/dashboard'),
// });
```

## Decisions

- **`license` object at the top level** — the REST API endpoint will return both the license data and the brand/plugin list in one call, avoiding two round-trips on page load.
- **`version: ""` for `not_included` and uninstalled `inactive` plugins** — an empty string rather than omitting the field keeps the `Plugin` interface uniform. The `PluginRow` component renders `–` when version is empty.
- **`downloadUrl` on uninstalled `inactive` plugins** — present when the plugin is licensed but not yet installed on this WordPress site (e.g. `event-tickets-plus`, `learndash-woocommerce`). The REST API uses this URL to install the plugin programmatically before activating. Absent when the plugin is installed but simply not activated (e.g. `give-stripe` is installed but deactivated).
- **`upgradeUrl: "#"` as a placeholder** — prevents broken `href` attributes during development. Real URLs come from the REST API response.
- **Each brand has at least one `active`, one `inactive`, and one `not_included` entry** — ensures all three row visual states are exercised when building and reviewing the UI in Phase 8.
- **`resolveJsonModule: true` in `tsconfig.json`** (set in Phase 2) enables fully typed JSON imports.

## Verification

```bash
node -e "JSON.parse(require('fs').readFileSync('./resources/js/data/mock-plugins.json','utf8')); console.log('JSON valid')"
```

Confirm:
- Top-level keys are `license` and `brands`
- Every `Plugin` entry has `slug`, `name`, `description`, `version`, and `licenseState`
- `not_included` plugins have `upgradeUrl`; `active`/`inactive` plugins do not
- Some `inactive` plugins have `downloadUrl` (not yet installed); others do not (installed but not activated)
- All `licenseState` values are one of `active`, `inactive`, `not_included`
