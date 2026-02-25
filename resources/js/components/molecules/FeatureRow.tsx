import { cn } from '@/lib/utils';
import { FeatureInfo } from '@/components/molecules/FeatureInfo';
import { StatusBadge } from '@/components/atoms/StatusBadge';
import { FeatureToggle } from '@/components/atoms/FeatureToggle';
import { UpsellAction } from '@/components/atoms/UpsellAction';
import type { Feature } from '@/types/api';

interface FeatureRowProps {
    feature: Feature;
    onToggle: ( slug: string, checked: boolean ) => void;
}

/**
 * @since TBD
 */
export function FeatureRow( { feature, onToggle }: FeatureRowProps ) {
    const isLocked = feature.licenseState === 'not_included';

    return (
        <tr
            className={ cn(
                'transition-colors',
                isLocked
                    ? 'bg-slate-50/50'
                    : 'hover:bg-slate-50'
            ) }
        >
            <td className="px-6 py-4">
                <FeatureInfo
                    name={ feature.name }
                    description={ feature.description }
                    state={ feature.licenseState }
                />
            </td>

            <td className="px-6 py-4 text-sm text-slate-600">
                { isLocked || ! feature.version ? '–' : `v${ feature.version }` }
            </td>

            <td className="px-6 py-4">
                <StatusBadge state={ feature.licenseState } />
            </td>

            <td className="px-6 py-4 text-right">
                { isLocked ? (
                    <UpsellAction
                        featureName={ feature.name }
                        upgradeUrl={ feature.upgradeUrl ?? '#' }
                    />
                ) : (
                    <FeatureToggle
                        state={ feature.licenseState }
                        onToggle={ ( checked ) => onToggle( feature.slug, checked ) }
                    />
                ) }
            </td>
        </tr>
    );
}
