/**
 * License section: header, key input, licensed-product cards, and edit dialog.
 *
 * @package StellarWP\Uplink
 */
import { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { KeyRound, Pencil, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogHeader, DialogContent, DialogFooter } from '@/components/ui/dialog';
import { SectionHeader } from '@/components/atoms/SectionHeader';
import { LicenseKeyInput } from '@/components/molecules/LicenseKeyInput';
import { LicenseProductCard } from '@/components/molecules/LicenseProductCard';
import { PRODUCTS } from '@/data/products';
import type { LicenseProduct } from '@/types/api';

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
            <SectionHeader
                icon={ <KeyRound className="w-4 h-4 text-muted-foreground" /> }
                label={ __( 'License', '%TEXTDOMAIN%' ) }
                action={ hasLicense && (
                    <button
                        type="button"
                        onClick={ () => setEditingOpen( true ) }
                        className="flex items-center gap-1 text-[11px] text-emerald-600 transition-colors hover:opacity-75"
                    >
                        <Pencil className="w-3 h-3" />
                        { __( 'Edit', '%TEXTDOMAIN%' ) }
                    </button>
                ) }
            />

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
                    { licenseProducts.map( ( lp ) => (
                        <LicenseProductCard
                            key={ lp.product_slug }
                            lp={ lp }
                            productName={ PRODUCTS.find( ( p ) => p.slug === lp.product_slug )?.name ?? lp.product_slug }
                            tierName={ tierNameMap[ lp.tier ] ?? lp.tier }
                        />
                    ) ) }
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
