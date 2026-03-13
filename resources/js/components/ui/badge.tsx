import * as React from 'react';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';

const badgeVariants = cva(
    'inline-flex items-center justify-center rounded-full border px-2 py-0.5 text-xs font-medium w-fit whitespace-nowrap shrink-0 [&>svg]:size-3 gap-1 [&>svg]:pointer-events-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive transition-[color,box-shadow] overflow-hidden',
    {
        variants: {
            variant: {
				default:
					"bg-primary text-primary-foreground [a&]:hover:bg-primary/90",
				secondary:
					"bg-secondary text-secondary-foreground [a&]:hover:bg-secondary/90",
				destructive:
					"bg-destructive text-white [a&]:hover:bg-destructive/90 focus-visible:ring-destructive/20 dark:focus-visible:ring-destructive/40 dark:bg-destructive/60",
				outline:
					"border-border text-foreground [a&]:hover:bg-accent [a&]:hover:text-accent-foreground",
				ghost:
					"[a&]:hover:bg-accent [a&]:hover:text-accent-foreground",
				link:
					"text-primary underline-offset-4 [a&]:hover:underline",
				success:
					"bg-emerald-100 text-emerald-800 border-emerald-200",
				gradient:
					"bg-gradient-to-r from-emerald-500 to-emerald-600 text-white border-0",
				warning:
					"bg-amber-100 text-amber-800 border-amber-200",
				info:
					"bg-blue-100 text-blue-800 border-blue-200",
            },
        },
        defaultVariants: {
            variant: 'default',
        },
    }
);

export interface BadgeProps
    extends React.HTMLAttributes<HTMLSpanElement>,
        VariantProps<typeof badgeVariants> {}

function Badge( { className, variant, ...props }: BadgeProps ) {
    return (
        <span
            className={ cn( badgeVariants( { variant } ), className ) }
            { ...props }
        />
    );
}

export { Badge, badgeVariants };
