# StellarWP Uplink

[![CI](https://github.com/the-events-calendar/stellar-uplink/workflows/CI/badge.svg)](https://github.com/the-events-calendar/stellar-uplink/actions?query=branch%3Amain) [![Static Analysis](https://github.com/the-events-calendar/stellar-uplink/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/the-events-calendar/stellar-uplink/actions/workflows/static-analysis.yml)

## Initialize the library

Initializing the StellarWP Uplink library should be done within the `plugins_loaded` action, preferably at priority `0`.

```php
use StellarWP\Uplink\Uplink;

add_action( 'plugins_loaded', function() {
	Uplink::init();
}, 0 );
```

## Embedding a license in your plugin

StellarWP Uplink plugins are downloaded with an embedded license key so that users do not need to manually enter the key when activating their plugin. To make this possible, the class must be in a specific location so that the licensing server can find it.

```bash
# The class file should be in this path:
src/Uplink/Helper.php
```

The file should match the following - keeping the `KEY` constant set to a blank string, or, if you want a default license key, set it to that.:

```php
<?php
namespace Whatever\Namespace\Uplink;

class Helper {
	const KEY = '';
}
```

## Registering a plugin

Registers a plugin for licensing and updates.

```php
use StellarWP\Uplink\Register;

$plugin_slug    = 'my-plugin';
$plugin_name    = 'My Plugin';
$plugin_version = MyPlugin::VERSION;
$plugin_path    = 'my-plugin/my-plugin.php';
$plugin_class   = MyPlugin::class;
$license_class  = MyPlugin\Uplink\Helper::class;

Register::plugin(
	$plugin_slug,
	$plugin_name,
	$plugin_version,
	$plugin_path,
	$plugin_class,
	$license_class
);
```

## Registering a service

Registers a service for licensing. Since services require a plugin, we pull version and class information from the plugin.

```php
use StellarWP\Uplink\Register;

$service_slug   = 'my-service';
$service_name   = 'My Service';
$plugin_version = MyPlugin::VERSION;
$plugin_path    = 'my-plugin/my-plugin.php';
$plugin_class   = MyPlugin::class;

Register::service(
	$service_slug,
	$service_name,
	$service_version,
	$plugin_path,
	$plugin_class
);
```
