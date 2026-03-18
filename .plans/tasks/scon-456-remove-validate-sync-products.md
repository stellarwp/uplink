---
ticket: SCON-456
url: https://stellarwp.atlassian.net/browse/SCON-456
status: todo
---

# Remove validate endpoint and sync products with V4

## Problem

The licensing client was built with a `validate()` method that consumed activation seats by calling `POST /stellarwp/v4/licenses/validate`. Seat consumption now happens in the Commerce Portal, which is authenticated separately. The validate method, its REST endpoint, Validation_Result, and all the frontend plumbing around it are dead code.

Meanwhile the products endpoint response has drifted from what Uplink expects. `installed_here` was renamed to `activated_here`, `pending_tier` was removed, `capabilities` was added (string array per entry), and `activations.domains` was added. The `subscription` terminology was replaced with `entitlement` throughout, and `no_subscription` became `no_entitlement` in the validation status enum.

## Proposed solution

Remove the validate path entirely and update the product data model to match the current V4 response shape. Fixture JSON files should include `capabilities` as empty arrays for now. Populating them with real values is the job of the capabilities-based feature resolution task.

## Requirements

- Delete `validate()` from the `Licensing_Client` contract and both implementations (Http_Client, Fixture_Client).
- Delete `Validation_Result`.
- Remove the `POST /stellarwp/uplink/v1/license/validate` REST endpoint from License_Controller.
- Remove `validateProduct` from the frontend store (action, reducer cases, selectors, resolver forwarding).
- Remove related tests.
- Rename `installed_here` to `activated_here` on Product_Entry.
- Remove `pending_tier` from Product_Entry.
- Add `capabilities` (string array) to Product_Entry.
- Add `activations.domains` (string array) to Product_Entry.
- Rename `NO_SUBSCRIPTION` to `NO_ENTITLEMENT` in Validation_Status.
- Add `ACTIVATION_REQUIRED` and `TIER_SELECTION_REQUIRED` to Validation_Status.
- Rename `subscription` to `entitlement` throughout licensing code.
- Update fixture JSON files in `tests/_data/licensing/` to match the new shape, with empty `capabilities` arrays.
- Grep for old field names (`installed_here`, `subscription`, `no_subscription`, `pending_tier`) and fix all call sites.
