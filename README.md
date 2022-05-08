# StellarWP Plugin Delivery Client

[![CI](https://github.com/the-events-calendar/stellar-uplink/workflows/CI/badge.svg)](https://github.com/the-events-calendar/stellar-uplink/actions?query=branch%3Amain) [![Static Analysis](https://github.com/the-events-calendar/stellar-uplink/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/the-events-calendar/stellar-uplink/actions/workflows/static-analysis.yml)

## Initialize the library

```php
use StellarWP\Uplink\Uplink;

Uplink::init();
```

## Registering a plugin

Registers a plugin for licensing and updates.

```php
use StellarWP\Uplink\Register;

Register::plugin(
	$plugin_name,
	$plugin_slug,
	$plugin_version,
	$plugin_path,
	$plugin_class,
	$license_class
);
```

## Registering a service

Registers a service for licensing.

```php
use StellarWP\Uplink\Register;

Register::service(
	$plugin_name,
	$plugin_slug,
	$plugin_version,
	$plugin_path,
	$plugin_class,
	$license_class
);
```
