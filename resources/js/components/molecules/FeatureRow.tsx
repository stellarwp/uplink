/**
 * A single feature row in the product feature list.
 *
 * Clicking the row header expands/collapses the feature description.
 * The toggle switch remains independently clickable.
 *
 * @package StellarWP\\Uplink
 */
import { useState } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { ChevronRight, ChevronDown } from 'lucide-react';
import { cn } from '@/lib/utils';
import { FeatureIcon } from '@/components/atoms/FeatureIcon';
import { StatusBadge } from '@/components/atoms/StatusBadge';
import { PurchaseLink } from '@/components/atoms/PurchaseLink';
import { Switch } from '@/components/ui/switch';
import { useToast } from '@/context/toast-context';
import { store as uplinkStore } from '@/store';
import { UplinkError } from '@/errors';
import type { Feature, Product } from '@/types/api';

interface FeatureRowProps {
	feature: Feature;
	product: Product;
}

/**
 * @since 3.0.0
 */
export function FeatureRow( { feature, product }: FeatureRowProps ) {
	const [ expanded, setExpanded ] = useState( false );
	const { addToast } = useToast();
	const { enableFeature, disableFeature } = useDispatch( uplinkStore );

	// When this feature is a plugin or theme, block toggling while any
	// other installable feature is mid-toggle (WordPress cannot safely
	// install/activate/deactivate multiple plugins or themes at once).
	const installableBusy = useSelect(
		( select ) =>
			feature.type !== 'flag' &&
			select( uplinkStore ).isAnyInstallableToggling(),
		[ feature.type ]
	);

	const [ pendingAction, setPendingAction ] = useState<
		'enabling' | 'disabling' | null
	>( null );

	const Chevron = expanded ? ChevronDown : ChevronRight;

	// Prefer the API's purchase_url for the locked-feature upgrade link;
	// fall back to the static fixture upgradeUrl. Hoisted unconditionally
	// to satisfy React's Rules of Hooks.
	const catalogTierForUpgrade = useSelect(
		( select ) => select( uplinkStore ).getCatalogTier( product.slug, feature.tier ?? '' ),
		[ product.slug, feature.tier ]
	);

	// Locked / unavailable feature row.
	if ( ! feature.is_available ) {
		const tierName   = catalogTierForUpgrade?.name         ?? feature.tier ?? '';
		const upgradeUrl = catalogTierForUpgrade?.purchase_url ?? '#';

		return (
			<div className="border-b last:border-b-0 bg-muted/30">
				<div
					role="button"
					tabIndex={ 0 }
					onClick={ () => setExpanded( ! expanded ) }
					onKeyDown={ ( e ) => e.key === 'Enter' && setExpanded( ! expanded ) }
					className="flex items-center gap-3 py-3 px-4 cursor-pointer hover:bg-accent/30 transition-colors"
				>
					<Chevron className="w-4 h-4 text-muted-foreground shrink-0" />
					<FeatureIcon slug={ feature.slug } />
					<span className="font-medium flex-1 min-w-0 text-sm text-muted-foreground">
						{ feature.name }
					</span>
					{ feature.version && (
						<span className="text-xs font-mono text-muted-foreground w-16 text-right shrink-0">
							{ feature.version }
						</span>
					) }
					<StatusBadge status="locked" requiredTier={ tierName } />
					<PurchaseLink tierName={ tierName } upgradeUrl={ upgradeUrl } />
				</div>

				{ expanded && (
					<div className="px-4 pb-3 pl-[2.75rem]">
						<p className="text-sm text-muted-foreground leading-relaxed mt-2 mb-0">
							{ feature.description }
						</p>
					</div>
				) }
			</div>
		);
	}

	// TODO: Refactor error display to use an error modal instead of
	// toasts. The modal will show safe, user-facing messages from the
	// UplinkError chain.

	const featureEnabled = feature.is_enabled;

	const handleToggle = async ( checked: boolean ) => {
		setPendingAction( checked ? 'enabling' : 'disabling' );
		if ( checked ) {
			const result = await enableFeature( feature.slug );
			if ( result instanceof UplinkError ) {
				addToast( result.message, 'error' );
			} else {
				/* translators: %s is the name of the feature being enabled */
				addToast(
					sprintf( __( '%s enabled', '%TEXTDOMAIN%' ), feature.name ),
					'success'
				);
			}
		} else {
			const result = await disableFeature( feature.slug );
			if ( result instanceof UplinkError ) {
				addToast( result.message, 'error' );
			} else {
				/* translators: %s is the name of the feature being disabled */
				addToast(
					sprintf( __( '%s disabled', '%TEXTDOMAIN%' ), feature.name ),
					'default'
				);
			}
		}
		setPendingAction( null );
	};

	const badgeStatus =
		pendingAction ?? ( featureEnabled ? 'enabled' : 'available' );

	// While a request is in-flight, reflect the intended state visually so
	// the switch position and badge stay in sync with pendingAction.
	const switchChecked =
		pendingAction === 'enabling'
			? true
			: pendingAction === 'disabling'
				? false
				: featureEnabled;

	return (
		<div className={ cn( 'border-b last:border-b-0 bg-white', pendingAction && 'opacity-75' ) }>
			<div
				role="button"
				tabIndex={ 0 }
				onClick={ () => setExpanded( ! expanded ) }
				onKeyDown={ ( e ) => e.key === 'Enter' && setExpanded( ! expanded ) }
				className="flex items-center gap-3 py-3 px-4 cursor-pointer hover:bg-accent/30 transition-colors"
			>
				<Chevron className="w-4 h-4 text-muted-foreground shrink-0" />
				<FeatureIcon slug={ feature.slug } />
				<span className="font-medium flex-1 min-w-0 text-sm">
					{ feature.name }
				</span>
				<div className="flex-1" />
				{ feature.version && (
					<span className="text-xs font-mono text-muted-foreground w-16 text-right shrink-0">
						{ feature.version }
					</span>
				) }
				<StatusBadge status={ badgeStatus } />
				{ /* Stop row click propagation when interacting with the switch */ }
				<div onClick={ ( e ) => e.stopPropagation() }>
					<Switch
						checked={ switchChecked }
						onCheckedChange={ handleToggle }
						disabled={ !! pendingAction || installableBusy }
						aria-label={
							switchChecked
								? /* translators: %s is the name of the feature to disable */
								  sprintf(
										__( 'Disable %s', '%TEXTDOMAIN%' ),
										feature.name
								  )
								: /* translators: %s is the name of the feature to enable */
								  sprintf(
										__( 'Enable %s', '%TEXTDOMAIN%' ),
										feature.name
								  )
						}
					/>
				</div>
			</div>

			{ expanded && (
				<div className="px-4 pb-3 pl-[2.75rem]">
					<p className="text-sm text-muted-foreground leading-relaxed !mt-[0.75em] !mb-0">
						{ feature.description }
					</p>
				</div>
			) }
		</div>
	);
}
