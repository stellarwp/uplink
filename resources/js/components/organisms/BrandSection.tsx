import { __, sprintf } from '@wordpress/i18n';
import { BrandIcon } from '@/components/atoms/BrandIcon';
import { FeatureTable } from '@/components/organisms/FeatureTable';
import { BRAND_CONFIGS } from '@/data/brands';
import type { Brand } from '@/types/api';

interface BrandSectionProps {
    brand: Brand;
    onToggle: ( slug: string, checked: boolean ) => void;
}

/**
 * @since TBD
 */
export function BrandSection( { brand, onToggle }: BrandSectionProps ) {
    const config = BRAND_CONFIGS[ brand.slug ];
    const activeCount = brand.features.filter(
        ( f ) => f.licenseState === 'active'
    ).length;

    return (
        <div className="flex flex-col gap-4">
            <div className="flex items-center justify-between border-b border-slate-200 pb-2">
                <div className="flex items-center gap-3">
                    { config && (
                        <BrandIcon
                            icon={ config.icon }
                            colorClass={ config.colorClass }
                        />
                    ) }
                    <div>
                        <h3 className="text-xl font-bold text-slate-800">
                            { brand.name }
                        </h3>
                        <p className="text-xs text-slate-500">{ brand.tagline }</p>
                    </div>
                </div>

                <span className="bg-slate-100 text-slate-600 text-xs px-2 py-1 rounded shrink-0">
                    { sprintf( __( '%d Active', '%TEXTDOMAIN%' ), activeCount ) }
                </span>
            </div>

            <FeatureTable features={ brand.features } onToggle={ onToggle } />
        </div>
    );
}
