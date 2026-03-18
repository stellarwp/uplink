/**
 * Displays the installed/available version of a feature, with an update
 * button when a newer version is available.
 *
 * When upgradeLabel is provided the update button is rendered fully disabled
 * (no onClick handler) with an upsell tooltip. The span wrapper around the
 * button is required because disabled:pointer-events-none on the Button
 * suppresses hover events — without the span the tooltip would never show.
 *
 * @package StellarWP\Uplink
 */
import { __, sprintf } from '@wordpress/i18n';
import { Download } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import type { Feature } from '@/types/api';

export interface VersionDisplayProps {
	feature:          Feature;
	/** When set, the update button is disabled and this text is shown as an upsell tooltip. */
	upgradeLabel?:    string;
	pendingAction?:   'enabling' | 'disabling' | 'installing' | 'updating' | null;
	installableBusy?: boolean;
	/** Required when upgradeLabel is not set and the button should be active. */
	onUpdate?:        () => void;
}

/**
 * @since 3.0.0
 */
export function VersionDisplay( {
	feature,
	upgradeLabel,
	pendingAction   = null,
	installableBusy = false,
	onUpdate,
}: VersionDisplayProps ) {
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
				{ upgradeLabel ? (
					<TooltipProvider>
						<Tooltip>
							<TooltipTrigger asChild>
								{ /* span required: disabled:pointer-events-none on Button
								     would otherwise block the hover events that open the tooltip. */ }
								<span>
									<Button
										variant="default"
										size="icon-xs"
										className="rounded-full"
										disabled
										aria-label={ sprintf( __( 'Update %s', '%TEXTDOMAIN%' ), feature.name ) }
									>
										<Download className="w-3.5 h-3.5" />
									</Button>
								</span>
							</TooltipTrigger>
							<TooltipContent>{ upgradeLabel }</TooltipContent>
						</Tooltip>
					</TooltipProvider>
				) : onUpdate && (
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
				) }
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
