/**
 * Brand visual configuration.
 *
 * Maps brand slugs to the @wordpress/icons icon element and the
 * Tailwind color classes used for the brand icon container.
 *
 * @package StellarWP\Uplink
 */
import {
    payment,
    calendar,
    plugins,
    brush,
} from '@wordpress/icons';
import type { ReactElement } from 'react';

/**
 * @since TBD
 */
export interface BrandConfig {
    /** @wordpress/icons icon element */
    icon: ReactElement;
    /**
     * Tailwind classes applied to the brand icon wrapper <div>.
     * Should include background + text color (e.g. "bg-green-100 text-green-600").
     */
    colorClass: string;
}

/**
 * Icon and color config per brand slug.
 *
 * Icons are the closest available match in @wordpress/icons.
 * When designs are approved, swap icons in this single file — no component changes needed.
 *
 * @since TBD
 */
export const BRAND_CONFIGS: Record<string, BrandConfig> = {
    givewp: {
        icon: payment,
        colorClass: 'bg-green-100 text-green-600',
    },
    'the-events-calendar': {
        icon: calendar,
        colorClass: 'bg-blue-100 text-blue-600',
    },
    learndash: {
        icon: plugins,
        colorClass: 'bg-indigo-100 text-indigo-600',
    },
    kadence: {
        icon: brush,
        colorClass: 'bg-orange-100 text-orange-600',
    },
};
