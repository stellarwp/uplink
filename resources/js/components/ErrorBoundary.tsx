/**
 * React error boundary.
 *
 * Wrap any subtree to prevent a render crash from taking down the whole page.
 * Used in App.tsx and around each tab panel in AppShell.tsx.
 *
 * @package StellarWP\Uplink
 */
import { Component, type ReactNode } from 'react';
import { __ } from '@wordpress/i18n';

interface Props {
    children:  ReactNode;
    /** Custom fallback UI — defaults to a generic error message. */
    fallback?: ReactNode;
}

interface BState {
    hasError: boolean;
}

/**
 * @since 3.0.0
 */
export class ErrorBoundary extends Component<Props, BState> {
    state: BState = { hasError: false };

    static getDerivedStateFromError(): BState {
        return { hasError: true };
    }

    render(): ReactNode {
        if ( this.state.hasError ) {
            return this.props.fallback ?? (
                <p className="px-4 py-6 text-sm text-muted-foreground text-center">
                    { __( 'Something went wrong.', '%TEXTDOMAIN%' ) }
                </p>
            );
        }

        return this.props.children;
    }
}
