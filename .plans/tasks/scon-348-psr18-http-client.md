---
ticket: SCON-348
url: https://stellarwp.atlassian.net/browse/SCON-348
status: todo
---

# Adopt PSR-18 HTTP client interface

## Problem

After the fixture client extraction (see `draft-extract-fixture-clients.md`), uplink's domain clients will use `wp_remote_request()` directly. This ties the HTTP transport to WordPress, making it impossible to swap in a different HTTP client for testing or non-WordPress contexts. The licensing-sdk already solved this with a PSR-18 abstraction that supports both WordPress and Guzzle transports, and uplink should follow the same pattern.

## Proposed solution

Add `psr/http-client` (PSR-18) and `psr/http-message` (PSR-7) as dependencies. Define the domain client implementations (`Licensing_Client`, `Catalog_Client`) to accept a PSR-18 `ClientInterface` for their HTTP transport.

Uplink should ship a default PSR-18 implementation using the Symfony HTTP client (`symfony/http-client`). Symfony's client is lightweight, supports PSR-18 natively, and doesn't require a full framework. Consumers can swap in a different PSR-18 client (WordPress adapter, Guzzle, etc.) through the container.

The uplink-sample-plugin would then provide a fixture client implementation at the PSR-18 level, replacing the `pre_http_request` hook approach from the initial extraction task with a proper client that returns fixture data directly. This is cleaner than intercepting WordPress HTTP hooks because it works regardless of the transport.
