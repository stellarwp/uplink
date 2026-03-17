/**
 * Displays the installed/available version of a feature, with an update
 * button when a newer version is available.
 *
 * @package StellarWP\Uplink
 */
import { __, sprintf } from '@wordpress/i18n';
import { Download } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { Feature } from '@/types/api';

export interface VersionDisplayProps {
	feature:         Feature;
	pendingAction:   'enabling' | 'disabling' | 'installing' | 'updating' | null;
	installableBusy: boolean;
	onUpdate:        () => void;
}

/**
 * @since 3.0.0
 */
export function VersionDisplay( { feature, pendingAction, installableBusy, onUpdate }: VersionDisplayProps ) {
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
