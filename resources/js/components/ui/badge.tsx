import * as React from 'react';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';

const badgeVariants = cva(
    'text-xs w-20 text-right',
    {
        variants: {
            variant: {
                default:
                    'text-primary',
                secondary:
                    'text-muted-foreground',
                destructive:
                    'text-destructive',
                outline:
                    'text-foreground',
				gradient:
					'inline-flex items-center justify-center rounded-full border-transparent px-2 py-0.5 text-xs font-medium w-fit whitespace-nowrap shrink-0 [&>svg]:size-3 gap-1 [&>svg]:pointer-events-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive transition-[color,box-shadow] overflow-hidden bg-gradient-to-r from-emerald-500 to-emerald-600 text-white border-0 cursor-default',
                success:
                    'text-green-700',
                warning:
                    'text-amber-700',
                info:
                    'text-blue-700',
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
