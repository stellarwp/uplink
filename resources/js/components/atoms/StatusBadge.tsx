import { __ } from '@wordpress/i18n';
import { cn } from '@/lib/utils';
import type { FeatureLicenseState } from '@/types/api';

interface StatusBadgeProps {
    state: FeatureLicenseState;
}

const STATE_CONFIG: Record<
    FeatureLicenseState,
    { label: string; badgeClass: string; dotClass?: string }
> = {
    active: {
        label: __( 'Active', '%TEXTDOMAIN%' ),
        badgeClass: 'bg-green-100 text-green-700',
        dotClass: 'bg-green-500',
    },
    inactive: {
        label: __( 'Inactive', '%TEXTDOMAIN%' ),
        badgeClass: 'bg-slate-100 text-slate-600',
        dotClass: 'bg-slate-400',
    },
    not_included: {
        label: __( 'Not Included', '%TEXTDOMAIN%' ),
        badgeClass: 'bg-amber-50 text-amber-600',
    },
};

/**
 * @since TBD
 */
export function StatusBadge( { state }: StatusBadgeProps ) {
    const { label, badgeClass, dotClass } = STATE_CONFIG[ state ];

    return (
        <span
            className={ cn(
                'inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-xs font-medium',
                badgeClass
            ) }
        >
            { dotClass && (
                <span className={ cn( 'h-1.5 w-1.5 rounded-full', dotClass ) } />
            ) }
            { label }
        </span>
    );
}
