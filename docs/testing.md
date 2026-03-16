# Automated tests

This repository uses Codeception for automated testing and leverages [`slic`](https://github.com/stellarwp/slic) for running the tests.

## Pre-requisites

- Docker
- A system-level PHP installation with MySQL libraries
- [`slic`](https://github.com/stellarwp/slic) set up and usable on your system (follow setup instructions in that repo)

## Running tests

### First time run

To run tests for the first time, there are a couple of things you need to do:

1. Run `slic here` in the parent directory from where this library is cloned. (e.g. If you ran `git clone` in your `wp-content/plugins` directory, run `slic here` from `wp-content/plugins`)
2. Run `slic use uplink` to tell `slic` to point to the uplink library.
3. Run `slic composer install` to bring in all the dependencies.

### Running the tests

You can simply run `slic run` or `slic run SUITE_YOU_WANT_TO_RUN` to quickly run automated tests for this library. If you want to use xdebug with your tests, you'll need to open a `slic ssh` session and turn xdebugging on (there's help text to show you how).

## Debug logging

Uplink uses the `With_Debugging` trait (`src/Uplink/Traits/With_Debugging.php`) for all debug output. When `WP_DEBUG` is enabled, log messages are written via `error_log()` with an `Uplink:` prefix so they're easy to filter.

The trait provides three methods:

| Method                  | Use for                                      |
| ----------------------- | -------------------------------------------- |
| `debug_log()`           | Plain string messages                        |
| `debug_log_throwable()` | Exceptions — logs message, file, line, trace |
| `debug_log_wp_error()`  | `WP_Error` objects — logs code and message   |

To see Uplink debug output during test runs, make sure `WP_DEBUG` is `true` in the test environment (slic sets this by default). Grep for `Uplink:` in the PHP error log to isolate Uplink messages from other output.

## Local development with fixtures

During development the [sample plugin](https://github.com/stellarwp/uplink-sample-plugin) replaces the real API clients with fixture clients that read local JSON files. The admin settings page (under **Uplink Sample Plugin**) exposes three controls:

- **Fixture Mode** — toggle between fixture files and the real API.
- **Fixture Key** — select which fixture set to use.
- **API Base URL** — override the real API endpoint (ignored when fixture mode is on).

For how catalog and licensing data join to produce features, see [Data Sources in features.md](features.md#data-sources).

### What happens when you switch the fixture key

Each fixture key maps to JSON files in two directories — one for catalog, one for licensing:

1. The **licensing** client loads `licensing/{key}.json`. Each file represents a different license scenario (basic tier, pro tier, expired, etc.).
2. The **catalog** client looks for `catalog/{key}.json`. If no key-specific file exists, it falls back to `catalog/default.json` — the full product catalog.

Most fixture keys only have a licensing file. They share the same `default.json` catalog because the catalog doesn't change per customer — only licensing does. This means you'll see features from **all** products in the output. The `is_available` column shows which ones the fixture key actually entitles.

For example, with the full `default.json` catalog:

- **`lwsw-unified-give-basic-2026`** — licensing says "GiveWP at basic tier." Features from all products appear, but only basic-tier GiveWP features have `is_available: true`. Kadence features have `is_available: false` (no license entry).
- **`lwsw-unified-pro-2026`** — licensing says "pro tier across multiple products." More features become available.

Some fixture keys (like `lwsw-unified-test-fixtures`) ship a dedicated catalog file with a curated subset of products. When that file exists, it replaces the full catalog entirely — so fewer features appear in the output.

### File resolution order

The sample plugin resolves fixture files from two directories, in order:

1. `fixtures/` inside the sample plugin — custom or one-off files
2. `WP_PLUGIN_DIR/uplink/tests/_data/` — shared uplink test fixtures

The first match wins. For the catalog, if no key-specific file is found in either location, it falls back to `default.json` in the same order.

### Adding custom fixtures

Drop JSON files into the sample plugin's `fixtures/catalog/` and `fixtures/licensing/` directories. The filename (without `.json`) becomes a selectable key in the settings dropdown. See the existing fixture files for the expected format.
