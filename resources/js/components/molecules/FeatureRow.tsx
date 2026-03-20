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
import { ChevronRight, ChevronDown } from 'lucide-react';
import { cn } from '@/lib/utils';
import { FeatureIcon } from '@/components/atoms/FeatureIcon';
import { StatusBadge } from '@/components/atoms/StatusBadge';
import { VersionDisplay } from '@/components/molecules/VersionDisplay';
import { Switch } from '@/components/ui/switch';
import { useFeatureRow } from '@/hooks/useFeatureRow';
import type { Feature } from '@/types/api';

interface FeatureRowProps {
	feature:          Feature;
	/** Tier display name passed by TierGroup; enables the upsell tooltip on the update button. */
	upgradeTierName?: string;
}

/**
 * @since 3.0.0
 */
export function FeatureRow( { feature, upgradeTierName }: FeatureRowProps ) {
	const [ expanded, setExpanded ] = useState( false );
	const {
		pendingAction,
		installableBusy,
		badgeStatus,
		showSwitch,
		switchChecked,
		handleToggle,
		handleUpdate,
	} = useFeatureRow( feature );

	const Chevron = expanded ? ChevronDown : ChevronRight;

	return (
		<div className={ cn(
			'border-b last:border-b-0',
			feature.is_available
				? cn( 'bg-white', pendingAction && 'opacity-75' )
				: 'bg-muted/30'
		) }>
			<div className="flex items-center gap-3 py-3 px-4">
				<div
					onClick={ () => setExpanded( ! expanded ) }
					className="flex items-center gap-3 min-w-0 cursor-pointer"
				>
					<Chevron className="w-4 h-4 text-muted-foreground shrink-0" />
					<FeatureIcon slug={ feature.slug } />
					<span className={ cn(
						'font-medium min-w-0 text-sm truncate',
						! feature.is_available && 'text-muted-foreground'
					) }>
						{ feature.name }
					</span>
				</div>

				{ feature.is_available ? (
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
										  sprintf( __( 'Disable %s', '%TEXTDOMAIN%' ), feature.name )
										: /* translators: %s is the name of the feature to enable */
										  sprintf( __( 'Enable %s', '%TEXTDOMAIN%' ), feature.name )
								}
							/>
						) }
					</div>
				) : (
					<div className="ml-auto shrink-0">
						<VersionDisplay
							feature={ feature }
							upgradeLabel={ upgradeTierName
								? /* translators: %s is the name of the tier required to receive updates */
								  sprintf( __( 'Upgrade to %s to receive updates and support.', '%TEXTDOMAIN%' ), upgradeTierName )
								: undefined
							}
						/>
					</div>
				) }
			</div>

			{ expanded && (
				<div className="px-4 pb-3 pl-[2.75rem]">
					<p className={ cn(
						'text-sm text-muted-foreground leading-relaxed',
						feature.is_available ? '!mt-[0.75em] !mb-0' : 'mt-2 mb-0'
					) }>
						{ feature.description }
					</p>
				</div>
			) }
		</div>
	);
}
