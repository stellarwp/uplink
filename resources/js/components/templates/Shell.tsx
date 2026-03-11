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
        <div className="flex relative">
            <main className="flex-1 min-w-0 px-8 pb-8 bg-neutral-50">
                { header }
                { children }
            </main>

            <aside
                className="w-[280px] shrink-0 border-l px-6 py-4
                           sticky top-[var(--wp-admin--admin-bar--height,32px)]
                           self-start
                           max-h-[calc(100vh-var(--wp-admin--admin-bar--height,32px))]
                           overflow-y-auto"
            >
                { sideContent }
            </aside>
        </div>
    );
}
