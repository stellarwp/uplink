/**
 * Feature icon resolved from a slug-based SVG asset.
 *
 * Renders a 32×32 placeholder box until per-feature SVG assets are
 * provided by the design team and added to resources/js/img/.
 *
 * @package StellarWP\Uplink
 */

// Feature logo assets are added here as they are delivered by design.
// Key: feature slug. Value: imported SVG URL.
const FEATURE_LOGOS: Record<string, string> = {};

interface FeatureIconProps {
    slug: string;
}

/**
 * @since 3.0.0
 */
export function FeatureIcon( { slug }: FeatureIconProps ) {
    const src = FEATURE_LOGOS[ slug ];

    if ( ! src ) {
        return <div className="rounded bg-muted shrink-0 w-8 h-8" />;
    }

    return (
        <img
            src={ src }
            alt=""
            className="shrink-0 rounded w-8 h-8"
        />
    );
}
