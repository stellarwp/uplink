import { __ } from '@wordpress/i18n';
import { Card } from '@/components/ui/card';
import { FeatureRow } from '@/components/molecules/FeatureRow';
import type { Feature } from '@/types/api';

interface FeatureTableProps {
    features: Feature[];
    onToggle: ( slug: string, checked: boolean ) => void;
}

/**
 * @since TBD
 */
export function FeatureTable( { features, onToggle }: FeatureTableProps ) {
    return (
        <Card className="overflow-hidden p-0">
            <table className="w-full text-left text-sm">
                <thead className="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th className="px-6 py-3 font-medium text-slate-600">
                            { __( 'Feature', '%TEXTDOMAIN%' ) }
                        </th>
                        <th className="px-6 py-3 font-medium text-slate-600 w-32">
                            { __( 'Version', '%TEXTDOMAIN%' ) }
                        </th>
                        <th className="px-6 py-3 font-medium text-slate-600 w-40">
                            { __( 'Status', '%TEXTDOMAIN%' ) }
                        </th>
                        <th className="px-6 py-3 font-medium text-slate-600 text-right w-32">
                            { __( 'Action', '%TEXTDOMAIN%' ) }
                        </th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-slate-200">
                    { features.map( ( feature ) => (
                        <FeatureRow
                            key={ feature.slug }
                            feature={ feature }
                            onToggle={ onToggle }
                        />
                    ) ) }
                </tbody>
            </table>
        </Card>
    );
}
