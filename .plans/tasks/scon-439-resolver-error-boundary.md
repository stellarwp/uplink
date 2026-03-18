---
ticket: SCON-439
url: https://stellarwp.atlassian.net/browse/SCON-439
status: todo
---

# Resolver errors should hit the React ErrorBoundary

## Problem

React ErrorBoundaries only catch errors thrown during the render cycle. Our `@wordpress/data` resolvers (`getFeatures`, `getCatalog`, `getLicenseKey`) throw `UplinkError` instances from async thunks, which means those errors escape into unhandled promise rejections instead of reaching the `ErrorBoundary` wrapping `AppShell`.

The result is that when an initial data fetch fails, the user sees a loading spinner forever (or a blank state) instead of the "Something went wrong" fallback.

Storing the error in the datastore would work but is wrong. Resolution failure is a rendering concern, not application state.

## Proposed solution

Introduce a `useResolvableSelect` hook (ported from sync-saas) that enriches `@wordpress/data` selectors with resolution metadata (`status`, `isResolving`, `hasResolved`, `error`). The error object comes from the resolution metadata that `@wordpress/data` already stores when a resolver throws.

A `useResolvableSelectWithError` wrapper detects when any enriched selector enters the `ERROR` status, captures the error via `useState`, and throws it on the next render. This bridges async resolver failures into the synchronous render cycle so the nearest `ErrorBoundary` catches them.

The thrown error is the original `UplinkError` from the resolver when available (via `UplinkError.syncFrom`), falling back to a generic `ResolutionFailed` error code for unexpected error types.

`AppShell` uses `useResolvableSelectWithError` instead of manually checking `hasFinishedResolution` with type casts.
