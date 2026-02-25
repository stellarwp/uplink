import { Icon } from '@wordpress/icons';
import type { ReactElement } from 'react';
import { cn } from '@/lib/utils';

interface BrandIconProps {
    /** @wordpress/icons icon element */
    icon: ReactElement;
    /** Tailwind bg + text color classes (e.g. "bg-green-100 text-green-600") */
    colorClass: string;
}

/**
 * @since TBD
 */
export function BrandIcon( { icon, colorClass }: BrandIconProps ) {
    return (
        <div
            className={ cn(
                'w-10 h-10 rounded flex items-center justify-center shrink-0',
                colorClass
            ) }
        >
            <Icon icon={ icon } size={ 24 } />
        </div>
    );
}
