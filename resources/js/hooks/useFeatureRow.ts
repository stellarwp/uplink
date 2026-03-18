/**
 * Behaviour hook for FeatureRow.
 *
 * Encapsulates store wiring, async action handlers, and all derived state
 * so FeatureRow itself stays a pure composition of atoms.
 *
 * @package StellarWP\Uplink
 */
import { useState } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as uplinkStore } from '@/store';
import { useToast } from '@/context/toast-context';
import { UplinkError } from '@/errors';
import type { Feature } from '@/types/api';
import type { FeatureStatus } from '@/components/atoms/StatusBadge';

export type PendingAction = 'enabling' | 'disabling' | 'installing' | 'updating' | null;

export interface FeatureRowState {
	pendingAction:   PendingAction;
	installableBusy: boolean;
	badgeStatus:     FeatureStatus;
	showSwitch:      boolean;
	switchChecked:   boolean;
	handleToggle:    ( checked: boolean ) => Promise<void>;
	handleUpdate:    () => Promise<void>;
}

/**
 * @since 3.0.0
 */
export function useFeatureRow( feature: Feature ): FeatureRowState {
	const { addToast } = useToast();
	const { enableFeature, disableFeature, updateFeature } = useDispatch( uplinkStore );

	const installableBusy = useSelect(
		( select ) =>
			feature.type !== 'flag' &&
			select( uplinkStore ).isAnyInstallableBusy(),
		[ feature.type ]
	);

	const [ pendingAction, setPendingAction ] = useState<PendingAction>( null );

	const featureEnabled   = feature.is_enabled;
	const featureInstalled = feature.installed_version !== null;

	const handleToggle = async ( checked: boolean ) => {
		setPendingAction( checked ? featureInstalled ? 'enabling' : 'installing' : 'disabling' );
		if ( checked ) {
			const result = await enableFeature( feature.slug );
			if ( result instanceof UplinkError ) {
				addToast( result.message, 'error' );
			} else {
				/* translators: %s is the name of the feature being enabled */
				addToast( sprintf( __( '%s enabled', '%TEXTDOMAIN%' ), feature.name ), 'success' );
			}
		} else {
			const result = await disableFeature( feature.slug );
			if ( result instanceof UplinkError ) {
				addToast( result.message, 'error' );
			} else {
				/* translators: %s is the name of the feature being disabled */
				addToast( sprintf( __( '%s disabled', '%TEXTDOMAIN%' ), feature.name ), 'default' );
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
			addToast( sprintf( __( '%s updated.', '%TEXTDOMAIN%' ), feature.name ), 'success' );
		}
		setPendingAction( null );
	};

	const badgeStatus  = pendingAction ?? ( featureEnabled ? 'enabled' : 'available' );
	const showSwitch   = pendingAction !== 'installing' && pendingAction !== 'updating';
	const switchChecked =
		pendingAction === 'enabling' || pendingAction === 'installing'
			? true
			: pendingAction === 'disabling'
				? false
				: featureEnabled;

	return {
		pendingAction,
		installableBusy,
		badgeStatus:  badgeStatus as FeatureStatus,
		showSwitch,
		switchChecked,
		handleToggle,
		handleUpdate,
	};
}
