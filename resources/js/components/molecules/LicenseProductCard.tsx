/**
 * Card showing a single licensed product: logo, name, tier badge, and expiry.
 *
 * @package StellarWP\Uplink
 */
import { __ } from '@wordpress/i18n';
import { Badge } from '@/components/ui/badge';
import { ProductLogo } from '@/components/atoms/ProductLogo';
import { formatDate, getExpiryStatus, expiryCardClass, expiryTextClass } from '@/lib/license-utils';
import type { LicenseProduct } from '@/types/api';

interface LicenseProductCardProps {
	lp:          LicenseProduct;
	productName: string;
	tierName:    string;
}

/**
 * @since 3.0.0
 */
export function LicenseProductCard( { lp, productName, tierName }: LicenseProductCardProps ) {
	const expiryStatus = getExpiryStatus( lp.expires );

	return (
		<div className={ `rounded-lg border bg-card px-3 py-2.5 space-y-2.5 ${ expiryCardClass[ expiryStatus ] }` }>
			<div className="flex items-center gap-2">
				<ProductLogo slug={ lp.product_slug } size={ 24 } variant="nobg" />
				<span className="text-sm font-medium text-foreground flex-1 min-w-0">
					{ productName }
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
}
