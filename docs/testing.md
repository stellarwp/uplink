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

## Postman collection

The `docs/postman/` directory contains Postman collections for manual API testing.

### Feature Toggling

**File:** `Feature.Toggling.postman_collection.json`

This collection covers the Feature Toggling REST API (`/wp-json/stellarwp/uplink/v1/features`). It includes requests grouped by scenario:

- **Features List** - list all features with optional filters (group, tier, available, type).
- **Flag Feature** - get, enable, and disable a flag feature.
- **Plugin Feature - Valid Scenarios** - get, enable, and disable a valid plugin feature.
- **Plugin Feature - Invalid Scenarios** - Invalid scenarios for the plugin feature (fatal errors, requirements not met, ownership mismatch, etc.).
- **Edge Cases** - Edge cases for the feature toggling API (nonexistent feature, invalid request, etc.).

### Setup

1. Import the collection into Postman.
2. Set the collection variables before running any requests:

| Variable                  | Description                                       |
| ------------------------- | ------------------------------------------------- |
| `WP_SITE`                 | Your WordPress site URL (e.g. `https://wp.test`). |
| `WP_USER`                 | An admin username.                                |
| `WP_APPLICATION_PASSWORD` | A WordPress application password for that user.   |

The collection uses **Basic Auth** with the credentials above, so make sure the [Application Passwords](https://make.wordpress.org/core/2020/11/05/application-passwords-integration-guide/) feature is enabled on your site.
