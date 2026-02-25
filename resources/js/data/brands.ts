/**
 * Brand visual configuration.
 *
 * Maps product slugs to a Lucide icon component and Tailwind color classes
 * used for the brand icon container.
 *
 * @package StellarWP\Uplink
 */
import { Heart, CalendarDays, GraduationCap, Layers } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

/**
 * @since TBD
 */
export interface BrandConfig {
    /** Lucide icon component */
    icon: LucideIcon;
    /**
     * Tailwind classes applied to the brand icon wrapper <div>.
     * Should include background + text color (e.g. "bg-green-100 text-green-600").
     */
    colorClass: string;
}

/**
 * Icon and color config per product slug.
 *
 * @since TBD
 */
export const BRAND_CONFIGS: Record<string, BrandConfig> = {
    givewp: {
        icon: Heart,
        colorClass: 'bg-green-100 text-green-600',
    },
    'the-events-calendar': {
        icon: CalendarDays,
        colorClass: 'bg-blue-100 text-blue-600',
    },
    learndash: {
        icon: GraduationCap,
        colorClass: 'bg-indigo-100 text-indigo-600',
    },
    kadence: {
        icon: Layers,
        colorClass: 'bg-orange-100 text-orange-600',
    },
};
