import { __ } from '@wordpress/i18n';
import { Switch } from '@/components/ui/switch';
import type { FeatureLicenseState } from '@/types/api';

interface FeatureToggleProps {
    state: FeatureLicenseState;
    onToggle: ( checked: boolean ) => void;
}

function getLabel( isLocked: boolean, isChecked: boolean ): string {
    if ( isLocked ) {
		return __( 'Feature not included in your plan', '%TEXTDOMAIN%' );
	}

	if ( isChecked ) {
		return __( 'Deactivate feature', '%TEXTDOMAIN%' );
	}

	return __( 'Activate feature', '%TEXTDOMAIN%' );
}

/**
 * @since TBD
 */
export function FeatureSwitch( { state, onToggle }: FeatureToggleProps ) {
    const isLocked  = state === 'not_included';
    const isChecked = state === 'active';

    return (
        <Switch
            checked={ isChecked }
            onCheckedChange={ onToggle }
            disabled={ isLocked }
            aria-label={ getLabel( isLocked, isChecked ) }
        />
    );
}
