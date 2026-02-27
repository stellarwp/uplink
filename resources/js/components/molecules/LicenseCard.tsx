/**
 * License card row + detail modal.
 *
 * @package StellarWP\Uplink
 */
import { useState } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { ExternalLink, Key, ChevronRight } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogHeader, DialogContent, DialogFooter } from '@/components/ui/dialog';
import { useLicenseStore } from '@/stores/license-store';
import { PRODUCTS } from '@/data/products';
import type { License } from '@/types/api';

interface LicenseCardProps {
    license: License;
}

/**
 * @since 3.0.0
 */
export function LicenseCard( { license }: LicenseCardProps ) {
    const [ detailOpen, setDetailOpen ] = useState( false );
    const { removeLicense } = useLicenseStore();

    const productNames = license.productSlugs
        .map( ( slug ) => PRODUCTS.find( ( p ) => p.slug === slug )?.name ?? slug )
        .join( ', ' );

    const tierLabel =
        license.tier.charAt( 0 ).toUpperCase() + license.tier.slice( 1 );

    return (
        <>
            <div className="flex items-center justify-between px-4 py-3 hover:bg-slate-50 transition-colors">
                <div className="flex items-center gap-3 min-w-0">
                    <Key className="w-4 h-4 text-slate-400 shrink-0" />
                    <div className="min-w-0">
                        <div className="flex items-center gap-2 flex-wrap">
                            <span className="font-mono text-sm text-slate-700 truncate">
                                { license.key }
                            </span>
                            { license.type === 'legacy' && (
                                <Badge variant="warning">
                                    { __( 'Legacy', '%TEXTDOMAIN%' ) }
                                </Badge>
                            ) }
                            <Badge variant={ license.isExpired ? 'destructive' : 'success' }>
                                { license.isExpired
                                    ? __( 'Expired', '%TEXTDOMAIN%' )
                                    : tierLabel }
                            </Badge>
                        </div>
                        <p className="text-xs text-slate-500 mt-0.5 m-0">
                            { productNames }
                            { ' · ' }
                            { license.isExpired
                                ? /* translators: %s is the expiration date of the license */
                                  sprintf( __( 'Expired %s', '%TEXTDOMAIN%' ), license.expires )
                                : /* translators: %s is the expiration date of the license */
                                  sprintf( __( 'Expires %s', '%TEXTDOMAIN%' ), license.expires ) }
                        </p>
                    </div>
                </div>

                <div className="flex items-center gap-2 shrink-0 ml-4">
                    { license.isExpired && (
                        <Button variant="outline" size="xs" asChild>
                            <a
                                href={ license.renewUrl }
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                { __( 'Renew', '%TEXTDOMAIN%' ) }
                                <ExternalLink className="w-3 h-3" />
                            </a>
                        </Button>
                    ) }
                    <Button
                        variant="ghost"
                        size="icon-xs"
                        onClick={ () => setDetailOpen( true ) }
                        aria-label={ __( 'View license details', '%TEXTDOMAIN%' ) }
                    >
                        <ChevronRight className="w-4 h-4" />
                    </Button>
                </div>
            </div>

            <LicenseDetailModal
                license={ license }
                open={ detailOpen }
                onClose={ () => setDetailOpen( false ) }
                onRemove={ () => {
                    removeLicense( license.key );
                    setDetailOpen( false );
                } }
            />
        </>
    );
}

// ---------------------------------------------------------------------------
// Detail Modal
// ---------------------------------------------------------------------------

interface LicenseDetailModalProps {
    license: License;
    open: boolean;
    onClose: () => void;
    onRemove: () => void;
}

export function LicenseDetailModal( {
    license,
    open,
    onClose,
    onRemove,
}: LicenseDetailModalProps ) {
    const productNames = license.productSlugs.map(
        ( slug ) => PRODUCTS.find( ( p ) => p.slug === slug )?.name ?? slug
    );

    const tierLabel =
        license.tier.charAt( 0 ).toUpperCase() + license.tier.slice( 1 );

    return (
        <Dialog open={ open } onClose={ onClose }>
            <DialogHeader
                title={ __( 'License Details', '%TEXTDOMAIN%' ) }
                onClose={ onClose }
            />
            <DialogContent>
                <dl className="grid grid-cols-[auto_1fr] gap-x-6 gap-y-3 text-sm">
                    <dt className="text-muted-foreground font-medium">
                        { __( 'License Key', '%TEXTDOMAIN%' ) }
                    </dt>
                    <dd className="font-mono text-foreground break-all m-0">{ license.key }</dd>

                    <dt className="text-muted-foreground font-medium">
                        { __( 'Type', '%TEXTDOMAIN%' ) }
                    </dt>
                    <dd className="m-0">
                        <Badge variant={ license.type === 'legacy' ? 'warning' : 'secondary' }>
                            { license.type === 'legacy'
                                ? __( 'Legacy', '%TEXTDOMAIN%' )
                                : __( 'Unified', '%TEXTDOMAIN%' ) }
                        </Badge>
                    </dd>

                    <dt className="text-muted-foreground font-medium">
                        { __( 'Tier', '%TEXTDOMAIN%' ) }
                    </dt>
                    <dd className="m-0">
                        <Badge variant="info">{ tierLabel }</Badge>
                    </dd>

                    <dt className="text-muted-foreground font-medium">
                        { __( 'Expires', '%TEXTDOMAIN%' ) }
                    </dt>
                    <dd className="m-0">
                        <span className={ license.isExpired ? 'text-destructive' : 'text-foreground' }>
                            { license.expires }
                            { license.isExpired && (
                                <span className="ml-1.5 text-xs">
                                    ({ __( 'Expired', '%TEXTDOMAIN%' ) })
                                </span>
                            ) }
                        </span>
                    </dd>

                    <dt className="text-muted-foreground font-medium">
                        { __( 'Products', '%TEXTDOMAIN%' ) }
                    </dt>
                    <dd className="m-0">{ productNames.join( ', ' ) }</dd>
                </dl>


            </DialogContent>
            <DialogFooter>
                <Button
                    variant="destructive"
                    size="sm"
                    onClick={ onRemove }
                >
                    { __( 'Remove License', '%TEXTDOMAIN%' ) }
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    onClick={ onClose }
                >
                    { __( 'Close', '%TEXTDOMAIN%' ) }
                </Button>
            </DialogFooter>
        </Dialog>
    );
}
