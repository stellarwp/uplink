---
ticket: SCON-349
url: https://stellarwp.atlassian.net/browse/SCON-349
status: todo
---

# Replace Feature_Repository transient with in-memory cache

## Problem

`Feature_Repository` caches the resolved `Feature_Collection` in a WordPress transient with a 12-hour TTL. The collection is a join of catalog and licensing data, both of which already have their own transient caches. This cache-on-cache creates a stale-data risk: if either upstream source changes (key activation, plan upgrade, catalog refresh), the feature transient can serve outdated resolved data until it expires independently. Invalidation requires coordinating three separate transient lifetimes.

The resolution itself is cheap. It iterates the cached catalog and licensing arrays and compares tier ranks. No filesystem reads or API calls happen at that layer.

## Proposed solution

Remove the transient from `Feature_Repository` and replace it with a simple in-memory property that caches the result for the current request. Multiple callers within the same request (update handlers, REST controllers) get the cached instance. Fresh requests always resolve from the upstream caches, which are the single source of truth for staleness.

Remove the `TRANSIENT_KEY` constant and the `refresh()` method (or simplify it to just clear the in-memory property). Audit callers of `refresh()` to confirm none depend on cross-request persistence of the resolved collection.
