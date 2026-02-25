# Phase 1: Package Setup

**Status:** Pending
**Ticket:** SCON-26

## Summary

Update `package.json` to add all frontend dependencies and scripts needed for the React/TypeScript build pipeline. The existing `changelogger` config must be preserved. Bun is the package manager.

## Files Changed

- `package.json` — Add `scripts`, `dependencies`, and `devDependencies` blocks

## Full `package.json` After Change

```json
{
  "name": "uplink",
  "private": true,
  "scripts": {
    "build":      "BUILD_MODE=prod NODE_ENV=production wp-scripts build",
    "build:dev":  "BUILD_MODE=dev NODE_ENV=development wp-scripts build",
    "build:prod": "BUILD_MODE=prod NODE_ENV=production wp-scripts build",
    "start":      "BUILD_MODE=dev NODE_ENV=development wp-scripts start",
    "typecheck":  "tsc --noEmit",
    "format":     "wp-scripts format",
    "lint:js":    "wp-scripts lint-js",
    "lint:css":   "wp-scripts lint-style"
  },
  "dependencies": {
    "@tanstack/react-query": "^5.90.10",
    "class-variance-authority": "^0.7.1",
    "clsx": "^2.1.1",
    "radix-ui": "^1.4.3",
    "tailwind-merge": "^2.6.0"
  },
  "devDependencies": {
    "@stellarwp/changelogger": "^0.10.0",
    "@tailwindcss/postcss": "^4.1.17",
    "@wordpress/i18n": "^6.13.0",
    "@wordpress/icons": "^11.7.0",
    "@wordpress/scripts": "^31.1.0",
    "autoprefixer": "^10.4.22",
    "postcss": "^8.5.6",
    "react": "^18.3.1",
    "react-dom": "^18.3.1",
    "tailwindcss": "^4.1.17",
    "typescript": "^5.9.3"
  },
  "changelogger": {
    "changesDir": "changelog",
    "linkTemplate": "https://github.com/stellarwp/uplink/compare/${old}...${new}",
    "ordering": ["type", "content"],
    "versioning": "semver",
    "files": [{ "path": "changelog.txt", "strategy": "stellarwp-changelog" }]
  }
}
```

## Commands

```bash
bun install
```

## Decisions

- `@wordpress/element` is **not** listed as a dependency — `@wordpress/scripts` externalizes it to `window.wp.element` automatically via webpack. Listing it would create a duplicate React instance.
- `react` and `react-dom` go in `devDependencies` (not `dependencies`) — webpack externalizes them to `window.React` and `window.ReactDOM` provided by WordPress Core, so they are never bundled. They are listed explicitly so TypeScript can resolve `react` and `react-dom/client` imports without relying on transitive resolution from `@wordpress/scripts`.
- `@wordpress/i18n` and `@wordpress/icons` go in `devDependencies` — both are externalized by `@wordpress/scripts` at build time (available as `window.wp.i18n` and `window.wp.icons` at runtime). Listing them explicitly gives TypeScript their type declarations for `tsc --noEmit`.
- `radix-ui` v1 umbrella package is used instead of individual `@radix-ui/*` packages — provides all Radix primitives in a single install without managing multiple package versions.
- `typescript` goes in `devDependencies` — only used for `tsc --noEmit` at build time.
- `@wordpress/i18n` import convention: use `import { __, sprintf } from '@wordpress/i18n'` with `'%TEXTDOMAIN%'` as the text domain in all `__()` calls to match the PHP convention used throughout this codebase.

## Verification

After `bun install`:
- `node_modules/@wordpress/scripts/` exists
- `node_modules/tailwindcss/` version starts with `4.`
- `node_modules/@tanstack/react-query/` exists
- `bun.lock` is updated
