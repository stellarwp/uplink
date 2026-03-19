---
ticket: SCON-347
url: https://stellarwp.atlassian.net/browse/SCON-347
status: in-progress
---

# Extract fixture clients to sample plugin

## Problem

The Licensing and Catalog providers in uplink hardcode `Fixture_Client` implementations that read JSON files from `tests/_data/`. This means the library ships with dev-only code baked into its production providers, and every time the codebase changes the fixture wireup breaks. The fixture clients are useful for local development, but they belong in the sample plugin, not the library.

## Proposed solution

**In uplink**, build real HTTP client implementations for `Licensing_Client` and `Catalog_Client` that make requests via `wp_remote_request()`. The base URLs should be filterable through WordPress hooks so consumers can point at different environments (local, staging, production). Update the providers to wire these real clients instead of the fixture clients. Remove the `Fixture_Client` classes from the library. The fixture JSON data stays in `tests/_data/` since the test suite still needs it.

**In uplink-sample-plugin**, add fixture support by hooking `pre_http_request` to intercept the client requests and return fixture JSON. The sample plugin should also provide a way to configure the base URL (for pointing at a real API in a specific environment) and to toggle fixture mode on and off.

## Follow-up

A separate task will investigate adopting PSR-18 for the HTTP client interface, allowing the transport to be swapped between WordPress HTTP and a PSR-compliant client (like Guzzle). The licensing-sdk already implements this pattern and could serve as a reference or direct dependency.
