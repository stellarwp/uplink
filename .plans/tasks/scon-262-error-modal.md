---
ticket: SCON-262
url: https://stellarwp.atlassian.net/browse/SCON-262
status: todo
---

# Error modal component for UplinkError

## Problem

The `ErrorBoundary` catches render errors but discards them. It renders a static "Something went wrong." string with no access to the error code, message, status, or additional errors. There's no way to surface structured error details to the user.

Separately, `UplinkError.additionalErrors` is typed as `WpRestError[]` (plain objects), not `UplinkError[]`. Code that receives the error can't walk the chain with typed access to `.code`, `.status`, `.data` on each entry. Making `UplinkError` chainable (additional errors are themselves `UplinkError` instances) is a prerequisite for the modal to render the full error chain.

## Proposed solution

1. **Make UplinkError chainable.** Change `additionalErrors` from `WpRestError[]` to `UplinkError[]`. The constructor hydrates each entry through `new UplinkError()` so the entire chain is typed.

2. **Create an ErrorModal component.** Accepts a `UplinkError` and renders it in a dialog. Shows the primary error message, code, HTTP status when present, and walks `additionalErrors` to render secondary errors. The modal should be presentational only, no fetching or retry logic.

3. **Wire ErrorBoundary to the modal.** Update `ErrorBoundary` to capture the error in `componentDidCatch` or `getDerivedStateFromError`, normalize it via `UplinkError.from()`, and pass it to `ErrorModal` as the fallback UI.
