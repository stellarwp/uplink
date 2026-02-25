import { Lock } from 'lucide-react';
import { cn } from '@/lib/utils';

interface FeatureInfoProps {
    name: string;
    description: string;
    isLocked: boolean;
}

/**
 * @since TBD
 */
export function FeatureInfo( { name, description, isLocked }: FeatureInfoProps ) {
    return (
        <div className="flex items-center gap-2">
            { isLocked && (
                <Lock
                    className="w-4 h-4 text-slate-400 shrink-0"
                    aria-hidden="true"
                />
            ) }
            <div>
                <span
                    className={ cn(
                        'font-medium block text-sm',
                        isLocked ? 'text-slate-500' : 'text-slate-900'
                    ) }
                >
                    { name }
                </span>
                <span
                    className={ cn(
                        'text-xs',
                        isLocked ? 'text-slate-400' : 'text-slate-500'
                    ) }
                >
                    { description }
                </span>
            </div>
        </div>
    );
}
