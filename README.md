# StellarWP Plugin Delivery Client

## Registering a plugin

Registers a plugin for licensing and updates.

```php
stellar_network_plugin_register(
	$plugin_name,
	$plugin_slug,
	$plugin_version,
	$plugin_path,
	$plugin_class
);

StellarWP\Network\Plugin::register(
	$plugin_name,
	$plugin_slug,
	$plugin_version,
	$plugin_path,
	$plugin_class
);
```
