/**
 * Sidebar section header: icon + uppercase label + optional trailing action.
 *
 * @package StellarWP\Uplink
 */
import { type ReactNode } from 'react';

interface SectionHeaderProps {
	icon:    ReactNode;
	label:   string;
	action?: ReactNode;
}

/**
 * @since 3.0.0
 */
export function SectionHeader( { icon, label, action }: SectionHeaderProps ) {
	return (
		<div className="flex items-center gap-2.5">
			{ icon }
			<span className="text-xs font-semibold text-muted-foreground uppercase tracking-wider">
				{ label }
			</span>
			{ action }
		</div>
	);
}
