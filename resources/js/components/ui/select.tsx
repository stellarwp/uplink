/**
 * Radix UI Select primitives styled to the project's design system.
 *
 * Intentionally NOT using Select.Portal — portal content renders outside
 * .uplink-ui and would be invisible to the PostCSS scope plugin (all Tailwind
 * utilities are scoped to .uplink-ui). The Content renders in the DOM tree but
 * Radix positions it with position:fixed so it still floats above other elements.
 *
 * @package StellarWP\Uplink
 */
import * as React from 'react';
import { Select as SelectPrimitive } from 'radix-ui';
import { ChevronDown, ChevronUp, Check } from 'lucide-react';
import { cn } from '@/lib/utils';

const Select = SelectPrimitive.Root;
const SelectGroup = SelectPrimitive.Group;
const SelectValue = SelectPrimitive.Value;

const SelectTrigger = React.forwardRef<
    React.ElementRef<typeof SelectPrimitive.Trigger>,
    React.ComponentPropsWithoutRef<typeof SelectPrimitive.Trigger>
>( ( { className, children, ...props }, ref ) => (
    <SelectPrimitive.Trigger
        ref={ ref }
        className={ cn(
            'flex h-9 w-full items-center justify-between whitespace-nowrap rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors',
            'placeholder:text-muted-foreground [&>span]:line-clamp-1',
            'outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
            'disabled:cursor-not-allowed disabled:opacity-50',
            className
        ) }
        { ...props }
    >
        { children }
        <SelectPrimitive.Icon asChild>
            <ChevronDown className="w-4 h-4 shrink-0 opacity-50" />
        </SelectPrimitive.Icon>
    </SelectPrimitive.Trigger>
) );
SelectTrigger.displayName = SelectPrimitive.Trigger.displayName;

const SelectScrollUpButton = React.forwardRef<
    React.ElementRef<typeof SelectPrimitive.ScrollUpButton>,
    React.ComponentPropsWithoutRef<typeof SelectPrimitive.ScrollUpButton>
>( ( { className, ...props }, ref ) => (
    <SelectPrimitive.ScrollUpButton
        ref={ ref }
        className={ cn( 'flex cursor-default items-center justify-center py-1', className ) }
        { ...props }
    >
        <ChevronUp className="w-4 h-4" />
    </SelectPrimitive.ScrollUpButton>
) );
SelectScrollUpButton.displayName = SelectPrimitive.ScrollUpButton.displayName;

const SelectScrollDownButton = React.forwardRef<
    React.ElementRef<typeof SelectPrimitive.ScrollDownButton>,
    React.ComponentPropsWithoutRef<typeof SelectPrimitive.ScrollDownButton>
>( ( { className, ...props }, ref ) => (
    <SelectPrimitive.ScrollDownButton
        ref={ ref }
        className={ cn( 'flex cursor-default items-center justify-center py-1', className ) }
        { ...props }
    >
        <ChevronDown className="w-4 h-4" />
    </SelectPrimitive.ScrollDownButton>
) );
SelectScrollDownButton.displayName = SelectPrimitive.ScrollDownButton.displayName;

const SelectContent = React.forwardRef<
    React.ElementRef<typeof SelectPrimitive.Content>,
    React.ComponentPropsWithoutRef<typeof SelectPrimitive.Content>
>( ( { className, children, position = 'popper', ...props }, ref ) => (
    <SelectPrimitive.Content
        ref={ ref }
        position={ position }
        className={ cn(
            'relative z-[100000] max-h-96 min-w-[8rem] overflow-hidden rounded-md border border-border bg-popover text-popover-foreground shadow-md',
            position === 'popper' && 'data-[side=bottom]:translate-y-1 data-[side=top]:-translate-y-1',
            className
        ) }
        { ...props }
    >
        <SelectScrollUpButton />
        <SelectPrimitive.Viewport
            className={ cn(
                'p-1',
                position === 'popper' && 'h-[var(--radix-select-trigger-height)] w-full min-w-[var(--radix-select-trigger-width)]'
            ) }
        >
            { children }
        </SelectPrimitive.Viewport>
        <SelectScrollDownButton />
    </SelectPrimitive.Content>
) );
SelectContent.displayName = SelectPrimitive.Content.displayName;

const SelectLabel = React.forwardRef<
    React.ElementRef<typeof SelectPrimitive.Label>,
    React.ComponentPropsWithoutRef<typeof SelectPrimitive.Label>
>( ( { className, ...props }, ref ) => (
    <SelectPrimitive.Label
        ref={ ref }
        className={ cn( 'px-2 py-1.5 text-sm font-semibold', className ) }
        { ...props }
    />
) );
SelectLabel.displayName = SelectPrimitive.Label.displayName;

const SelectItem = React.forwardRef<
    React.ElementRef<typeof SelectPrimitive.Item>,
    React.ComponentPropsWithoutRef<typeof SelectPrimitive.Item>
>( ( { className, children, ...props }, ref ) => (
    <SelectPrimitive.Item
        ref={ ref }
        className={ cn(
            'relative flex w-full cursor-default select-none items-center gap-2 rounded-sm py-1.5 pl-2 pr-8 text-sm outline-none',
            'focus:bg-accent focus:text-accent-foreground',
            'data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
            className
        ) }
        { ...props }
    >
        <span className="absolute right-2 flex h-3.5 w-3.5 items-center justify-center">
            <SelectPrimitive.ItemIndicator>
                <Check className="w-4 h-4" />
            </SelectPrimitive.ItemIndicator>
        </span>
        <SelectPrimitive.ItemText>{ children }</SelectPrimitive.ItemText>
    </SelectPrimitive.Item>
) );
SelectItem.displayName = SelectPrimitive.Item.displayName;

const SelectSeparator = React.forwardRef<
    React.ElementRef<typeof SelectPrimitive.Separator>,
    React.ComponentPropsWithoutRef<typeof SelectPrimitive.Separator>
>( ( { className, ...props }, ref ) => (
    <SelectPrimitive.Separator
        ref={ ref }
        className={ cn( '-mx-1 my-1 h-px bg-muted', className ) }
        { ...props }
    />
) );
SelectSeparator.displayName = SelectPrimitive.Separator.displayName;

export {
    Select,
    SelectGroup,
    SelectValue,
    SelectTrigger,
    SelectContent,
    SelectLabel,
    SelectItem,
    SelectSeparator,
    SelectScrollUpButton,
    SelectScrollDownButton,
};
