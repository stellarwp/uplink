# StellarWP Plugin Delivery Client

## Initialize the library

```php
use StellarWP\Network\Network;

Network::init();
```

## Registering a plugin

Registers a plugin for licensing and updates.

```php
use StellarWP\Network\Register;

Register::plugin(
	$plugin_name,
	$plugin_slug,
	$plugin_version,
	$plugin_path,
	$plugin_class
);
```

## Registering a service

Registers a service for licensing.

```php
use StellarWP\Network\Register;

Register::service(
	$plugin_name,
	$plugin_slug,
	$plugin_version,
	$plugin_path,
	$plugin_class
);
```
