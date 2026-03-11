interface FeatureInfoProps {
    name: string;
}

/**
 * @since 3.0.0
 */
export function FeatureInfo( { name }: FeatureInfoProps ) {
    return (
        <span className="font-medium flex-1 min-w-0 text-sm">
            { name }
        </span>
    );
}
