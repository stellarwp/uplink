/**
 * Product logo resolved from a slug-based SVG asset.
 *
 * Falls back to a neutral placeholder box when no asset is found.
 * Use variant="nobg" for the transparent (no background) logo variants.
 *
 * @package StellarWP\Uplink
 */
import logoGive from '@img/logo-give.svg';
import logoTheEventsCalendar from '@img/logo-tec.svg';
import logoLearnDash from '@img/logo-learndash.svg';
import logoKadence from '@img/logo-kadence.svg';
import logoGiveNobg from '@img/logo-givewp-nobg.svg';
import logoLearnDashNobg from '@img/logo-learndash-nobg.svg';
import logoTecNobg from '@img/logo-tec-nobg.svg';
import logoKadenceNobg from '@img/logo-kadence-nobg.svg';

const LOGOS: Record<string, string> = {
    give:                  logoGive,
    'the-events-calendar': logoTheEventsCalendar,
    learndash:             logoLearnDash,
    kadence:               logoKadence,
};

const LOGOS_NOBG: Record<string, string> = {
    give:                  logoGiveNobg,
    'the-events-calendar': logoTecNobg,
    learndash:             logoLearnDashNobg,
    kadence:               logoKadenceNobg,
};

interface ProductLogoProps {
    slug:     string;
    size:     number;
    variant?: 'default' | 'nobg';
}

/**
 * @since 3.0.0
 */
export function ProductLogo( { slug, size, variant = 'default' }: ProductLogoProps ) {
    const src = ( variant === 'nobg' ? LOGOS_NOBG : LOGOS )[ slug ];

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
