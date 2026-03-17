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
import { ChevronRight, ChevronDown, Download } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { FeatureIcon } from '@/components/atoms/FeatureIcon';
import { StatusBadge } from '@/components/atoms/StatusBadge';
import { PurchaseLink } from '@/components/atoms/PurchaseLink';
import { Switch } from '@/components/ui/switch';
import { useToast } from '@/context/toast-context';
import { store as uplinkStore } from '@/store';
import { UplinkError } from '@/errors';
import type { Feature, Product } from '@/types/api';

interface VersionDisplayProps {
	feature:        Feature;
	pendingAction:  'enabling' | 'disabling' | 'installing' | 'updating' | null;
	installableBusy: boolean;
	onUpdate:       () => void;
}

function VersionDisplay( { feature, pendingAction, installableBusy, onUpdate }: VersionDisplayProps ) {
	if ( feature.has_update ) {
		return (
			<div className="flex items-center gap-1.5">
				<span className="text-xs font-mono text-muted-foreground line-through">
					v{ feature.installed_version }
				</span>
				<span className="text-muted-foreground text-xs">→</span>
				<span className="text-xs font-mono font-bold">
					v{ feature.version }
				</span>
				<Button
					variant="default"
					size="icon-xs"
					className="rounded-full"
					disabled={ !! pendingAction || installableBusy }
					onClick={ onUpdate }
					aria-label={ sprintf( __( 'Update %s', '%TEXTDOMAIN%' ), feature.name ) }
				>
					<Download className="w-3.5 h-3.5" />
				</Button>
			</div>
		);
	}

	if ( ! feature.version && ! feature.installed_version ) {
		return null;
	}

	return (
		<span className="text-xs font-mono text-muted-foreground text-right">
			{ `v${ feature.installed_version ?? feature.version }` }
		</span>
	);
}

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
	const { enableFeature, disableFeature, updateFeature } = useDispatch( uplinkStore );

	// When this feature is a plugin or theme, block toggling while any
	// other installable feature is mid-toggle (WordPress cannot safely
	// install/activate/deactivate/update multiple plugins or themes at once).
	const installableBusy = useSelect(
		( select ) =>
			feature.type !== 'flag' &&
			select( uplinkStore ).isAnyInstallableBusy(),
		[ feature.type ]
	);

	const [ pendingAction, setPendingAction ] = useState<
		'enabling' | 'disabling' | 'installing' | 'updating' | null
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
		return (
			<div className="border-b last:border-b-0 bg-muted/30">
				<div className="flex items-center gap-3 py-3 px-4">
					<div
						onClick={ () => setExpanded( ! expanded ) }
						className="flex items-center gap-3 min-w-0 cursor-pointer"
					>
						<Chevron className="w-4 h-4 text-muted-foreground shrink-0" />
						<FeatureIcon slug={ feature.slug } />
						<span className="font-medium min-w-0 text-sm text-muted-foreground truncate">
							{ feature.name }
						</span>
					</div>
					{ (feature.installed_version || feature.version) && (
						<span className="text-xs font-mono text-muted-foreground w-16 text-right shrink-0 ml-auto">
							{ `v${feature.installed_version ?? feature.version}` }
						</span>
					) }
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
	const featureInstalled = feature.installed_version !== null;

	const handleToggle = async ( checked: boolean ) => {
		setPendingAction( checked ? featureInstalled ? 'enabling' : 'installing' : 'disabling' );
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

	const handleUpdate = async () => {
		setPendingAction( 'updating' );
		const result = await updateFeature( feature.slug );
		if ( result instanceof UplinkError ) {
			addToast( result.message, 'error' );
		} else {
			/* translators: %s is the name of the feature being updated */
			addToast(
				sprintf( __( '%s updated.', '%TEXTDOMAIN%' ), feature.name ),
				'success'
			);
		}
		setPendingAction( null );
	};

	const badgeStatus  = pendingAction ?? ( featureEnabled ? 'enabled' : 'available' );
	const showSwitch   = pendingAction !== 'installing' && pendingAction !== 'updating';

	// While a request is in-flight, reflect the intended state visually so
	// the switch position and badge stay in sync with pendingAction.
	const switchChecked =
		pendingAction === 'enabling' || pendingAction === 'installing'
			? true
			: pendingAction === 'disabling'
				? false
				: featureEnabled;

	return (
		<div className={ cn( 'border-b last:border-b-0 bg-white', pendingAction && 'opacity-75' ) }>
			<div className="flex items-center gap-3 py-3 px-4">
				<div
					onClick={ () => setExpanded( ! expanded ) }
					className="flex items-center gap-3 min-w-0 cursor-pointer"
				>
					<Chevron className="w-4 h-4 text-muted-foreground shrink-0" />
					<FeatureIcon slug={ feature.slug } />
					<span className="font-medium min-w-0 text-sm truncate">
						{ feature.name }
					</span>
				</div>
				<div className="flex items-center gap-3 ml-auto shrink-0">
					<VersionDisplay
						feature={ feature }
						pendingAction={ pendingAction }
						installableBusy={ installableBusy }
						onUpdate={ handleUpdate }
					/>
					<StatusBadge status={ badgeStatus } />
					{ showSwitch && (
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
					) }
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
