/**
 * Radix UI Tooltip primitives.
 *
 * Uses a Portal + inline styles instead of Tailwind utilities because the
 * Portal teleports content outside .uplink-ui, where the PostCSS scope plugin
 * would no longer apply. z-index 100001 clears the WP admin bar (99999) and
 * our dialogs (100000).
 *
 * The span wrapper pattern in consumers is intentional: Button's
 * disabled:pointer-events-none suppresses hover events on the button itself,
 * so the span must act as the Tooltip trigger to keep hover detection intact.
 *
 * @package StellarWP\Uplink
 */
"use client"

import * as React from 'react';
import { Tooltip as TooltipPrimitive } from 'radix-ui';

const TooltipProvider = TooltipPrimitive.Provider;
const Tooltip         = TooltipPrimitive.Root;
const TooltipTrigger  = TooltipPrimitive.Trigger;

const TooltipContent = React.forwardRef<
	React.ElementRef< typeof TooltipPrimitive.Content >,
	React.ComponentPropsWithoutRef< typeof TooltipPrimitive.Content >
>( ( { sideOffset = 6, style, ...props }, ref ) => (
	<TooltipPrimitive.Portal>
		<TooltipPrimitive.Content
			ref={ ref }
			sideOffset={ sideOffset }
			style={ {
				zIndex:          100001,
				maxWidth:        240,
				padding:         '6px 10px',
				borderRadius:    6,
				fontSize:        12,
				lineHeight:      1.45,
				backgroundColor: '#1a1a1a',
				color:           '#fff',
				boxShadow:       '0 4px 12px rgba(0,0,0,0.2)',
				...style,
			} }
			{ ...props }
		/>
	</TooltipPrimitive.Portal>
) );
TooltipContent.displayName = 'TooltipContent';

export { TooltipProvider, Tooltip, TooltipTrigger, TooltipContent };
