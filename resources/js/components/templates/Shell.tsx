/**
 * Two-column page shell: scrollable main area + sticky sidebar.
 *
 * @package StellarWP\Uplink
 */
import { ReactNode } from 'react';

interface ShellProps {
    /** Optional content rendered above children (FilterBar slot). */
    header?: ReactNode;
    /** Content rendered in the right sidebar. */
    sideContent?: ReactNode;
    children: ReactNode;
}

/**
 * @since 3.0.0
 */
export function Shell( { header, sideContent, children }: ShellProps ) {
    return (
        <div className="flex flex-col overflow-hidden h-[calc(100vh-32px)]">
            <header className="shrink-0 border-b bg-background py-4 px-8 flex items-center gap-3">
                { header }
            </header>
			<div className="flex-1 min-h-0 flex overflow-hidden">
				<main className="flex-1 min-w-0 overflow-y-auto pb-6 px-8 bg-neutral-50">
					{ children }
				</main>
				<aside className="shrink-0 overflow-y-auto border-l px-8 py-4">
					{ sideContent }
				</aside>
			</div>
        </div>
    );
}
