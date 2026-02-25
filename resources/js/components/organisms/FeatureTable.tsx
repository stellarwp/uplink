/**
 * @deprecated Superseded by ProductSection. Deleted in Phase G.
 * @package StellarWP\Uplink
 */
import { __ } from '@wordpress/i18n';
import { Card } from '@/components/ui/card';
import type { LegacyFeature } from '@/types/api';

interface FeatureTableProps {
    features: LegacyFeature[];
    onToggle: ( slug: string, checked: boolean ) => void;
}

export function FeatureTable( { features }: FeatureTableProps ) {
    return (
        <Card className="overflow-hidden p-0">
            <table className="w-full text-left text-sm">
                <thead className="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th className="px-6 py-3 font-medium text-slate-600">
                            { __( 'Feature', '%TEXTDOMAIN%' ) }
                        </th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-slate-200">
                    { features.map( ( feature ) => (
                        <tr key={ feature.slug }>
                            <td className="px-6 py-4 text-sm">{ feature.name }</td>
                        </tr>
                    ) ) }
                </tbody>
            </table>
        </Card>
    );
}
