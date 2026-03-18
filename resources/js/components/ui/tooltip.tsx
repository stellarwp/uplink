/**
 * Single-component tooltip wrapper built on Radix UI.
 *
 * Uses a Portal + inline styles instead of Tailwind utilities because the
 * Portal teleports content outside .uplink-ui, where the PostCSS scope plugin
 * would no longer apply. z-index 100001 clears the WP admin bar (99999) and
 * our dialogs (100000).
 *
 * The children are wrapped in a <span> so that hover detection still works
 * when the child element has pointer-events disabled (e.g. a disabled button).
 *
 * Usage:
 *   <Tooltip label="Some helpful text">
 *       <Button disabled>...</Button>
 *   </Tooltip>
 *
 * @package StellarWP\Uplink
 */
import { Tooltip as TooltipPrimitive } from 'radix-ui';

interface TooltipProps {
	label:    string;
	children: React.ReactNode;
}

/**
 * @since 3.0.0
 */
export function Tooltip( { label, children }: TooltipProps ) {
	return (
		<TooltipPrimitive.Provider>
			<TooltipPrimitive.Root>
				<TooltipPrimitive.Trigger asChild>
					<span>{ children }</span>
				</TooltipPrimitive.Trigger>
				<TooltipPrimitive.Portal>
					<TooltipPrimitive.Content
						sideOffset={ 6 }
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
						} }
					>
						{ label }
					</TooltipPrimitive.Content>
				</TooltipPrimitive.Portal>
			</TooltipPrimitive.Root>
		</TooltipPrimitive.Provider>
	);
}
