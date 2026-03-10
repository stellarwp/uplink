<?php declare( strict_types=1 );

namespace StellarWP\Uplink\CLI\Commands;

use StellarWP\Uplink\Features\Feature_Collection;
use StellarWP\Uplink\Features\Manager;
use StellarWP\Uplink\Features\Types\Feature as Feature_Type;
use StellarWP\Uplink\Utils\Cast;
use WP_CLI;
use WP_CLI\Formatter;
use WP_CLI_Command;

/**
 * Manage Uplink features.
 *
 * ## EXAMPLES
 *
 *     # List all features
 *     wp uplink feature list
 *
 *     # List available flag features as JSON
 *     wp uplink feature list --type=flag --available=true --format=json
 *
 *     # Count features in a group
 *     wp uplink feature list --group=Kadence --format=count
 *
 *     # Get a single feature
 *     wp uplink feature get my-feature
 *
 *     # Check if a feature is enabled
 *     wp uplink feature is-enabled my-feature
 *
 *     # Enable a feature by slug
 *     wp uplink feature enable my-feature
 *
 *     # Disable a feature by slug
 *     wp uplink feature disable my-feature
 *
 * @since 3.0.0
 */
class Feature extends WP_CLI_Command {

	/**
	 * Default fields shown in table output.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	private const DEFAULT_FIELDS = 'slug,name,type,group,is_available,is_enabled';

	/**
	 * The feature manager instance.
	 *
	 * @since 3.0.0
	 *
	 * @var Manager
	 */
	private Manager $manager;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param Manager $manager The feature manager.
	 */
	public function __construct( Manager $manager ) {
		$this->manager = $manager;
	}

	/**
	 * Lists features with optional filters.
	 *
	 * ## OPTIONS
	 *
	 * [--group=<group>]
	 * : Filter by product group.
	 *
	 * [--tier=<tier>]
	 * : Filter by tier.
	 *
	 * [--available=<available>]
	 * : Filter by availability (true or false).
	 *
	 * [--type=<type>]
	 * : Filter by feature type (flag, plugin, theme).
	 *
	 * [--fields=<fields>]
	 * : Comma-separated list of fields to display.
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 *   - count
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields are available for display:
	 *
	 * * slug
	 * * name
	 * * description
	 * * type
	 * * group
	 * * tier
	 * * is_available
	 * * is_enabled
	 * * documentation_url
	 * * plugin_file
	 * * plugin_slug
	 * * authors
	 * * is_dot_org
	 *
	 * ## EXAMPLES
	 *
	 *     # List all features in a table
	 *     wp uplink feature list
	 *
	 *     # List available flag features as JSON
	 *     wp uplink feature list --type=flag --available=true --format=json
	 *
	 *     # Count features in a group
	 *     wp uplink feature list --group=Kadence --format=count
	 *
	 * @subcommand list
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 *
	 * @return void
	 */
	public function list_( array $args, array $assoc_args ): void {
		$features = $this->manager->get_features();

		if ( is_wp_error( $features ) ) {
			WP_CLI::error( $features->get_error_message() );

			return; // WP_CLI::error() exits, but PHPStan needs this for type narrowing.
		}

		$group     = $assoc_args['group'] ?? null;
		$tier      = $assoc_args['tier'] ?? null;
		$available = isset( $assoc_args['available'] ) ? Cast::to_bool( $assoc_args['available'] ) : null;
		$type      = $assoc_args['type'] ?? null;

		if ( $group !== null || $tier !== null || $available !== null || $type !== null ) {
			$features = $features->filter( $group, $tier, $available, $type );
		}

		$items = $this->collection_to_display_items( $features );

		$formatter = new Formatter(
			$assoc_args,
			explode( ',', self::DEFAULT_FIELDS )
		);

		$formatter->display_items( $items );
	}

	/**
	 * Gets a single feature by slug.
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : The feature slug.
	 *
	 * [--fields=<fields>]
	 * : Comma-separated list of fields to display.
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Show feature details
	 *     wp uplink feature get my-feature
	 *
	 *     # Get feature as JSON
	 *     wp uplink feature get my-feature --format=json
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 *
	 * @return void
	 */
	public function get( array $args, array $assoc_args ): void {
		$slug    = $args[0];
		$feature = $this->manager->get_feature( $slug );

		if ( ! $feature ) {
			WP_CLI::error( sprintf( 'Feature "%s" not found.', $slug ) );

			return; // WP_CLI::error() exits, but PHPStan needs this for type narrowing.
		}

		$item = $this->feature_to_display_item( $feature );

		$fields = isset( $assoc_args['fields'] )
			? explode( ',', $assoc_args['fields'] )
			: array_keys( $item );

		$formatter = new Formatter( $assoc_args, $fields );
		$formatter->display_item( $item );
	}

	/**
	 * Checks whether a feature is currently enabled.
	 *
	 * Exits with code 0 if the feature is enabled, 1 if not enabled or not found.
	 * Useful in shell scripts: `if wp uplink feature is-enabled my-feature; then ...`
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : The feature slug.
	 *
	 * ## EXAMPLES
	 *
	 *     # Check if a feature is enabled (exit code 0 = enabled)
	 *     wp uplink feature is-enabled my-feature
	 *
	 *     # Use in a script
	 *     if wp uplink feature is-enabled my-feature; then
	 *       echo "Feature is enabled"
	 *     fi
	 *
	 * @subcommand is-enabled
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 *
	 * @return void
	 */
	public function is_enabled( array $args, array $assoc_args ): void {
		$slug = $args[0];

		if ( ! $this->manager->get_feature( $slug ) ) {
			WP_CLI::error( sprintf( 'Feature "%s" not found.', $slug ) );

			return;
		}

		$result = $this->manager->is_enabled( $slug );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );

			return;
		}

		if ( $result ) {
			WP_CLI::log( sprintf( 'Feature "%s" is enabled.', $slug ) );
		} else {
			WP_CLI::error( sprintf( 'Feature "%s" is not enabled.', $slug ) );
		}
	}

	/**
	 * Enables a feature.
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : The feature slug. Use `wp uplink feature list` to see available slugs.
	 *
	 * ## EXAMPLES
	 *
	 *     # Enable a feature
	 *     wp uplink feature enable my-feature
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 *
	 * @return void
	 */
	public function enable( array $args, array $assoc_args ): void {
		$slug   = $args[0];
		$result = $this->manager->enable( $slug );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		WP_CLI::success( sprintf( 'Feature "%s" enabled.', $slug ) );
	}

	/**
	 * Disables a feature.
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : The feature slug. Use `wp uplink feature list` to see available slugs.
	 *
	 * ## EXAMPLES
	 *
	 *     # Disable a feature
	 *     wp uplink feature disable my-feature
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 *
	 * @return void
	 */
	public function disable( array $args, array $assoc_args ): void {
		$slug   = $args[0];
		$result = $this->manager->disable( $slug );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		WP_CLI::success( sprintf( 'Feature "%s" disabled.', $slug ) );
	}

	/**
	 * Converts a feature collection to display items with boolean casting.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature_Collection $features The feature collection.
	 *
	 * @return list<array<string, mixed>>
	 */
	private function collection_to_display_items( Feature_Collection $features ): array {
		$items = [];

		foreach ( $features as $feature ) {
			$items[] = $this->feature_to_display_item( $feature );
		}

		return $items;
	}

	/**
	 * Converts a single feature to a display-ready associative array.
	 *
	 * Booleans are cast to 'true'/'false' strings for table display.
	 * The is_enabled field is resolved via the Manager.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature_Type $feature The feature instance.
	 *
	 * @return array<string, mixed>
	 */
	private function feature_to_display_item( Feature_Type $feature ): array {
		$is_enabled = $this->manager->is_enabled( $feature->get_slug() );

		$item = $feature->to_array();

		$item['is_available'] = $this->to_display_bool( ! empty( $item['is_available'] ) );
		$item['is_enabled']   = $this->to_display_bool( $is_enabled === true );
		$item['is_dot_org']   = $this->to_display_bool( ! empty( $item['is_dot_org'] ) );

		foreach ( $item as $key => $value ) {
			if ( is_array( $value ) ) {
				$item[ $key ] = implode( ', ', $value );
			}
		}

		return $item;
	}

	/**
	 * Converts a boolean to a display-friendly 'true'/'false' string.
	 *
	 * @since 3.0.0
	 *
	 * @param bool $value The boolean value.
	 *
	 * @return string
	 */
	private function to_display_bool( bool $value ): string {
		return $value ? 'true' : 'false';
	}
}
