# StellarWP Plugin Delivery Client

## Registering a plugin

Registers a plugin for licensing and updates.

```php
use StellarWP\Network\Resource;

Resource::register_plugin(
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
use StellarWP\Network\Resource;

Resource::register_service(
	$plugin_name,
	$plugin_slug,
	$plugin_version,
	$plugin_path,
	$plugin_class
);
```
