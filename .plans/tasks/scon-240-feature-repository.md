---
ticket: SCON-240
url: https://stellarwp.atlassian.net/browse/SCON-240
status: todo
---

# Feature Repository: resolve features from catalog + licensing

## Problem

Features aren't a third data source — they're the join of catalog data (what features exist per product/tier) and licensing data (what products/tiers this site's key covers). Both clients now exist (`Catalog_Repository` from PR #127, `Product_Repository` from PR #125), but nothing combines them to produce the `Feature_Collection` that the REST controller serves. The existing `Client::request()` returns an empty array — it was a placeholder for data that should come from the catalog and licensing layers, not a separate API.

There are two data shape mismatches between the catalog and the existing Feature type system that the repository needs to reconcile:

- **Type strings:** The catalog uses `plugin`, `flag`, `theme`. The Feature type system uses `zip` and `built_in`. These need to be mapped — `plugin` → `zip`, `flag` → `built_in`. Theme support can be deferred (the `Zip` type can handle themes for now with a note to revisit). We also need to figure out how to handle `is_dot_org` and install from WordPress.org not a zip.
- **Tier slugs:** The catalog uses product-prefixed tier slugs (`kadence-pro`, rank 2). The licensing API returns bare tier names (`pro`). The repository fixture data needs to be updated to use the convention (`{product_slug}-{tier}`).

## Proposed solution

Create a `Feature_Repository` that replaces the `Client` as the data source for the `Manager`. It combines catalog + licensing to produce a resolved `Feature_Collection`:

1. **Get catalog data** from `Catalog_Repository` — all products, their tiers (with ranks), and their features (each with a `minimum_tier`).
2. **Get licensing data** from `Product_Repository` — the products on this site's key, each with a `tier` and `validation_status`.
3. **Join by `product_slug`** — for each catalog product, find the matching licensing entry.
4. **Resolve `is_available`** per feature — look up the rank of the feature's `minimum_tier` and the rank of the license's tier in the catalog's tier list. The feature is available if the license tier rank >= minimum tier rank AND the license is valid.
5. **Hydrate Feature objects** — map catalog types to Feature subclasses (`plugin` → `Zip`, `flag` → `Built_In`), pass through the resolved `is_available`. If it's possible unify the naming.
6. **Cache the collection** — transient cache on the resolved result, same pattern as the existing `Client`, or a network aware option.

The `Manager` switches its dependency from `Client` to `Feature_Repository`. The `Client` and `Fixture` classes are removed — the catalog and licensing fixture clients already provide the underlying data.

- **Existing routes (unchanged):** `GET /features`, `GET /features/{slug}`, `POST /features/{slug}/enable`, `POST /features/{slug}/disable`
- **Strategy implementations** (`Built_In_Strategy`, `Zip_Strategy`) are a separate concern and handled elsewhere.
