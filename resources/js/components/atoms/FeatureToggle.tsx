import { __ } from '@wordpress/i18n';
import { Switch } from '@/components/ui/switch';
import type { FeatureLicenseState } from '@/types/api';

interface FeatureToggleProps {
    state: FeatureLicenseState;
    onToggle: ( checked: boolean ) => void;
}

/**
 * @since TBD
 */
export function FeatureToggle( { state, onToggle }: FeatureToggleProps ) {
    const isLocked = state === 'not_included';
    const isChecked = state === 'active';

    return (
        <Switch
            checked={ isChecked }
            onCheckedChange={ onToggle }
            disabled={ isLocked }
            aria-label={
                isLocked
                    ? __( 'Feature not included in your plan', '%TEXTDOMAIN%' )
                    : isChecked
                      ? __( 'Deactivate feature', '%TEXTDOMAIN%' )
                      : __( 'Activate feature', '%TEXTDOMAIN%' )
            }
        />
    );
}
