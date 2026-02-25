import { Icon, lock } from '@wordpress/icons';
import { cn } from '@/lib/utils';
import type { FeatureLicenseState } from '@/types/api';

interface FeatureInfoProps {
    name: string;
    description: string;
    state: FeatureLicenseState;
}

/**
 * @since TBD
 */
export function FeatureInfo( { name, description, state }: FeatureInfoProps ) {
    const isLocked = state === 'not_included';

    return (
        <div className="flex items-center gap-2">
            { isLocked && (
                <Icon
                    icon={ lock }
                    size={ 16 }
                    className="text-slate-400 shrink-0"
                />
            ) }
            <div>
                <span
                    className={ cn(
                        'font-medium block text-sm',
                        isLocked ? 'text-slate-500' : 'text-slate-900'
                    ) }
                >
                    { name }
                </span>
                <span
                    className={ cn(
                        'text-xs',
                        isLocked ? 'text-slate-400' : 'text-slate-500'
                    ) }
                >
                    { description }
                </span>
            </div>
        </div>
    );
}
