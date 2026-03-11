/**
 * Product logo resolved from a slug-based SVG asset.
 *
 * Falls back to a neutral placeholder box when no asset is found.
 *
 * @package StellarWP\Uplink
 */
import logoGive from '@/img/logo-give.svg';
import logoTheEventsCalendar from '@/img/logo-the-events-calendar.svg';
import logoLearnDash from '@/img/logo-learndash.svg';
import logoKadence from '@/img/logo-kadence.svg';

const PRODUCT_LOGOS: Record<string, string> = {
    give: logoGive,
    'the-events-calendar': logoTheEventsCalendar,
    learndash: logoLearnDash,
    kadence: logoKadence,
};

interface ProductLogoProps {
    slug: string;
    size: number;
}

/**
 * @since 3.0.0
 */
export function ProductLogo( { slug, size }: ProductLogoProps ) {
    const src = PRODUCT_LOGOS[ slug ];

    if ( ! src ) {
        return (
            <div
                className="rounded bg-muted shrink-0"
                style={ { width: size, height: size } }
            />
        );
    }

    return (
        <img
            src={ src }
            alt=""
            className="shrink-0 rounded"
            style={ { width: size, height: size } }
        />
    );
}
