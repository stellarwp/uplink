/**
 * License sidebar panel.
 *
 * Always visible. Shows the license key input when no license is stored,
 * or license cards when one is active. Below the license section, shows
 * upsell cards for products not covered by the current license.
 *
 * @package StellarWP\Uplink
 */
import { useState, useMemo } from 'react';
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { KeyRound, Pencil, ExternalLink, Rocket, Trash2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogHeader, DialogContent, DialogFooter } from '@/components/ui/dialog';
import { LicenseKeyInput } from '@/components/molecules/LicenseKeyInput';
import { store as uplinkStore } from '@/store';
import { PRODUCTS } from '@/data/products';
import { useToast } from '@/context/toast-context';
import { UplinkError } from '@/errors';
import logoGiveNobg from '@img/logo-givewp-nobg.svg';
import logoLearnDashNobg from '@img/logo-learndash-nobg.svg';
import logoTecNobg from '@img/logo-tec-nobg.svg';
import logoKadenceNobg from '@img/logo-kadence-nobg.svg';

const NOBG_LOGOS: Record<string, string> = {
    give: logoGiveNobg,
    learndash: logoLearnDashNobg,
    'the-events-calendar': logoTecNobg,
    kadence: logoKadenceNobg,
};

const UPSELL_TAGLINES: Record<string, string> = {
    give: __( 'Beautiful donation forms & fundraising', '%TEXTDOMAIN%' ),
    'the-events-calendar': __( 'Tickets, RSVPs & event management', '%TEXTDOMAIN%' ),
    learndash: __( 'Sell courses & manage learners', '%TEXTDOMAIN%' ),
    kadence: __( 'Themes, blocks & design tools', '%TEXTDOMAIN%' ),
};

function formatDate( dateStr: string ): string {
    return new Date( dateStr ).toLocaleDateString( 'en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    } );
}

function getExpiryStatus( dateStr: string ): 'expired' | 'expiring-soon' | 'ok' {
    const diff = new Date( dateStr ).getTime() - Date.now();
    if ( diff <= 0 ) return 'expired';
    if ( diff <= 30 * 24 * 60 * 60 * 1000 ) return 'expiring-soon';
    return 'ok';
}

const expiryCardClass: Record<string, string> = {
    expired: 'border-destructive/60 bg-destructive/5',
    'expiring-soon': 'border-amber-400 bg-amber-50',
    ok: '',
};

const expiryTextClass: Record<string, string> = {
    expired: 'text-destructive font-medium',
    'expiring-soon': 'text-amber-600 font-medium',
    ok: 'text-muted-foreground',
};

/**
 * @since 3.0.0
 */
export function LicensePanel() {
    const [ editingOpen, setEditingOpen ] = useState( false );
    const { addToast } = useToast();
    const { deleteLicense } = useDispatch( uplinkStore );

    const { licenseKey, licenseProducts, catalogs } = useSelect(
        ( select ) => ({
            licenseKey: select( uplinkStore ).getLicenseKey(),
            licenseProducts: select( uplinkStore ).getLicenseProducts(),
            catalogs: select( uplinkStore ).getCatalog(),
        }),
        []
    );

    // Build a flat tier slug → display name lookup from all catalog tiers.
    const tierNameMap = useMemo( () => {
        const map: Record<string, string> = {};
        catalogs.forEach( ( catalog ) => {
            catalog.tiers.forEach( ( t ) => {
                map[ t.slug ] = t.name;
            } );
        } );
        return map;
    }, [ catalogs ] );

    // Build a product slug → lowest-tier purchase URL map from the API catalog.
    // Falls back to the static fixture's first tier upgradeUrl when unavailable.
    const upsellUrlMap = useMemo( () => {
        const map: Record<string, string> = {};
        catalogs.forEach( ( catalog ) => {
            const sorted = catalog.tiers.slice().sort( ( a, b ) => a.rank - b.rank );
            if ( sorted[ 0 ]?.purchase_url ) {
                map[ catalog.product_slug ] = sorted[ 0 ].purchase_url;
            }
        } );
        return map;
    }, [ catalogs ] );

    const upsellUrl = ( slug: string ): string =>
        upsellUrlMap[ slug ] ?? '#';

    const licensedSlugs = new Set( licenseProducts.map( ( lp ) => lp.product_slug ) );
    const upsellProducts = PRODUCTS.filter( ( p ) => ! licensedSlugs.has( p.slug ) );

    const hasLicense = licenseKey !== null;

    const handleRemove = async () => {
        const result = await deleteLicense();
        setEditingOpen( false );
        if ( result instanceof UplinkError ) {
            addToast( result.message, 'error' );
        } else {
            addToast( __( 'License removed.', '%TEXTDOMAIN%' ), 'default' );
        }
    };

    return (
        <div className="sticky top-4 w-[280px] shrink-0 space-y-6">

            {/* ── License section ── */}
            <div className="space-y-3">
                <div className="flex items-center gap-2.5">
                    <KeyRound className="w-4 h-4 text-muted-foreground" />
                    <span className="text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                        { __( 'License', '%TEXTDOMAIN%' ) }
                    </span>
                    { hasLicense && (
                        <button
                            type="button"
                            onClick={ () => setEditingOpen( true ) }
                            className="flex items-center gap-1 text-[11px] text-primary transition-colors hover:opacity-75"
                        >
                            <Pencil className="w-3 h-3" />
                            { __( 'Edit', '%TEXTDOMAIN%' ) }
                        </button>
                    ) }
                </div>

                { ! hasLicense && (
                    <div className="space-y-2">
                        <LicenseKeyInput />
                        <p className="text-xs text-muted-foreground leading-relaxed mt-0 mb-0">
                            { __( 'Enter your license key to unlock features.', '%TEXTDOMAIN%' ) }
                        </p>
                    </div>
                ) }

                { hasLicense && licenseProducts.length > 0 && (
                    <div className="space-y-3">
                        { licenseProducts.map( ( lp ) => {
                            const product = PRODUCTS.find( ( p ) => p.slug === lp.product_slug );
                            const tierName = tierNameMap[ lp.tier ] ?? lp.tier;
                            const expiryStatus = getExpiryStatus( lp.expires );
                            const logo = NOBG_LOGOS[ lp.product_slug ];

                            return (
                                <div
                                    key={ lp.product_slug }
                                    className={ `rounded-lg border bg-card px-3 py-2.5 space-y-2.5 ${ expiryCardClass[ expiryStatus ] }` }
                                >
                                    <div className="flex items-center gap-2">
                                        { logo ? (
                                            <img src={ logo } alt="" className="w-6 h-6 shrink-0" />
                                        ) : (
                                            <div className="w-6 h-6 rounded bg-neutral-300 shrink-0" />
                                        ) }
                                        <span className="text-sm font-medium text-foreground flex-1 min-w-0">
                                            { product?.name ?? lp.product_slug }
                                        </span>
                                        <Badge variant="gradient" className="text-[10px]">
                                            { tierName }
                                        </Badge>
                                    </div>
                                    <span className={ `text-xs ${ expiryTextClass[ expiryStatus ] }` }>
                                        { expiryStatus === 'expired'
                                            ? __( 'Expired', '%TEXTDOMAIN%' )
                                            : __( 'Expires', '%TEXTDOMAIN%' ) }
                                        { ' ' }
                                        { formatDate( lp.expires ) }
                                    </span>
                                </div>
                            );
                        } ) }
                    </div>
                ) }
            </div>

            {/* ── Upsell section ── */}
            { upsellProducts.length > 0 && (
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
                            { upsellProducts.map( ( p ) => {
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
            ) }

            {/* ── Edit / remove license dialog ── */}
            <Dialog
                open={ editingOpen }
                onClose={ () => setEditingOpen( false ) }
                maxWidth="max-w-sm"
            >
                <DialogHeader
                    title={ __( 'Edit License', '%TEXTDOMAIN%' ) }
                    description={ __( 'View or remove your license key.', '%TEXTDOMAIN%' ) }
                    onClose={ () => setEditingOpen( false ) }
                />
                <DialogContent>
                    <input
                        readOnly
                        value={ licenseKey ?? '' }
                        className="w-full rounded-md border bg-muted/40 px-3 py-2 text-sm font-mono text-foreground focus:outline-none select-all"
                    />
                </DialogContent>
                <DialogFooter className="justify-between">
                    <Button
                        variant="destructive"
                        size="sm"
                        className="gap-1.5"
                        onClick={ handleRemove }
                    >
                        <Trash2 className="w-3.5 h-3.5" />
                        { __( 'Remove License', '%TEXTDOMAIN%' ) }
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={ () => setEditingOpen( false ) }
                    >
                        { __( 'Close', '%TEXTDOMAIN%' ) }
                    </Button>
                </DialogFooter>
            </Dialog>

        </div>
    );
}
