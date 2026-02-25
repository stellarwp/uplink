# Phase 2: Build Tooling

**Status:** Pending
**Ticket:** SCON-26

## Summary

Create the four configuration files that define the entire build pipeline: `webpack.config.js` (extends `@wordpress/scripts` defaults), `tsconfig.json` (strict TypeScript with path aliases), `postcss.config.js` (Tailwind v4 + autoprefixer), and `.gitignore` (excludes build output and `node_modules`).

## Files Created

- `webpack.config.js` — Custom entry, dual output paths, path aliases
- `tsconfig.json` — Strict mode, `bundler` module resolution, path aliases
- `postcss.config.js` — Tailwind v4 + autoprefixer (CommonJS format)
- `.gitignore` — Excludes `build/`, `build-dev/`, `node_modules/`

## `webpack.config.js`

```javascript
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

const isProduction = process.env.NODE_ENV === 'production';
const buildMode    = process.env.BUILD_MODE || ( isProduction ? 'prod' : 'dev' );
const outputPath   = path.resolve(
    process.cwd(),
    buildMode === 'prod' ? 'build' : 'build-dev'
);

module.exports = {
    ...defaultConfig,
    entry: {
        index: path.resolve( process.cwd(), 'resources', 'js', 'index.tsx' ),
    },
    output: {
        path:          outputPath,
        filename:      '[name].js',
        chunkFilename: '[name].js?ver=[contenthash]',
    },
    resolve: {
        ...defaultConfig.resolve,
        alias: {
            '@':           path.resolve( process.cwd(), 'resources', 'js' ),
            '@components': path.resolve( process.cwd(), 'resources', 'js', 'components' ),
            '@lib':        path.resolve( process.cwd(), 'resources', 'js', 'lib' ),
            '@css':        path.resolve( process.cwd(), 'resources', 'css' ),
        },
    },
    optimization: {
        ...defaultConfig.optimization,
        ...( buildMode === 'prod' && { minimize: true, usedExports: true } ),
    },
    devtool: buildMode === 'prod' ? false : 'source-map',
};
```

## `tsconfig.json`

```json
{
  "compilerOptions": {
    "target": "es2020",
    "module": "esnext",
    "moduleResolution": "bundler",
    "jsx": "react-jsx",
    "strict": true,
    "noEmit": true,
    "esModuleInterop": true,
    "skipLibCheck": true,
    "forceConsistentCasingInFileNames": true,
    "resolveJsonModule": true,
    "isolatedModules": true,
    "baseUrl": ".",
    "paths": {
      "@/*":           ["./resources/js/*"],
      "@components/*": ["./resources/js/components/*"],
      "@lib/*":        ["./resources/js/lib/*"],
      "@css/*":        ["./resources/css/*"]
    }
  },
  "include": ["resources/js/**/*.ts", "resources/js/**/*.tsx"],
  "exclude": ["node_modules", "build", "build-dev", "vendor"]
}
```

## `postcss.config.js`

> **CRITICAL:** Must remain CommonJS (`module.exports`). ESM syntax (`export default`) breaks `@wordpress/scripts`' postcss-loader at build time with a `SyntaxError: Cannot use import statement in a module`.

```javascript
module.exports = {
    plugins: [
        require( '@tailwindcss/postcss' ),
        require( 'autoprefixer' ),
    ],
};
```

## `.gitignore`

A `.gitignore` already exists at the plugin root with entries for `vendor/`, `.idea/`, `composer.lock`, `node_modules/`, `local-scripts/`, etc. **Do not overwrite it.** Append only the entries below that are not already present:

```gitignore
# Frontend build outputs
/build/
/build-dev/

# OS
.DS_Store
```

> `node_modules/` is already in the existing file — do not add it again. The existing file uses it without a leading slash; either form works.

## Decisions

- `BUILD_MODE` env var controls output directory: `prod` → `build/`, anything else → `build-dev/`. This keeps production and development artifacts separate without requiring manual cleanup between builds.
- `moduleResolution: "bundler"` is the correct setting for webpack-bundled projects. It supports path aliases and `.tsx` extension resolution without explicit file extensions in imports.
- `chunkFilename: '[name].js?ver=[contenthash]'` provides cache busting for code-split chunks. The main `index.js` file does not use contenthash in the filename since it is always redeployed.
- Source maps are only included in `build-dev/` — disabled in production to reduce file size.
- `@wordpress/scripts` v31 already externalizes `react`, `react-dom`, and `@wordpress/element` via webpack `externals`. No manual externals config needed.

## Verification

```bash
node -e "require('./webpack.config.js'); console.log('webpack OK')"
node -e "require('./postcss.config.js'); console.log('postcss OK')"
node -e "JSON.parse(require('fs').readFileSync('./tsconfig.json','utf8')); console.log('tsconfig OK')"
```
