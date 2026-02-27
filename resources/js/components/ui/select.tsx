/**
 * Native <select> wrapper with an onValueChange prop.
 *
 * @package StellarWP\Uplink
 */
import * as React from 'react';
import { cn } from '@/lib/utils';

interface SelectProps extends React.SelectHTMLAttributes<HTMLSelectElement> {
    onValueChange?: ( value: string ) => void;
}

function Select( { className, onValueChange, onChange, ...props }: SelectProps ) {
    const handleChange = ( e: React.ChangeEvent<HTMLSelectElement> ) => {
        onValueChange?.( e.target.value );
        onChange?.( e );
    };

    return (
        <select
            className={ cn(
                'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors',
                'focus:outline-none focus:ring-[3px] focus:ring-ring/50 focus:border-ring',
                'disabled:cursor-not-allowed disabled:opacity-50',
                className
            ) }
            onChange={ handleChange }
            { ...props }
        />
    );
}

export { Select };
