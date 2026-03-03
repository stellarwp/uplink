/**
 * Suspense-enabled hook for fetching features by group.
 *
 * Suspends while the resolver is in flight; re-throws resolver
 * errors as UplinkError for an error boundary to catch.
 *
 * @package StellarWP\Uplink
 */
import { useSuspenseSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { UplinkError } from '@/errors';
import { store as uplinkStore } from '@/store';
import type { Feature } from '@/types/api';

/**
 * @throws {Promise}     While the resolver is pending (caught by Suspense).
 * @throws {UplinkError} If the resolver rejects (caught by ErrorBoundary).
 *
 * @since 3.0.0
 */
export default function useFeaturesByGroup( group: string ): Feature[] {
	try {
		return useSuspenseSelect(
			( select ) => select( uplinkStore ).getFeaturesByGroup( group ),
			[ group ],
		);
	} catch ( error ) {
		if ( error instanceof Promise ) {
			throw error;
		}

		throw UplinkError.from(
			error,
			'features.fetch_failed',
			__( 'Failed to load features.', '%TEXTDOMAIN%' ),
		);
	}
}
