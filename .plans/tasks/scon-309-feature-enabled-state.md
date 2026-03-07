---
ticket: SCON-309
url: https://stellarwp.atlassian.net/browse/SCON-309
status: todo
---

# Include is_enabled in Feature objects

## Problem

Every Feature has two independent states per the docs: `is_available` (license tier qualifies) and `is_enabled` (actively turned on for this site). But only `is_available` is part of the Feature object. `is_enabled` is computed separately by `Manager::is_enabled()` and bolted on at the REST boundary in `Feature_Controller::prepare_feature_data()`.

This means the Feature object is incomplete. Any consumer outside the REST controller (global functions, cross-instance hooks, thin instance queries) has to go through the Manager to determine enabled state rather than reading it from the Feature they already have. Plugin and Theme features are the most affected since their source of truth is live WordPress state (`is_plugin_active()`, `wp_get_theme()->exists()`), but that state never makes it onto the Feature object.

## Proposed solution

`is_enabled` should be a first-class attribute on the Feature object, populated during hydration in `Resolve_Feature_Collection` using the existing strategy pattern. Each feature type's strategy already knows how to check live state. The Feature base class should have an `is_enabled(): bool` getter matching the existing `is_available()` pattern.

Since the Feature_Collection is cached in a transient, `is_enabled` values may go stale (a plugin gets activated or deactivated between cache refreshes). The Manager should re-stamp `is_enabled` with fresh live state from the strategies before returning the collection to any consumer. The cached value is just overwritten.

The REST controller's `prepare_feature_data()` should drop the manual `is_enabled` merge and rely on the Feature's own `to_array()` output.
