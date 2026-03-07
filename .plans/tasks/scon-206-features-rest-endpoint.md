---
ticket: SCON-206
url: https://stellarwp.atlassian.net/browse/SCON-206
status: done
---

# Features REST endpoint with mock client

## Problem

The Features backend is scaffolded — REST controller, Manager, type system, strategy pattern, caching — but `Client::request()` returns an empty array. The feature data is mocked entirely on the frontend. The existing endpoints work but return nothing.

## Proposed solution

Implement `Client::request()` as a mock returning fixture data matching the features in `products.ts`. The existing hydration pipeline and REST routes already handle everything from there — listing, filtering by group/tier, single feature lookup, enable/disable.

Also need to implement `Built_In_Strategy` so the enable/disable routes have a strategy to delegate to.

- **Existing routes:** `GET /features`, `GET /features/{slug}`, `POST /features/{slug}/enable`, `POST /features/{slug}/disable`
