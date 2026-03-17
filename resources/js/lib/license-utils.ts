/**
 * Pure utility functions for license expiry display.
 *
 * @package StellarWP\Uplink
 */

/**
 * @since 3.0.0
 */
export function formatDate( dateStr: string ): string {
    return new Date( dateStr ).toLocaleDateString( 'en-US', {
        year:  'numeric',
        month: 'short',
        day:   'numeric',
    } );
}

/**
 * @since 3.0.0
 */
export function getExpiryStatus( dateStr: string ): 'expired' | 'expiring-soon' | 'ok' {
    const diff = new Date( dateStr ).getTime() - Date.now();
    if ( diff <= 0 ) return 'expired';
    if ( diff <= 30 * 24 * 60 * 60 * 1000 ) return 'expiring-soon';
    return 'ok';
}

export const expiryCardClass: Record<string, string> = {
    expired:          'border-destructive/60 bg-destructive/5',
    'expiring-soon':  'border-amber-400 bg-amber-50',
    ok:               '',
};

export const expiryTextClass: Record<string, string> = {
    expired:          'text-destructive font-medium',
    'expiring-soon':  'text-amber-600 font-medium',
    ok:               'text-muted-foreground',
};
