import { __ } from '@wordpress/i18n';
import { useState } from 'react';
import { MasterLicenseForm } from '@/components/organisms/MasterLicenseForm';
import { BrandSection } from '@/components/organisms/BrandSection';
import type { Brand, DashboardData } from '@/types/api';
import mockData from '@/data/mock-features.json';

const initialData = mockData as DashboardData;

/**
 * @since TBD
 */
export function LicenseDashboard() {
    // TODO: Replace with useQuery hook when REST API is ready.
    const [ brands, setBrands ] = useState<Brand[]>( initialData.brands );

    function handleToggle( slug: string, checked: boolean ) {
        // TODO: Replace optimistic update with REST API call.
        // POST /wp-json/uplink/v1/features/{slug}/toggle
        // If the feature has a downloadUrl, the REST API installs it first, then activates.
        setBrands( ( prev ) =>
            prev.map( ( brand ) => ( {
                ...brand,
                features: brand.features.map( ( feature ) =>
                    feature.slug === slug
                        ? { ...feature, licenseState: checked ? 'active' : 'inactive' }
                        : feature
                ),
            } ) )
        );
    }

    function handleActivate( key: string, email: string ) {
        // TODO: POST /wp-json/uplink/v1/license/activate
        console.log( 'Activate license:', key, email );
    }

    function handleDeactivate() {
        // TODO: POST /wp-json/uplink/v1/license/deactivate
        console.log( 'Deactivate license' );
    }

    return (
        <div className="max-w-[1200px] mx-auto space-y-8 p-4 md:p-8">
            {/* Page Header */}
            <div className="flex flex-col gap-2">
                <h1 className="text-3xl font-light text-slate-800">
                    { __( 'License Management', '%TEXTDOMAIN%' ) }
                </h1>
                <p className="text-slate-600 text-base max-w-3xl">
                    { __( 'Manage your premium feature licenses across all brands. Enter your master license key below to unlock features for GiveWP, The Events Calendar, LearnDash, and Kadence.', '%TEXTDOMAIN%' ) }
                </p>
            </div>

            {/* Master License Form */}
            <MasterLicenseForm
                license={ initialData.license }
                onActivate={ handleActivate }
                onDeactivate={ handleDeactivate }
            />

            {/* Brand Sections */}
            <div className="grid grid-cols-1 gap-8">
                { brands.map( ( brand ) => (
                    <BrandSection
                        key={ brand.slug }
                        brand={ brand }
                        onToggle={ handleToggle }
                    />
                ) ) }
            </div>
        </div>
    );
}
