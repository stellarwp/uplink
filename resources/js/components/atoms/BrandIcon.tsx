import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

interface BrandIconProps {
    /** Lucide icon component */
    icon: LucideIcon;
    /** Tailwind bg + text color classes (e.g. "bg-green-100 text-green-600") */
    colorClass: string;
}

/**
 * @since 3.0.0
 */
export function BrandIcon( { icon: IconComponent, colorClass }: BrandIconProps ) {
    return (
        <div
            className={ cn(
                'w-10 h-10 rounded flex items-center justify-center shrink-0',
                colorClass
            ) }
        >
            <IconComponent className="w-6 h-6" />
        </div>
    );
}
