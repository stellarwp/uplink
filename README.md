# StellarWP Uplink

[![CI](https://github.com/the-events-calendar/stellar-uplink/workflows/CI/badge.svg)](https://github.com/the-events-calendar/stellar-uplink/actions?query=branch%3Amain) [![Static Analysis](https://github.com/the-events-calendar/stellar-uplink/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/the-events-calendar/stellar-uplink/actions/workflows/static-analysis.yml)

## Installation

It's recommended that you install Uplink as a project dependency via [Composer](https://getcomposer.org/):

```bash
composer require stellarwp/uplink
```

> We _actually_ recommend that this library gets included in your project using [Strauss](https://github.com/BrianHenryIE/strauss).
>
> Luckily, adding Strauss to your `composer.json` is only slightly more complicated than adding a typical dependency, so checkout our [strauss docs](https://github.com/stellarwp/global-docs/blob/main/docs/strauss-setup.md).

## Initialize the library

Initializing the StellarWP Uplink library should be done within the `plugins_loaded` action, preferably at priority `0`.

```php
use StellarWP\Uplink\Uplink;

add_action( 'plugins_loaded', function() {
	/**
	 * Configure the container.
	 *
	 * The container must be compatible with stellarwp/container-contract.
	 * See here: https://github.com/stellarwp/container-contract#usage.
	 *
	 * If you do not have a container, we recommend https://github.com/lucatume/di52
	 * and the corresponding wrapper:
	 * https://github.com/stellarwp/container-contract/blob/main/examples/di52/Container.php
	 */
	 $container = new Container();
	Config::set_container( $container );
	// Optional: Set a unique prefix for actions & filters.
	Config::set_hook_prefix( 'my-custom-prefix' );

	Uplink::init();
}, 0 );
```

## Translation

Package is using `__( 'Invalid request: nonce field is expired. Please try again.', '%TEXTDOMAIN%' )` function for translation. In order to change domain placeholder `'%TEXTDOMAIN%'` to your plugin translation domain run
```bash
./vendor/bin/stellar-uplink domain=<your-plugin-domain>
```
or
```bash
./vendor/bin/stellar-uplink
```
and prompt the plugin domain
You can also add lines below to your composer file in order to run command automatically
```json
"scripts": {
	"stellar-uplink": [
	  "vendor/bin/stellar-uplink domain=<your-plugin-domain>"
	],
	"post-install-cmd": [
	  "@stellar-uplink"
	],
	"post-update-cmd": [
	  "@stellar-uplink"
	]
  }
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

## Render license key form on your settings page
In order to render license key form just add 2 lines of code to your settings page, tab, etc.
```php
use StellarWP\Uplink\Config;

$container = Config::get_container();
$container->get( License_Field::class )->render();
```
### Example: Register settings page and render license fields
Register a settings page for a plugin if you need it
```php
add_action( 'admin_menu', function () {
    add_menu_page(
        'Sample',
        'Sample',
        'manage_options',
        'sample-plugin-lib',
        'render_settings_page',
        '',
        null
    );

}, 11 );
```
Add lines below to your settings page. This will render license key form with submit button
```php
use StellarWP\Uplink\Config;

function render_settings_page() {
    // ....
    $container = Config::get_container();
    $container->get( License_Field::class )->render();
    //....
}
```

