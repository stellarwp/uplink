/**
 * Upsell card for a product not covered by the current license.
 *
 * @package StellarWP\Uplink
 */
import { __ } from '@wordpress/i18n';
import { ExternalLink } from 'lucide-react';
import { ProductLogo } from '@/components/atoms/ProductLogo';
import type { Product } from '@/types/api';

const UPSELL_TAGLINES: Record<string, string> = {
	give:                  __( 'Beautiful donation forms & fundraising', '%TEXTDOMAIN%' ),
	'the-events-calendar': __( 'Tickets, RSVPs & event management', '%TEXTDOMAIN%' ),
	learndash:             __( 'Sell courses & manage learners', '%TEXTDOMAIN%' ),
	kadence:               __( 'Themes, blocks & design tools', '%TEXTDOMAIN%' ),
};

interface UpsellCardProps {
	product: Product;
	href:    string;
}

/**
 * @since 3.0.0
 */
export function UpsellCard( { product, href }: UpsellCardProps ) {
	return (
		<a
			href={ href }
			target="_blank"
			rel="noopener noreferrer"
			className="flex items-center gap-2.5 rounded-xl border bg-card px-4 py-3 hover:bg-muted/50 transition-colors"
		>
			<ProductLogo slug={ product.slug } size={ 32 } variant="nobg" />
			<div className="flex-1 min-w-0">
				<span className="text-sm font-medium text-foreground block">
					{ product.name }
				</span>
				<span className="text-xs text-muted-foreground">
					{ UPSELL_TAGLINES[ product.slug ] ?? product.tagline }
				</span>
			</div>
			<ExternalLink className="w-3.5 h-3.5 text-muted-foreground shrink-0" />
		</a>
	);
}
