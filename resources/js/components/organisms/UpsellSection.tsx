/**
 * Upsell section: products not covered by the current license.
 *
 * @package StellarWP\Uplink
 */
import { __ } from '@wordpress/i18n';
import { ExternalLink, Rocket } from 'lucide-react';
import logoGiveNobg from '@img/logo-givewp-nobg.svg';
import logoLearnDashNobg from '@img/logo-learndash-nobg.svg';
import logoTecNobg from '@img/logo-tec-nobg.svg';
import logoKadenceNobg from '@img/logo-kadence-nobg.svg';
import type { Product } from '@/types/api';

const NOBG_LOGOS: Record<string, string> = {
    give:                 logoGiveNobg,
    learndash:            logoLearnDashNobg,
    'the-events-calendar': logoTecNobg,
    kadence:              logoKadenceNobg,
};

const UPSELL_TAGLINES: Record<string, string> = {
    give:                 __( 'Beautiful donation forms & fundraising', '%TEXTDOMAIN%' ),
    'the-events-calendar': __( 'Tickets, RSVPs & event management', '%TEXTDOMAIN%' ),
    learndash:            __( 'Sell courses & manage learners', '%TEXTDOMAIN%' ),
    kadence:              __( 'Themes, blocks & design tools', '%TEXTDOMAIN%' ),
};

interface UpsellSectionProps {
    products:     Product[];
    upsellUrlMap: Record<string, string>;
}

/**
 * @since 3.0.0
 */
export function UpsellSection( { products, upsellUrlMap }: UpsellSectionProps ) {
    if ( products.length === 0 ) return null;

    const upsellUrl = ( slug: string ): string => upsellUrlMap[ slug ] ?? '#';

    return (
        <>
            <hr className="border-t border-0 !border-b-0" />

            <div className="space-y-3">
                <div className="flex items-center gap-2.5">
                    <Rocket className="w-4 h-4 text-muted-foreground" />
                    <span className="text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                        { __( 'Add to your plan', '%TEXTDOMAIN%' ) }
                    </span>
                </div>
                <div className="space-y-2">
                    { products.map( ( p ) => {
                        const logo = NOBG_LOGOS[ p.slug ];
                        return (
                            <a
                                key={ p.slug }
                                href={ upsellUrl( p.slug ) }
                                target="_blank"
                                rel="noopener noreferrer"
                                className="flex items-center gap-2.5 rounded-xl border bg-card px-4 py-3 hover:bg-muted/50 transition-colors"
                            >
                                { logo ? (
                                    <img src={ logo } alt="" className="w-8 h-8 shrink-0" />
                                ) : (
                                    <div className="w-8 h-8 rounded bg-neutral-900 shrink-0" />
                                ) }
                                <div className="flex-1 min-w-0">
                                    <span className="text-sm font-medium text-foreground block">
                                        { p.name }
                                    </span>
                                    <span className="text-xs text-muted-foreground">
                                        { UPSELL_TAGLINES[ p.slug ] ?? p.tagline }
                                    </span>
                                </div>
                                <ExternalLink className="w-3.5 h-3.5 text-muted-foreground shrink-0" />
                            </a>
                        );
                    } ) }
                </div>
            </div>
        </>
    );
}
