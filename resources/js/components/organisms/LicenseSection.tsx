/**
 * License section: header, key input, licensed-product cards, and edit dialog.
 *
 * @package StellarWP\Uplink
 */
import { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { KeyRound, Pencil, Trash2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogHeader, DialogContent, DialogFooter } from '@/components/ui/dialog';
import { LicenseKeyInput } from '@/components/molecules/LicenseKeyInput';
import { PRODUCTS } from '@/data/products';
import { formatDate, getExpiryStatus, expiryCardClass, expiryTextClass } from '@/lib/license-utils';
import logoGiveNobg from '@img/logo-givewp-nobg.svg';
import logoLearnDashNobg from '@img/logo-learndash-nobg.svg';
import logoTecNobg from '@img/logo-tec-nobg.svg';
import logoKadenceNobg from '@img/logo-kadence-nobg.svg';
import type { LicenseProduct } from '@/types/api';

const NOBG_LOGOS: Record<string, string> = {
    give:                 logoGiveNobg,
    learndash:            logoLearnDashNobg,
    'the-events-calendar': logoTecNobg,
    kadence:              logoKadenceNobg,
};

interface LicenseSectionProps {
    licenseKey:      string | null;
    licenseProducts: LicenseProduct[];
    tierNameMap:     Record<string, string>;
    onRemove:        () => Promise<void>;
}

/**
 * @since 3.0.0
 */
export function LicenseSection( { licenseKey, licenseProducts, tierNameMap, onRemove }: LicenseSectionProps ) {
    const [ editingOpen, setEditingOpen ] = useState( false );

    const hasLicense = licenseKey !== null;

    const handleRemove = async () => {
        await onRemove();
        setEditingOpen( false );
    };

    return (
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
                        className="flex items-center gap-1 text-[11px] text-emerald-600 transition-colors hover:opacity-75"
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
                        const product      = PRODUCTS.find( ( p ) => p.slug === lp.product_slug );
                        const tierName     = tierNameMap[ lp.tier ] ?? lp.tier;
                        const expiryStatus = getExpiryStatus( lp.expires );
                        const logo         = NOBG_LOGOS[ lp.product_slug ];

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
