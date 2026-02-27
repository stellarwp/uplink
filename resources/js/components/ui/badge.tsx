import * as React from 'react';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';

const badgeVariants = cva(
    'inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors',
    {
        variants: {
            variant: {
                default:
                    'border-transparent bg-primary text-primary-foreground',
                secondary:
                    'border-transparent bg-secondary text-secondary-foreground',
                destructive:
                    'border-transparent bg-destructive text-white',
                outline:
                    'text-foreground border-border',
                success:
                    'border-transparent bg-green-100 text-green-700',
                gradient:
                    'border-transparent bg-gradient-to-r from-primary to-purple-500 text-white',
                warning:
                    'border-transparent bg-amber-100 text-amber-700',
                info:
                    'border-transparent bg-blue-100 text-blue-700',
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
