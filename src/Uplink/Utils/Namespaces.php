<?php declare(strict_types=1);

namespace StellarWP\Uplink\Utils;

class Namespaces {

	public const DEFAULT_NAME = 'stellarwp';

	public static function get_hook_name( string $entity, string $plugin_slug = '' ): string {
		return sprintf( '%s/%s', $plugin_slug ?: self::DEFAULT_NAME, $entity );
	}

	public static function get_option_name( string $entity, string $plugin_slug = '' ): string {
		return sprintf( '%s_%s_', $plugin_slug ?: self::DEFAULT_NAME, $entity );
	}

}
